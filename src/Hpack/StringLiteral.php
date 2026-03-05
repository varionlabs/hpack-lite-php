<?php

declare(strict_types=1);

namespace Hpack;

final class StringLiteral
{
    /**
     * @return array{value:string,nextOffset:int}
     */
    public static function decode(string $data, int $offset): array
    {
        $length = strlen($data);
        if ($offset < 0 || $offset >= $length) {
            throw new DecodeException(sprintf('String literal out of bounds (offset=%d, len=%d)', $offset, $length));
        }

        $firstByte = ord($data[$offset]);
        if (($firstByte & 0x80) !== 0) {
            throw new UnsupportedFeatureException(sprintf('Huffman string literal is unsupported (offset=%d)', $offset));
        }

        $lengthInfo = Integer::decodeInteger($data, $offset, 7);
        $stringLength = $lengthInfo['value'];
        $cursor = $lengthInfo['nextOffset'];

        if ($stringLength < 0) {
            throw new DecodeException(sprintf('Negative string literal length at offset=%d', $offset));
        }

        if ($cursor + $stringLength > $length) {
            throw new DecodeException(
                sprintf(
                    'String literal truncated (offset=%d, expected=%d, available=%d)',
                    $cursor,
                    $stringLength,
                    $length - $cursor
                )
            );
        }

        $value = substr($data, $cursor, $stringLength);
        return ['value' => $value, 'nextOffset' => $cursor + $stringLength];
    }

    public static function encode(string $value): string
    {
        return Integer::encodeInteger(strlen($value), 7, 0x00) . $value;
    }
}
