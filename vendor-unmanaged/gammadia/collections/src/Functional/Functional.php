<?php

declare(strict_types=1);

namespace Gammadia\Collections\Functional;

const FUNCTIONS_REPLACEMENTS_MAP = [
    'array_chunk' => __NAMESPACE__ . '\\chunk',
    'array_column' => __NAMESPACE__ . '\\column',
    'array_merge' => __NAMESPACE__ . '\\concat',
    'in_array' => __NAMESPACE__ . '\\contains',
    'array_combine' => __NAMESPACE__ . '\\combine',
    'array_diff' => __NAMESPACE__ . '\\diff',
    'array_udiff' => __NAMESPACE__ . '\\diffUsing',
    'array_walk' => __NAMESPACE__ . '\\each',
    'array_fill' => __NAMESPACE__ . '\\fill',
    'array_filter' => __NAMESPACE__ . '\\filter',
    'reset' => __NAMESPACE__ . '\\first',
    'array_flip' => __NAMESPACE__ . '\\flip',
    'array_intersect' => __NAMESPACE__ . '\\intersect',
    'array_uintersect' => __NAMESPACE__ . '\\intersectUsing',
    'array_intersect_key' => __NAMESPACE__ . '\\intersectKeys',
    'array_pop' => __NAMESPACE__ . '\\init',
    'array_keys' => __NAMESPACE__ . '\\keys',
    'array_key_last' => __NAMESPACE__ . '\\last',
    'end' => __NAMESPACE__ . '\\last',
    'array_map' => __NAMESPACE__ . '\\map',
    'array_reduce' => __NAMESPACE__ . '\\reduce',
    'array_reverse' => __NAMESPACE__ . '\\reverse',
    'sort' => __NAMESPACE__ . '\\sort',
    'asort' => __NAMESPACE__ . '\\sort',
    'uasort' => __NAMESPACE__ . '\\sort',
    'ksort' => __NAMESPACE__ . '\\sortKeys',
    'array_shift' => __NAMESPACE__ . '\\tail',
    'array_unique' => __NAMESPACE__ . '\\unique',
    'array_values' => __NAMESPACE__ . '\\values',
];

/*
 * See
 *  - [https://github.com/laravel/framework/blob/master/src/Illuminate/Support/Collection.php]
 *  - [https://lodash.com/docs/]
 * for inspiration
*/

function chunk(array $array, int $size, bool $preserveKey = false): array
{
    return array_chunk($array, $size, $preserveKey);
}

function collect(array $array, callable $fn): array {
    $chunks = [];
    foreach ($array as $key => $value) {
        $chunks[] = iterator_to_array(Util::assertTraversable($fn($value, $key)));
    }
    return flatten($chunks);
}

function column(array $array, string $column, string $index = null): array
{
    return array_column($array, $column, $index);
}

function concat(array ...$arrays): array
{
    return array_merge([], ...$arrays);
}

function contains(array $array, $item, bool $strict = false): bool
{
    return in_array($item, $array, $strict);
}

function combine(array $keys, array $values): array
{
    return array_combine($keys, $values);
}

function diff(array $array, array ...$others): array
{
    return array_diff($array, ...$others);
}

function diffUsing(array $array, array $other, callable $comparator): array
{
    return array_udiff($array, $other, $comparator);
}

function each(array $array, callable $fn): array
{
    foreach ($array as $key => $value) {
        if ($fn($value, $key) === false) {
            break;
        }
    }
    return $array;
}

function eachSpread(array $array, callable $fn): array
{
    return each($array, static function (array $chunk, $key) use ($fn) {
        $chunk[] = $key;
        return $fn(...$chunk);
    });
}

function every(array $array, callable $fn): bool
{
    foreach ($array as $key => $value) {
        if (!$fn($value, $key)) {
            return false;
        }
    }
    return true;
}

function fill(int $startIndex, int $num, $defaultValue = null): array
{
    return array_fill($startIndex, $num, $defaultValue);
}

function fillWith(array $array, int $start, int $num, callable $generator): array {
    for ($i = 0; $i < $num; ++$i) {
      $array[$i + $start] = $generator();
    }
    return $array;
}

