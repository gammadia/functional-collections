<?php

declare(strict_types=1);

namespace Gammadia\Collections\Test\Unit\Functional;

use PHPUnit\Framework\TestCase;
use function Gammadia\Collections\Functional\chunk;
use function Gammadia\Collections\Functional\collect;
use function Gammadia\Collections\Functional\column;
use function Gammadia\Collections\Functional\concat;
use function Gammadia\Collections\Functional\contains;
use function Gammadia\Collections\Functional\diff;
use function Gammadia\Collections\Functional\diffUsing;
use function Gammadia\Collections\Functional\each;
use function Gammadia\Collections\Functional\eachSpread;
use function Gammadia\Collections\Functional\every;
use function Gammadia\Collections\Functional\groupBy;
use function Gammadia\Collections\Functional\init;
use function Gammadia\Collections\Functional\map;
use function Gammadia\Collections\Functional\tail;
use function Gammadia\Collections\Functional\values;

final class FunctionalTest extends TestCase
{
    public function testChunk(): void
    {
        $data = [1, 2, 3, 4];

        self::assertSame([[1, 2], [3, 4]], chunk($data, 2));
        self::assertSame([[1, 2, 3], [4]], chunk($data, 3));
        self::assertSame([[1, 2, 3, 4]], chunk($data, 5));
    }

    public function testCollect(): void
    {
        $data = [1, 2, 3, 4, 5];

        self::assertSame([4, 8], collect($data, function ($v): iterable {
            if (0 === $v % 2) {
                yield $v * 2;
            }
        }));

        self::assertSame([1, 2, 3, 6, 5, 10], collect($data, function ($v, $k): iterable {
            if (0 === $k % 2) {
                yield $v;
                yield $v * 2;
            }
        }));

        $data = ['Alex', 'Aude', 'Bob', 'Claire', 'Daniel'];

        self::assertSame(['A' => 'Aude', 'B' => 'Bob'], collect($data, function ($name): iterable {
            if (strlen($name) <= 4) {
                yield $name[0] => $name;
            }
        }));
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
        self::assertTrue(contains([1, 2, 3], '3'));
        self::assertFalse(contains([1, 2, 3], '3', true));
        self::assertFalse(contains([1, 2, 3], 4));
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
            diffUsing($data, [['id' => 2]], function ($a, $b): int {
                return $a['id'] <=> $b['id'];
            })
        );
    }

    public function testEach(): void
    {
        $calls = [];
        $cb = function ($value, $key) use (&$calls): bool {
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

        self::assertSame(
            values(map($data, function ($items, $key): array {
                return concat($items, [$key]);
            }, true)),
            $calls
        );
    }

    public function testEvery(): void
    {
        $count = 0;
        $cb = function ($value, $key) use (&$count): bool {
            ++$count;

            return $key < 3 && $value < 10;
        };

        self::assertTrue(every([1, 2, 3], $cb));
        self::assertFalse(every([1, 2, 3, 4, 5], $cb));
        self::assertFalse(every([0, 10, 20], $cb));

        self::assertSame(9, $count);
    }

    public function testGroupBy(): void
    {
        $a = (object) ['a' => 10, 'b' => 'x', 'c' => [1, 2, 3]];
        $b = (object) ['a' => 10, 'b' => 'x', 'c' => [1]];
        $c = (object) ['a' => 10, 'b' => 'y', 'c' => [2]];
        $d = (object) ['a' => 20, 'b' => 'y', 'c' => []];
        $e = (object) ['a' => 20, 'b' => 'y', 'c' => [1, 2]];

        $data = ['a' => $a, 'b' => $b, 'c' => $c, 'd' => $d, 'e' => $e];

        $extractA = function ($item) {
            return $item->a;
        };
        $extractB = function ($item) {
            return $item->b;
        };
        $extractC = function ($item) {
            return $item->c;
        };

        self::assertSame([10 => [$a, $b, $c], 20 => [$d, $e]], groupBy($data, $extractA));

        self::assertSame(
            [10 => ['a' => $a, 'b' => $b, 'c' => $c], 20 => ['d' => $d, 'e' => $e]],
            groupBy($data, $extractA, true)
        );

        self::assertSame(
            ['x' => [10 => [$a, $b]], 'y' => [10 => [$c], 20 => [$d, $e]]],
            groupBy($data, [$extractB, $extractA])
        );

        self::assertSame(
            [10 => ['x' => ['a' => $a, 'b' => $b], 'y' => ['c' => $c]], 20 => ['y' => ['d' => $d, 'e' => $e]]],
            groupBy($data, [$extractA, $extractB], true)
        );

        self::assertSame([1 => [$a, $b, $e], 2 => [$a, $c, $e], 3 => [$a]], groupBy($data, $extractC));
    }

    public function testInit(): void
    {
        $input = ['first', 'second', 'third'];
        $output = ['first', 'second'];

        self::assertSame($output, init($input));
        self::assertSame([], init([]));
    }

    public function testTail(): void
    {
        $input = ['first', 'second', 'third'];
        $output = ['second', 'third'];

        self::assertSame($output, tail($input));
        self::assertSame([], tail([]));
    }
}
