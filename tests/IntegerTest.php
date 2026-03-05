<?php

declare(strict_types=1);

namespace Minihpack\Tests;

use Hpack\DecodeException;
use Hpack\EncodeException;
use Hpack\Integer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IntegerTest extends TestCase
{
    /**
     * @return array<string,array{prefixBits:int,value:int}>
     */
    public static function boundaryCases(): array
    {
        return [
            'prefix5-zero' => ['prefixBits' => 5, 'value' => 0],
            'prefix5-max-1' => ['prefixBits' => 5, 'value' => 30],
            'prefix5-max' => ['prefixBits' => 5, 'value' => 31],
            'prefix5-max+1' => ['prefixBits' => 5, 'value' => 32],
            'prefix5-large' => ['prefixBits' => 5, 'value' => 1337],
            'prefix6-zero' => ['prefixBits' => 6, 'value' => 0],
            'prefix6-max-1' => ['prefixBits' => 6, 'value' => 62],
            'prefix6-max' => ['prefixBits' => 6, 'value' => 63],
            'prefix6-max+1' => ['prefixBits' => 6, 'value' => 64],
            'prefix6-large' => ['prefixBits' => 6, 'value' => 4242],
            'prefix7-zero' => ['prefixBits' => 7, 'value' => 0],
            'prefix7-max-1' => ['prefixBits' => 7, 'value' => 126],
            'prefix7-max' => ['prefixBits' => 7, 'value' => 127],
            'prefix7-max+1' => ['prefixBits' => 7, 'value' => 128],
            'prefix7-large' => ['prefixBits' => 7, 'value' => 8192],
        ];
    }

    #[DataProvider('boundaryCases')]
    public function testEncodeDecodeBoundaries(int $prefixBits, int $value): void
    {
        $encoded = Integer::encodeInteger($value, $prefixBits, 0x00);
        $decoded = Integer::decodeInteger($encoded, 0, $prefixBits);

        $this->assertSame($value, $decoded['value']);
        $this->assertSame(strlen($encoded), $decoded['nextOffset']);
    }

    public function testEncodeKeepsPrefixMaskBits(): void
    {
        $encoded = Integer::encodeInteger(10, 5, 0xe0);
        $this->assertSame(0xea, ord($encoded[0]));
    }

    public function testDecodeTruncatedContinuationThrows(): void
    {
        $this->expectException(DecodeException::class);
        Integer::decodeInteger(chr(0x1f), 0, 5);
    }

    public function testDecodeShiftOverflowThrows(): void
    {
        $data = chr(0x1f) . str_repeat("\x80", 9) . "\x00";
        $this->expectException(DecodeException::class);
        Integer::decodeInteger($data, 0, 5);
    }

    public function testEncodeRejectsInvalidPrefixMaskBase(): void
    {
        $this->expectException(EncodeException::class);
        Integer::encodeInteger(1, 5, 0x1f);
    }
}

