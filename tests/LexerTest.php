<?php


use PHPUnit\Framework\TestCase;

use Qpaso\Lexer\{Token,Lexer,TokenType};

final class LexerTest extends TestCase
{
    
    function testlexesSetterInFn()
    {
        $query = 'map(name=(attr href), age=text)';
        $ctx = (new Lexer)->parse($query);
        $count = count($ctx->items);

        $this->assertEquals($ctx->items[$count - 1]->type, TokenType::PAREN_END);
    }

    public function testLexesJson()
    {
        $json = '{"key": "value", "age": 12, "items":[1,2,3]}';
        $ctx = (new Lexer)->parse(" $json ");
        $compare = new Token($json, TokenType::JSON);
        $this->assertEquals($ctx->items[0], $compare);
        $this->assertCount(1, $ctx->items);

    }

    public function testLexesRegex()
    {
        $data = '/[a-z0-9+\\/]/';
        $ctx = (new Lexer)->parse("$data");
        $compare = new Token($data, TokenType::REGEX);
        $this->assertEquals($ctx->items[0], $compare);
    }

    public function testLexesQuotes()
    {
        $data = "Hello there human!";
        $quotes = ["'", '"', "`"];
        foreach($quotes as $quote) {
            $withQuote = $quote . $data . $quote;
            $ctx = (new Lexer)->parse($withQuote);
            $compare = new Token($withQuote,  TokenType::QSTRING);
            $this->assertEquals($ctx->items[0], $compare);
        }
    }

    public function testLexesQuotesMixed()
    {
        $data = "'Hello there human!\"<-- In the single quoted '";
        $ctx = (new Lexer)->parse($data);
        $compare = new Token($data,  TokenType::QSTRING);
        $this->assertEquals($ctx->items[0], $compare);
    }

    public function testLexesMultipleQuotes()
    {
        $data = "command 'arg1' `arg2` \"arg3\"";
        $ctx = (new Lexer)->parse($data);
        $compare = new Token($data,  TokenType::QSTRING);
        $this->assertCount(7, $ctx->items);
    }

    public function testLexesEscapedQuotes()
    {
        $data = "'\'part one\' and part two'";
        $ctx = (new Lexer)->parse($data);
        $compare = new Token($data,  TokenType::QSTRING);
        $this->assertCount(1, $ctx->items);
    }

    public function testLexesPipeCorrectNumber()
    {
        $pipe = new Token("|",  TokenType::PIPE, 0);
        $pipe->depth = 0;
        $data = 'cat | grep | sort';
        $pipesCount = substr_count($data, "|");
        $ctx = (new Lexer)->parse("$data");

        $lexedPipes = count(array_filter($ctx->items, function($t) {
            return $t->type === TokenType::PIPE;
        }));

        $this->assertEquals($lexedPipes, $pipesCount);
    }

    public function testLexesSetter()
    {
        $tokens = [
            new Token("bob", TokenType::USTRING),
            new Token("=", TokenType::VARIABLE_SET),
            new Token("cat", TokenType::USTRING),
        ];
        $data = "bob=cat";
        $ctx = (new Lexer)->parse("$data");

        $this->assertEquals($ctx->items, $tokens);
    }

