<?php

declare(strict_types=1);

namespace Hpack;

final class StaticTable
{
    /**
     * 1-indexed HPACK static table.
     *
     * @var array<int,array{name:string,value:string}>
     */
    private const TABLE = [
        1 => ['name' => ':authority', 'value' => ''],
        2 => ['name' => ':method', 'value' => 'GET'],
        3 => ['name' => ':method', 'value' => 'POST'],
        4 => ['name' => ':path', 'value' => '/'],
        5 => ['name' => ':path', 'value' => '/index.html'],
        6 => ['name' => ':scheme', 'value' => 'http'],
        7 => ['name' => ':scheme', 'value' => 'https'],
        8 => ['name' => ':status', 'value' => '200'],
        9 => ['name' => ':status', 'value' => '204'],
        10 => ['name' => ':status', 'value' => '206'],
        11 => ['name' => ':status', 'value' => '304'],
        12 => ['name' => ':status', 'value' => '400'],
        13 => ['name' => ':status', 'value' => '404'],
        14 => ['name' => ':status', 'value' => '500'],
        15 => ['name' => 'accept-charset', 'value' => ''],
        16 => ['name' => 'accept-encoding', 'value' => 'gzip, deflate'],
        17 => ['name' => 'accept-language', 'value' => ''],
        18 => ['name' => 'accept-ranges', 'value' => ''],
        19 => ['name' => 'accept', 'value' => ''],
        20 => ['name' => 'access-control-allow-origin', 'value' => ''],
        21 => ['name' => 'age', 'value' => ''],
        22 => ['name' => 'allow', 'value' => ''],
        23 => ['name' => 'authorization', 'value' => ''],
        24 => ['name' => 'cache-control', 'value' => ''],
        25 => ['name' => 'content-disposition', 'value' => ''],
        26 => ['name' => 'content-encoding', 'value' => ''],
        27 => ['name' => 'content-language', 'value' => ''],
        28 => ['name' => 'content-length', 'value' => ''],
        29 => ['name' => 'content-location', 'value' => ''],
        30 => ['name' => 'content-range', 'value' => ''],
        31 => ['name' => 'content-type', 'value' => ''],
        32 => ['name' => 'cookie', 'value' => ''],
        33 => ['name' => 'date', 'value' => ''],
        34 => ['name' => 'etag', 'value' => ''],
        35 => ['name' => 'expect', 'value' => ''],
        36 => ['name' => 'expires', 'value' => ''],
        37 => ['name' => 'from', 'value' => ''],
        38 => ['name' => 'host', 'value' => ''],
        39 => ['name' => 'if-match', 'value' => ''],
        40 => ['name' => 'if-modified-since', 'value' => ''],
        41 => ['name' => 'if-none-match', 'value' => ''],
        42 => ['name' => 'if-range', 'value' => ''],
        43 => ['name' => 'if-unmodified-since', 'value' => ''],
        44 => ['name' => 'last-modified', 'value' => ''],
        45 => ['name' => 'link', 'value' => ''],
        46 => ['name' => 'location', 'value' => ''],
        47 => ['name' => 'max-forwards', 'value' => ''],
        48 => ['name' => 'proxy-authenticate', 'value' => ''],
        49 => ['name' => 'proxy-authorization', 'value' => ''],
        50 => ['name' => 'range', 'value' => ''],
        51 => ['name' => 'referer', 'value' => ''],
        52 => ['name' => 'refresh', 'value' => ''],
        53 => ['name' => 'retry-after', 'value' => ''],
        54 => ['name' => 'server', 'value' => ''],
        55 => ['name' => 'set-cookie', 'value' => ''],
        56 => ['name' => 'strict-transport-security', 'value' => ''],
        57 => ['name' => 'transfer-encoding', 'value' => ''],
        58 => ['name' => 'user-agent', 'value' => ''],
        59 => ['name' => 'vary', 'value' => ''],
        60 => ['name' => 'via', 'value' => ''],
        61 => ['name' => 'www-authenticate', 'value' => ''],
    ];

    /**
     * @return array{name:string,value:string}
     */
    public static function getByIndex(int $index): array
    {
        if (!isset(self::TABLE[$index])) {
            throw new DecodeException(sprintf('Static table index out of range: %d', $index));
        }

        return self::TABLE[$index];
    }

    public static function findIndexByPair(string $name, string $value): ?int
    {
        foreach (self::TABLE as $index => $entry) {
            if ($entry['name'] === $name && $entry['value'] === $value) {
                return $index;
            }
        }

        return null;
    }

    public static function findIndexByName(string $name): ?int
    {
        foreach (self::TABLE as $index => $entry) {
            if ($entry['name'] === $name) {
                return $index;
            }
        }

        return null;
    }

    public static function count(): int
    {
        return count(self::TABLE);
    }
}
