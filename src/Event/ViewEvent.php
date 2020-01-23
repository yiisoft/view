<?php
declare(strict_types=1);

namespace Yiisoft\View\Event;

/**
 * ViewEvent represents events triggered by the {@see View} component.
 */
abstract class ViewEvent
{
    /**
     * @var string the view file being rendered.
     */
    private string $file;
    /**
     * @var array the parameter array passed to the {@see View::render()} method.
     */
    private array $parameters;

    public function __construct(string $file, array $parameters = [])
    {
        $this->file = $file;
        $this->parameters = $parameters;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}
