# minihpack-php

Minimal HPACK implementation in PHP for HTTP/2 header block encoding/decoding.

Package name on Composer: `varionlabs/minipack`

## Requirements

- PHP 8.2+

## Scope

This library intentionally implements only a small HPACK subset:

- Static Table (RFC 7541) only
- Header representations:
  - Indexed Header Field
  - Literal Header Field without Indexing
  - Literal Header Field never Indexed
- HPACK integer encoding/decoding
- String literal encoding/decoding with Huffman disabled (`H=0`)

Not supported:

- Dynamic Table (indexing, eviction, size management)
- Dynamic Table Size Update (`001xxxxx`)
- Huffman string encoding/decoding (`H=1`)
- HTTP/2 frame processing

Unsupported features raise `Hpack\UnsupportedFeatureException`.

## Installation

```bash
composer require varionlabs/minipack
```

## Public API

### Decode

```php
<?php

declare(strict_types=1);

use Hpack\Decoder;

$decoder = new Decoder();
$headers = $decoder->decode($headerBlock);

// $headers format:
// [
//   ['name' => ':method', 'value' => 'GET'],
//   ['name' => ':path', 'value' => '/'],
// ]
```

### Encode

```php
<?php

declare(strict_types=1);

use Hpack\Encoder;

$encoder = new Encoder();

// List format
$blockA = $encoder->encode([
    ['name' => ':method', 'value' => 'GET'],
    ['name' => ':path', 'value' => '/hello'],
]);

// Associative format
$blockB = $encoder->encode([
    ':method' => 'GET',
    ':path' => '/hello',
]);
```

Encoding strategy:

1. Exact `(name, value)` match in static table -> indexed representation
2. Name match only -> literal without indexing (indexed name)
3. No match -> literal without indexing (new name)

## Low-level Helpers

- `Hpack\Integer::decodeInteger(string $data, int $offset, int $prefixBits): array{value:int,nextOffset:int}`
- `Hpack\Integer::encodeInteger(int $value, int $prefixBits, int $prefixMaskBase): string`
- `Hpack\StringLiteral::decode(string $data, int $offset): array{value:string,nextOffset:int}`
- `Hpack\StringLiteral::encode(string $value): string`

All methods operate on binary-safe PHP strings.

## Error Handling

Main exception types:

- `Hpack\DecodeException`
- `Hpack\EncodeException`
- `Hpack\UnsupportedFeatureException`

Exceptions include context such as byte offset when possible.

## Development

Install dependencies:

```bash
composer install
```

Run PHPUnit:

```bash
composer test
```

Optional plain assert-based test runner:

```bash
php tests/run.php
```

## License

MIT

