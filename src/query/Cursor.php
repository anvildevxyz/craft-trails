<?php

namespace anvildev\trails\query;

final class Cursor
{
    public static function encode(string $dateCreated, int $id): string
    {
        $json = json_encode(['d' => $dateCreated, 'i' => $id]);
        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    /**
     * @return array{dateCreated: string, id: int}|null
     */
    public static function decode(?string $token): ?array
    {
        if ($token === null || $token === '') {
            return null;
        }

        $padded = str_pad(strtr($token, '-_', '+/'), strlen($token) + (4 - strlen($token) % 4) % 4, '=');
        $json = base64_decode($padded, strict: true);

        if ($json === false) {
            return null;
        }

        $data = json_decode($json, associative: true);

        if (!is_array($data)) {
            return null;
        }

        if (!isset($data['d'], $data['i']) || !is_string($data['d']) || !is_int($data['i'])) {
            return null;
        }

        return ['dateCreated' => $data['d'], 'id' => $data['i']];
    }
}
