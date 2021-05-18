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

    /**
     * @var array The parameters array passed to the event.
     */
    private array $parameters;

    final public function __construct(View $view, array $parameters)
    {
        $this->view = $view;
        $this->parameters = $parameters;
    }

    final public function getView(): View
    {
        return $this->view;
    }

    final public function getParameters(): array
    {
        return $this->parameters;
    }
}
