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
     * @param T $value
     */
    protected function __construct(
        private mixed $value,
        private bool $none
    ) {}

    /**
     * @param T $value
     *
     * @return static
     */
    public static function of(mixed $value): self
    {
        /** @phpstan-ignore-next-line */
        return new static($value, false);
    }

    /**
     * @param T|null $value
     *
     * @return static
     */
    public static function wrap(mixed $value): self
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
    public function value(): mixed
    {
        Assert::false($this->none, 'You cannot call value() without checking first if there is a value.');

        return $this->value;
    }

    /**
     * @return T|null
     */
    public function unwrap(): mixed
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
    public function valueOr(mixed $otherValue): mixed
    {
        return $this->none ? $otherValue : $this->value;
    }

    public function ifPresent(callable $fn): void
    {
        if ($this->isNone()) {
            return;
        }

        $fn($this->value());
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
    abstract protected function areValuesEqual(mixed $a, mixed $b): bool;
}
