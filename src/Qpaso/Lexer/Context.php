<?php

namespace Qpaso\Lexer;

class Context
{
    public $isNew = true;
    public $isVariable = false;
    public $quote = null;
    public $type = null;
    public $depth = 0;
    public $items = [];
    public $aggr = "";
    public $position = 0;
    public $prevChar ;
    public $lastToken;

    function isLastType($type)
    {
        if (is_null($this->lastToken)) {
            return false;
        }
        return $this->lastToken->type === $type;
    }

    function addToken($token)
    {   

        $this->aggr = "";
        $this->isNew = true;
        if (empty($token->string)) {
            return;
        }
        $this->lastToken = $token;
        $this->items[] = $token;
    }

    function inQuote()
    {
        return !is_null($this->quote);
    }

}
