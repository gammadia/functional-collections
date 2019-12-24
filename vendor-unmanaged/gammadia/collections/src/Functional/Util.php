<?php

namespace Gammadia\Collections\Functional;

use Traversable;
use UnexpectedValueException;

final class Util
{
    public static function assertTraversable($traversable)
    {
        if (is_array($traversable) || $traversable instanceof Traversable) {
            return $traversable;
        }

        throw new UnexpectedValueException('Traversable expected');
    }
}
