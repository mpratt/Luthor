<?php
/**
 * Luthor.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor;

/**
 * The Main Class of this library
 */
class Luthor
{
    /** @var int Class constant with the current Version of this library */
    const VERSION = '0.1';

    /** @var array Associative array with configuration directives */
    protected $config = array();

    /** @var object Instance of \Luthor\Lexer\TokenMap */
    protected $tokenMap;

    /** @var object Instance of \Luthor\Lexer\Lexer */
    protected $lexer;

    /** @var object Instance of \Luthor\Parser\Parser */
    protected $parser;

    /**
     * Construct
     *
     * @param array $config Associative array with configuration directives
     * @return void
     *
     * Posible configuration options
     * auto_p -> bool | Wether or not to automatically add "<p>" tags.
     * auto_p_strategy -> string | The Strategy, "autoParagraph" or "autoParagraph2"
     *                             The first one is based on wordpress wpautop and the second one
     *                             is based on Kohanas auto_p
     * allow_html -> bool | Wether or not to run htmlspecialchars before lexing/parsing
     * max_nesting -> int | How many indentation levels are allowed/detected
     * tab_to_spaces -> int | Converts tabs into "x" spaces
     * indent_trigger -> int | How many spaces trigger an indent
     * additional_reserved -> string | Add custom chars into the reserved space
     *                                 Important for custom token creation.
     * ignore_attr -> array | Array with token types where we should ignore attribute flags {#id} or {.class}
     * force_line_start -> array | Array with token types that *must* only be valid when the line starts.
     */
    public function __construct(array $config = array())
    {
        $this->config = array_replace_recursive(array(
            'auto_p' => true,
            'auto_p_strategy' => 'autoParagraph',
            'allow_html' => true,
            'max_nesting' => 4,
            'tab_to_spaces' => 4,
            'indent_trigger' => 4,
            'additional_reserved' => ''
        ), $config);

        $this->tokenMap = new Lexer\TokenMap($this->config);
        $this->lexer    = new Lexer\Lexer($this->config);
        $this->parser   = new Parser\Parser($this->config);

        if ($this->config['auto_p']){
            $this->addFilter(
                array(new \Luthor\Parser\Filters\Paragraph, $this->config['auto_p_strategy'])
            );
        }

        // htmlspecialchars inside codeblocks
        $this->addFilter(function ($text) {
            return preg_replace_callback('~<pre(.*)><code>(.*)</code></pre>~ms', function ($m) {
                return '<pre' . $m['1'] . '><code>' . htmlspecialchars($m['2'], ENT_QUOTES, 'UTF-8', false) . '</code></pre>';
            }, $text);
        });


    }

    /**
     * Parses the given markdown input into Html
     *
     * @param string $text Markdown text
     * @return string The HTML output
     */
    public function parse($text)
    {
        $text = $this->prepareText($text);

        $this->lexer->setMap($this->tokenMap);

        $tokens = $this->lexer->getTokens($text);
        $parsed = $this->parser->parse($tokens);

        return $parsed;
    }

    /**
     * Does some cleanup and normalization to the given
     * text.
     *
     * - Removes UTF-8 Bom markers when available.
     * - Normalizes line breaks.
     * - Replaces Tabs with configured spaces.
     * - Converts &gt; on starting lines with regular '>' for
     *   blockquotes - when the escaping directive is enabled.
     *
     * @param string $text
     * @return string
     */
    protected function prepareText($text)
    {
        $text = preg_replace('~^\xEF\xBB\xBF|\x1A~', '', $text);
        $text = preg_replace('~\r\n?~', "\n", $text);
        $text = preg_replace('~\t~', str_repeat(' ', $this->config['tab_to_spaces']), $text);
        $text = preg_replace('~^[ ]+$~', '', $text);

        if ($this->config['allow_html']) {
            /**
             * Trim starting whitespace on each line that seems to contain html
             * So that it doesnt get converted into code blocks or something else.
             */
            $text = preg_replace('~^[ \t]+\<~m', '<', $text);
        } else {
            $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
            $text = preg_replace_callback('~^(&gt;)+~m', function ($m) {
                return str_replace('&gt;', '>', $m[0]);
            }, $text);
        }

        // Append Setext type headings into the previous line
        $text = preg_replace('~([^\s]*)\n(=+|-+)[ ]*\n~', "$1\$2\n", $text);

        // Add a couple of new lines at the end of the text
        return trim($text, "\n") . "\n\n";
    }

    /**
     * Registers a new regex => token relation to the class.
     *
     * @param string $rule Regex
     * @param string $token token name
     * @param callable $operation the functiont to be called on the found token
     * @return void
     */
    public function addTokenOperation($rule, $token, $operation)
    {
        $this->tokenMap->add($rule, strtoupper($token));
        $this->overwriteOperation(strtoupper($token), $operation);
    }

    /**
     * Overwrites a token operation
     *
     * @param string $token token name
     * @param callable $operation the functiont to be called on the found token
     * @return void
     */
    public function overwriteOperation($token, $operation)
    {
        $this->parser->addOperation($token, $operation);
    }

    /**
     * Adds a new filter into the parser
     *
     * @param mixed $func A Callable function/method to be used as a filter
     * @return void
     */
    public function addFilter($func)
    {
        $this->parser->addFilter($func);
    }
}

?>
