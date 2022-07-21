<?php

declare(strict_types=1);

namespace Yiisoft\View\State;

/**
 * @internal
 */
final class LocaleState
{
    private string $locale;

    public function __construct(string $locale = 'en')
    {
        $this->locale = $locale;
    }

    /**
     * Set the specified locale code.
     *
     * @param string $locale The locale code.
     *
     * @return static
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
