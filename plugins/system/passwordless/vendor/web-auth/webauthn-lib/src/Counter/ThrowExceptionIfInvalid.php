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

namespace Akeeba\Passwordless\Webauthn\Counter;

use Akeeba\Passwordless\Assert\Assertion;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Akeeba\Passwordless\Webauthn\PublicKeyCredentialSource;

final class ThrowExceptionIfInvalid implements \Akeeba\Passwordless\Webauthn\Counter\CounterChecker
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function check(\Akeeba\Passwordless\Webauthn\PublicKeyCredentialSource $publicKeyCredentialSource, int $currentCounter): void
    {
        try {
            \Akeeba\Passwordless\Assert\Assertion::greaterThan($currentCounter, $publicKeyCredentialSource->getCounter(), 'Invalid counter.');
        } catch (Throwable $throwable) {
            $this->logger->error('The counter is invalid', [
                'current' => $currentCounter,
                'new' => $publicKeyCredentialSource->getCounter(),
            ]);
            throw $throwable;
        }
    }
}
