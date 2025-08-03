<?php

declare(strict_types=1);

namespace Inertia\Support\Arr;

use ArrayAccess;
use Closure;
use Tempest\Support\Arr;
use Tempest\Support\Arr\ArrayInterface;

if (!function_exists(__NAMESPACE__ . '\forget_keys')) {
    /**
     * Removes the specified keys from the array using dot notation. The array is mutated.
     */
    function forget_keys(array &$array, string|int|array $keys): array
    {
        $original = &$array;
        $keys = is_array($keys) ? $keys : [$keys];

        if ($keys === []) {
            return $array;
        }

        foreach ($keys as $key) {
            $key = (string) $key;

            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                continue;
            }

            if (!str_contains($key, '.')) {
                continue;
            }

            $parts = explode('.', $key);
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }

        return $original;
    }
}

if (!function_exists(__NAMESPACE__ . '\data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     */
    function data_get(mixed $target, string|array|int|null $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', (string) $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if ($target instanceof ArrayInterface) {
                    $target = $target->toArray();
                }

                if (!is_iterable($target)) {
                    return ($default instanceof Closure) ? $default() : $default;
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key, true) ? Arr\flatten($result, 1) : $result;
            }

            $arrayableTarget = is_array($target) ? $target : ((array) $target);

            $segment = match ($segment) {
                '\*' => '*',
                '\{first}' => '{first}',
                '{first}' => array_key_first($arrayableTarget),
                '\{last}' => '{last}',
                '{last}' => array_key_last($arrayableTarget),
                default => $segment,
            };

            if (is_null($segment)) {
                return ($default instanceof Closure) ? $default() : $default;
            }

            if ((is_array($target) || $target instanceof ArrayAccess) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return ($default instanceof Closure) ? $default() : $default;
            }
        }

        return $target;
    }
}
