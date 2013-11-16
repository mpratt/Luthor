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
    /** @var string Chars reserved by markdown */
    const MARKDOWN_RESERVED = '\\`*_{}[]()#+-.!';

    /** @var string Chars reserved by Luthor */
    const LUTHOR_RESERVED = '<>=~';

    /** @var Array With regex and token name relation */
    protected $rules = array();

    /**
     * Construct
     *
     * @param array $config
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->config = array_replace_recursive(array(
            'max_nesting' => 4,
            'indent_trigger' => 4,
            'additional_reserved' => ''
        ), $config);

        $this->rules = $this->buildRules();
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
        // Prepend the new rule
        $this->rules = array_merge(
            array('~' . $rule . '~A' => strtoupper($token)),
            $this->rules
        );
    }

    /**
     * Builds the regex taking into account
     * the given configuration directives.
     *
     * @return array
     */
    protected function buildRules()
    {
        $nesting = (int) $this->config['max_nesting'];
        $indent  = (int) $this->config['indent_trigger'];
        $rules = array();

        // Horizontal Rule ***
        $rules['~(?:([\*] ?){3,})$~A'] = 'HR';

        // Horizontal Rule ---
        $rules['~(?:([\-] ?){3,})$~A'] = 'HR';

        // Generate LISTBLOCK indents
        for ($i = $nesting; $i > 0; $i--) {
            $num = ($i*$indent);
            $rules['~( {' . $num . '}([\-\+\*]|(\d)+\.)\s+)~A'] = 'LISTBLOCK_INDENT_' . $i;
        }
        $rules['~(([\-\+\*]|(\d)+\.)\s+)~A'] = 'LISTBLOCK';

        // Catch **hi**
        $rules['~(([\*]{1,2})([^\*]+)(?:[\*]{1,2}))~A'] = 'INLINE_ELEMENT';

        // Catch __hi__
        $rules['~(([\_]{1,2})([^_]+)(?:[_]{1,2}))~A'] = 'INLINE_ELEMENT';

        // Catch `hi`
        $rules['~(([`]{1,2})([^`]+)(?:[`]{1,2}))~A'] = 'INLINE_ELEMENT';

        // Catch ~~hi~~ for striked out text
        $rules['~(([\~]{2})([^\~]+)(?:[\~]{2}))~A'] = 'INLINE_ELEMENT';

        // Inline Images
        $rules['~(!\[([^\[]+)\]\(([^\)]+)\)(?:{(?:.|#).*})?)~A'] = 'INLINE_IMG';

        // Link with image inside
        $rules['~(\[\!\[([^\[]+)\]\(([^\)]+)\)\]\(([^\)]+\))(?:{(?:.|#).*})?)~A'] = 'INLINE_IMG_LINK';

        // Inline links
        $rules['~(\[([^\[]+)\]\(([^\)]+)\)(?:{(?:.|#).*})?)~A'] = 'INLINE_LINK';

        // Image or link references [hi][id]
        $rules['~(\!?\[([^\]]+)\] ?\[(.*?)\])~A'] = 'INLINE_REFERENCE';

        // [id]: http...
        $rules['~(\s*\[([^\]\^]+)\] ?\: ?(.+)(?:{(?:.|#).*})?)$~A'] = 'REFERENCE_DEFINITION';

        // [^id]: Definition
        $rules['~(\s*\[\^([^\^ \]]+)\] ?\: ?(.+))$~A'] = 'FOOTNOTE_DEFINITION';

        // *[id]: Definition
        $rules['~(\s*\*\[([^\]]+)\] ?\: ?(.+))$~A'] = 'ABBR_DEFINITION';

        // Setext Headers
        $rules['~(.+(?:=+|-+))$~A'] = 'H_SETEXT';

        // Atx type Headers
        $rules['~(#{1,}(?:.+))$~A'] = 'H_ATX';

        // Generate Blockquote indents
        for ($i = $nesting; $i > 0; $i--) {
            $num = ($i*$indent);
            $rules['~>(?:[ ]{' . $num . '})> ?~A'] = 'BLOCKQUOTE_INDENT_' . $i;
        }
        $rules['~> ?~A'] = 'BLOCKQUOTE';

        // Generate Block code rules
        $rules['~```(?:{(?:.|#).*})?$~A'] = 'FENCED_CODEBLOCK';
        $rules['~ {' . $indent . '}~A'] = 'CODEBLOCK';

        // <email@domain.com>
        $rules['~(<(?:mailto:)?[^ ]+@[^ ]+>)~A'] = 'EMAIL';

        // <https://link.com>
        $rules['~(<https?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))>)~iA'] = 'URL';

        // Add RAW token rules and escapes at the end
        return array_merge($rules, $this->buildRawRules());
    }

    /**
     * Builds the RAW and ESCAPED token regex.
     * Takes into account configuration directives.
     *
     * @return array
     */
    protected function buildRawRules()
    {
        $reserved = self::MARKDOWN_RESERVED;
        $reserved .= self::LUTHOR_RESERVED;
        $reserved .= $this->config['additional_reserved'];
        $chars = array_unique(str_split($reserved));

        $escaped = array();
        foreach ($chars as $c){
            $escaped[] = preg_quote($c, '~');
        }

        return array(
            '~\\\\([' . implode('|', $escaped) . '])~A' => 'ESCAPED',
            '~[^' . implode('', $escaped) . ']+~A' => 'RAW',
        );
    }

    /**
     * Required Method for the \IteratorAggregate Interface
     *
     * @return object Instance of ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->rules);
    }
}
?>
