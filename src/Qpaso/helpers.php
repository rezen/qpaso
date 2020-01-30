<?php

namespace Qpaso;

use \Qpaso\Lexer\Lexer;
use \Qpaso\Parser\Parser;
use \Qpaso\Executor\{
    InstructionsBuilder,
    Pipeline,
};
use \Qpaso\Executor\Resolvers\ExecutionResolver;


function query(string $query, $data)
{
    $ctx = (new Lexer)->parse($query);
    $tree = (new Parser)->parse($ctx->items, 0);
    $instruction = (new InstructionsBuilder)->build($tree);
    return \Qpaso\resolveInstructions($data, $instruction);
}


function isAssociative($array) 
{
    if (!is_array($array)) {
        return false;
    }
    $keys        = array_keys($array);
    $numericKeys = array_filter($keys, 'is_numeric');
    return (count($keys) !== count($numericKeys));
}

function resolveInstructions($target, $instruction)
{
    $pointer = $target;

    if (is_string($instruction) || is_numeric($instruction)) {
        return $instruction;
    }

    if (is_array($instruction)) {
        $instruction = new Pipeline($instruction);
    }

    if ($instruction instanceof ExecutionResolver) {
       return $instruction->execute($pointer);
    } else if ($instruction instanceof ListPrimitive) {
        return array_map(function($item) use ($pointer) {
            return \Qpaso\resolveInstructions($pointer, $item);
        }, $instruction->items);
    } else if ($instruction instanceof Pipeline) {
        $isAssociative = \Qpaso\isAssociative($instruction->executors);
        $aggr = [];

    
        foreach ($instruction->executors as $key => $fn) {
            if (!$isAssociative) {
                $pointer = \Qpaso\resolveInstructions($pointer, $fn);
            } else {
                if ($fn instanceof ExecutionResolver) {
                    $aggr[$key] = \Qpaso\resolveInstructions($pointer, $fn);
                } else if ($fn instanceof Pipeline) {
                    $aggr[$key] = \Qpaso\resolveInstructions($pointer, $fn);
                } else {
                   $aggr[$key] = $fn;
                }
            }
        }

        if ($isAssociative) {
            return $aggr;
        }
    }
    return $pointer;
}
