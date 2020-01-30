# qpaso
`kay-pass-oh` 

What's up? - `q` is shorthand for que which is what and `paso` is a passage or step. It's a basic query language with inspiration from linux shell, [Sumo Logic's query language](https://help.sumologic.com/05Search/Search-Query-Language/Search-Operators) & functional programming. It's functional purpose is primarily around selecting, filtering & transforming data sets in a simple manner. There aren't "reserved" keywords & any non-primitive is implicitly a function. When you implement `qpaso` you register a set of functions, any query which has a unquoted string that may normally be an implicit function, however if there is no registered function it should be treated as string.

## Example
Say we had a html document we wanted to get all anchor which have `http` in their href attribute, we could have a query like the item below, which would get all the elements matching the selector `a[href]` and filter down to the first one containing `http` and then return it's `href` value and `text` value.

With the pipe, the value of the previous segment is passed down to the next function. So `selectall` would return a collection & `filter` would trim down the collection based on the truthiness of the filter expression. That collection would then be passed on to `first` which would return the 1st item in the collection and `pick` you grab the attributes you specify.

```
selectall("a[href]") 
| filter(attr(href) | contains "http") 
| first 
| pick(href text)
```

## Data Types
- String
- Regex
- JSON
- Number
- List
- Function
  - Explicit
  - Implicit




### String
Starts with an encapsulator (``` ' ` " ```) and ends with an unescaped matching quote or backtick.

- `"double quotes"`
- `'single quotes'`
- ``` `with backticks` ```

### Regex
Starts with a `/` and ends with an unescaped `/`
- `/[a-z]+/`

### JSON
JSON objects are supported, currently lists are not
- `{"name":"Doe","age":45}`


### Number
Any numeric like value that matches regex `[0-9\.]+` is treated as a numeric value

- `12`
- `0.33`

### List
A list starts with a square bracket (`[`) and ends with a square bracket (`]`). You must 
use either a space delimiter (` `) or a comma (`,`).

- `[1 2 3]`
- `[1 2 name]`
- `[fn1 arg1 arg2]` 
  - A list with one implicit function
- `[fn1 arg1, fn2 a1 a2, fn3]`

### Functions
Functions can executed in one of two ways, implicitly or explicitly. You can think of implicit functions like binaries in bash (eg `cat ~/my_file.txt`). Functions can use either positional args or keyword args, but not both. 



#### Examples
**Implicit 1**
`fn arg1 arg2`
    -  Function `fn`
       -  `arg1` is positional arg 1
       -  `arg2` is positional arg 2

**Explicit 1**
`fn_x(arg1, arg2)`
    - Function `fn_x`
       -  `arg1` is positional arg 1
       -  `arg2` is positional arg 2

**Explicit 2**
`fn_y(fn1 | fn2)`
    - Function `fn_x`
      - The value of `fn1 | fn2` is positional arg 1

**Keyword Args**
You have to use commas in explicit functions
`fn_a argname="string" another=(cat /name/ | grep b)`
`fn_a(argname="string", another=(cat /name/ | grep b))`
    - Function `fn_a`

**Illegal**
`fn_a(argname=fn_cats | dogs, another=(cat /name/ | grep b))`
Will trigger Named arg with multiple children


### Errors

Error | Implementor | Sample 
--- | --- | --- 
Missing matching encloser        | lexer | `map(cats` 
Missing matching encloser        | lexer | `[1 2 3` 
Missing matching encloser        | lexer | `"Hello ...` 
Missing matching encloser        | lexer | `'Hello ...` 
Missing matching encloser        | lexer | <code>\`Hello ... </code>
Incomplete setter                | parser | ```json_encode pretty=```
Named arg with multiple children | parser | ```json_encode(pretty=contains 1)```
Named arg with multiple children | parser | ```json_encode(pretty=contains 1, indent=match /[a-z]{4}/)```
Function does not exist          | executor  |  `splt x \| first`


./vendor/bin/phpunit --verbose --bootstrap vendor/autoload.php tests/

## TODO
- `$:name` expands to `($ | attr name)`

## Links

- https://github.com/nette/tokenizer
- http://lisperator.net/pltut/parser/token-stream
- https://nitschinger.at/Writing-a-simple-lexer-in-PHP/
- https://jack-vanlightly.com/blog/2016/2/3/creating-a-simple-tokenizer-lexer-in-c
- https://jwage.com/posts/2012/09/15/writing-a-parser-in-php-with-the-help-of-doctrine/
- https://github.com/martynshutt/php-tokenizer

