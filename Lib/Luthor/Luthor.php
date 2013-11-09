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

    /** @var object Instance of \Luthor\Lexer\Lexer */
    protected $lexer;

    /**
     * Construct
     *
     * @param array $config Associative array with configuration directives
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->config = array_merge(array(
            'tab_width' => 4,
            'escape' => false,
        ), $config);

        $this->lexer = new Lexer\Lexer($this->config);
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
        $tokenCollection = $this->lexer->getTokens($text);

        $parser = new Parser\Parser($tokenCollection);

        return $parser->parse();
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
        $text = preg_replace('~\t~', str_repeat(' ', $this->config['tab_width']), $text);
        $text = preg_replace('~^[ ]+$~', '', $text);

        if ($this->config['escape']) {
            $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
            $text = preg_replace_callback('~^(&gt;)+~m', function ($m) {
                return str_replace('&gt;', '>', $m[0]);
            }, $text);
        }

        // Append Setext type headings into the previous line
        $text = preg_replace('~([^\s]*)\n(=+|-+)[ ]*\n~', "$1\$2\n", $text);

        return trim($text, "\n") . "\n\n";
    }
}

?>
