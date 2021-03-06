<?php

declare(strict_types=1);

namespace Gammadia\Collections\Test\Unit\Functional;

use Gammadia\Snowflake\Snowflake;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;
use function func_get_args;
use function Gammadia\Collections\Functional\all;
use function Gammadia\Collections\Functional\chunk;
use function Gammadia\Collections\Functional\collect;
use function Gammadia\Collections\Functional\collectWithKeys;
use function Gammadia\Collections\Functional\column;
use function Gammadia\Collections\Functional\combine;
use function Gammadia\Collections\Functional\concat;
use function Gammadia\Collections\Functional\contains;
use function Gammadia\Collections\Functional\diff;
use function Gammadia\Collections\Functional\diffUsing;
use function Gammadia\Collections\Functional\each;
use function Gammadia\Collections\Functional\eachSpread;
use function Gammadia\Collections\Functional\fill;
use function Gammadia\Collections\Functional\fillWith;
use function Gammadia\Collections\Functional\filter;
use function Gammadia\Collections\Functional\first;
use function Gammadia\Collections\Functional\flatten;
use function Gammadia\Collections\Functional\flip;
use function Gammadia\Collections\Functional\groupBy;
use function Gammadia\Collections\Functional\indexBy;
use function Gammadia\Collections\Functional\init;
use function Gammadia\Collections\Functional\intersect;
use function Gammadia\Collections\Functional\keys;
use function Gammadia\Collections\Functional\last;
use function Gammadia\Collections\Functional\map;
use function Gammadia\Collections\Functional\reduce;
use function Gammadia\Collections\Functional\some;
use function Gammadia\Collections\Functional\tail;
use function Gammadia\Collections\Functional\unique;
use function Gammadia\Collections\Functional\values;
use function Gammadia\Collections\Functional\window;
use function is_string;
use function strlen;

final class FunctionalTest extends TestCase
{
    public function testAll(): void
    {
        self::assertTrue(all([true]));
        self::assertTrue(all([true, true, true]));

        // This one demonstrates a custom function
        self::assertTrue(all([42, '42', '42.1337', 42.1337, '0.0'], static fn (mixed $value): bool
            => is_numeric($value),
        ));

        // These are cases you would expect to be false
        self::assertFalse(all([true, false, true]));
        self::assertFalse(all([true, null, true]));
        self::assertFalse(all([true, 0, true]));
        self::assertFalse(all([true, 0.0, true]));
        self::assertFalse(all([true, '0', true]));
        self::assertFalse(all([true, [], true]));
        self::assertFalse(all([false]));

        // Beware, some are trickier than you think, like PHP interpreting "0.0" as true
        self::assertTrue(all([true, '0.0', true]));
    }

    public function testChunk(): void
    {
        $data = [1, 2, 3, 4];

        self::assertSame([[1, 2], [3, 4]], chunk($data, 2));
        self::assertSame([[1, 2, 3], [4]], chunk($data, 3));
        self::assertSame([[1, 2, 3, 4]], chunk($data, 5));
    }

    /**
     * @dataProvider collect
     *
     * @param mixed[] $array
     * @param mixed[] $expected
     */
    public function testCollect(array $array, callable $fn, array $expected): void
    {
        self::assertSame($expected, collect($array, $fn));
    }

