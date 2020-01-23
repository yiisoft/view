<?php
declare(strict_types=1);

namespace Yiisoft\View;

/**
 * ViewContextInterface is the interface that should implemented by classes who want to support relative view names.
 *
 * The method [[getViewPath()]] should be implemented to return the view path that may be prefixed to a relative view name.
 */
interface ViewContextInterface
{
    /**
     * @return string the view path that may be prefixed to a relative view name.
     */
    public function getViewPath(): string;
}
