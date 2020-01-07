<?php

declare(strict_types=1);

namespace Gammadia\Collections\Functional;

use Traversable;
use UnexpectedValueException;

final class Util
{
    /**
     * An iterable can be either a \Traversable or an array (which cannot be passed to iterator_to_array() for example)
     *
     * @param mixed $iterable
     *
     * @return iterable<mixed>
     */
    public static function assertIterable($iterable): iterable
    {
        if (is_iterable($iterable)) {
            return $iterable;
        }

        throw new UnexpectedValueException('Iterable expected');
    }

    /**
     * @param mixed $traversable
     *
     * @return \Traversable<mixed>
     */
    public static function assertTraversable($traversable): Traversable
    {
        if ($traversable instanceof Traversable) {
            return $traversable;
        }

        throw new UnexpectedValueException('Traversable expected');
    }
}