function filter(array $array, callable $predicate = null): array
{
    return array_filter($array, $predicate, ARRAY_FILTER_USE_BOTH);
}

function first(array $array)
{
    return empty($array) ? null : $array[array_key_first($array)];
}

function flatten(array $arrays): array
{
    return array_merge([], ...$arrays);
}

function flip(array $array): array
{
    return array_flip($array);
}

/**
 * @param array               $array
 * @param callable|callable[] $groupBy
 * @param bool                $preserveKey
 * @return array
 */
function groupBy(array $array, $groupBy, bool $preserveKey = false): array
{
    if (is_array($groupBy)) {
        $nextGroups = $groupBy;
        $groupBy = array_shift($nextGroups);
    }

    $results = [];

    foreach ($array as $key => $value) {
        $groupKeys = $groupBy($value, $key);

        if (!is_array($groupKeys)) {
            $groupKeys = [$groupKeys];
        }

        foreach ($groupKeys as $groupKey) {
            $preserveKey
                ? $results[$groupKey][$key] = $value
                : $results[$groupKey][] = $value;
        }
    }

    if (!empty($nextGroups)) {
        return map($results, static function ($group) use ($preserveKey, $nextGroups) {
            return groupBy($group, $nextGroups, $preserveKey);
        });
    }

    return $results;
}

function intersect(array $array, array ...$others): array
{
    return array_intersect($array, ...$others);
}

function intersectUsing(array $array, array $other, callable $comparator): array
{
    return array_uintersect($array, $other, $comparator);
}

function intersectKeys(array $array, array ...$others): array
{
    return array_intersect_key($array, ...$others);
}

function indexBy(array $array, callable $key): array
{
    $indexed = [];
    foreach ($array as $item) {
        $indexed[$key($item)] = $item;
    }
    return $indexed;
}

function init(array $array): array
{
    array_pop($array);

    return $array;
}

function keys(array $array): array
{
    return array_keys($array);
}

function last(array $array)
{
    return empty($array) ? null : $array[array_key_last($array)];
}

function map(array $array, callable $fn, bool $withKeyArgument = false): array
{
    if ($withKeyArgument) {
        $keys = array_keys($array);
        return array_combine($keys, array_map($fn, $array, $keys));
    }

    return array_map($fn, $array);
}

function mapSpread(array $array, callable $fn): array
{
    return map($array, static function (array $chunk, $key) use ($fn) {
        $chunk[] = $key;
        return $fn(...$chunk);
    }, true);
}

function mapWithKeys(array $array, callable $fn): array
{
    $result = [];
    foreach ($array as $key => $value) {
        foreach (Util::assertTraversable($fn($value, $key)) as $mapKey => $mapValue) {
            $result[$mapKey] = $mapValue;
        }
    }
    return $result;
}

function reduce(array $array, callable $reducer, $initial = null)
{
    return array_reduce($array, $reducer, $initial);
}

function reverse(array $array, bool $preserveKey = false): array
{
    return array_reverse($array, $preserveKey);
}

function some(array $array, callable $predicate): bool
{
    foreach ($array as $item) {
        if ($predicate($item)) {
            return true;
        }
    }
    return false;
}

function sort(array $array, callable $comparator = null): array
{
    $comparator ? uasort($array, $comparator) : asort($array);
    return $array;
}

function sortKeys(array $array, int $options = SORT_REGULAR): array
{
    ksort($array, $options);
    return $array;
}

function tail(array $array): array
{
    array_shift($array);

    return $array;
}

function unique(array $array, callable $key = null, bool $strict = false): array
{
    $exists = [];
    return array_filter($array, static function ($item) use ($key, $strict, &$exists) {
        if (!in_array($id = $key ? $key($item) : $item, $exists, $strict)) {
            $exists[] = $id;
            return true;
        }
        return false;
    });
}

function values(array $array): array
{
    return array_values($array);
}

function zip(array ...$arrays): array
{
    return array_map(null, ...$arrays);
}
