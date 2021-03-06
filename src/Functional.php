<?php

declare(strict_types=1);

namespace Gammadia\Collections\Functional;

use InvalidArgumentException;
use TypeError;
use UnexpectedValueException;
use Webmozart\Assert\Assert;
use function App\Infrastructure\Shared\Utils\equals;
use function array_slice;
use function count;
use function is_array;
use function is_callable;

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

function all(array $array, ?callable $predicate = null): bool
{
    foreach ($array as $item) {
        $result = null === $predicate ? (bool) $item : $predicate($item);
        if (!$result) {
            return false;
        }
    }

    return true;
}

function chunk(array $array, int $size, bool $preserveKey = false): array
{
    return array_chunk($array, $size, $preserveKey);
}

function collect(array $array, callable $fn): array
{
    /** @phpstan-ignore-next-line */
    $stream = scollect($array, $fn);

    return iterator_to_array($stream, preserve_keys: false);
}

function collectWithKeys(array $array, callable $fn): array
{
    /** @phpstan-ignore-next-line */
    $stream = scollect($array, $fn);

    $values = [];
    $counter = 0;

    foreach ($stream as $key => $value) {
        try {
            $values[$key] = $value;
            /** @phpstan-ignore-next-line That's probably a PHPStan bug as the catch can definitely happen */
        } catch (TypeError) {
            throw new UnexpectedValueException('The key yielded in the callable is not compatible with the type "array-key".');
        }

        ++$counter;
    }

    if ($counter !== count($values)) {
        throw new UnexpectedValueException(
            'Data loss occurred because of duplicated keys. Use `collect()` if you do not care about ' .
            'the yielded keys, or use `scollect()` if you need to support duplicated keys (as arrays cannot).',
        );
    }

    return $values;
}

function column(array $array, string|int|null $column, string|int|null $index = null): array
{
    return array_column($array, $column, $index);
}

function concat(array ...$arrays): array
{
    return array_merge([], ...$arrays);
}

function contains(array $array, mixed $value): bool
{
    foreach ($array as $item) {
        if (equals($item, $value)) {
            return true;
        }
    }

    return false;
}

function combine(array $keys, array $values): array
{
    /** @var mixed[]|false $result */
    $result = array_combine($keys, $values);

    if (false === $result) {
        throw new InvalidArgumentException('The number of elements for each array is not equal or the arrays are empty.');
    }

    return $result;
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
        if (false === $fn($value, $key)) {
            break;
        }
    }

    return $array;
}

function eachSpread(array $array, callable $fn): array
{
    return each($array, static function (array $chunk, mixed $key) use ($fn) {
        $chunk[] = $key;

        return $fn(...$chunk);
    });
}

/**
 * @param positive-int $count
 */
function fill(int $startIndex, int $count, mixed $defaultValue = null): array
{
    return array_fill($startIndex, $count, $defaultValue);
}

function fillWith(array $array, int $start, int $count, callable $generator): array
{
    for ($i = 0; $i < $count; ++$i) {
        $array[$i + $start] = $generator();
    }

    return $array;
}

function filter(array $array, ?callable $predicate = null): array
{
    // We cannot call array_filter with "null" as the callback, otherwise it results in this error :
    // TypeError: array_filter() expects parameter 2 to be a valid callback, no array or string given
    if (null !== $predicate) {
        return array_filter($array, $predicate, ARRAY_FILTER_USE_BOTH);
    }

    return array_filter($array);
}

function first(array $array): mixed
{
    return empty($array) ? null : $array[array_key_first($array)];
}

function flatten(array $arrays): array
{
    return array_merge([], ...values($arrays));
}

function flip(array $array): array
{
    return array_flip($array);
}

/**
 * @param callable|callable[] $groupBy
 */
function groupBy(array $array, array|callable $groupBy, bool $preserveKey = false): array
{
    $nextGroups = [];
    if (is_array($groupBy)) {
        $nextGroups = $groupBy;
        $groupBy = array_shift($nextGroups);
    }
    /** @var callable[] $nextGroups */
    if (!is_callable($groupBy)) {
        throw new InvalidArgumentException('The $groupBy argument must be a callable or an array of callables.');
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
        return map($results, static fn (array $group): array => groupBy($group, $nextGroups, $preserveKey));
    }

    return $results;
}

/**
 * @todo BEWARE! This method does unstrict comparisons. Fix that someday.
 */
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

function last(array $array): mixed
{
    return empty($array) ? null : $array[array_key_last($array)];
}

function map(array $array, callable $fn, bool $withKeyArgument = false): array
{
    if ($withKeyArgument) {
        $keys = keys($array);

        return combine($keys, array_map($fn, $array, $keys));
    }

    return array_map($fn, $array);
}

function mapSpread(array $array, callable $fn): array
{
    return map($array, static function (array $chunk, mixed $key) use ($fn) {
        $chunk[] = $key;

        return $fn(...$chunk);
    }, withKeyArgument: true);
}

function reduce(array $array, callable $reducer, mixed $initial = null, bool $withKeyArgument = false): mixed
{
    if ($withKeyArgument) {
        return array_reduce(
            keys($array),
            static fn (mixed $carry, mixed $key): mixed => $reducer($carry, $array[$key], $key),
            $initial,
        );
    }

    return array_reduce($array, $reducer, $initial);
}

function reverse(array $array, bool $preserveKey = false): array
{
    return array_reverse($array, $preserveKey);
}

function some(array $array, ?callable $predicate = null): bool
{
    foreach ($array as $item) {
        if (null === $predicate ? (bool) $item : $predicate($item)) {
            return true;
        }
    }

    return false;
}

function tail(array $array): array
{
    array_shift($array);

    return $array;
}

function unique(array $array, ?callable $key = null): array
{
    $exists = [];

    return array_filter($array, static function (mixed $item) use ($key, &$exists): bool {
        $id = $key ? $key($item) : $item;

        if (!contains($exists, $id)) {
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

function window(array $array, int $width): array
{
    $count = count($array);
    Assert::notEq($width, 0);
    Assert::lessThanEq($width, $count, 'Not enough items in array');

    $windows = [];
    for ($i = 0; ($i + $width - 1) < $count; ++$i) {
        $windows[] = array_slice($array, $i, $width);
    }

    return $windows;
}

function zip(array ...$arrays): array
{
    /** @phpstan-ignore-next-line Remove this once we upgrade to PHPStan 1.0 after 2021-11-01, cf. https://github.com/phpstan/phpstan/issues/5730 */
    return array_map(null, ...$arrays);
}
