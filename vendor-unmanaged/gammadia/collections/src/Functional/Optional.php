<?php

declare(strict_types=1);

namespace Gammadia\Collections\Functional;

use Webmozart\Assert\Assert;

/**
 * @template T
 */
abstract class Optional
{
    /**
     * @var T
     */
    private $value;

    /**
     * @var bool
     */
    private $none;

    /**
     * @param T $value
     */
    protected function __construct($value, bool $none)
    {
        $this->value = $value;
        $this->none = $none;
    }

    /**
     * @param T $value
     *
     * @return static
     */
    public static function of($value): self
    {
        /** @phpstan-ignore-next-line */
        return new static($value, false);
    }

    /**
     * @param T|null $value
     *
     * @return static
     */
    public static function wrap($value): self
    {
        /** @phpstan-ignore-next-line */
        return new static($value, null === $value);
    }

    /**
     * @return static
     */
    public static function none(): self
    {
        /** @phpstan-ignore-next-line */
        return new static(null, true);
    }

    public function isNone(): bool
    {
        return $this->none;
    }

    /**
     * @return T
     */
    public function value()
    {
        Assert::false($this->none, 'You cannot call value() without checking first if there is a value.');

        return $this->value;
    }

    /**
     * @return T|null
     */
    public function unwrap()
    {
        return $this->valueOr(null);
    }

    /**
     * @template U
     *
     * @param U $otherValue
     *
     * @return T|U
     */
    public function valueOr($otherValue)
    {
        return $this->none ? $otherValue : $this->value;
    }

    /**
     * @param self<T> $other
     */
    public function equals(self $other): bool
    {
        return ($this->isNone() === $other->isNone()) &&
            ($this->isNone() || $this->areValuesEqual($this->value(), $other->value()));
    }

    /**
     * @param T $a
     * @param T $b
     */
    abstract protected function areValuesEqual($a, $b): bool;
}
