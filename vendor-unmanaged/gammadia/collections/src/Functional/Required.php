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
    final protected function __construct($value, bool $none)
    {
        Assert::false($none, 'Please use Optional instead.');

        parent::__construct($value, $none);
    }
}
