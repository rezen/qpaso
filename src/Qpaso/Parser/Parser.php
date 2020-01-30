<?php

namespace Qpaso\Parser;

use Qpaso\Lexer\{TokenType, Token};

use Qpaso\Parser\Node\{
    Node,
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
    NamedArg,
};

class Parser
{
    function tokenToNode($item, $default=Node::class)
    {
        if ($item->type === TokenType::FUNCTION) {
            $node = new FunctionNode($item->string);
        } else if ($item->type === TokenType::JSON) {
            $node = new JsonNode($item->string);
        } else if ($item->type === TokenType::NUMBER) {
            $node = new NumberNode($item->string);
        } else if ($item->type === TokenType::QSTRING) {
            $node = new TextNode($item->string);
        } else if ($item->type === TokenType::VARIABLE) {
            $node = new VariableNode($item->string);
        } else if ($item->type === TokenType::REGEX) {
            $node = new RegexNode($item->string);
        } else if ($item->type === TokenType::LOGIC) {
            $node = new LogicNode($item->string);
        } else if ($item->type === TokenType::VARIABLE_SET) {
            $node = new SetNode($item->string);
        } else {
            $node = new $default($item->string);
        }
        return $node;
    }


    function inScopeTil($items, $i, $test)
    {
        $opened = 0;
        $subset = [];
        do {
            $i += 1;
            $next = $items[$i] ?? null;
            if (is_null($next)) {
                break;
            }
        
            if (in_array($next->type, [TokenType::PAREN_START, TokenType::LIST_START])) {
                $opened += 1;
            } else if (in_array($next->type, [TokenType::PAREN_END, TokenType::LIST_END])) {

                if ($opened === 0) {
                    break;
                }
                $opened -= 1;
            }

            if ($opened === 0 && $test($next)) {
                $subset[] = $next;
                break;
            }
            $subset[] = $next;
        } while(true);
        return [$subset, $i];
    }

    function subsetOfType($items, $i, $start, $end) 
    {
        $opened = 1;
        $subset = [];
        do {
            $i += 1;
            $next = $items[$i] ?? null;
            if (is_null($next)) {
                throw new Exception("Parser - Did not find a matching closing token for $start -  $end");
            }
        
            if ($next->type === $start) {
                $opened += 1;
            } else if ($next->type === $end) {
                $opened -= 1;
            }

            if ($opened === 0) {
                if ($next->type !== $end) {
                    $subset[] = $next;
                }
                break;
            }
            $subset[] = $next;
        } while(true);
        return [$subset, $i];
    }

    function canBeFunction($token, $next)
    {
        if ($token->type === TokenType::FUNCTION) {
            return true;
        }

        if ($token->type !== TokenType::USTRING) {
            return false;
        }
        return true;
    }
    
    function closePointer(&$pointer)
    {
        if (!is_null($pointer)) {
            unset($pointer->var);
        }
        return null;
    }

    function addChild($node, $child)
    {
        $node->addChild($child);
        // $child->parent = $node;
        return $node;
    }

    function parse($items, $i, $parent=null) 
    {
        $pointer    = null;
        $count      = count($items);
        $prevNode   = null;
        $pipes      = [];
        $commas     = [];
        $lastNode   = null;

        if ($count === 0) {
            return new GroupNode("");
        }

        for ($i = 0; $i < $count; $i++) {
            $lastItem = $items[$i - 1] ??  (object) ['type' => null];
            $item     = $items[$i];
            $nextItem = $items[$i + 1] ?? (object) ['type' => null];
       
 
            if ($item->type === TokenType::WS) {
                // A whitespace ends a variables
                if (isset($pointer->var)) {
                    unset($pointer->var);
                }
              
                continue;
            }

            if ($this->canBeFunction($item, $nextItem)) {
                $isNextSetter = $nextItem->type === TokenType::VARIABLE_SET;
                if ($nextItem->type === TokenType::PAREN_START) {
                    $default = FunctionNode::class;
                } else {
                    // $default = is_null($pointer) ? ImplicitFn::class : ($pointer->isEmpty() ? ImplicitFn::class : Node::class);
                     $default = is_null($pointer) ? ImplicitFn::class : Node::class;
                }
                
                $node = $this->tokenToNode($item, $default);
                $node = $lastNode = $isNextSetter ? $node->convert(NamedArg::class) : $node;

                if (is_null($pointer)) {
                    $pointer = $node;
                    $pipes[] = $node;
                } else if ($isNextSetter) {
                    $pointer = $this->addChild($pointer, $node);
                    $pointer->var = $node;
                } else {
                    $pointer = $this->addChild($pointer, $node);

                    if ($node instanceof FunctionNode) {
                        $pointer = $node;
                    }
                }

                if ($isNextSetter) {
                    $i++;
                }
                continue;
            }

            if ($item->type === TokenType::LIST_START) {
                [$tmp, $i] = $this->subsetOfType($items, $i, TokenType::LIST_START, TokenType::LIST_END);
                $children  = $this->parse($tmp, 0, $pointer);



                $node = $lastNode = new ListGroup("", [$children]);

                if (is_null($pointer)) {
                    $pipes[] = $node;
                } else {
                    $pointer = $this->addChild($pointer, $node);
                }
                continue;
            }

            // If run into parens ... parse through children
            if ($item->type === TokenType::PAREN_START) {
                [$tmp, $i] = $this->subsetOfType($items, $i, TokenType::PAREN_START, TokenType::PAREN_END);
                $group     = $this->parse($tmp, 0, $pointer);
                $node = new ParenGroup("", [$group]);

                if (is_null($pointer)) {
                    $pipes[] = $node;

                } else if ($lastItem->type === TokenType::WS) {
                    // Parent used as grouping for evaluation
                    $pointer = $this->addChild($pointer, $node);
                } else {
                    // Was probably explicit function
                    $pointer = $this->addChild($pointer, $node);
                    $pointer = $this->closePointer($pointer);
                }
                continue;
            }

            // If a pipe ... break off as a new method
            if ($item->type === TokenType::PIPE) {
                $pointer = $this->closePointer($pointer);
                continue;
            }

            // If a comma ... break off as a new method
            // A comma should only be in () or []
            if ($item->type === TokenType::COMMA) {
                $pointer  = $this->closePointer($pointer);
                $commas[] = new GroupNode("", $pipes);
                $pipes    = [];
                continue;
            }

            
            $node = $lastNode = $this->tokenToNode($item);

            if (is_null($pointer)) {

                $pipes[] = $node;
            } else {
                $pointer = $this->addChild($pointer, $node);
            }
        }


        if (count($commas) > 0) {
            $commas[] = new GroupNode("", $pipes);
            return new CommaGroup(",", $commas);
        }

        if (count($pipes) === 1) {
            return $pipes[0];
        }

        return new GroupNode("", $pipes);
    }
}