    /**
     * @return iterable<mixed>
     */
    public function collect(): iterable
    {
        yield 'collect() without a condition acts like map(), but loses the keys' => [
            [1 => 1, 2 => 2, 3 => 3],
            static fn (int $v): iterable => yield $v * 2,
            [0 => 2, 1 => 4, 2 => 6],
        ];

        yield 'collect() can yield multiple times, but loses the keys' => [
            [1, 2, 3],
            static function (int $v): iterable {
                yield 0 => $v;
                yield 1 => $v * 2;
            },
            [1, 2, 2, 4, 3, 6],
        ];

        $map = [
            1 => ['A' => 1, 'B' => 10, 'C' => 100],
            2 => [2, 20, 200],
            3 => [3, 30, 300],
        ];
        yield 'collect() can use "yield from ...", but loses the keys' => [
            [1, 2, 3],
            static fn (int $v): iterable => yield from $map[$v],
            [1, 10, 100, 2, 20, 200, 3, 30, 300],
        ];

        yield 'collect() can filter data and return a partial array with auto-generated keys.' => [
            [1, 2, 3, 4, 5],
            static function (int $v): iterable {
                if (0 === $v % 2) {
                    yield $v * 2;
                }
            },
            [4, 8],
        ];

        yield 'collect() loses the generator\'s yielded keys too and returns a new array with auto-generated keys.' => [
            ['Alex', 'Aude', 'Bob', 'Charlie'],
            static function (string $name): iterable {
                if (strlen($name) <= 4) {
                    yield $name[0] => $name;
                }
            },
            [0 => 'Alex', 1 => 'Aude', 2 => 'Bob'],
        ];

        yield 'collect() does not care about the type of the generator\'s yielded key.' => [
            ['Alex', 'Aude', 'Bob', 'Charlie'],
            static function (string $name): iterable {
                if (strlen($name) <= 4) {
                    yield new stdClass() => $name;
                }
            },
            [0 => 'Alex', 1 => 'Aude', 2 => 'Bob'],
        ];
    }

    /**
     * @dataProvider collectWithKeys
     *
     * @param mixed[] $array
     * @param string|mixed[] $expected
     */
    public function testCollectWithKeys(array $array, callable $fn, string|array $expected): void
    {
        if (is_string($expected)) {
            $this->expectException(UnexpectedValueException::class);
            $this->expectExceptionMessage($expected);
        }

        self::assertSame($expected, collectWithKeys($array, $fn));
    }

    /**
     * @return iterable<mixed>
     */
    public function collectWithKeys(): iterable
    {
        yield 'collectWithKeys() without a condition acts like map() with keys as long as there are no duplicated keys.' => [
            ['Alex', 'Bob', 'Charlie'],
            static function (string $name): iterable {
                yield $name[0] => $name;
            },
            ['A' => 'Alex', 'B' => 'Bob', 'C' => 'Charlie'],
        ];

        yield 'collectWithKeys() can yield only values if and only if *one* value ends up being yielded (as there cannot be a duplicated key).' => [
            [1, 10, 100, 1000],
            static function (int $v): iterable {
                if (500 < $v) {
                    yield $v;
                }
            },
            [0 => 1000],
        ];

        yield 'collectWithKeys() can yield multiple times as long as there are no duplicated keys.' => [
            [1, 10, 100, 1000],
            static function (int $v): iterable {
                if (50 < $v) {
                    yield $v . '_1' => $v;
                    yield $v . '_2' => $v * 2;
                }
            },
            ['100_1' => 100, '100_2' => 200, '1000_1' => 1000, '1000_2' => 2000],
        ];

        $map = [
            1 => ['A' => 1, 'B' => 10, 'C' => 100],
            2 => ['D' => 2, 'E' => 20, 'F' => 200],
            3 => ['G' => 3, 'H' => 30, 'I' => 300],
        ];
        yield 'collectWithKeys() can use "yield from ..." as long as there are no duplicated keys.' => [
            [1, 2, 3],
            static function (int $v) use ($map): iterable {
                if (0 === $v % 2) {
                    yield from $map[$v];
                }
            },
            ['D' => 2, 'E' => 20, 'F' => 200],
        ];

        $dataLossErrorMessage = 'Data loss occurred because of duplicated keys. Use `collect()` if you do not care about the yielded keys, or use `scollect()` if you need to support duplicated keys (as arrays cannot).';

        yield 'collectWithKeys() will throw an exception if multiple values are yielded and keys are not set (as the generator will use zero for all keys).' => [
            [1, 2, 3, 4, 5],
            static function (int $v): iterable {
                if (0 === $v % 2) {
                    yield $v * 2;
                }
            },
            $dataLossErrorMessage,
        ];

        yield 'collectWithKeys() will throw an exception if multiples keys and values are yielded and keys have duplicates.' => [
            ['Alex', 'Aude', 'Bob', 'Charlie'],
            static function (string $name): iterable {
                if (strlen($name) <= 4) {
                    yield $name[0] => $name;
                }
            },
            $dataLossErrorMessage,
        ];

        $invalidArrayKeyType = 'The key yielded in the callable is not compatible with the type "array-key".';

        yield 'collectWithKeys() will throw an exception if an invalid array-key is yielded.' => [
            ['Alex', 'Bob', 'Charlie'],
            static fn (string $name): iterable => yield new stdClass() => $name,
            $invalidArrayKeyType,
        ];
    }

