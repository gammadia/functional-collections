<?php

declare(strict_types=1);

namespace Gammadia\Collections\Functional;

use Webmozart\Assert\Assert;

/**
 * @template T
 * @extends Optional<T>
 */
abstract class Required extends Optional
{
    /**
     * @param T $value
     */
    final protected function __construct(mixed $value, bool $none)
    {
        Assert::false($none, 'Please use Optional instead.');

        parent::__construct($value, $none);
    }
}
