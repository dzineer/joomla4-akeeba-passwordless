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

use Akeeba\Passwordless\Webauthn\PublicKeyCredentialSource;

interface CounterChecker
{
    public function check(\Akeeba\Passwordless\Webauthn\PublicKeyCredentialSource $publicKeyCredentialSource, int $currentCounter): void;
}
