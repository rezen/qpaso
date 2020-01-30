<?php

namespace Qpaso\Executor;

use Qpaso\Parser\Node\{
    FunctionNode,
    ImplicitFn,
    JsonNode,
    NumberNode,
    TextNode,
    VariableNode,
    RegexNode,
    LogicNode,
    SetNode,
    GroupNode,
    ParenGroup,
    ListGroup,
    CommaGroup,
    NamedArg
};
use Qpaso\Executor\Resolvers\{
    SelectallResolver,
    SplitResolver,
    MapResolver,
    InListResolver,
    FilterResolver,
    AttrResolver,
    OrResolver,
    NotResolver,
    FirstResolver,
    ContainsResolver,
    JsonEncodeResolver,
    ExecutionResolver,
};

class NodeResolver
{
    public $resolvers = [];

    function __construct($resolvers=[])
    {
        $this->resolvers = [
            new SelectallResolver,
            new SplitResolver,
            new MapResolver,
            new InListResolver,
            new FilterResolver,
            new AttrResolver,
            new OrResolver,
            new NotResolver,
            new FirstResolver,
            new ContainsResolver,
            new JsonEncodeResolver,
        ];
    }

    function resolveNode($node)
    {
         switch (get_class($node)) {
        case ImplicitFn::class:
        case FunctionNode::class:            
            return $this->resolveFunction($node);
         }
         return $this->resolvePrimitive($node);
    }

    function isResolvableFunction($node)
    {
        switch (get_class($node)) {
        case ImplicitFn::class:
        case FunctionNode::class:
            $match = ucwords(str_replace(['-', '_'], ' ', "{$node->value}_resolver"));
            $match = str_replace(' ', '', $match);        
                
            foreach ($this->resolvers as $resolver) {
                $className = (new \ReflectionClass($resolver))->getShortName();
                if ($className === $match) {
                    return true;
                }
            }
            return false;
        default:
            return false;
        }
    }


    function resolveFunction($node)
    {
        $match = ucwords(str_replace(['-', '_'], ' ', "{$node->value}_resolver"));
        $match = str_replace(' ', '', $match);        
        
        foreach ($this->resolvers as $resolver) {
            $className = (new \ReflectionClass($resolver))->getShortName();

            if ($className === $match) {
                $copy = clone $resolver;
                return $copy;
            }

        }

        throw new \Exception("Did not find function $node->value");
    }


    function resolvePrimitive($node)
    {
        switch (get_class($node)) {
        case JsonNode::class:
            return json_decode($node->value);
        case NumberNode::class:
            return strpos($node->value, ".") !== false ? floatval($node->value) : intval($node->value);
        case TextNode::class:
            $encloser = $node->value[0];
            $substring = substr($node->value, 1, strlen($node->value) - 2);
            return str_replace("\\{$encloser}", $encloser, $substring);
        }

        return $node->value;
    }
}

