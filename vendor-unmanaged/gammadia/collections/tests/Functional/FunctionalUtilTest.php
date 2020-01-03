<?php

declare(strict_types=1);

namespace Gammadia\Collections\Test\Unit\Functional;

use Gammadia\Collections\Functional\Util;
use PHPUnit\Framework\TestCase;

final class FunctionalUtilTest extends TestCase
{
    public function testAssertIterable(): void
    {
        $array = [];
        self::assertSame($array, Util::assertIterable($array));

        $fn = static function () {
            yield 2;
        };
        $generator = $fn();
        self::assertSame($generator, Util::assertIterable($generator));

        $this->expectException(\UnexpectedValueException::class);
        Util::assertIterable(42);
    }
}
