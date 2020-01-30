<?php

namespace Qpaso\Executor\Resolvers;

class AttrResolver implements ExecutionResolver
{
    function execute($data)
    {
        $attr = $this->args[0];
        $fallback = $this->args[1] ?? "";
        
        if (is_array($data)) { 
            $val =  $data[$attr] ?? $fallback;
        } else {
            $val = $data->$attr ?? $fallback;
        }
        return $val;
    } 
}