    public function testLexesSetterFromGroup()
    {
        $tokens = [
            new Token("bob", TokenType::USTRING),
            new Token("=", TokenType::VARIABLE_SET),
            new Token("(", TokenType::PAREN_START, 0),
                new Token("hey", TokenType::USTRING, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("|", TokenType::PIPE, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("now", TokenType::USTRING, 1),
            new Token(")", TokenType::PAREN_END),
        ];
        $data = "bob=(hey | now)";
        $ctx = (new Lexer)->parse("$data");
        $this->assertEquals($ctx->items, $tokens);
    }

    public function testLexesVariable()
    {
        $data = '$name';
        $ctx = (new Lexer)->parse("$data");
        $compare = new Token($data, TokenType::VARIABLE);
        $this->assertEquals($ctx->items[0], $compare);
    }

    public function testLexesSetVariable()
    {
        $data = 'dimensions(ratio=3/2)';
        $ctx = (new Lexer)->parse("$data");
        $subset = array_filter($ctx->items, function($t) {
            return $t->type === TokenType::VARIABLE_SET;
        });

        $this->assertCount(1, $subset);
    }

    public function testLexesSetVariables()
    {
        $data = 'map(age=text dob=/test/)';
        $ctx = (new Lexer)->parse("$data");
        $subset = array_filter($ctx->items, function($t) {
            return $t->type === TokenType::VARIABLE_SET;
        });

        $this->assertCount(2, $subset);
    }

    public function testLexesComma()
    {
        $data = 'one,true,three';
        $tokens = [
            new Token("one", TokenType::USTRING),
            new Token(",", TokenType::COMMA),
            new Token("true", TokenType::USTRING),
            new Token(",", TokenType::COMMA),
            new Token("three", TokenType::USTRING),
        ];
        $ctx = (new Lexer)->parse("$data");
        $this->assertEquals($ctx->items, $tokens);
    }

    public function testLexerExpandsColon()
    {
        $data = 'one:bob,two';
        $tokens = [
            new Token("one", TokenType::USTRING),
            new Token("(", TokenType::PAREN_START),
                new Token("bob", TokenType::USTRING, 1),
                new Token(",", TokenType::COMMA, 1),
                new Token("two", TokenType::USTRING, 1),
            new Token(")", TokenType::PAREN_END),
        ];
        $ctx = (new Lexer)->parse("$data");
        $this->assertEquals($ctx->items, $tokens);
    }
    
    public function testLexerParsesList()
    {
        $tokens = [
            new Token("[", TokenType::LIST_START),
                new Token("1", TokenType::NUMBER, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("2", TokenType::NUMBER, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("3", TokenType::NUMBER, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("4", TokenType::NUMBER, 1),
            new Token("]", TokenType::LIST_END),
        ];

        $ctx = (new Lexer)->parse("[1 2 3 4]");
        $this->assertEquals($ctx->items, $tokens);
    }

    public function testLexerParsesListA()
    {
        $ctx = (new Lexer)->parse("[fn arg1 arg2]");
        $count = count($ctx->items);

        $this->assertEquals($ctx->items[0]->type, TokenType::LIST_START);
        $this->assertEquals($ctx->items[1]->type, TokenType::USTRING);
        $this->assertEquals($ctx->items[$count - 1]->type, TokenType::LIST_END);
    }

    public function testLexerParsesListB()
    {
        $ctx = (new Lexer)->parse("[fn() fn() fn()]");
        $count = count($ctx->items);

        $this->assertEquals($ctx->items[0]->type, TokenType::LIST_START);
        $this->assertEquals($ctx->items[$count - 1]->type, TokenType::LIST_END);
    }



    function dataForMunch()
    {
        return [
            new Token("(", TokenType::PAREN_START),
                new Token("1", TokenType::NUMBER, 1),
                new Token(",", TokenType::COMMA, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("2", TokenType::NUMBER, 1),
            new Token(")", TokenType::PAREN_END),
        ];
    }

    public function testLexerMunchExtraWhitespaceBetweenChars()
    {
        $tokens = $this->dataForMunch();
        $ctx = (new Lexer)->parse("(1   ,   2)");
        $this->assertEquals($ctx->items, $tokens);
    }

    public function testLexerMunchWhitespaceBeforeComma()
    {
        $tokens = $this->dataForMunch();
        $ctx = (new Lexer)->parse("( 1 , 2)");
        $this->assertEquals($ctx->items, $tokens);
    }

    public function testLexerMunchWhitespaceAfterParenOpen()
    {
        $tokens = $this->dataForMunch();
        $ctx = (new Lexer)->parse("(1 , 2)");
        $this->assertEquals($ctx->items, $tokens);
    }

    public function testLexerMunchWhitespaceBeforeParenClose()
    {
        $tokens = $this->dataForMunch();
        $ctx = (new Lexer)->parse("(1 , 2   )");
        $this->assertEquals($ctx->items, $tokens);
    }

    public function testLexerHandlesComplex()
    {

        $raw =  "outer(test | arg_fn(sfn_as_arg_1 | test, last), [1 2 3 4 (one | two)])";
        $ctx = (new Lexer)->parse($raw);
        
        $this->assertEquals($ctx->items[0], new Token("outer", TokenType::USTRING, 0));
        $this->assertEquals($ctx->items[2], new Token("test", TokenType::USTRING, 1));
        $this->assertEquals($ctx->items[13], new Token(",", TokenType::COMMA, 2));
        $this->assertEquals($ctx->items[count($ctx->items) - 3], new Token(")", TokenType::PAREN_END, 2));
        $this->assertEquals($ctx->items[count($ctx->items) - 1], new Token(")", TokenType::PAREN_END, 0));
    }    
}


