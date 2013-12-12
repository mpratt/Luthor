Luthor
======
[![Build Status](https://secure.travis-ci.org/mpratt/Luthor.png?branch=master)](http://travis-ci.org/mpratt/Luthor)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/mpratt/Luthor/badges/quality-score.png?s=24c42108df50eba8149dfc291f549dfe0d317ef1)](https://scrutinizer-ci.com/g/mpratt/Luthor/)
[![Code Coverage](https://scrutinizer-ci.com/g/mpratt/Luthor/badges/coverage.png?s=537bc5b18469395beb0f944222c0b15bc72c9510)](https://scrutinizer-ci.com/g/mpratt/Luthor/)
[![Latest Stable Version](https://poser.pugx.org/mpratt/luthor/v/stable.png)](https://packagist.org/packages/mpratt/luthor)
[![Total Downloads](https://poser.pugx.org/mpratt/luthor/downloads.png)](https://packagist.org/packages/mpratt/luthor)

** Warning: This library is not production ready **

Luthor is an extendable Markdown Lexer/Parser for PHP. It converts markdown text into HTML. In other words, what it does is,
It reads the markdown text line by line and analyses its content. It uses a Lexer to tokenize each element and passes them to the parser.
The Parser operates on that token and returns an HTML string.

The key feature of this library, is that you can extend the token map quite easily, giving you the hability to practically have
custom notations and build your own flavored markdown. There are other cool things that can be easily done, like applying filters
or modifying the way a token is handled or displayed. It also has a bunch of configuration options which can be used to change the behaviour of the library!
See the Usage section for more information about how everything is done. Or better yet, hop into the source and take a peak.

Here is a quick list of supported markup:
- Paragraphs and line breaks
- Headers
- Blockquotes
- Code blocks and Fenced code blocks
- Lists (starting with `* `, `- `, `+ `, or `1. `)
- Horizontal Rules
- Span Elements (links, emphasis, code, images, striked out text)
- Element Escaping
- Footnotes and Abbreviations
- Special attributes on headers, links and images via `{#id .class1 .class2}`

The library works but is **not** stable enough to be used on production environments. In comparision with other markdown parsers, this one is
slow! Very slow! And It takes indentation very seriously, maybe a little too much.

Im not giving support for this library, this was just a hobby project. Some parts of the library could be done more elegantly and perhaps
later I will cleanup the codebase, but Im not making any promises.

I think the best alternative, if you are looking for an extandable markdown parser is [Ciconia](https://github.com/kzykhys/Ciconia)! Elegant
and stable, requires PHP 5.4.

Requirements
============
- PHP >= 5.3

Installation
============

### Install with Composer
If you're using [Composer](https://github.com/composer/composer) to manage
dependencies, you can use this library by creating a composer.json and adding this:

    {
        "require": {
            "mpratt/luthor": "dev-master"
        }
    }

Save it and run `composer.phar install`

### Standalone Installation (without Composer)
Download the latest release or clone this repository, place the `Lib/Luthor` directory on your project. Afterwards, you only need to include
the Autoload.php file.

```php
    require '/path/to/Luthor/Autoload.php';
    $embera = new \Luthor\Luthor();
```

Or if you already have PSR-0 complaint autoloader, you just need to register Luthor
```php
    $loader->registerNamespace('Luthor', 'path/to/Luthor');
```

Basic Usage
===========

```php
    $lex = new \Luthor\Luthor();
    echo $lex->parse('**I dont like Superman**');
    // <p><strong>I dont like Superman</strong></p>
```

In order to extend the lexer/parser you need to create a new class extending the InlineAdapter or BlockAdapter.
Use the `addExtension()` method to register the extension
```php

    class MyExtension extends \Luthor\Parser\Extensions\Adapters\InlineAdapter
    {
        protected $regex = '~^([^ ]+)~A';
        public function parse()
        {
            return '<strong>' . $this->matches['1'] . '</strong>';
        }
    }

    $lex = new \Luthor\Luthor();
    $lex->addExtension(new MyExtension());
    echo $lex->parse('I love ^Luthor !');
    // <p>I love <strong>Luthor</strong></p>
```

Filters are runned when the text is already processed
```php
    $lex = new \Luthor\Luthor();
    $lex->addFilter(function ($text){
        return str_replace('Hello', 'World', $text);
    });

    echo $lex->parse('Hello World!');
    // <p>World World!</p>
```

Take a look at the Lib or Tests directory in order to see other configuration options.

License
=======
**MIT**
For the full copyright and license information, please view the LICENSE file.

Why Oh' Why?
===========
Yeah, I know right?, _another_ markdown parser? really? - You know, there are days where you think to yourself, stuff like
"hey, I wanna learn more about _X_, so Im going to write _Y_ and see what happens"?, Well that happened to me a long time ago,
and started coding this lexer/parser just for fun/learning. The thing is, I never finished it.

So one day I was digging around my "un-finished projects" folder and found about half of this code base and decided it was time
to either finish it or delete it. I end up rewriting most of the code, there are bits here and there that could be done with more
though in mind, but in the end I think it turned out not that bad.

Author
=====
Hi! I'm Michael Pratt and I'm from Colombia!

My [Personal Website](http://www.michael-pratt.com) is in spanish.
