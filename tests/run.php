<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Hpack/Exceptions.php';
require_once __DIR__ . '/../src/Hpack/Integer.php';
require_once __DIR__ . '/../src/Hpack/StringLiteral.php';
require_once __DIR__ . '/../src/Hpack/StaticTable.php';
require_once __DIR__ . '/../src/Hpack/Decoder.php';
require_once __DIR__ . '/../src/Hpack/Encoder.php';

use Hpack\DecodeException;
use Hpack\Decoder;
use Hpack\EncodeException;
use Hpack\Encoder;
use Hpack\Integer;
use Hpack\StringLiteral;
use Hpack\UnsupportedFeatureException;

$assertions = 0;

$assertSame = static function (mixed $expected, mixed $actual, string $label) use (&$assertions): void {
    if ($expected !== $actual) {
        fwrite(STDERR, "[FAIL] {$label}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
    $assertions++;
};

$assertThrows = static function (callable $fn, string $expectedClass, string $label) use (&$assertions): void {
    try {
        $fn();
    } catch (\Throwable $e) {
        if ($e instanceof $expectedClass) {
            $assertions++;
            return;
        }
        fwrite(STDERR, "[FAIL] {$label}\nExpected exception {$expectedClass}, got " . $e::class . ": {$e->getMessage()}\n");
        exit(1);
    }

    fwrite(STDERR, "[FAIL] {$label}\nExpected exception {$expectedClass}, but no exception was thrown.\n");
    exit(1);
};

// Integer encode/decode boundaries for prefix=5/6/7.
foreach ([5, 6, 7] as $prefixBits) {
    $maxPrefix = (1 << $prefixBits) - 1;
    $values = [0, $maxPrefix - 1, $maxPrefix, $maxPrefix + 1, 1337];

    foreach ($values as $value) {
        $encoded = Integer::encodeInteger($value, $prefixBits, 0x00);
        $decoded = Integer::decodeInteger($encoded, 0, $prefixBits);
        $assertSame($value, $decoded['value'], "Integer round-trip value (prefix={$prefixBits}, value={$value})");
        $assertSame(strlen($encoded), $decoded['nextOffset'], "Integer nextOffset (prefix={$prefixBits}, value={$value})");
    }
}

$assertSame(0xea, ord(Integer::encodeInteger(10, 5, 0xe0)[0]), 'Integer preserves prefix mask base bits');
$assertThrows(
    static fn (): int => Integer::decodeInteger(chr(0x1f), 0, 5)['value'],
    DecodeException::class,
    'Integer decode truncated continuation'
);
$assertThrows(
    static fn (): int => Integer::decodeInteger(chr(0x1f) . str_repeat("\x80", 9) . "\x00", 0, 5)['value'],
    DecodeException::class,
    'Integer decode shift overflow'
);
$assertThrows(
    static fn (): string => Integer::encodeInteger(1, 5, 0x1f),
    EncodeException::class,
    'Integer encode rejects invalid prefixMaskBase'
);

// String literal encode/decode.
$encodedEmpty = StringLiteral::encode('');
$decodedEmpty = StringLiteral::decode($encodedEmpty, 0);
$assertSame('', $decodedEmpty['value'], 'String literal empty value');
$assertSame(strlen($encodedEmpty), $decodedEmpty['nextOffset'], 'String literal empty nextOffset');

$binValue = "abc\x00xyz";
$encodedBin = StringLiteral::encode($binValue);
$decodedBin = StringLiteral::decode($encodedBin, 0);
$assertSame($binValue, $decodedBin['value'], 'String literal binary value');
$assertSame(strlen($encodedBin), $decodedBin['nextOffset'], 'String literal binary nextOffset');

$assertThrows(
    static fn (): array => StringLiteral::decode("\x80", 0),
    UnsupportedFeatureException::class,
    'String literal rejects Huffman=1'
);
$assertThrows(
    static fn (): array => StringLiteral::decode(substr(StringLiteral::encode('abc'), 0, -1), 0),
    DecodeException::class,
    'String literal truncated decode'
);

// Header representation decode/encode.
$decoder = new Decoder();
$encoder = new Encoder();

$assertSame(
    [['name' => ':method', 'value' => 'GET']],
    $decoder->decode(Integer::encodeInteger(2, 7, 0x80)),
    'Decode indexed header'
);

$assertSame(
    [['name' => ':path', 'value' => '/hello']],
    $decoder->decode(Integer::encodeInteger(4, 4, 0x00) . StringLiteral::encode('/hello')),
    'Decode literal without indexing (indexed name)'
);

$assertSame(
    [['name' => 'x-foo', 'value' => 'bar']],
    $decoder->decode(Integer::encodeInteger(0, 4, 0x00) . StringLiteral::encode('x-foo') . StringLiteral::encode('bar')),
    'Decode literal without indexing (new name)'
);

$assertSame(
    Integer::encodeInteger(2, 7, 0x80),
    $encoder->encode([['name' => ':method', 'value' => 'GET']]),
    'Encode chooses indexed representation for static pair match'
);

$roundTrip1 = [['name' => ':path', 'value' => '/custom']];
$assertSame($roundTrip1, $decoder->decode($encoder->encode($roundTrip1)), 'Round-trip literal indexed name');

$roundTrip2 = [['name' => 'x-test', 'value' => "a\x00b"]];
$assertSame($roundTrip2, $decoder->decode($encoder->encode($roundTrip2)), 'Round-trip literal new name');

$assertSame(
    [['name' => ':method', 'value' => 'GET']],
    $decoder->decode($encoder->encode([':method' => 'GET'])),
    'Encode accepts associative input'
);

$assertThrows(
    static fn (): array => $decoder->decode(Integer::encodeInteger(0, 4, 0x00) . "\x80" . StringLiteral::encode('v')),
    UnsupportedFeatureException::class,
    'Decode rejects Huffman flag in header block'
);
$assertThrows(
    static fn (): array => $decoder->decode(chr(0x20)),
    UnsupportedFeatureException::class,
    'Decode rejects dynamic table size update'
);
$assertThrows(
    static fn (): array => $decoder->decode(chr(0x80)),
    DecodeException::class,
    'Decode rejects invalid index=0'
);
$assertThrows(
    static fn (): array => $decoder->decode(Integer::encodeInteger(4, 4, 0x00) . "\x03ab"),
    DecodeException::class,
    'Decode rejects truncated header block'
);
$assertThrows(
    static fn (): array => $decoder->decode(chr(0x40)),
    UnsupportedFeatureException::class,
    'Decode rejects literal with indexing representation'
);
$assertThrows(
    static fn (): array => $decoder->decode(Integer::encodeInteger(62, 7, 0x80)),
    UnsupportedFeatureException::class,
    'Decode rejects static out-of-range as dynamic index'
);

fwrite(STDOUT, "All tests passed ({$assertions} assertions).\n");

