<?php

declare(strict_types=1);

namespace Hpack;

final class Encoder
{
    public function encode(array $headers): string
    {
        $normalized = $this->normalizeHeaders($headers);
        $result = '';

        foreach ($normalized as $header) {
            $name = $header['name'];
            $value = $header['value'];

            $pairIndex = StaticTable::findIndexByPair($name, $value);
            if ($pairIndex !== null) {
                $result .= Integer::encodeInteger($pairIndex, 7, 0x80);
                continue;
            }

            $nameIndex = StaticTable::findIndexByName($name);
            if ($nameIndex !== null) {
                $result .= Integer::encodeInteger($nameIndex, 4, 0x00);
                $result .= StringLiteral::encode($value);
                continue;
            }

            $result .= Integer::encodeInteger(0, 4, 0x00);
            $result .= StringLiteral::encode($name);
            $result .= StringLiteral::encode($value);
        }

        return $result;
    }

    /**
     * @return array<int,array{name:string,value:string}>
     */
    private function normalizeHeaders(array $headers): array
    {
        if ($headers === []) {
            return [];
        }

        $isList = array_keys($headers) === range(0, count($headers) - 1);
        if ($isList) {
            $normalized = [];
            foreach ($headers as $i => $header) {
                if (!is_array($header) || !array_key_exists('name', $header) || !array_key_exists('value', $header)) {
                    throw new EncodeException(sprintf('Header list item must contain name/value keys (index=%d)', $i));
                }
                if (!is_string($header['name']) || !is_string($header['value'])) {
                    throw new EncodeException(sprintf('Header name/value must be strings (index=%d)', $i));
                }
                $normalized[] = ['name' => $header['name'], 'value' => $header['value']];
            }
            return $normalized;
        }

        $normalized = [];
        foreach ($headers as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                throw new EncodeException('Associative headers must be string => string');
            }
            $normalized[] = ['name' => $name, 'value' => $value];
        }

        return $normalized;
    }
}
