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
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use function Akeeba\Passwordless\Safe\json_decode;
use function Akeeba\Passwordless\Safe\sprintf;

/**
 * @deprecated This class is deprecated since v3.3 and will be removed in v4.0
 */
class MetadataStatementFetcher
{
    public static function fetchTableOfContent(string $uri, ClientInterface $client, RequestFactoryInterface $requestFactory, array $additionalHeaders = []): \Akeeba\Passwordless\Webauthn\MetadataService\MetadataTOCPayload
    {
        $content = self::fetch($uri, $client, $requestFactory, $additionalHeaders);
        $payload = self::getJwsPayload($content);
        $data = \Akeeba\Passwordless\Safe\json_decode($payload, true);

        return \Akeeba\Passwordless\Webauthn\MetadataService\MetadataTOCPayload::createFromArray($data);
    }

    public static function fetchMetadataStatement(string $uri, bool $isBase64UrlEncoded, ClientInterface $client, RequestFactoryInterface $requestFactory, array $additionalHeaders = [], string $hash = '', string $hashingFunction = 'sha256'): \Akeeba\Passwordless\Webauthn\MetadataService\MetadataStatement
    {
        $payload = self::fetch($uri, $client, $requestFactory, $additionalHeaders);
        if ('' !== $hash) {
            \Akeeba\Passwordless\Assert\Assertion::true(hash_equals($hash, hash($hashingFunction, $payload, true)), 'The hash cannot be verified. The metadata statement shall be rejected');
        }
        $json = $isBase64UrlEncoded ? Base64Url::decode($payload) : $payload;
        $data = \Akeeba\Passwordless\Safe\json_decode($json, true);

        return \Akeeba\Passwordless\Webauthn\MetadataService\MetadataStatement::createFromArray($data);
    }

    private static function fetch(string $uri, ClientInterface $client, RequestFactoryInterface $requestFactory, array $additionalHeaders = []): string
    {
        $request = $requestFactory->createRequest('GET', $uri);
        foreach ($additionalHeaders as $k => $v) {
            $request = $request->withHeader($k, $v);
        }
        $response = $client->sendRequest($request);
        \Akeeba\Passwordless\Assert\Assertion::eq(200, $response->getStatusCode(), \Akeeba\Passwordless\Safe\sprintf('Unable to contact the server. Response code is %d', $response->getStatusCode()));
        $content = $response->getBody()->getContents();
        \Akeeba\Passwordless\Assert\Assertion::notEmpty($content, 'Unable to contact the server. The response has no content');

        return $content;
    }

    private static function getJwsPayload(string $token): string
    {
        $jws = (new CompactSerializer())->unserialize($token);
        \Akeeba\Passwordless\Assert\Assertion::eq(1, $jws->countSignatures(), 'Invalid response from the metadata service. Only one signature shall be present.');
        $signature = $jws->getSignature(0);
        $payload = $jws->getPayload();
        \Akeeba\Passwordless\Assert\Assertion::notEmpty($payload, 'Invalid response from the metadata service. The token payload is empty.');
        $header = $signature->getProtectedHeader();
        \Akeeba\Passwordless\Assert\Assertion::keyExists($header, 'alg', 'The "alg" parameter is missing.');
        \Akeeba\Passwordless\Assert\Assertion::eq($header['alg'], 'ES256', 'The expected "alg" parameter value should be "ES256".');
        \Akeeba\Passwordless\Assert\Assertion::keyExists($header, 'x5c', 'The "x5c" parameter is missing.');
        \Akeeba\Passwordless\Assert\Assertion::isArray($header['x5c'], 'The "x5c" parameter should be an array.');
        $key = JWKFactory::createFromX5C($header['x5c']);
        $algorithm = new ES256();
        $isValid = $algorithm->verify($key, $signature->getEncodedProtectedHeader().'.'.$jws->getEncodedPayload(), $signature->getSignature());
        \Akeeba\Passwordless\Assert\Assertion::true($isValid, 'Invalid response from the metadata service. The token signature is invalid.');

        return $jws->getPayload();
    }
}
