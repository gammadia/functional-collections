<?php

namespace Gammadia\Collections\Test\Unit\Functional;

use Gammadia\Collections\Functional\Util;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class FunctionalUtilTest extends TestCase
{
    public function test_assertTraversable()
    {
        $array = [];
        self::assertEquals($array, Util::assertTraversable([]));

        $fn = function () { yield 2; };
        $generator = $fn();
        self::assertEquals($generator, Util::assertTraversable($generator));

        self::expectException(UnexpectedValueException::class);
        Util::assertTraversable(42);
    }
}
