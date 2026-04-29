<?php

declare(strict_types=1);

namespace anvildev\trails\helpers;

final class DiffRenderer
{
    /**
     * Compare two arrays and return a flat list of changes.
     *
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     * @param bool $skipUnchanged When true, omits 'unchanged' entries from the result
     * @return list<array{key: string, change: 'added'|'removed'|'modified'|'unchanged', oldValue: mixed, newValue: mixed}>
     */
    public static function compare(array $old, array $new, bool $skipUnchanged = false): array
    {
        $flatOld = self::flatten($old);
        $flatNew = self::flatten($new);
        $allKeys = array_unique(array_merge(array_keys($flatOld), array_keys($flatNew)));
        sort($allKeys);

        $result = [];
        foreach ($allKeys as $key) {
            $inOld = array_key_exists($key, $flatOld);
            $inNew = array_key_exists($key, $flatNew);
            $oldValue = $flatOld[$key] ?? null;
            $newValue = $flatNew[$key] ?? null;

            if (!$inOld && $inNew) {
                $change = 'added';
            } elseif ($inOld && !$inNew) {
                $change = 'removed';
            } elseif ($oldValue !== $newValue) {
                $change = 'modified';
            } else {
                $change = 'unchanged';
            }

            if ($skipUnchanged && $change === 'unchanged') {
                continue;
            }

            $result[] = [
                'key' => $key,
                'change' => $change,
                'oldValue' => $oldValue,
                'newValue' => $newValue,
            ];
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function flatten(array $input, string $prefix = ''): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value) && !array_is_list($value) && $value !== []) {
                $result = array_merge($result, self::flatten($value, $path));
            } else {
                $result[$path] = $value;
            }
        }
        return $result;
    }
}
