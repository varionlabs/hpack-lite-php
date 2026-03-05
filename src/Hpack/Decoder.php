<?php

declare(strict_types=1);

namespace Hpack;

final class Decoder
{
    /**
     * @return array<int,array{name:string,value:string}>
     */
    public function decode(string $headerBlock): array
    {
        $headers = [];
        $offset = 0;
        $length = strlen($headerBlock);

        while ($offset < $length) {
            $byte = ord($headerBlock[$offset]);

            if (($byte & 0x80) !== 0) {
                $decoded = Integer::decodeInteger($headerBlock, $offset, 7);
                $index = $decoded['value'];
                $offset = $decoded['nextOffset'];

                if ($index === 0) {
                    throw new DecodeException(sprintf('Invalid indexed header field index=0 at offset=%d', $offset - 1));
                }

                if ($index > StaticTable::count()) {
                    throw new UnsupportedFeatureException(
                        sprintf('Dynamic table indexed header is unsupported (index=%d, offset=%d)', $index, $offset - 1)
                    );
                }

                $headers[] = StaticTable::getByIndex($index);
                continue;
            }

            if (($byte & 0xe0) === 0x20) {
                throw new UnsupportedFeatureException(
                    sprintf('Dynamic table size update is unsupported (offset=%d)', $offset)
                );
            }

            if (($byte & 0xc0) === 0x40) {
                throw new UnsupportedFeatureException(
                    sprintf('Literal header field with indexing is unsupported (offset=%d)', $offset)
                );
            }

            if (($byte & 0xf0) === 0x00 || ($byte & 0xf0) === 0x10) {
                $decoded = $this->decodeLiteralHeader($headerBlock, $offset, 4);
                $headers[] = ['name' => $decoded['name'], 'value' => $decoded['value']];
                $offset = $decoded['nextOffset'];
                continue;
            }

            throw new DecodeException(
                sprintf('Unknown header field representation (byte=0x%02x, offset=%d)', $byte, $offset)
            );
        }

        return $headers;
    }

    /**
     * @return array{name:string,value:string,nextOffset:int}
     */
    private function decodeLiteralHeader(string $data, int $offset, int $namePrefixBits): array
    {
        $nameIndexInfo = Integer::decodeInteger($data, $offset, $namePrefixBits);
        $nameIndex = $nameIndexInfo['value'];
        $cursor = $nameIndexInfo['nextOffset'];

        if ($nameIndex === 0) {
            $nameInfo = StringLiteral::decode($data, $cursor);
            $name = $nameInfo['value'];
            $cursor = $nameInfo['nextOffset'];
        } else {
            if ($nameIndex > StaticTable::count()) {
                throw new UnsupportedFeatureException(
                    sprintf('Dynamic table name reference is unsupported (index=%d, offset=%d)', $nameIndex, $offset)
                );
            }

            $name = StaticTable::getByIndex($nameIndex)['name'];
        }

        $valueInfo = StringLiteral::decode($data, $cursor);
        return [
            'name' => $name,
            'value' => $valueInfo['value'],
            'nextOffset' => $valueInfo['nextOffset'],
        ];
    }
}
