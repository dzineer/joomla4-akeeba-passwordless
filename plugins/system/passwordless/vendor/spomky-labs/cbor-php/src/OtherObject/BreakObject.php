<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Akeeba\Passwordless\CBOR\OtherObject;

use Akeeba\Passwordless\CBOR\OtherObject as Base;

final class BreakObject extends \Akeeba\Passwordless\CBOR\OtherObject
{
    public function __construct()
    {
        parent::__construct(self::OBJECT_BREAK, null);
    }

    public static function create(): self
    {
        return new self();
    }

    public static function supportedAdditionalInformation(): array
    {
        return [self::OBJECT_BREAK];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): \Akeeba\Passwordless\CBOR\OtherObject
    {
        return new self();
    }

    /**
     * @deprecated The method will be removed on v3.0. Please rely on the CBOR\Normalizable interface
     */
    public function getNormalizedData(bool $ignoreTags = false): bool
    {
        return false;
    }
}
