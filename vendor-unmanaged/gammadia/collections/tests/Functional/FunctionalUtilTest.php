<?php

declare(strict_types=1);

namespace Gammadia\Collections\Test\Unit\Functional;

use Gammadia\Collections\Functional\Util;
use PHPUnit\Framework\TestCase;

final class FunctionalUtilTest extends TestCase
{
    public function test_assertIterable(): void
    {
        $array = [];
        self::assertEquals($array, Util::assertIterable([]));

        $fn = static function () { yield 2; };
        $generator = $fn();
        self::assertEquals($generator, Util::assertIterable($generator));

        $this->expectException(\UnexpectedValueException::class);
        Util::assertIterable(42);
    }
}
