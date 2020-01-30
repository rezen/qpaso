<?php

namespace Qpaso\Lexer;

class Token
{
    public $type;
    public $string;
    public $depth = 0;

    function __construct($string, $type, $depth=0)
    {
        $this->string = $string;
        $this->type =  $type;
        $this->depth = $depth;
    }
}
