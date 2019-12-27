<?php

declare(strict_types=1);

namespace Gammadia\Collections\Functional;

final class Util
{
    public static function assertIterable($traversable)
    {
        if (is_iterable($traversable)) {
            return $traversable;
        }

        throw new \UnexpectedValueException('Traversable expected');
    }
}
