<?php

namespace Yiisoft\Widget\Event;

class AfterRun
{
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }
}
