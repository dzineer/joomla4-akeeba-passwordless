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

namespace Akeeba\Passwordless\CBOR\Tag;

use \Akeeba\Passwordless\CBOR\CBORObject;
use Akeeba\Passwordless\CBOR\ListObject;
use Akeeba\Passwordless\CBOR\NegativeIntegerObject;
use Akeeba\Passwordless\CBOR\Normalizable;
use Akeeba\Passwordless\CBOR\Tag;
use Akeeba\Passwordless\CBOR\UnsignedIntegerObject;
use function count;
use function extension_loaded;
use InvalidArgumentException;
use RuntimeException;

final class DecimalFractionTag extends \Akeeba\Passwordless\CBOR\Tag implements \Akeeba\Passwordless\CBOR\Normalizable
{
    public function __construct(\Akeeba\Passwordless\CBOR\CBORObject $object)
    {
        if (! extension_loaded('bcmath')) {
            throw new RuntimeException('The extension "bcmath" is required to use this tag');
        }
        if (! $object instanceof \Akeeba\Passwordless\CBOR\ListObject || count($object) !== 2) {
            throw new InvalidArgumentException(
                'This tag only accepts a ListObject object that contains an exponent and a mantissa.'
            );
        }
        $e = $object->get(0);
        if (! $e instanceof \Akeeba\Passwordless\CBOR\UnsignedIntegerObject && ! $e instanceof \Akeeba\Passwordless\CBOR\NegativeIntegerObject) {
            throw new InvalidArgumentException('The exponent must be a Signed Integer or an Unsigned Integer object.');
        }
        $m = $object->get(1);
        if (! $m instanceof \Akeeba\Passwordless\CBOR\UnsignedIntegerObject && ! $m instanceof \Akeeba\Passwordless\CBOR\NegativeIntegerObject && ! $m instanceof \Akeeba\Passwordless\CBOR\Tag\NegativeBigIntegerTag && ! $m instanceof \Akeeba\Passwordless\CBOR\Tag\UnsignedBigIntegerTag) {
            throw new InvalidArgumentException(
                'The mantissa must be a Positive or Negative Signed Integer or an Unsigned Integer object.'
            );
        }

        parent::__construct(self::TAG_DECIMAL_FRACTION, null, $object);
    }

    public static function create(\Akeeba\Passwordless\CBOR\CBORObject $object): self
    {
        return new self($object);
    }

    public static function getTagId(): int
    {
        return self::TAG_DECIMAL_FRACTION;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, \Akeeba\Passwordless\CBOR\CBORObject $object): \Akeeba\Passwordless\CBOR\Tag
    {
        return new self($object);
    }

    public static function createFromExponentAndMantissa(\Akeeba\Passwordless\CBOR\CBORObject $e, \Akeeba\Passwordless\CBOR\CBORObject $m): \Akeeba\Passwordless\CBOR\Tag
    {
        $object = \Akeeba\Passwordless\CBOR\ListObject::create()
            ->add($e)
            ->add($m)
        ;

        return self::create($object);
    }

    public function normalize()
    {
        /** @var ListObject $object */
        $object = $this->object;
        /** @var UnsignedIntegerObject|NegativeIntegerObject $e */
        $e = $object->get(0);
        /** @var UnsignedIntegerObject|NegativeIntegerObject|NegativeBigIntegerTag|UnsignedBigIntegerTag $m */
        $m = $object->get(1);

        return rtrim(bcmul($m->normalize(), bcpow('10', $e->normalize(), 100), 100), '0');
    }

    /**
     * @deprecated The method will be removed on v3.0. Please rely on the CBOR\Normalizable interface
     */
    public function getNormalizedData(bool $ignoreTags = false)
    {
        if ($ignoreTags) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        if (! $this->object instanceof \Akeeba\Passwordless\CBOR\ListObject || count($this->object) !== 2) {
            return $this->object->getNormalizedData($ignoreTags);
        }
        $e = $this->object->get(0);
        $m = $this->object->get(1);

        if (! $e instanceof \Akeeba\Passwordless\CBOR\UnsignedIntegerObject && ! $e instanceof \Akeeba\Passwordless\CBOR\NegativeIntegerObject) {
            return $this->object->getNormalizedData($ignoreTags);
        }
        if (! $m instanceof \Akeeba\Passwordless\CBOR\UnsignedIntegerObject && ! $m instanceof \Akeeba\Passwordless\CBOR\NegativeIntegerObject && ! $m instanceof \Akeeba\Passwordless\CBOR\Tag\NegativeBigIntegerTag && ! $m instanceof \Akeeba\Passwordless\CBOR\Tag\UnsignedBigIntegerTag) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        return $this->normalize();
    }
}
