<?php
/**
 * TokenMap.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Lexer;

/**
 * Class with defined with the relevant regex and tokens.
 * Basically an overdone array
 */
class TokenMap implements \IteratorAggregate
{
    /** @var Array With regex and token name relation */
    protected $tokens = array(
        '~(?:([\*] ?){3,})$~A' => 'HR', // Horizontal Rule ***
        '~(?:([\-] ?){3,})$~A' => 'HR', // Horizontal Rule ---
        '~( {12}\- ?)~A' => 'LISTBLOCK_INDENT_12',
        '~( {8}\- ?)~A' => 'LISTBLOCK_INDENT_8',
        '~( {4}\- ?)~A' => 'LISTBLOCK_INDENT_4',
        '~(\- ?)~A' => 'LISTBLOCK',
        '~(([\*]{1,2})([^\*]+)(?:[\*]{1,2}))~A' => 'INLINE_ELEMENT', // Catch **hi**
        '~(([\_]{1,2})([^_]+)(?:[_]{1,2}))~A' => 'INLINE_ELEMENT',   // Catch __hi__
        '~(([`]{1,2})([^`]+)(?:[`]{1,2}))~A' => 'INLINE_ELEMENT',    // Catch `hi`
        '~(([\~]{2})([^\~]+)(?:[\~]{2}))~A' => 'INLINE_ELEMENT',     // Catch ~~hi~~ for striked out text
        '~(!\[([^\[]+)\]\(([^\)]+)\)(?:{(?:.|#).*})?)~A' => 'INLINE_IMG', // Inline Images
        '~(\[([^\[]+)\]\(([^\)]+)\)(?:{(?:.|#).*})?)~A' => 'INLINE_LINK', // Inline links
        '~(\!?\[([^\]]+)\] ?\[(.*?)\])~A' => 'INLINE_REFERENCE', // Image or link references [hi][id]
        '~(\s*\[([^\]\^]+)\] ?\: ?(.+)(?:{(?:.|#).*})?)$~A' => 'REFERENCE_DEFINITION', // [id]: http...
        '~(\s*\[\^([^\^ \]]+)\] ?\: ?(.+))$~A' => 'FOOTNOTE_DEFINITION', // [^id]: Definition
        '~(\s*\*\[([^\]]+)\] ?\: ?(.+))$~A' => 'ABBR_DEFINITION', // *[id]: Definition
        '~(.+(?:=+|-+))$~A' => 'H_SETEXT', // Setext Headers
        '~(#{1,}(?:.+))~A' => 'H_ATX', // Atx type Headers
        '~> {4}> ?~A' => 'BLOCKQUOTE_INDENT_4', // Blockquote Marker at the start of a line
        '~> ?~A' => 'BLOCKQUOTE', // Blockquote Marker at the start of a line
        '~```(?:{(?:.|#).*})?$~A' => 'FENCED_CODEBLOCK', // Fenced codeblock
        '~ {4}~A' => 'CODEBLOCK', // Code indented with 4 spaces
        '~(<https?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))>)~iA' => 'URL',
        '~(<(?:mailto:)?[^ ]+@[^ ]+>)~A' => 'EMAIL',
        '~[^\+`\[\]\(\)\{\}\*\-\=_\!\~\>\<]+~A' => 'RAW', // Everything else
    );

    /**
     * Construct
     *
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->config = $config;
    }

    /**
     * Adds a new regex => token relation to the class.
     * Instead of appending the info at the end of the
     * array, it prepends it.
     *
     * @param string $rule Regex
     * @param string $token token name
     * @return void
     */
    public function add($rule, $token)
    {
        $this->tokens = array_merge(
            array($rule => strtoupper($token)),
            $this->tokens
        );
    }

    /**
     * Required Method for the \IteratorAggregate Interface
     *
     * @return object Instance of ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->tokens);
    }
}

?>
