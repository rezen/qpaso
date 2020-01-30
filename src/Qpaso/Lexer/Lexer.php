<?php

namespace Qpaso\Lexer;

function isWhitespace($char)
{
    return preg_match('/\s/', $char);
}

function isPipe($char)
{
    return $char === "|";
}


class Lexer
{
    public $max = 1000;

    function subsetOfType($items, $ctx, $start="{", $end="}") 
    {
        $opened = 1;
        $subset = "";
        do {
            $ctx->position += 1;
            $next = $items[$ctx->position] ?? null;
            if (is_null($next)) {
                throw new Exception("Lexer - Did not find a matching closing token for $start -  $end");        
            }
        
            if ($next === $start) {
                $opened += 1;
            } else if ($next === $end) {
                $opened -= 1;
            }

            if ($opened === 0) {
                if ($next !== $end) {
                    $subset .= $next;
                }
                break;
            }
            $subset .= $next;
        } while(true);
        return [$subset, $ctx];
    }

    function readUntil($string, $fn, $ctx)  
    {
        $escaped = false;
        $max      = $ctx->position + $this->max;
        $sequence = "";
    
        while (true) {
            $ctx->position += 1;
            $char = mb_substr($string, $ctx->position, 1, 'UTF-8');

            if ($fn($char, $escaped, $sequence)) {
                break;
            }
        
            if ($ctx->position >= $this->max) {
                // throw new Exception("String not enclosed");
                break;
            }
            $sequence .= $char;
            $escaped = ($char === "\\");
        }
        
        return [$sequence, $ctx];
    }
    
    function munchAfter($char)
    {
        return in_array($char, [ "(", "["]);
    }

    function munchBefore($char)
    {
        return in_array($char, [")", ",", "]"]);
    }

    function parse($string)
    {
        $string     = trim($string);
        $charsCount = mb_strlen($string, 'UTF-8');
        $lastIndex  = $charsCount - 1;
        $ctx        = new Context;
        $pointer    = null;
        $lastChar   = null;

        for ($ctx->position; $ctx->position < $charsCount; $ctx->position++) {
            $lastChar =  mb_substr($string, ($ctx->position - 1), 1, 'UTF-8');
            $char = mb_substr($string, $ctx->position, 1, 'UTF-8');
            $nextChar =  mb_substr($string, ($ctx->position + 1), 1, 'UTF-8');
            
            if (isWhitespace($char)) {
                $ctx = $this->endToken($ctx);
                if (!isWhitespace($nextChar) && !$this->munchBefore($nextChar) && !$this->munchAfter($lastChar)) {
                    $ctx->addToken(new Token(" ", TokenType::WS, $ctx->depth));
                }
        
                $lastChar = $char;
                continue;
            }

            if (isPipe($char)) {
                $ctx = $this->endToken($ctx);
                $ctx->addToken(new Token("|", TokenType::PIPE, $ctx->depth));
            } else if (preg_match("/\\//", $char, $matches) && $ctx->isNew) {
                $ctx = $this->parseRegex($string, $ctx);
            } else if ($char === "{") {
                $ctx = $this->parseJson($string, $ctx);
            } else if (preg_match("/[\(\)]/", $char, $matches)) {
                $ctx = $this->parseParen($matches[0], $ctx);
            } else if (preg_match("/[\[\]]/", $char, $matches)) {
                $ctx = $this->parseList($matches[0], $ctx);
            } else if ($char === ":") {
                $ctx = $this->parseColon($string, $ctx);
            } else if ($char === "=") {
                $ctx = $this->parseEquals($ctx);
            } else if ($char === ",") {
                $ctx = $this->parseComma($ctx);
            } else if (preg_match("/[\"'`]/", $char, $matches)) {
                $ctx = $this->parseQuotes($string, $matches[0], $ctx);
            } else {
                $ctx = $this->continueContext($char, $ctx);
            }
            $lastChar = $char;  
        }
        $ctx = $this->endToken($ctx);
        return $ctx;
    }

