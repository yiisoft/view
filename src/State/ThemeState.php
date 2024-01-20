<?php

declare(strict_types=1);

namespace Yiisoft\View\State;

use Yiisoft\View\Theme;

/**
 * @internal
 */
final class ThemeState
{
    public function __construct(
        private ?Theme $theme = null
    ) {
    }

    /**
     * Set the specified view theme.
     *
     * @param Theme|null $theme $theme The theme instance or `null` for reset theme.
     */
    public function setTheme(?Theme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Gets the theme instance, or `null` if no theme has been set.
     *
     * @return Theme|null The theme instance, or `null` if no theme has been set.
     */
    public function getTheme(): ?Theme
    {
        return $this->theme;
    }
}
