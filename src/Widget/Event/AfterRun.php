<?php

namespace Yiisoft\Widget\Event;

class AfterRun
{
    private $result;

    public function __construct(string $result)
    {
        $this->result = $result;
    }

    public function getResult(): string
    {
        return $this->result;
    }
}
