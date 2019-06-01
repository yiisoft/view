<?php

namespace Yiisoft\Widget\Event;

use Yiisoft\Widget\ActiveField;

class AfterActiveFieldRender
{
    private $field;

    public function __construct(ActiveField $field)
    {
        $this->field = $field;
    }

    public function field(): ActiveField
    {
        return $this->field;
    }
}
