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

namespace Akeeba\Passwordless\Cose\Key;

use function array_key_exists;
use Akeeba\Passwordless\Assert\Assertion;

class Key
{
    public const TYPE = 1;
    public const TYPE_OKP = 1;
    public const TYPE_EC2 = 2;
    public const TYPE_RSA = 3;
    public const TYPE_OCT = 4;
    public const KID = 2;
    public const ALG = 3;
    public const KEY_OPS = 4;
    public const BASE_IV = 5;

    /**
     * @var array
     */
    private $data;

    public function __construct(array $data)
    {
        \Akeeba\Passwordless\Assert\Assertion::keyExists($data, self::TYPE, 'Invalid key: the type is not defined');
        $this->data = $data;
    }

    public static function createFromData(array $data): self
    {
        \Akeeba\Passwordless\Assert\Assertion::keyExists($data, self::TYPE, 'Invalid key: the type is not defined');
        switch ($data[self::TYPE]) {
            case 1:
                return new \Akeeba\Passwordless\Cose\Key\OkpKey($data);
            case 2:
                return new \Akeeba\Passwordless\Cose\Key\Ec2Key($data);
            case 3:
                return new \Akeeba\Passwordless\Cose\Key\RsaKey($data);
            case 4:
                return new \Akeeba\Passwordless\Cose\Key\SymmetricKey($data);
            default:
                return new self($data);
        }
    }

    /**
     * @return int|string
     */
    public function type()
    {
        return $this->data[self::TYPE];
    }

    public function alg(): int
    {
        return (int) $this->get(self::ALG);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function has(int $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @return mixed
     */
    public function get(int $key)
    {
        \Akeeba\Passwordless\Assert\Assertion::keyExists($this->data, $key, sprintf('The key has no data at index %d', $key));

        return $this->data[$key];
    }
}