    public function testColumn(): void
    {
        $data = [
            ['id' => 10, 'name' => 'A'],
            ['id' => 20, 'name' => 'B'],
            ['id' => 30, 'name' => 'C'],
        ];

        self::assertSame(['A', 'B', 'C'], column($data, 'name'));
        self::assertSame([10 => 'A', 20 => 'B', 30 => 'C'], column($data, 'name', 'id'));

        // Columns can also be accessed by numerical indexes, which is especially useful when using tuples
        $tuples = [
            ['A', 42],
            ['B', 1337],
        ];
        self::assertSame(['A', 'B'], column($tuples, 0));
        self::assertSame([42, 1337], column($tuples, 1));
    }

    public function testConcat(): void
    {
        self::assertSame([], concat());
        self::assertSame([1], concat([1]));
        self::assertSame([1, 2], concat([1], [2]));
        self::assertSame([1, 2, 3, 4], concat([1], [2, 3], [4]));

        self::assertSame(['A' => 'A', 'B' => 'C'], concat(['A' => 'A', 'B' => 'B'], ['B' => 'C']));
    }

    public function testContains(): void
    {
        self::assertTrue(contains([1, 2, 3], 3));
        self::assertFalse(contains([1, 2, 3], '3'));
        self::assertFalse(contains([1, 2, 3], 4));
    }

    public function testCombine(): void
    {
        self::assertSame([1 => 'test1', 2 => 'test2'], combine([1, 2], ['test1', 'test2']));
        self::assertSame(['test1' => 1, 'test2' => 2], combine(['test1', 'test2'], [1, 2]));
    }

    public function testDiff(): void
    {
        self::assertSame([1, 2], diff([1, 2, 3, 4], [3], [4, 5]));
        self::assertSame([1, 2, 3 => 4], diff([1, 2, 3, 4], ['3']));
    }

    public function testDiffUsing(): void
    {
        $data = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];

