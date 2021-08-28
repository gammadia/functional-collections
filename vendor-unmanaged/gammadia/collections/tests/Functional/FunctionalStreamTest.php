<?php

declare(strict_types=1);

namespace Gammadia\Collections\Test\Unit\Functional;

use Generator;
use PHPUnit\Framework\TestCase;
use function Gammadia\Collections\Functional\sall;
use function Gammadia\Collections\Functional\scollect;
use function Gammadia\Collections\Functional\sconcat;
use function Gammadia\Collections\Functional\scontains;
use function Gammadia\Collections\Functional\sfilter;
use function Gammadia\Collections\Functional\sfirst;
use function Gammadia\Collections\Functional\sflatten;
use function Gammadia\Collections\Functional\skeys;
use function Gammadia\Collections\Functional\slast;
use function Gammadia\Collections\Functional\smap;
use function Gammadia\Collections\Functional\soffset;
use function Gammadia\Collections\Functional\sreduce;
use function Gammadia\Collections\Functional\ssome;
use function Gammadia\Collections\Functional\sunique;
use function Gammadia\Collections\Functional\svalues;

final class FunctionalStreamTest extends TestCase
{
    public function testAll(): void
    {
        self::assertTrue(sall($this->generator([true])));
        self::assertTrue(sall($this->generator([true, true, true])));

        // This one demonstrates a custom function
        self::assertTrue(sall($this->generator([42, '42', '42.1337', 42.1337, '0.0']), static fn (mixed $value): bool
            => is_numeric($value),
        ));

        // These are cases you would expect to be false
        self::assertFalse(sall($this->generator([true, false, true])));
        self::assertFalse(sall($this->generator([true, null, true])));
        self::assertFalse(sall($this->generator([true, 0, true])));
        self::assertFalse(sall($this->generator([true, 0.0, true])));
        self::assertFalse(sall($this->generator([true, '0', true])));
        self::assertFalse(sall($this->generator([true, [], true])));
        self::assertFalse(sall($this->generator([false])));

        // Beware, some are trickier than you think, like PHP interpreting "0.0" as true
        self::assertTrue(sall($this->generator([true, '0.0', true])));
    }

    public function testCollect(): void
    {
        self::assertSame(
            [4, 8],
            $this->values(scollect($this->generator([1, 2, 3, 4, 5]), function (int $v): Generator {
                if (0 === $v % 2) {
                    yield $v * 2;
                }
            })),
        );
        self::assertSame(
            [1, 2, 3, 6, 5, 10],
            $this->values(scollect($this->generator([1, 2, 3, 4, 5]), function (int $v, int $k): Generator {
                if (0 === $k % 2) {
                    yield $v;
                    yield $v * 2;
                }
            })),
        );

        self::assertSame(
            ['A' => 'Aude', 'B' => 'Bob'],
            $this->array(
                scollect(
                    $this->generator(['Alex', 'Aude', 'Bob', 'Claire', 'Daniel']),
                    function (string $name): Generator {
                        if (strlen($name) <= 4) {
                            yield $name[0] => $name;
                        }
                    },
                ),
            ),
        );
    }

    public function testConcat(): void
    {
        /** @phpstan-ignore-next-line it cannot resolve the template types K and T because it's empty */
        self::assertSame([], $this->array(sconcat()));
        self::assertSame([1], $this->array(sconcat($this->generator([1]))));
        self::assertSame([1, 2], $this->values(sconcat($this->generator([1]), $this->generator([2]))));
        self::assertSame(
            [1, 2, 3, 4],
            $this->values(sconcat($this->generator([1]), $this->generator([2, 3]), $this->generator([4]))),
        );

        self::assertSame(
            ['A' => 'A', 'B' => 'C'],
            $this->array(sconcat($this->generator(['A' => 'A', 'B' => 'B']), $this->generator(['B' => 'C']))),
        );
    }

    public function testContains(): void
    {
        // Here we cannot use the same Generator again as it cannot be rewind (because it's not entirely run)
        self::assertTrue(scontains($this->generator([1, 2, 3]), 3));
        self::assertTrue(scontains($this->generator([1, 2, 3]), '3'));
        self::assertFalse(scontains($this->generator([1, 2, 3]), '3', true));
        self::assertFalse(scontains($this->generator([1, 2, 3]), 4));
    }

    public function testFilter(): void
    {
        // These are cases you would expect to be false
        self::assertSame([], $this->array(sfilter($this->generator([null, 0, false, '0', []]))));

        // Beware, some are trickier than you think, like PHP interpreting "0.0" as true
        self::assertSame(['0.0'], $this->array(sfilter($this->generator(['0.0']))));

        // With a custom callback
        self::assertSame(
            [2, 4],
            $this->values(sfilter($this->generator(range(1, 5)), static fn (int $v): bool
                => 0 === $v % 2,
            )),
        );
    }

    public function testFirst(): void
    {
        self::assertSame(1, sfirst($this->generator([1, 2, 3])));
        self::assertSame(1, sfirst($this->generator(['test1' => 1, 'test2' => 2])));
        self::assertSame(['test1'], sfirst($this->generator([['test1'], ['test2']])));
    }

