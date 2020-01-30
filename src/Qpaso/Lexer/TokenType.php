<?php

namespace Qpaso\Lexer;

class TokenType
{
    const WS   = "ws";
    const PIPE = "pipe";
    const REGEX = "regex";
    const NUMBER = "number";
    const USTRING = "string";
    const QSTRING = "string_quoted";
    const VARIABLE = "variable";
    const LOGIC = "logic";
    const JSON = "json_object";
    const PAREN_START = "paren_start";
    const PAREN_END = "paren_end";
    const LIST_START = "list_start";
    const LIST_END = "list_end";
    const COMMA = "comma";
    const VARIABLE_SET = "equal_set";
    
    const FUNCTION = "fn";

    const FN_ATTR = "attr";
    const FN_MAP = "map";
    const FN_FILTER = "filter";
    const FN_OR = "or";
    const FN_NOR = "nor";
    const FN_AND = "and";
}