    function endToken($ctx)
    {
        if (empty($ctx->aggr)) {
            return $ctx;
        }
        
        $ctx->isNew = true;

        if ($ctx->isVariable) {
            $ctx->addToken(new Token($ctx->aggr, TokenType::VARIABLE, $ctx->depth));
            $ctx->isVariable = false;
            return  $ctx;
        }

        if (preg_match('/^[0-9\.]+$/', $ctx->aggr)) {
            $ctx->addToken(new Token($ctx->aggr, TokenType::NUMBER, $ctx->depth));
            return $ctx;        
        }

        $ctx->addToken(new Token($ctx->aggr, TokenType::USTRING, $ctx->depth));
        return $ctx;
    }
 
    function continueContext($char, $ctx)
    {
        if ($char === "$") {

        }

        if ($char === "$" && $ctx->isNew) {
            $ctx->isVariable = true;
        }
        $ctx->aggr .= $char;
        $ctx->isNew = false;
        return $ctx;
    }

    function parseRegex($string, $ctx)
    {
        [$match, $ctx] = $this->readUntil(
            $string, function ($char, $isEscaped, $sequence) { 
                return $char === "/" && !$isEscaped;
            }, $ctx
        );

        $ctx->addToken(new Token("/" . $match . "/", TokenType::REGEX, $ctx->depth));
        return $ctx;
    }

    function parseParen($paren, $ctx)
    {
        $this->endToken($ctx);
        if ($paren === "(") {
            $ctx->addToken(new Token($paren, TokenType::PAREN_START, $ctx->depth));
            $ctx->depth += 1;
        } else {
            $ctx->depth -= 1;
            $ctx->addToken(new Token($paren, TokenType::PAREN_END, $ctx->depth));
        }
        return $ctx;
    }

    function parseList($bracket, $ctx)
    {
        $this->endToken($ctx);
        if ($bracket === "[") {
            $ctx->addToken(new Token($bracket, TokenType::LIST_START, $ctx->depth));
            $ctx->depth += 1;
        } else {
            $ctx->depth -= 1;
            $ctx->addToken(new Token($bracket, TokenType::LIST_END, $ctx->depth));
        }
        return $ctx;
    }


    function parseJson($string, $ctx)
    {
        $this->endToken($ctx);
        [$subset, $ctx] =  $this->subsetOfType($string, $ctx, "{", "}");
        $ctx->addToken(new Token("{" . $subset ."}", TokenType::JSON, $ctx->depth));
        return $ctx;
    }

    function parseColon($string, $ctx)
    {
        $this->endToken($ctx);

        [$match, $ctx] = $this->readUntil(
            $string, function ($char, $isEscaped, $sequence) { 
                return in_array($char, [' ', ')', '|']);
            }, $ctx
        );

        $tokens = $this->parse($match)->items;

        $ctx->addToken(new Token("(", TokenType::PAREN_START, $ctx->depth));

        foreach ($tokens as $token) {
            $token->depth = $ctx->depth + 1;
            $ctx->addToken($token);
        }
        $ctx->addToken(new Token(")", TokenType::PAREN_END, $ctx->depth));
        return $ctx;
    }

    function parseEquals($ctx)
    {
        $this->endToken($ctx);
        $ctx->addToken(new Token("=", TokenType::VARIABLE_SET, $ctx->depth));
        return $ctx;
    }

    function parseComma($ctx)
    {
        $this->endToken($ctx);
        $ctx->addToken(new Token(",", TokenType::COMMA, $ctx->depth));
        return $ctx;
    }

    function parseQuotes($string, $quote, $ctx)
    {
        $ctx->aggr .= $quote;
        [$match, $ctx] = $this->readUntil(
            $string, function ($char, $isEscaped, $sequence) use ($quote) { 
                return $char === $quote && !$isEscaped;
            }, $ctx
        );

        $ctx->addToken(new Token($ctx->aggr . $match . $quote, TokenType::QSTRING, $ctx->depth));
        $ctx->prevChar = $quote;
        return $ctx;
    }
}
