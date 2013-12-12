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

use \Luthor\Parser\Filters\Paragraph,
    \Luthor\Parser\Filters\Corrections,
    \Luthor\Parser\Filters\Code,
    \Luthor\Parser\Filters\InlineReference,
    \Luthor\Parser\Lexer,
    \Luthor\Parser\Parser;

/**
 * The Main Class of this library
 */
class Luthor
{
    /** @var int Class constant with the current Version of this library */
    const VERSION = '0.2';

    /** @var string Chars reserved by markdown */
    const MARKDOWN_RESERVED = '\\`*_{}[]()#+-.!';

    /** @var string Chars reserved by Luthor */
    const LUTHOR_RESERVED = '<~@';

    /** @var array Associative array with configuration directives */
    protected $config = array();

    /** @var array Array with filters */
    protected $filters = array();

    /** @var object Instance of \Luthor\Parser\Lexer */
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
     * allow_html -> bool | Wether or not to run htmlspecialchars before lexing/parsing
     * max_nesting -> int | How many indentation levels are allowed/detected
     * tab_to_spaces -> int | Converts tabs into "x" spaces
     * additional_reserved -> string | Add custom chars into the reserved space
     *                                 Important for custom token creation.
     * ignore_attr -> array | Array with token types where we should ignore attribute flags {#id} or {.class}
     * force_line_start -> array | Array with token types that *must* only be valid when the line starts.
     */
    public function __construct(array $config = array())
    {
        $this->config = array_replace_recursive(array(
            'auto_p' => true,
            'allow_html' => true,
            'max_nesting' => 4,
            'tab_to_spaces' => 4,
            'reserve_chars' => ''
        ), $config);

        $reserved = self::MARKDOWN_RESERVED . self::LUTHOR_RESERVED . $this->config['reserve_chars'];
        $this->config['reserve_chars'] = implode('', array_unique(str_split($reserved)));

        $this->lexer = new Lexer($this->config);
        $this->parser = new Parser();
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

        $this->lexer->setText($text);
        $parsedText = $this->parser->parse($this->lexer);

        $filters = array_merge(
            $this->defaultFilters(),
            $this->filters
        );

        foreach ($filters as $filter) {
            $parsedText = call_user_func($filter, $parsedText);
        }

        return trim($parsedText, "\n");
    }

    /**
     * Loads the default filters that should be run when the text
     * is already processed.
     *
     * @return array
     */
    protected function defaultFilters()
    {
        $filters = array();

        // Parse Inline markdown
        $filters[] = array(new InlineReference(), 'translate');

        // Minor corrections on the finished html
        $filters[] = array(new Corrections(), 'correct');

        // Call AutoParagraph when enabled
        if ($this->config['auto_p']){
            $filters[] = array(new Paragraph(), 'autoParagraph');
        }

        return $filters;
    }

    /**
     * Adds a new filter
     * Not using the callable typehint because I want to support PHP 5.3 :(
     *
     * @param mixed $func A Callable function/method to be used as a filter
     * @return void
     *
     * @throws InvalidArgumentException when the function/method is not callable
     */
    public function addFilter($func = null)
    {
        if (!empty($func)) {
            if (!is_callable($func)) {
                throw new \InvalidArgumentException('Filter is not a callable operation');
            }

            $this->filters[] = $func;
        }
    }

    /**
     * Adds a new extension to the lexex
     *
     * @param object $extension
     * @return void
     */
    public function addExtension($extension)
    {
        $this->lexer->addExtension($extension);
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
             * Trim starting whitespace on each line that seems to contain html tags
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
        return "\n\n" . trim($text, "\n") . "\n\n";
    }
}

?>
