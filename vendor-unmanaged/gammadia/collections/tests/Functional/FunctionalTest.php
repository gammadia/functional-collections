<?php

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
    public function test_chunk(): void
    {
        $data = [1, 2, 3, 4];

        self::assertEquals([[1, 2], [3, 4]], chunk($data, 2));
        self::assertEquals([[1, 2, 3], [4]], chunk($data, 3));
        self::assertEquals([[1, 2, 3, 4]], chunk($data, 5));
    }

    public function test_collect(): void
    {
        $data = [1, 2, 3, 4, 5];

        self::assertEquals([4, 8], collect($data, function ($v) {
            if ($v % 2 === 0) {
                yield $v * 2;
            }
        }));

        self::assertEquals([1, 2, 3, 6, 5, 10], collect($data, function ($v, $k) {
            if ($k % 2 === 0) {
                yield $v;
                yield $v * 2;
            }
        }));

        $data = ['Alex', 'Aude', 'Bob', 'Claire', 'Daniel'];

        self::assertEquals(['A' => 'Aude', 'B' => 'Bob'], collect($data, function ($name) {
            if (strlen($name) <= 4) {
                yield $name[0] => $name;
            }
        }));
    }

    public function test_column(): void
    {
        $data = [
            ['id' => 10, 'name' => 'A'],
            ['id' => 20, 'name' => 'B'],
            ['id' => 30, 'name' => 'C'],
        ];

        self::assertEquals(['A', 'B', 'C'], column($data, 'name'));
        self::assertEquals([10 => 'A', 20 => 'B', 30 => 'C'], column($data, 'name', 'id'));
    }

    public function test_concat(): void
    {
        self::assertEquals([], concat());
        self::assertEquals([1], concat([1]));
        self::assertEquals([1, 2], concat([1], [2]));
        self::assertEquals([1, 2, 3, 4], concat([1], [2, 3], [4]));

        self::assertEquals(['A' => 'A', 'B' => 'C'], concat(['A' => 'A', 'B' => 'B'], ['B' => 'C']));
    }

    public function test_contains(): void
    {
        self::assertEquals(true, contains([1, 2, 3], 3));
        self::assertEquals(true, contains([1, 2, 3], '3'));
        self::assertEquals(false, contains([1, 2, 3], '3', true));
        self::assertEquals(false, contains([1, 2, 3], 4));
    }

    public function test_diff(): void
    {
        self::assertEquals([1, 2], diff([1, 2, 3, 4], [3], [4, 5]));
        self::assertEquals([1, 2, 3 => 4], diff([1, 2, 3, 4], ['3']));
    }

    public function test_diffUsing(): void
    {
        $data = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ];

        self::assertEquals(
            [['id' => 1], 2 => ['id' => 3]],
            diffUsing($data, [['id' => 2]], function ($a, $b) { return $a['id'] <=> $b['id']; })
        );
    }

    public function test_each(): void
    {
        $calls = [];
        $cb = function ($value, $key) use (&$calls) {
            $calls[] = func_get_args();
            return $value !== 2;
        };

        each([1, 'a' => 2, 3], $cb);

        self::assertEquals([[1, 0], [2, 'a']], $calls);
    }

    public function test_eachSpread(): void
    {
        $calls = [];
        $cb = function () use (&$calls) {
            $calls[] = func_get_args();
        };

        $data = [
            'a' => [1, 2, 3],
            'b' => [1, 2]
        ];

        eachSpread($data, $cb);

        self::assertEquals(
            values(map($data, function($items, $key) { return concat($items, [$key]); }, true)),
            $calls
        );
    }

    public function test_every(): void
    {
        $count = 0;
        $cb = function ($value, $key) use (&$count) {
            $count++;
            return $key < 3 && $value < 10;
        };

        self::assertEquals(true, every([1, 2, 3], $cb));
        self::assertEquals(false, every([1, 2, 3, 4, 5], $cb));
        self::assertEquals(false, every([0, 10, 20], $cb));

        self::assertEquals(9, $count);
    }

    public function test_groupBy(): void
    {
        $a = (object)['a' => 10, 'b' => 'x', 'c' => [1, 2, 3]];
        $b = (object)['a' => 10, 'b' => 'x', 'c' => [1]];
        $c = (object)['a' => 10, 'b' => 'y', 'c' => [2]];
        $d = (object)['a' => 20, 'b' => 'y', 'c' => []];
        $e = (object)['a' => 20, 'b' => 'y', 'c' => [1, 2]];

        $data = ['a' => $a, 'b' => $b, 'c' => $c, 'd' => $d, 'e' => $e];

        $extractA = function ($item) { return $item->a; };
        $extractB = function ($item) { return $item->b; };
        $extractC = function ($item) { return $item->c; };

        self::assertEquals([10 => [$a, $b, $c], 20 => [$d, $e]], groupBy($data, $extractA));

        self::assertEquals(
            [10 => ['a' => $a, 'b' => $b, 'c' => $c], 20 => ['d' => $d, 'e' => $e]],
            groupBy($data, $extractA, true)
        );

        self::assertEquals(
            ['x' => [10 => [$a, $b]], 'y' => [10 => [$c], 20 => [$d, $e]]],
            groupBy($data, [$extractB, $extractA])
        );

        self::assertEquals(
            [10 => ['x' => ['a' => $a, 'b' => $b], 'y' => ['c' => $c]], 20 => ['y' => ['d' => $d, 'e' => $e]]],
            groupBy($data, [$extractA, $extractB], true)
        );

        self::assertEquals([1 => [$a, $b, $e], 2 => [$a, $c, $e], 3 => [$a]], groupBy($data, $extractC));
    }

    public function test_init(): void
    {
        $input = ['first', 'second', 'third'];
        $output = ['first', 'second'];

        self::assertEquals($output, init($input));
        self::assertEquals([], init([]));
    }

    public function test_tail(): void
    {
        $input = ['first', 'second', 'third'];
        $output = ['second', 'third'];

        self::assertEquals($output, tail($input));
        self::assertEquals([], tail([]));
    }
}
