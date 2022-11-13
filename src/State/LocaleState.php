<?php

declare(strict_types=1);

namespace Yiisoft\View\State;

use Stringable;

/**
 * @internal
 */
final class LocaleState implements Stringable
{
    public function __construct(
        private string $locale = 'en'
    ) {
    }

    /**
     * Set the specified locale code.
     *
     * @param string $locale The locale code.
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Gets the locale code.
     *
     * @return string The locale code.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    public function __toString(): string
    {
        return $this->getLocale();
    }
}
