<?php

namespace Filipac\PageController\Classes;

use Cms\Classes\CodeBase;

class Ajax extends CodeBase
{
    public function onTest()
    {
        $value1 = input('value1');
        $value2 = input('value2');
        $operation = input('operation');

        switch ($operation) {
            case '+':
                $this['result'] = $value1 + $value2;
                break;
            case '-':
                $this['result'] = $value1 - $value2;
                break;
            case '*':
                $this['result'] = $value1 * $value2;
                break;
            default:
                $this['result'] = $value1 / $value2;
                break;
        }
    }
}
