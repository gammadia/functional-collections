<?php

declare(strict_types=1);

namespace Gammadia\Collections\Functional;

use Generator;

/*
 * See
 *  - [https://github.com/laravel/framework/blob/master/src/Illuminate/Support/Collection.php]
 *  - [https://lodash.com/docs/]
 * for inspiration
*/

/**
 * @template K
 * @template T
 *
 * @param iterable<K, T> $stream
 * @param (callable(T, K): bool)|null $predicate
 */
function sall(iterable $stream, ?callable $predicate = null): bool
{
    foreach ($stream as $key => $value) {
        $result = null === $predicate ? (bool) $value : $predicate($value, $key);
        if (!$result) {
            return false;
        }
    }

    return true;
}

/**
 * @template K
 * @template T
 * @template L
 * @template U
 *
 * @param iterable<K, T> $stream
 * @param callable(T, K): Generator<L, U> $fn
 *
 * @return Generator<L, U>
 */
function scollect(iterable $stream, callable $fn): Generator
{
    foreach ($stream as $key => $value) {
        yield from $fn($value, $key);
    }
}

/**
 * @template K
 * @template T
 *
 * @param iterable<K, T> ...$streams
 *
 * @return Generator<K, T>
 */
function sconcat(iterable ...$streams): Generator
{
    return sflatten($streams);
}

/**
 * @template T
 *
 * @param iterable<T> $stream
 * @param T $item
 */
function scontains(iterable $stream, $item, bool $strict = false): bool
{
    foreach ($stream as $value) {
        /** @noinspection TypeUnsafeComparisonInspection */
        if ($strict ? $item === $value : $item == $value) {
            return true;
        }
    }

    return false;
}

/**
 * @template K
 * @template T
 *
 * @param iterable<K, T> $stream
 * @param (callable(T, K): bool)|null $predicate
 *
 * @return Generator<K, T>
 */
function sfilter(iterable $stream, ?callable $predicate = null): Generator
{
    foreach ($stream as $key => $value) {
        if (null !== $predicate ? $predicate($value, $key) : $value) {
            yield $key => $value;
        }
    }
}

/**
 * @template T
 *
 * @param iterable<T> $stream
 *
 * @return T|null
 */
function sfirst(iterable $stream)
{
    /** @noinspection LoopWhichDoesNotLoopInspection */
    foreach ($stream as $value) {
        return $value;
    }

    return null;
}

/**
 * @template K
 * @template T
 *
 * @param iterable<iterable<T>> $streams
 *
 * @return Generator<T>
 */
function sflatten(iterable $streams): Generator
{
    foreach ($streams as $stream) {
        yield from $stream;
    }
}

/**
 * @template K
 * @template T
 *
 * @param iterable<K, T> $stream
 *
 * @return Generator<K>
 */
function skeys(iterable $stream): Generator
{
    foreach ($stream as $key => $value) {
        yield $key;
    }
}

/**
 * @template K
 * @template T
 *
 * @param iterable<K, T> $stream
 *
 * @return T|null
 */
function slast(iterable $stream)
{
    /** @var T|null $last */
    $last = null;
    foreach ($stream as $value) {
        $last = $value;
    }

    return $last;
}

/**
 * @template K
 * @template T
 * @template U
 *
 * @param iterable<K, T> $stream
 * @param callable(T, K): U $fn
 *
 * @return Generator<K, U>
 */
function smap(iterable $stream, callable $fn): Generator
{
    foreach ($stream as $key => $value) {
        yield $key => $fn($value, $key);
    }
}

/**
 * @template K
 * @template T
 * @template U
 *
 * @param iterable<K, T> $stream
 * @param callable(U, T, K): U $reducer
 * @param U $carry
 *
 * @return U
 */
function sreduce(iterable $stream, callable $reducer, $carry)
{
    foreach ($stream as $key => $value) {
        $carry = $reducer($carry, $value, $key);
    }

    return $carry;
}

/**
 * @template K
 * @template T
 *
 * @param iterable<K, T> $stream
 * @param callable(T, K): bool|null $predicate
 */
function ssome(iterable $stream, ?callable $predicate = null): bool
{
    foreach ($stream as $key => $value) {
        if (null === $predicate ? $value : $predicate($value, $key)) {
            return true;
        }
    }

    return false;
}

/**
 * @template K
 * @template T
 *
 * @param iterable<K, T> $stream
 *
 * @return Generator<K, T>
 */
function soffset(iterable $stream, int $n): Generator
{
    foreach ($stream as $key => $value) {
        if ($n > 0) {
            --$n;
        } else {
            yield $key => $value;
        }
    }
}

/**
 * @template K
 * @template T
 *
 * @param iterable<K, T> $stream
 * @param (callable(T, K): mixed)|null $key
 *
 * @return Generator<K, T>
 */
function sunique(iterable $stream, ?callable $key = null, bool $strict = false): Generator
{
    $exists = [];

    foreach ($stream as $skey => $value) {
        $k = null !== $key ? $key($value, $skey) : $value;
        if (!in_array($k, $exists, $strict)) {
            $exists[] = $k;
            yield $skey => $value;
        }
    }
}

/**
 * @template K
 * @template T
 *
 * @param iterable<K, T> $stream
 *
 * @return Generator<int, T>
 */
function svalues(iterable $stream): Generator
{
    foreach ($stream as $value) {
        yield $value;
    }
}
