<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Akeeba\Passwordless\Ramsey\Uuid\Math;

use Akeeba\Passwordless\Ramsey\Uuid\Type\Hexadecimal;
use Akeeba\Passwordless\Ramsey\Uuid\Type\Integer as IntegerObject;
use Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface;

/**
 * A calculator performs arithmetic operations on numbers
 *
 * @psalm-immutable
 */
interface CalculatorInterface
{
    /**
     * Returns the sum of all the provided parameters
     *
     * @param NumberInterface $augend The first addend (the integer being added to)
     * @param NumberInterface ...$addends The additional integers to a add to the augend
     *
     * @return NumberInterface The sum of all the parameters
     */
    public function add(\Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface $augend, \Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface ...$addends): \Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface;

    /**
     * Returns the difference of all the provided parameters
     *
     * @param NumberInterface $minuend The integer being subtracted from
     * @param NumberInterface ...$subtrahends The integers to subtract from the minuend
     *
     * @return NumberInterface The difference after subtracting all parameters
     */
    public function subtract(\Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface $minuend, \Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface ...$subtrahends): \Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface;

    /**
     * Returns the product of all the provided parameters
     *
     * @param NumberInterface $multiplicand The integer to be multiplied
     * @param NumberInterface ...$multipliers The factors by which to multiply the multiplicand
     *
     * @return NumberInterface The product of multiplying all the provided parameters
     */
    public function multiply(\Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface $multiplicand, \Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface ...$multipliers): \Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface;

    /**
     * Returns the quotient of the provided parameters divided left-to-right
     *
     * @param int $roundingMode The RoundingMode constant to use for this operation
     * @param int $scale The scale to use for this operation
     * @param NumberInterface $dividend The integer to be divided
     * @param NumberInterface ...$divisors The integers to divide $dividend by, in
     *     the order in which the division operations should take place
     *     (left-to-right)
     *
     * @return NumberInterface The quotient of dividing the provided parameters left-to-right
     */
    public function divide(
        int $roundingMode,
        int $scale,
        \Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface $dividend,
        \Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface ...$divisors
    ): \Akeeba\Passwordless\Ramsey\Uuid\Type\NumberInterface;

    /**
     * Converts a value from an arbitrary base to a base-10 integer value
     *
     * @param string $value The value to convert
     * @param int $base The base to convert from (i.e., 2, 16, 32, etc.)
     *
     * @return IntegerObject The base-10 integer value of the converted value
     */
    public function fromBase(string $value, int $base): \Akeeba\Passwordless\Ramsey\Uuid\Type\Integer;

    /**
     * Converts a base-10 integer value to an arbitrary base
     *
     * @param IntegerObject $value The integer value to convert
     * @param int $base The base to convert to (i.e., 2, 16, 32, etc.)
     *
     * @return string The value represented in the specified base
     */
    public function toBase(\Akeeba\Passwordless\Ramsey\Uuid\Type\Integer $value, int $base): string;

    /**
     * Converts an Integer instance to a Hexadecimal instance
     */
    public function toHexadecimal(\Akeeba\Passwordless\Ramsey\Uuid\Type\Integer $value): \Akeeba\Passwordless\Ramsey\Uuid\Type\Hexadecimal;

    /**
     * Converts a Hexadecimal instance to an Integer instance
     */
    public function toInteger(\Akeeba\Passwordless\Ramsey\Uuid\Type\Hexadecimal $value): \Akeeba\Passwordless\Ramsey\Uuid\Type\Integer;
}