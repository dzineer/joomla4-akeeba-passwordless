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

namespace Akeeba\Passwordless\Webauthn;

use function Akeeba\Passwordless\Safe\json_encode;

/**
 * @see https://www.w3.org/TR/webauthn/#iface-pkcredential
 */
class PublicKeyCredential extends \Akeeba\Passwordless\Webauthn\Credential
{
    /**
     * @var string
     */
    protected $rawId;

    /**
     * @var AuthenticatorResponse
     */
    protected $response;

    public function __construct(string $id, string $type, string $rawId, \Akeeba\Passwordless\Webauthn\AuthenticatorResponse $response)
    {
        parent::__construct($id, $type);
        $this->rawId = $rawId;
        $this->response = $response;
    }

    public function __toString()
    {
        return \Akeeba\Passwordless\Safe\json_encode($this);
    }

    public function getRawId(): string
    {
        return $this->rawId;
    }

    public function getResponse(): \Akeeba\Passwordless\Webauthn\AuthenticatorResponse
    {
        return $this->response;
    }

    /**
     * @param string[] $transport
     */
    public function getPublicKeyCredentialDescriptor(array $transport = []): \Akeeba\Passwordless\Webauthn\PublicKeyCredentialDescriptor
    {
        return new \Akeeba\Passwordless\Webauthn\PublicKeyCredentialDescriptor($this->getType(), $this->getRawId(), $transport);
    }
}
