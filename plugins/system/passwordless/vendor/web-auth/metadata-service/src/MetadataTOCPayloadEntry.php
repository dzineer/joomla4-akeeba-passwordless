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

namespace Akeeba\Passwordless\Webauthn\MetadataService;

use Akeeba\Passwordless\Assert\Assertion;
use Akeeba\Passwordless\Base64Url\Base64Url;
use function count;
use JsonSerializable;
use LogicException;

class MetadataTOCPayloadEntry implements JsonSerializable
{
    /**
     * @var string|null
     */
    private $aaid;

    /**
     * @var string|null
     */
    private $aaguid;

    /**
     * @var string[]
     */
    private $attestationCertificateKeyIdentifiers = [];

    /**
     * @var string|null
     */
    private $hash;

    /**
     * @var string|null
     */
    private $url;

    /**
     * @var StatusReport[]
     */
    private $statusReports = [];

    /**
     * @var string
     */
    private $timeOfLastStatusChange;

    /**
     * @var string
     */
    private $rogueListURL;

    /**
     * @var string
     */
    private $rogueListHash;

    public function __construct(?string $aaid, ?string $aaguid, array $attestationCertificateKeyIdentifiers, ?string $hash, ?string $url, string $timeOfLastStatusChange, ?string $rogueListURL, ?string $rogueListHash)
    {
        if (null !== $aaid && null !== $aaguid) {
            throw new LogicException('Authenticators cannot support both AAID and AAGUID');
        }
        if (null === $aaid && null === $aaguid && 0 === count($attestationCertificateKeyIdentifiers)) {
            throw new LogicException('If neither AAID nor AAGUID are set, the attestation certificate identifier list shall not be empty');
        }
        foreach ($attestationCertificateKeyIdentifiers as $attestationCertificateKeyIdentifier) {
            \Akeeba\Passwordless\Assert\Assertion::string($attestationCertificateKeyIdentifier, \Akeeba\Passwordless\Webauthn\MetadataService\Utils::logicException('Invalid attestation certificate identifier. Shall be a list of strings'));
            \Akeeba\Passwordless\Assert\Assertion::notEmpty($attestationCertificateKeyIdentifier, \Akeeba\Passwordless\Webauthn\MetadataService\Utils::logicException('Invalid attestation certificate identifier. Shall be a list of strings'));
            \Akeeba\Passwordless\Assert\Assertion::regex($attestationCertificateKeyIdentifier, '/^[0-9a-f]+$/', \Akeeba\Passwordless\Webauthn\MetadataService\Utils::logicException('Invalid attestation certificate identifier. Shall be a list of strings'));
        }
        $this->aaid = $aaid;
        $this->aaguid = $aaguid;
        $this->attestationCertificateKeyIdentifiers = $attestationCertificateKeyIdentifiers;
        $this->hash = Base64Url::decode($hash);
        $this->url = $url;
        $this->timeOfLastStatusChange = $timeOfLastStatusChange;
        $this->rogueListURL = $rogueListURL;
        $this->rogueListHash = $rogueListHash;
    }

    public function getAaid(): ?string
    {
        return $this->aaid;
    }

    public function getAaguid(): ?string
    {
        return $this->aaguid;
    }

    public function getAttestationCertificateKeyIdentifiers(): array
    {
        return $this->attestationCertificateKeyIdentifiers;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function addStatusReports(\Akeeba\Passwordless\Webauthn\MetadataService\StatusReport $statusReport): self
    {
        $this->statusReports[] = $statusReport;

        return $this;
    }

    /**
     * @return StatusReport[]
     */
    public function getStatusReports(): array
    {
        return $this->statusReports;
    }

    public function getTimeOfLastStatusChange(): string
    {
        return $this->timeOfLastStatusChange;
    }

    public function getRogueListURL(): string
    {
        return $this->rogueListURL;
    }

    public function getRogueListHash(): string
    {
        return $this->rogueListHash;
    }

    public static function createFromArray(array $data): self
    {
        $data = \Akeeba\Passwordless\Webauthn\MetadataService\Utils::filterNullValues($data);
        \Akeeba\Passwordless\Assert\Assertion::keyExists($data, 'timeOfLastStatusChange', \Akeeba\Passwordless\Webauthn\MetadataService\Utils::logicException('Invalid data. The parameter "timeOfLastStatusChange" is missing'));
        \Akeeba\Passwordless\Assert\Assertion::keyExists($data, 'statusReports', \Akeeba\Passwordless\Webauthn\MetadataService\Utils::logicException('Invalid data. The parameter "statusReports" is missing'));
        \Akeeba\Passwordless\Assert\Assertion::isArray($data['statusReports'], \Akeeba\Passwordless\Webauthn\MetadataService\Utils::logicException('Invalid data. The parameter "statusReports" shall be an array of StatusReport objects'));
        $object = new self(
        $data['aaid'] ?? null,
        $data['aaguid'] ?? null,
        $data['attestationCertificateKeyIdentifiers'] ?? [],
        $data['hash'] ?? null,
        $data['url'] ?? null,
            $data['timeOfLastStatusChange'],
            $data['rogueListURL'] ?? null,
            $data['rogueListHash'] ?? null
        );
        foreach ($data['statusReports'] as $statusReport) {
            $object->addStatusReports(\Akeeba\Passwordless\Webauthn\MetadataService\StatusReport::createFromArray($statusReport));
        }

        return $object;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'aaid' => $this->aaid,
            'aaguid' => $this->aaguid,
            'attestationCertificateKeyIdentifiers' => $this->attestationCertificateKeyIdentifiers,
            'hash' => Base64Url::encode($this->hash),
            'url' => $this->url,
            'statusReports' => array_map(static function (\Akeeba\Passwordless\Webauthn\MetadataService\StatusReport $object): array {
                return $object->jsonSerialize();
            }, $this->statusReports),
            'timeOfLastStatusChange' => $this->timeOfLastStatusChange,
            'rogueListURL' => $this->rogueListURL,
            'rogueListHash' => $this->rogueListHash,
        ];

        return \Akeeba\Passwordless\Webauthn\MetadataService\Utils::filterNullValues($data);
    }
}
