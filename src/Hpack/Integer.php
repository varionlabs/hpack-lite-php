<?php

declare(strict_types=1);

namespace Hpack;

final class Integer
{
    /**
     * @return array{value:int,nextOffset:int}
     */
    public static function decodeInteger(string $data, int $offset, int $prefixBits): array
    {
        if ($prefixBits < 1 || $prefixBits > 8) {
            throw new DecodeException(sprintf('Invalid prefix bits for decode: %d', $prefixBits));
        }

        $length = strlen($data);
        if ($offset < 0 || $offset >= $length) {
            throw new DecodeException(sprintf('Integer decode out of bounds (offset=%d, len=%d)', $offset, $length));
        }

        $maxPrefix = (1 << $prefixBits) - 1;
        $firstByte = ord($data[$offset]);
        $value = $firstByte & $maxPrefix;
        $offset++;

        if ($value < $maxPrefix) {
            return ['value' => $value, 'nextOffset' => $offset];
        }

        $m = 0;
        while (true) {
            if ($m > 56) {
                throw new DecodeException(sprintf('Integer decode shift overflow (offset=%d, shift=%d)', $offset, $m));
            }

            if ($offset >= $length) {
                throw new DecodeException(sprintf('Integer decode truncated continuation (offset=%d)', $offset));
            }

            $byte = ord($data[$offset]);
            $offset++;

            $chunk = $byte & 0x7f;
            $addition = $chunk << $m;
            if ($value > PHP_INT_MAX - $addition) {
                throw new DecodeException(sprintf('Integer decode value overflow (offset=%d)', $offset - 1));
            }
            $value += $addition;

            if (($byte & 0x80) === 0) {
                return ['value' => $value, 'nextOffset' => $offset];
            }

            $m += 7;
        }
    }

    public static function encodeInteger(int $value, int $prefixBits, int $prefixMaskBase): string
    {
        if ($prefixBits < 1 || $prefixBits > 8) {
            throw new EncodeException(sprintf('Invalid prefix bits for encode: %d', $prefixBits));
        }

        if ($value < 0) {
            throw new EncodeException(sprintf('Integer encode requires non-negative value (value=%d)', $value));
        }

        if ($prefixMaskBase < 0 || $prefixMaskBase > 0xff) {
            throw new EncodeException(sprintf('Integer encode prefixMaskBase out of range (prefixMaskBase=%d)', $prefixMaskBase));
        }

        $maxPrefix = (1 << $prefixBits) - 1;
        if (($prefixMaskBase & $maxPrefix) !== 0) {
            throw new EncodeException(
                sprintf(
                    'Integer encode prefixMaskBase overlaps prefix bits (prefixMaskBase=%d, prefixBits=%d)',
                    $prefixMaskBase,
                    $prefixBits
                )
            );
        }

        if ($value < $maxPrefix) {
            return chr($prefixMaskBase | $value);
        }

        $result = chr($prefixMaskBase | $maxPrefix);
        $value -= $maxPrefix;

        while ($value >= 128) {
            $result .= chr(($value % 128) + 128);
            $value = intdiv($value, 128);
        }

        $result .= chr($value);
        return $result;
    }
}
