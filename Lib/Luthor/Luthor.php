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
     */
    public function __construct(array $config = array())
    {
        $this->config = array_replace_recursive(array(
            'tab_to_spaces' => 4,
            'escape' => false,
        ), $config);

        $this->tokenMap = new Lexer\TokenMap($this->config);
        $this->lexer    = new Lexer\Lexer($this->config);
        $this->parser   = new Parser\Parser($this->config);
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

        if ($this->config['escape']) {
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
    public function addTokenOperation($rule, $token, callable $operation)
    {
        $this->tokenMap->add($rule, strtoupper($token));
        $this->parser->addOperation($token, $operation);
    }

    /**
     * Adds a new filter into the parser
     *
     * @param mixed $func A Callable function/method to be used as a filter
     * @return void
     */
    public function addFilter(callable $func)
    {
        $this->parser->addFilter($func);
    }
}

?>
