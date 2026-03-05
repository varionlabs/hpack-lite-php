<?php

declare(strict_types=1);

namespace Minihpack\Tests;

use Hpack\DecodeException;
use Hpack\Decoder;
use Hpack\Encoder;
use Hpack\Integer;
use Hpack\StringLiteral;
use Hpack\UnsupportedFeatureException;
use PHPUnit\Framework\TestCase;

final class CodecTest extends TestCase
{
    public function testDecodeIndexedHeaderField(): void
    {
        $decoder = new Decoder();
        $headerBlock = Integer::encodeInteger(2, 7, 0x80);

        $headers = $decoder->decode($headerBlock);
        $this->assertSame([['name' => ':method', 'value' => 'GET']], $headers);
    }

    public function testDecodeLiteralWithoutIndexingWithIndexedName(): void
    {
        $decoder = new Decoder();
        $headerBlock = Integer::encodeInteger(4, 4, 0x00) . StringLiteral::encode('/hello');

        $headers = $decoder->decode($headerBlock);
        $this->assertSame([['name' => ':path', 'value' => '/hello']], $headers);
    }

    public function testDecodeLiteralWithoutIndexingWithNewName(): void
    {
        $decoder = new Decoder();
        $headerBlock = Integer::encodeInteger(0, 4, 0x00)
            . StringLiteral::encode('x-foo')
            . StringLiteral::encode('bar');

        $headers = $decoder->decode($headerBlock);
        $this->assertSame([['name' => 'x-foo', 'value' => 'bar']], $headers);
    }

    public function testEncodeUsesIndexedWhenPairMatchesStaticTable(): void
    {
        $encoder = new Encoder();
        $encoded = $encoder->encode([['name' => ':method', 'value' => 'GET']]);

        $this->assertSame(Integer::encodeInteger(2, 7, 0x80), $encoded);
    }

    public function testEncodeDecodeLiteralWithIndexedNameRoundTrip(): void
    {
        $encoder = new Encoder();
        $decoder = new Decoder();

        $headers = [['name' => ':path', 'value' => '/custom']];
        $encoded = $encoder->encode($headers);
        $decoded = $decoder->decode($encoded);

        $this->assertSame($headers, $decoded);
    }

    public function testEncodeDecodeLiteralWithNewNameRoundTrip(): void
    {
        $encoder = new Encoder();
        $decoder = new Decoder();

        $headers = [['name' => 'x-test', 'value' => "a\x00b"]];
        $encoded = $encoder->encode($headers);
        $decoded = $decoder->decode($encoded);

        $this->assertSame($headers, $decoded);
    }

    public function testDecodeRejectsHuffmanFlagInsideHeaderBlock(): void
    {
        $decoder = new Decoder();
        $headerBlock = Integer::encodeInteger(0, 4, 0x00) . "\x80" . StringLiteral::encode('value');

        $this->expectException(UnsupportedFeatureException::class);
        $decoder->decode($headerBlock);
    }

    public function testDecodeRejectsDynamicTableSizeUpdate(): void
    {
        $decoder = new Decoder();
        $this->expectException(UnsupportedFeatureException::class);
        $decoder->decode(chr(0x20));
    }

    public function testDecodeRejectsInvalidIndexZero(): void
    {
        $decoder = new Decoder();
        $this->expectException(DecodeException::class);
        $decoder->decode(chr(0x80));
    }

    public function testDecodeRejectsTruncatedHeaderBlock(): void
    {
        $decoder = new Decoder();
        $headerBlock = Integer::encodeInteger(4, 4, 0x00) . "\x03ab";

        $this->expectException(DecodeException::class);
        $decoder->decode($headerBlock);
    }

    public function testDecodeRejectsLiteralWithIndexingRepresentation(): void
    {
        $decoder = new Decoder();
        $this->expectException(UnsupportedFeatureException::class);
        $decoder->decode(chr(0x40));
    }

    public function testDecodeRejectsStaticTableOutOfRangeAsDynamicReference(): void
    {
        $decoder = new Decoder();
        $headerBlock = Integer::encodeInteger(62, 7, 0x80);

        $this->expectException(UnsupportedFeatureException::class);
        $decoder->decode($headerBlock);
    }
}