    public function testFlatten(): void
    {
        self::assertSame([1, 2, 3], $this->values(sflatten($this->generator([[1], [2], [3]]))));
        self::assertSame([1, 2, 3], $this->values(sflatten($this->generator([[1], [2, 3]]))));
        self::assertSame([1, 2, [3]], $this->values(sflatten($this->generator([[1], [2, [3]]]))));
    }

    public function testKeys(): void
    {
        self::assertSame([0, 1, 2], $this->values(skeys($this->generator([1, 2, 3]))));
        self::assertSame([1, 2, 3], $this->values(skeys($this->generator([1 => 1, 2 => 2, 3 => 3]))));
        self::assertSame([0, 1, 'key', 2], $this->values(skeys($this->generator([1, 2, 'key' => 'value', 3]))));

        // PHP will convert numeric-string to int internally
        self::assertSame([1, 2, 3], $this->values(skeys($this->generator(['1' => 1, '2' => 2, '3' => 3]))));
    }

    public function testLast(): void
    {
        self::assertSame(3, slast($this->generator([1, 2, 3])));
        self::assertSame(2, slast($this->generator(['test1' => 1, 'test2' => 2])));
        self::assertSame(['test2'], slast($this->generator([['test1'], ['test2']])));
    }

    public function testMap(): void
    {
        self::assertSame([2, 4], $this->values(smap($this->generator([1, 2]), static fn (int $v): int
            => $v * 2,
        )));
        self::assertSame([0, 1], $this->values(smap($this->generator([2, 4]), static fn (int $v, int $k): int
            => $k,
        )));
        self::assertSame([false, true], $this->values(smap($this->generator([0, 1]), static fn (int $v): bool
            => (bool) $v,
        )));
    }

    public function testOffset(): void
    {
        self::assertSame([], $this->values(soffset($this->generator($this->emptyArray()), 2)));
        self::assertSame([3, 4, 5], $this->values(soffset($this->generator(range(1, 5)), 2)));
    }

    public function testReduce(): void
    {
        self::assertNull(sreduce($this->generator($this->emptyArray()), static function (): void {}, null));
        self::assertSame(6, sreduce($this->generator([1, 2, 3]), static fn (int $carry, int $value): int => $carry + $value, initial: 0));
        self::assertSame('123', sreduce($this->generator([1, 2, 3]), static fn (string $carry, int $value): string => $carry . $value, initial: ''));
    }

    public function testSome(): void
    {
        // These are cases you would expect to be false
        self::assertFalse(ssome($this->generator([null, 0, false, '0', []])));

        // Beware, some are trickier than you think, like PHP interpreting "0.0" as true
        self::assertTrue(ssome($this->generator(['0.0'])));

        // With a custom callback
        $isEven = static fn (int $v): bool => 0 === $v % 2;
        self::assertTrue(ssome($this->generator(range(1, 5)), $isEven));
        self::assertFalse(ssome($this->generator([1, 3, 5]), $isEven));
    }

    public function testUnique(): void
    {
        self::assertSame([1], $this->array(sunique($this->generator([1, 1, 1]))));

        // Strictness test
        self::assertSame([1], $this->array(sunique($this->generator([1, '1', 1]))));
        self::assertSame([1, '1'], $this->array(sunique($this->generator([1, '1', 1]), null, true)));

        // Keys are preserved
        self::assertSame([0 => 1, 2 => 3, 4 => 2], $this->array(sunique($this->generator([1, 1, 3, 1, 2, 1]))));

        // Custom callable
        $name = static fn (array $data): string => $data['name'];
        self::assertSame(
            [0 => ['id' => 1, 'name' => 'John'], 2 => ['id' => 3, 'name' => 'Paul']],
            $this->array(sunique(
                $this->generator([
                    ['id' => 1, 'name' => 'John'],
                    ['id' => 2, 'name' => 'John'],
                    ['id' => 3, 'name' => 'Paul'],
                    ['id' => 4, 'name' => 'John'],
                    ['id' => 5, 'name' => 'Paul'],
                ]),
                $name,
            )),
        );
    }

    public function testValues(): void
    {
        self::assertSame([1, 2, 3], $this->array(svalues($this->generator([1, 2, 3]))));
        self::assertSame([1, 2, 3], $this->array(svalues($this->generator([1 => 1, 2 => 2, 3 => 3]))));
        self::assertSame([1, 2, 'value', 3], $this->array(svalues($this->generator([1, 2, 'key' => 'value', 3]))));
    }

    /**
     * @return never[]
     * @noinspection PhpUndefinedClassInspection
     */
    private function emptyArray(): array
    {
        return [];
    }

    /**
     * @template K
     * @template T
     *
     * @param Generator<K, T> $generator
     *
     * @return array<K, T>
     */
    private function array(Generator $generator): array
    {
        return iterator_to_array($generator);
    }

    /**
     * @template K
     * @template T
     *
     * @param Generator<K, T> $generator
     *
     * @return array<int, T>
     */
    private function values(Generator $generator): array
    {
        return iterator_to_array($generator, false);
    }

    /**
     * @template K
     * @template T
     *
     * @param array<K, T> $data
     *
     * @return Generator<K, T>
     */
    private function generator(array $data): Generator
    {
        return (static function () use ($data): Generator {
            foreach ($data as $key => $value) {
                yield $key => $value;
            }
        })();
    }
}
