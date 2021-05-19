<?php

declare(strict_types=1);

namespace Yiisoft\View\Event\View;

use Yiisoft\View\View;

/**
 * @internal `ViewEvent` represents events triggered by the {@see View} component.
 */
abstract class ViewEvent
{
    private View $view;

    final public function __construct(View $view)
    {
        $this->view = $view;
    }

    final public function getView(): View
    {
        return $this->view;
    }
}
