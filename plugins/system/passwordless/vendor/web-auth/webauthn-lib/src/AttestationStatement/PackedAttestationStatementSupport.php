<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2021 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Akeeba\Passwordless\Webauthn\AttestationStatement;

use function array_key_exists;
use Akeeba\Passwordless\Assert\Assertion;
use Akeeba\Passwordless\CBOR\Decoder;
use Akeeba\Passwordless\CBOR\MapObject;
use Akeeba\Passwordless\CBOR\OtherObject\OtherObjectManager;
use Akeeba\Passwordless\CBOR\Tag\TagObjectManager;
use Akeeba\Passwordless\Cose\Algorithm\Manager;
use Akeeba\Passwordless\Cose\Algorithm\Signature\Signature;
use Akeeba\Passwordless\Cose\Algorithms;
use Akeeba\Passwordless\Cose\Key\Key;
use function in_array;
use InvalidArgumentException;
use function is_array;
use RuntimeException;
use Akeeba\Passwordless\Webauthn\AuthenticatorData;
use Akeeba\Passwordless\Webauthn\CertificateToolbox;
use Akeeba\Passwordless\Webauthn\StringStream;
use Akeeba\Passwordless\Webauthn\TrustPath\CertificateTrustPath;
use Akeeba\Passwordless\Webauthn\TrustPath\EcdaaKeyIdTrustPath;
use Akeeba\Passwordless\Webauthn\TrustPath\EmptyTrustPath;
use Akeeba\Passwordless\Webauthn\Util\CoseSignatureFixer;

final class PackedAttestationStatementSupport implements \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatementSupport
{
    /**
     * @var Decoder
     */
    private $decoder;

    /**
     * @var Manager
     */
    private $algorithmManager;

    public function __construct(\Akeeba\Passwordless\Cose\Algorithm\Manager $algorithmManager)
    {
        $this->decoder = new \Akeeba\Passwordless\CBOR\Decoder(new \Akeeba\Passwordless\CBOR\Tag\TagObjectManager(), new \Akeeba\Passwordless\CBOR\OtherObject\OtherObjectManager());
        $this->algorithmManager = $algorithmManager;
    }

    public function name(): string
    {
        return 'packed';
    }

    /**
     * @param mixed[] $attestation
     */
    public function load(array $attestation): \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement
    {
        \Akeeba\Passwordless\Assert\Assertion::keyExists($attestation['attStmt'], 'sig', 'The attestation statement value "sig" is missing.');
        \Akeeba\Passwordless\Assert\Assertion::keyExists($attestation['attStmt'], 'alg', 'The attestation statement value "alg" is missing.');
        \Akeeba\Passwordless\Assert\Assertion::string($attestation['attStmt']['sig'], 'The attestation statement value "sig" is missing.');
        switch (true) {
            case array_key_exists('x5c', $attestation['attStmt']):
                return $this->loadBasicType($attestation);
            case array_key_exists('ecdaaKeyId', $attestation['attStmt']):
                return $this->loadEcdaaType($attestation['attStmt']);
            default:
                return $this->loadEmptyType($attestation);
        }
    }

    public function isValid(string $clientDataJSONHash, \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement $attestationStatement, \Akeeba\Passwordless\Webauthn\AuthenticatorData $authenticatorData): bool
    {
        $trustPath = $attestationStatement->getTrustPath();
        switch (true) {
            case $trustPath instanceof \Akeeba\Passwordless\Webauthn\TrustPath\CertificateTrustPath:
                return $this->processWithCertificate($clientDataJSONHash, $attestationStatement, $authenticatorData, $trustPath);
            case $trustPath instanceof \Akeeba\Passwordless\Webauthn\TrustPath\EcdaaKeyIdTrustPath:
                return $this->processWithECDAA();
            case $trustPath instanceof \Akeeba\Passwordless\Webauthn\TrustPath\EmptyTrustPath:
                return $this->processWithSelfAttestation($clientDataJSONHash, $attestationStatement, $authenticatorData);
            default:
                throw new InvalidArgumentException('Unsupported attestation statement');
        }
    }

