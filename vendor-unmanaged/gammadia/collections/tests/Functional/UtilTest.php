<?php

declare(strict_types=1);

namespace Gammadia\Collections\Test\Unit\Functional;

use ArrayIterator;
use Gammadia\Collections\Functional\Util;
use PHPUnit\Framework\TestCase;
use Traversable;
use UnexpectedValueException;

final class UtilTest extends TestCase
{
    /**
     * @param iterable<mixed> $iterable
     *
     * @dataProvider iterable
     */
    public function testAssertIterable(iterable $iterable): void
    {
        self::assertSame($iterable, Util::assertIterable($iterable));
    }

    /**
     * @return iterable<mixed>
     */
    public function iterable(): iterable
    {
        yield [[]];

        $fn = static function (): iterable {
            yield 2;
        };
        $generator = $fn();
        yield [$generator];
    }

    /**
     * @param mixed $iterable
     *
     * @dataProvider invalidIterable
     */
    public function testInvalidIterable($iterable): void
    {
        $this->expectException(UnexpectedValueException::class);
        Util::assertIterable($iterable);
    }

    /**
     * @return iterable<mixed>
     */
    public function invalidIterable(): iterable
    {
        yield [42];
    }

    /**
     * @param \Traversable<mixed> $traversable
     *
     * @dataProvider traversable
     */
    public function testAssertTraversable(Traversable $traversable): void
    {
        self::assertSame($traversable, Util::assertTraversable($traversable));

        $fn = static function (): iterable {
            yield 2;
        };
        $generator = $fn();
        self::assertSame($generator, Util::assertTraversable($generator));
    }

    /**
     * @return iterable<mixed>
     */
    public function traversable(): iterable
    {
        yield [new ArrayIterator([])];

        $fn = static function (): iterable {
            yield 2;
        };
        $generator = $fn();
        yield [$generator];
    }

    /**
     * @param mixed $traversable
     *
     * @dataProvider invalidTraversable
     */
    public function testInvalidTraversable($traversable): void
    {
        $this->expectException(UnexpectedValueException::class);
        Util::assertTraversable($traversable);
    }

    /**
     * @return iterable<mixed>
     */
    public function invalidTraversable(): iterable
    {
        yield [42];
        yield [[42]];
    }
}
