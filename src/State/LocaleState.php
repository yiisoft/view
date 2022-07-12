<?php

declare(strict_types=1);

namespace Yiisoft\View\State;

final class LocaleState
{
    private string $language;

    public function __construct(string $language = 'en')
    {
        $this->language = $language;
    }

    /**
     * Set the specified language code.
     *
     * @param string $language The language code.
     *
     * @return static
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Gets the language code.
     *
     * @return string The language code.
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    public function __toString(): string
    {
        return $this->getLanguage();
    }
}