    /**
     * @param mixed[] $attestation
     */
    private function loadBasicType(array $attestation): \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement
    {
        $certificates = $attestation['attStmt']['x5c'];
        \Akeeba\Passwordless\Assert\Assertion::isArray($certificates, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        \Akeeba\Passwordless\Assert\Assertion::minCount($certificates, 1, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        $certificates = \Akeeba\Passwordless\Webauthn\CertificateToolbox::convertAllDERToPEM($certificates);

        return \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement::createBasic($attestation['fmt'], $attestation['attStmt'], new \Akeeba\Passwordless\Webauthn\TrustPath\CertificateTrustPath($certificates));
    }

    private function loadEcdaaType(array $attestation): \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement
    {
        $ecdaaKeyId = $attestation['attStmt']['ecdaaKeyId'];
        \Akeeba\Passwordless\Assert\Assertion::string($ecdaaKeyId, 'The attestation statement value "ecdaaKeyId" is invalid.');

        return \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement::createEcdaa($attestation['fmt'], $attestation['attStmt'], new \Akeeba\Passwordless\Webauthn\TrustPath\EcdaaKeyIdTrustPath($attestation['ecdaaKeyId']));
    }

    /**
     * @param mixed[] $attestation
     */
    private function loadEmptyType(array $attestation): \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement
    {
        return \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement::createSelf($attestation['fmt'], $attestation['attStmt'], new \Akeeba\Passwordless\Webauthn\TrustPath\EmptyTrustPath());
    }

    private function checkCertificate(string $attestnCert, \Akeeba\Passwordless\Webauthn\AuthenticatorData $authenticatorData): void
    {
        $parsed = openssl_x509_parse($attestnCert);
        \Akeeba\Passwordless\Assert\Assertion::isArray($parsed, 'Invalid certificate');

        //Check version
        \Akeeba\Passwordless\Assert\Assertion::false(!isset($parsed['version']) || 2 !== $parsed['version'], 'Invalid certificate version');

        //Check subject field
        \Akeeba\Passwordless\Assert\Assertion::false(!isset($parsed['name']) || false === mb_strpos($parsed['name'], '/OU=Authenticator Attestation'), 'Invalid certificate name. The Subject Organization Unit must be "Authenticator Attestation"');

        //Check extensions
        \Akeeba\Passwordless\Assert\Assertion::false(!isset($parsed['extensions']) || !is_array($parsed['extensions']), 'Certificate extensions are missing');

        //Check certificate is not a CA cert
        \Akeeba\Passwordless\Assert\Assertion::false(!isset($parsed['extensions']['basicConstraints']) || 'CA:FALSE' !== $parsed['extensions']['basicConstraints'], 'The Basic Constraints extension must have the CA component set to false');

        $attestedCredentialData = $authenticatorData->getAttestedCredentialData();
        \Akeeba\Passwordless\Assert\Assertion::notNull($attestedCredentialData, 'No attested credential available');

        // id-fido-gen-ce-aaguid OID check
        \Akeeba\Passwordless\Assert\Assertion::false(in_array('1.3.6.1.4.1.45724.1.1.4', $parsed['extensions'], true) && !hash_equals($attestedCredentialData->getAaguid()->getBytes(), $parsed['extensions']['1.3.6.1.4.1.45724.1.1.4']), 'The value of the "aaguid" does not match with the certificate');
    }

    private function processWithCertificate(string $clientDataJSONHash, \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement $attestationStatement, \Akeeba\Passwordless\Webauthn\AuthenticatorData $authenticatorData, \Akeeba\Passwordless\Webauthn\TrustPath\CertificateTrustPath $trustPath): bool
    {
        $certificates = $trustPath->getCertificates();

        // Check leaf certificate
        $this->checkCertificate($certificates[0], $authenticatorData);

        // Get the COSE algorithm identifier and the corresponding OpenSSL one
        $coseAlgorithmIdentifier = (int) $attestationStatement->get('alg');
        $opensslAlgorithmIdentifier = \Akeeba\Passwordless\Cose\Algorithms::getOpensslAlgorithmFor($coseAlgorithmIdentifier);

        // Verification of the signature
        $signedData = $authenticatorData->getAuthData().$clientDataJSONHash;
        $result = openssl_verify($signedData, $attestationStatement->get('sig'), $certificates[0], $opensslAlgorithmIdentifier);

        return 1 === $result;
    }

    private function processWithECDAA(): bool
    {
        throw new RuntimeException('ECDAA not supported');
    }

    private function processWithSelfAttestation(string $clientDataJSONHash, \Akeeba\Passwordless\Webauthn\AttestationStatement\AttestationStatement $attestationStatement, \Akeeba\Passwordless\Webauthn\AuthenticatorData $authenticatorData): bool
    {
        $attestedCredentialData = $authenticatorData->getAttestedCredentialData();
        \Akeeba\Passwordless\Assert\Assertion::notNull($attestedCredentialData, 'No attested credential available');
        $credentialPublicKey = $attestedCredentialData->getCredentialPublicKey();
        \Akeeba\Passwordless\Assert\Assertion::notNull($credentialPublicKey, 'No credential public key available');
        $publicKeyStream = new \Akeeba\Passwordless\Webauthn\StringStream($credentialPublicKey);
        $publicKey = $this->decoder->decode($publicKeyStream);
        \Akeeba\Passwordless\Assert\Assertion::true($publicKeyStream->isEOF(), 'Invalid public key. Presence of extra bytes.');
        $publicKeyStream->close();
        \Akeeba\Passwordless\Assert\Assertion::isInstanceOf($publicKey, \Akeeba\Passwordless\CBOR\MapObject::class, 'The attested credential data does not contain a valid public key.');
        $publicKey = $publicKey->getNormalizedData(false);
        $publicKey = new \Akeeba\Passwordless\Cose\Key\Key($publicKey);
        \Akeeba\Passwordless\Assert\Assertion::eq($publicKey->alg(), (int) $attestationStatement->get('alg'), 'The algorithm of the attestation statement and the key are not identical.');

        $dataToVerify = $authenticatorData->getAuthData().$clientDataJSONHash;
        $algorithm = $this->algorithmManager->get((int) $attestationStatement->get('alg'));
        if (!$algorithm instanceof \Akeeba\Passwordless\Cose\Algorithm\Signature\Signature) {
            throw new RuntimeException('Invalid algorithm');
        }
        $signature = \Akeeba\Passwordless\Webauthn\Util\CoseSignatureFixer::fix($attestationStatement->get('sig'), $algorithm);

        return $algorithm->verify($dataToVerify, $publicKey, $signature);
    }
}
