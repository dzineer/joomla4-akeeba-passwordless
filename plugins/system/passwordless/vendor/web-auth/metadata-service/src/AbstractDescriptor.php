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
use JsonSerializable;

abstract class AbstractDescriptor implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $maxRetries;

    /**
     * @var int|null
     */
    private $blockSlowdown;

    public function __construct(?int $maxRetries = null, ?int $blockSlowdown = null)
    {
        \Akeeba\Passwordless\Assert\Assertion::greaterOrEqualThan($maxRetries, 0, \Akeeba\Passwordless\Webauthn\MetadataService\Utils::logicException('Invalid data. The value of "maxRetries" must be a positive integer'));
        \Akeeba\Passwordless\Assert\Assertion::greaterOrEqualThan($blockSlowdown, 0, \Akeeba\Passwordless\Webauthn\MetadataService\Utils::logicException('Invalid data. The value of "blockSlowdown" must be a positive integer'));

        $this->maxRetries = $maxRetries;
        $this->blockSlowdown = $blockSlowdown;
    }

    public function getMaxRetries(): ?int
    {
        return $this->maxRetries;
    }

    public function getBlockSlowdown(): ?int
    {
        return $this->blockSlowdown;
    }
}
