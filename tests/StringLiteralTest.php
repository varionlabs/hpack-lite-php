<?php

declare(strict_types=1);

namespace Minihpack\Tests;

use Hpack\DecodeException;
use Hpack\StringLiteral;
use Hpack\UnsupportedFeatureException;
use PHPUnit\Framework\TestCase;

final class StringLiteralTest extends TestCase
{
    public function testEncodeDecodeEmptyString(): void
    {
        $encoded = StringLiteral::encode('');
        $decoded = StringLiteral::decode($encoded, 0);

        $this->assertSame('', $decoded['value']);
        $this->assertSame(strlen($encoded), $decoded['nextOffset']);
    }

    public function testEncodeDecodeAsciiAndBinary(): void
    {
        $value = "abc\x00xyz";
        $encoded = StringLiteral::encode($value);
        $decoded = StringLiteral::decode($encoded, 0);

        $this->assertSame($value, $decoded['value']);
        $this->assertSame(strlen($encoded), $decoded['nextOffset']);
    }

    public function testDecodeRejectsHuffmanStringLiteral(): void
    {
        $this->expectException(UnsupportedFeatureException::class);
        StringLiteral::decode("\x80", 0);
    }

    public function testDecodeThrowsOnTruncatedInput(): void
    {
        $encoded = StringLiteral::encode('abc');
        $truncated = substr($encoded, 0, -1);

        $this->expectException(DecodeException::class);
        StringLiteral::decode($truncated, 0);
    }
}

