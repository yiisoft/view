<?php

declare(strict_types=1);

namespace Yiisoft\View;

/**
 * `ViewContextInterface` is the interface that should be implemented by classes who want to support relative view
 * names.
 *
 * The method {@see ViewContextInterface::getViewPath()} should be implemented to return the view path
 * that may be prefixed to a relative view name.
 */
interface ViewContextInterface
{
    /**
     * Returns the view path that may be prefixed to a relative view name.
     *
     * @return string The view path that may be prefixed to a relative view name.
     */
    public function getViewPath(): string;
}
