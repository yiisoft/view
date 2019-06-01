<?php
namespace Yiisoft\View\View\Event;

/**
 * AfterRender event is triggered by [[View::renderFile()]] right after it renders a view file.
 */
class AfterRender extends ViewEvent
{
    private $result;

    /**
     * AfterRender constructor.
     * @param $result
     */
    public function __construct(string $file, array $parameters, $result)
    {
        $this->result = $result;
        parent::__construct($file, $parameters);
    }

    public function getResult()
    {
        return $this->result;
    }
}