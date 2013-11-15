Luthor
======
[![Build Status](https://secure.travis-ci.org/mpratt/Luthor.png?branch=master)](http://travis-ci.org/mpratt/Luthor)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/mpratt/Luthor/badges/quality-score.png?s=24c42108df50eba8149dfc291f549dfe0d317ef1)](https://scrutinizer-ci.com/g/mpratt/Luthor/)
[![Code Coverage](https://scrutinizer-ci.com/g/mpratt/Luthor/badges/coverage.png?s=537bc5b18469395beb0f944222c0b15bc72c9510)](https://scrutinizer-ci.com/g/mpratt/Luthor/)

Luthor is an extendable Markdown Lexer/Parser for PHP. It converts markdown text into HTML. In other words, what it does is,
It reads the markdown text line by line and analyses its content. It uses a Lexer to tokenize each element and based on a token map,
builds a stream of tokens and passes it to the parser. The Parser operates on that token stream and returns an HTML string.

The key feature of this library, is that you can extend the token map quite easily, giving you the hability to practically have
custom notations and build your own flavored markdown. There are other cool things that can be easily done, like applying filters
or modifying the way a token is handled or displayed. It also has a bunch of configuration options which can be used to change the behaviour of the library!
See the Usage section for more information about how everything is done (not written yet, sorry). Or better yet, hop into the source and take a peak.

Here is a quick list of supported markup:
- Paragraphs and line breaks
- Headers
- Blockquotes
- Code blocks and Fenced code blocks
- Lists
- Horizontal Rules
- Span Elements (links, emphasis, code, images, striked out text)
- Element Escaping
- Footnotes and Abbreviations
- Special attributes on headers, links and images via `{#id .class1 .class2}`

Why Oh' Why?
===========
Yeah, I know right?, _another_ markdown parser? really? - You know, there are days where you say to yourself, stuff like
"hey, I wanna learn more about _X_, so Im going to write _Y_ and see what happens"?, Well that happened to me a long time ago,
and started coding this lexer/parser just for fun/learning. The thing is, I never finished it.

So one day I was digging around my "un-finished projects" folder and found about half of this code base and decided it was time
to either finish it or delete it. I end up rewriting most of the code, there are bits here and there that could be done more
elegantly, but in the end I think it turned out quite nice. So I decided to opensource it and share it with strangers!