        self::assertSame(
            [['id' => 1], 2 => ['id' => 3]],
            diffUsing($data, [['id' => 2]], fn (array $a, array $b): int
                => $a['id'] <=> $b['id'],
            ),
        );
    }

    public function testEach(): void
    {
        $calls = [];
        $cb = function (mixed $value, mixed $key) use (&$calls): bool {
            $calls[] = func_get_args();

            return 2 !== $value;
        };

        each([1, 'a' => 2, 3], $cb);

        self::assertSame([[1, 0], [2, 'a']], $calls);
    }

    public function testEachSpread(): void
    {
        $calls = [];
        $cb = function () use (&$calls): void {
            $calls[] = func_get_args();
        };

        $data = [
            'a' => [1, 2, 3],
            'b' => [1, 2],
        ];

        eachSpread($data, $cb);

        self::assertSame(values(map($data, fn (array $items, mixed $key): array => concat($items, [$key]), true)), $calls);
    }

    public function testFill(): void
    {
        self::assertSame([null, null, null], fill(0, 3));
        self::assertSame([42, 42, 42], fill(0, 3, 42));
        self::assertSame([1 => null, 2 => null], fill(1, 2));
    }

    public function testFillWith(): void
    {
        $callable = static fn (): int => 42;
        self::assertSame([42, 42, 42], fillWith([], 0, 3, $callable));
        self::assertSame([42, 42, 42], fillWith([13, 37, 42], 0, 3, $callable));
    }

    public function testFilter(): void
    {
        // These are cases you would expect to be false
        self::assertSame([], filter([null, 0, false, '0', []]));

        // Beware, some are trickier than you think, like PHP interpreting "0.0" as true
        self::assertSame(['0.0'], filter(['0.0']));

        // With a custom callback
        self::assertSame([2, 4], values(filter(range(1, 5), static fn (int $v): bool
            => 0 === $v % 2,
        )));
    }

    public function testFirst(): void
    {
        self::assertSame(1, first([1, 2, 3]));
        self::assertSame(1, first(['test1' => 1, 'test2' => 2]));
        self::assertSame(['test1'], first([['test1'], ['test2']]));
    }

    public function testFlatten(): void
    {
        self::assertSame([1, 2, 3], flatten([[1], [2], [3]]));
        self::assertSame([1, 2, 3], flatten([[1], [2, 3]]));
        self::assertSame([1, 2, [3]], flatten([[1], [2, [3]]]));
    }

    public function testFlip(): void
    {
        self::assertSame(['test1' => 1, 'test2' => 2], flip([1 => 'test1', 2 => 'test2']));
        self::assertSame([1 => 'test1', 2 => 'test2'], flip(['test1' => 1, 'test2' => 2]));
    }

    public function testGroupBy(): void
    {
        $a = (object) ['a' => 10, 'b' => 'x', 'c' => [1, 2, 3]];
        $b = (object) ['a' => 10, 'b' => 'x', 'c' => [1]];
        $c = (object) ['a' => 10, 'b' => 'y', 'c' => [2]];
        $d = (object) ['a' => 20, 'b' => 'y', 'c' => []];
        $e = (object) ['a' => 20, 'b' => 'y', 'c' => [1, 2]];

        $data = ['a' => $a, 'b' => $b, 'c' => $c, 'd' => $d, 'e' => $e];

        $extractA = fn (mixed $item) => $item->a;
        $extractB = fn (mixed $item) => $item->b;
        $extractC = fn (mixed $item) => $item->c;

        self::assertSame([10 => [$a, $b, $c], 20 => [$d, $e]], groupBy($data, $extractA));

        self::assertSame(
            [10 => ['a' => $a, 'b' => $b, 'c' => $c], 20 => ['d' => $d, 'e' => $e]],
            groupBy($data, $extractA, preserveKey: true),
        );

        self::assertSame(
            ['x' => [10 => [$a, $b]], 'y' => [10 => [$c], 20 => [$d, $e]]],
            groupBy($data, [$extractB, $extractA]),
        );

        self::assertSame(
            [10 => ['x' => ['a' => $a, 'b' => $b], 'y' => ['c' => $c]], 20 => ['y' => ['d' => $d, 'e' => $e]]],
            groupBy($data, [$extractA, $extractB], preserveKey: true),
        );

        self::assertSame([1 => [$a, $b, $e], 2 => [$a, $c, $e], 3 => [$a]], groupBy($data, $extractC));
    }

    /**
     * @dataProvider intersect
     *
     * @param mixed[] $array
     * @param mixed[] $others
     * @param mixed[] $expected
     */
    public function testIntersect(array $array, array $others, array $expected): void
    {
        self::assertEquals($expected, intersect($array, ...$others));
    }

    /**
     * @return iterable<mixed>
     */
    public function intersect(): iterable
    {
        yield 'Strict types works' => [
            [Snowflake::cast(42), Snowflake::cast(13)],
            [[Snowflake::cast(42)]],
            [Snowflake::cast(42)],
        ];
        yield 'Unstrict types work too : it should return the value of the first array (supposedly)...' => [
            [42, Snowflake::cast(13)],
            [[Snowflake::cast(42)]],
            [42],
        ];
    }

    public function testIntersectUsing(): void
    {
        self::markTestIncomplete('@todo Implement this');
    }

    public function testIntersectKeys(): void
    {
        self::markTestIncomplete('@todo Implement this');
    }

    public function testIndexBy(): void
    {
        self::assertSame([1 => 2, 2 => 4], indexBy([2, 4], static fn (int $v): int
            => $v / 2,
        ));
    }

    public function testInit(): void
    {
        $input = ['first', 'second', 'third'];
        $output = ['first', 'second'];

        self::assertSame($output, init($input));
        self::assertSame([], init([]));
    }

    public function testKeys(): void
    {
        self::assertSame([0, 1, 2], keys([1, 2, 3]));
        self::assertSame([1, 2, 3], keys([1 => 1, 2 => 2, 3 => 3]));
        self::assertSame([0, 1, 'key', 2], keys([1, 2, 'key' => 'value', 3]));

        // PHP will convert numeric-string to int internally
        self::assertSame([1, 2, 3], keys(['1' => 1, '2' => 2, '3' => 3]));
    }

    public function testLast(): void
    {
        self::assertSame(3, last([1, 2, 3]));
        self::assertSame(2, last(['test1' => 1, 'test2' => 2]));
        self::assertSame(['test2'], last([['test1'], ['test2']]));
    }

    public function testMap(): void
    {
        self::assertSame([2, 4], map([1, 2], static fn (int $v): int
            => $v * 2,
        ));
        self::assertSame([false, true], map([0, 1], static fn (int $v): bool
            => (bool) $v,
        ));
    }

    public function testMapSpread(): void
    {
        self::markTestIncomplete('@todo Implement this');
    }

    public function testReduce(): void
    {
        self::assertNull(reduce([], static function (): void {}));
        self::assertSame(6, reduce([1, 2, 3], static fn (int $carry, int $value): int => $carry + $value, initial: 0));
        self::assertSame('123', reduce([1, 2, 3], static fn (string $carry, int $value): string => $carry . $value, initial: ''));
    }

    public function testReverse(): void
    {
        self::markTestIncomplete('@todo Implement this');
    }

    public function testSome(): void
    {
        // These are cases you would expect to be false
        self::assertFalse(some([null, 0, false, '0', []]));

        // Beware, some are trickier than you think, like PHP interpreting "0.0" as true
        self::assertTrue(some(['0.0']));

        // With a custom callback
        $isEven = static fn (int $v): bool => 0 === $v % 2;
        self::assertTrue(some(range(1, 5), $isEven));
        self::assertFalse(some([1, 3, 5], $isEven));
    }

    public function testTail(): void
    {
        $input = ['first', 'second', 'third'];
        $output = ['second', 'third'];

        self::assertSame($output, tail($input));
        self::assertSame([], tail([]));
    }

    public function testUnique(): void
    {
        self::assertSame([1], unique([1, 1, 1]));

        // Strictness test
        self::assertSame([1, '1'], unique([1, '1', 1]));

        // Keys are preserved
        self::assertSame([0 => 1, 2 => 3, 4 => 2], unique([1, 1, 3, 1, 2, 1]));

        // Custom callable
        $name = static fn (array $data): string => $data['name'];
        self::assertSame(
            [0 => ['id' => 1, 'name' => 'John'], 2 => ['id' => 3, 'name' => 'Paul']],
            unique(
                [
                    ['id' => 1, 'name' => 'John'],
                    ['id' => 2, 'name' => 'John'],
                    ['id' => 3, 'name' => 'Paul'],
                    ['id' => 4, 'name' => 'John'],
                    ['id' => 5, 'name' => 'Paul'],
                ],
                $name,
            ),
        );
    }

    public function testValues(): void
    {
        self::assertSame([1, 2, 3], values([1, 2, 3]));
        self::assertSame([1, 2, 3], values([1 => 1, 2 => 2, 3 => 3]));
        self::assertSame([1, 2, 'value', 3], values([1, 2, 'key' => 'value', 3]));
    }

    public function testWindow(): void
    {
        $input = ['first', 'second', 'third', 'fourth', 'fifth'];

        $output = [['first'], ['second'], ['third'], ['fourth'], ['fifth']];
        self::assertSame($output, window($input, 1));

        $output = [['first', 'second'], ['second', 'third'], ['third', 'fourth'], ['fourth', 'fifth']];
        self::assertSame($output, window($input, 2));

        $output = [['first', 'second', 'third'], ['second', 'third', 'fourth'], ['third', 'fourth', 'fifth']];
        self::assertSame($output, window($input, 3));

        // This would make no sense whatsoever
        $this->expectException(InvalidArgumentException::class);
        window($input, 0);
    }

    public function testWindowOutsideOfRange(): void
    {
        $input = ['first', 'second'];

        $this->expectException(InvalidArgumentException::class);
        window($input, 4);
    }

    public function testZip(): void
    {
        self::markTestIncomplete('@todo Implement this');
    }
}
