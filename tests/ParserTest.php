<?php


use PHPUnit\Framework\TestCase;

use Qpaso\Parser\Parser;
use Qpaso\Lexer\{Token,Lexer,TokenType};
use Qpaso\Parser\Node\{
    FunctionNode, 
    ImplicitFn, 
    GroupNode, 
    Node, 
    ListGroup,
    ParenGroup, 
    CommaGroup,
    NamedArg,
};

final class ParserTest extends TestCase
{
    
    function testToStringOfNodeIsSameTree()
    {
        $queries =[ 
            "or(
                (
                    contains (test ' inner \'text \"' b [1 3 4] ) | testing
                ), 
                and(
                    dogs dogs_arg |  into_cats, 
                    other yay
                ) | x,
                test | what(hey, b)
            )",
            'map(whoami /pattern/ x) | test {"age":12}',

            "outer(
                test | arg_fn(sfn_as_arg_1 | test, last), 
                [1 2 3 4 (one | two)]
            )",
            "(age | dob (cats | dog) [1 2])",
            "first | mid | end",
            '[test 2, b 3, (abc | pipe), [test 2 3 (abc | two)]]',
            'test (one, two)',
            'dimensions(ratio=3/2)',
        ];
        foreach ($queries as $raw) {
            $ctx = (new Lexer)->parse($raw);
            $tree = (new Parser)->parse($ctx->items, 0);

            $ctx2 = (new Lexer)->parse("{$tree}");
            $tree2 = (new Parser)->parse($ctx2->items, 0);
            $this->assertEquals(
                $tree, 
                $tree2
            );
            //   echo "\n---------\n+{$tree}\n-$raw\n----------\n";
        }
        echo " ";
    }
    
  
    public function testJsonSerializer()
    {
        $structure = [
            "value" => "grep",
            "class" => "FunctionNode",
            "children" => [
                [
                    [
                        "value"=> "hey",
                        "class"=> "ImplicitFn",
                        "children"=> []
                    ],
                    [
                        "value" => "now",
                        "class" => "ImplicitFn",
                        "children"=> []
                    ]
                ]
            ]
        ];

        $group =  new FunctionNode("grep", [
            new GroupNode("", [
                new ImplicitFn("hey"),
                new ImplicitFn("now"),
            ])
        ]);
    
        $this->assertEquals(
            json_encode($structure, JSON_PRETTY_PRINT), 
            json_encode($group, JSON_PRETTY_PRINT)
        );        echo " ";

    }


    function testParsingList()
    {
        $raw = '[1, 2, 3, 4]';

        $ctx = (new Lexer)->parse($raw);
        $tree = (new Parser)->parse($ctx->items, 0);

        $this->assertInstanceOf(ListGroup::class, $tree);
        $this->assertCount(1, $tree->children);
        $this->assertInstanceOf(CommaGroup::class, $tree->children[0]);
        $this->assertCount(4, $tree->children[0]->children);
                echo " ";

    }
    

    function testSetterInFn()
    {
        $query = 'map(age=123, dob=/[0-9]{4}-[0-9]{2}-[0-9]{2}/)';
        $ctx = (new Lexer)->parse($query);
        $tree = (new Parser)->parse($ctx->items, 0);
        $this->assertInstanceOf(FunctionNode::class, $tree);
        $this->assertEquals($tree->value, "map");
        $this->assertCount(1, $tree->children);
        $this->assertInstanceOf(ParenGroup::class, $tree->children[0]);

        $paren = $tree->children[0];
        $this->assertCount(1, $paren->children);
        $this->assertEquals($query, "{$tree}");
                echo " ";

    }

    function testSetterInFnWithoutComma()
    {
        $this->expectException(\Exception::class);

        $query = 'map(bad=1 comma_fail)';
        $ctx = (new Lexer)->parse($query);
        $tree = (new Parser)->parse($ctx->items, 0);
                echo " ";

    }

    function testSetterInImplicitFn()
    {
        $query = 'map key=text dob=(one /test/)';
        $ctx = (new Lexer)->parse($query);
        $tree = (new Parser)->parse($ctx->items, 0);
        
        $this->assertCount(2, $tree->children);
        $this->assertInstanceOf(NamedArg::class, $tree->children[1]);
        $this->assertCount(1, $tree->children[1]->children);
        $this->assertEquals($query, "{$tree}");
                echo " ";

    }


       function testSetterInFnFail()
    {
        /*
        $illegal = 'cats(name=attr) href';
        $query = 'a | b data=json_encode c=other | test';

        $query = 'a | b data=(json_encode c=other) | test';
        $query = 'a | b data=json_encode(other) | test';
        */
        $query = 'a | b data=(json_encode(other) | test)';

        $ctx = (new Lexer)->parse($query);
        $tree = (new Parser)->parse($ctx->items, 0);
        $this->assertEquals($query, "{$tree}");

        $this->assertInstanceOf(GroupNode::class, $tree);
        $this->assertCount(2, $tree->children);
        $this->assertInstanceOf(ImplicitFn::class, $tree->children[0]);
        
        $fnB = $tree->children[1];
        $this->assertCount(1, $fnB->children);
        $this->assertInstanceOf(NamedArg::class, $fnB->children[0]);
                        echo " ";

    }

    public function testParsesListOfFn()
    {
        $ctx = (new Lexer)->parse("[fn1() fn2() fn3]"); // parses as fn1() | fn2() | fn
        $tree = (new Parser)->parse($ctx->items, 0);
        $this->assertEquals("[fn1() | fn2() | fn3]", "{$tree}");

        $this->assertInstanceOf(ListGroup::class, $tree);
        $this->assertCount(1, $tree->children);
        $this->assertCount(3, $tree->children[0]->children);
        $this->assertTrue($tree->children[0]->children[0]->isEmpty());

    }

    
    public function testParsesListWithImplicitFn()
    {
        $ctx = (new Lexer)->parse("[fn arg1 arg2]");
        $tree = (new Parser)->parse($ctx->items, 0);
        $this->assertCount(1, $tree->children);
        $this->assertCount(2, $tree->children[0]->children);
    }

    public function testParsesParenWithList()
    {
        $ctx = (new Lexer)->parse("[attr name | split | filter contains(`D`)]");
        $tree = (new Parser)->parse($ctx->items, 0);

    
        $this->assertCount(1, $tree->children);
        $this->assertCount(3, $tree->children[0]->children);
    }

    
    public function testParsesErrorMixNamedWithPositionalArgs()
    {
        $ctx = (new Lexer)->parse("map(href=(attr name | first), json_encode)");
        $tree = (new Parser)->parse($ctx->items, 0);
      
    }

    public function testParsesLiteralFirst()
    {
        $ctx = (new Lexer)->parse("`hello` | contains `hello`");
        $tree = (new Parser)->parse($ctx->items, 0);
      
        $this->assertCount(2, $tree->children);
    }

    public function testSecondOrderFn()
    {
        $ctx = (new Lexer)->parse("filter contains(`b`)");
        $tree = (new Parser)->parse($ctx->items, 0);
        $this->assertCount(1, $tree->children);
        $this->assertCount(1, $tree->children[0]->children);
        $this->assertInstanceOf(FunctionNode::class, $tree->children[0]);
        $this->assertInstanceOf(ImplicitFn::class, $tree);
    }

    public function testParsesTokens()
    {
        $tokens = [
            new Token("bob", TokenType::USTRING),
            new Token("(", TokenType::PAREN_START, 0),
                new Token("hey", TokenType::USTRING, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("|", TokenType::PIPE, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("now", TokenType::USTRING, 1),
            new Token(")", TokenType::PAREN_END),
        ];
    
        $tree = (new Parser)->parse($tokens, 0);

        $group = new FunctionNode("bob", [
                new ParenGroup("", [
                    new GroupNode("", [
                        new ImplicitFn("hey"),
                        new ImplicitFn("now"),
                    ])
                ])
            ]);;
        $this->assertEquals($group, $tree);
    }
    public function testNext()
    {
          $tokens = [
            new Token("bob", TokenType::USTRING),
            new Token("(", TokenType::PAREN_START, 0),
                new Token("hey", TokenType::USTRING, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("|", TokenType::PIPE, 1),
                new Token(" ", TokenType::WS, 1),
                new Token("now", TokenType::USTRING, 1),
            new Token(")", TokenType::PAREN_END),
            new Token(",", TokenType::COMMA, 0),
            new Token("bob2(", TokenType::PAREN_START, 0),
            new Token(")", TokenType::PAREN_END),

        ]; 
        $parser = new Parser;
        [$subset, $x] = $parser->inScopeTil($tokens, 1, function($token) {
            return $token->type === TokenType::COMMA;
        });

        $this->assertCount(5, $subset);

        [$subset, $x] = $parser->inScopeTil($tokens, 0, function($token) {
            return $token->type === TokenType::PAREN_END;
        });
        $this->assertCount(7, $subset);
    }
}