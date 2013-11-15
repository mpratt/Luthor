<?php
/**
 * Paragraph.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Filters;

/**
 * Manages Paragraphs and new lines, after the markdown
 * text was already converted
 */
class Paragraph
{
    /** @var array stores content inside <pre> tags */
    protected $preTags = array();

    /** @var string blocks */
    protected $blocks;

    /**
     * Construct
     *
     * @return void
     */
    public function __construct()
    {
        // Elements that should not be surrounded by p tags
        $blocks = array(
            'table', 'thead', 'tfoot', 'caption', 'col', 'colgroup', 'tbody', 'tr', 'td', 'th', 'div',
            'dl', 'dd', 'dt', 'ul', 'ol', 'li', 'pre', 'select', 'option', 'form', 'map', 'area', 'blockquote',
            'address', 'math', 'style', 'p', 'h[1-6r]', 'fieldset', 'noscript', 'legend', 'section', 'article',
            'aside', 'hgroup', 'header', 'footer', 'nav', 'figure', 'figcaption', 'details', 'menu', 'summary',
        );

        $this->blocks = '(?:' . implode('|', $blocks) . ')';
    }

    /**
     * Automatically applies "p" and "br" markup to text.
     * Based on Wordpress's wpautop function.
     *
     * @param string $text
     * @return string
     * @link http://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
     */
    public function autoParagraph($text)
    {
        // Reserve content that should not be trimmed
        $text = $this->reserve($text);

        // Trim starting whitespace on each line
        $text = preg_replace('~^[ \t]+~m', '', $text);

        // Space things out a little
        $text = preg_replace('!(<' . $this->blocks . '[^>]*>)!', "\n$1", $text);
        $text = preg_replace('!(</' . $this->blocks . '>)!', "$1\n\n", $text);

        // No "<p>" inside object/embed tags
        if (strpos($text, '<object') !== false ) {
            $text = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $text);
            $text = preg_replace('|\s*</embed>\s*|', '</embed>', $text);
        }

        // take care of duplicate line breaks
        $text = preg_replace("/\n\n+/", "\n\n", $text);

        // make paragraphs, including one at the end
        $content = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $text = '';
        foreach ($content as $c) {
            $text .= '<p>' . trim($c, "\n") . "</p>\n";
        }

        // Remove empty paragraphs
        $text = preg_replace('|<p>\s*</p>|', '', $text);
        $text = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $text);

        // Dont <p> all over a tag
        $text = preg_replace('!<p>\s*(</?' . $this->blocks . '[^>]*>)\s*</p>!', "$1", $text);

        // Manage nested <li>, <blockquote>, etc
        $text = preg_replace("|<p>(<li.+?)</p>|", "$1", $text);
        $text = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $text);
        $text = str_replace('</blockquote></p>', '</p></blockquote>', $text);
        $text = preg_replace('!<p>\s*(</?' . $this->blocks . '[^>]*>)!', "$1", $text);
        $text = preg_replace('!(</?' . $this->blocks . '[^>]*>)\s*</p>!', "$1", $text);
        $text = preg_replace('!(</?' . $this->blocks . '[^>]*>)\s*<br />!', "$1", $text);
        $text = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $text);
        $text = preg_replace("|\n</p>$|", '</p>', $text);

        // Add <br/> on lines ending with 2 spaces
        $text = preg_replace('~ {2}$~', "<br />\n", $text);

        if (!empty($this->preTags)) {
            $text = str_replace(array_keys($this->preTags), array_values($this->preTags), $text);
        }

        return $text;
    }

    /**
     * Automatically applies "p" and "br" markup to text.
     * Based on Kohana's auto_p method.
     *
     * @param string $text
     * @return string
     * @link http://kohanaframework.org/3.3/guide-api/Text#auto_p
     */
    public function autoParagraph2($text)
    {
        // Reserve content that should not be trimmed
        $text = $this->reserve($text);

        // Trim starting whitespace on each line
        $text = preg_replace('~^[ \t]+~m', '', $text);
        //$text = preg_replace('~[ \t]+$~m', '', $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found = (strpos($text, '<') !== false))
        {
            // Put at least two linebreaks before and after $thi->blocks elements
            $text = preg_replace('~^<'.$this->blocks.'[^>]*+>~im', "\n$0", $text);
            $text = preg_replace('~</'.$this->blocks.'\s*+>$~im', "$0\n", $text);
        }

        // Do the <p> magic!
        $text = '<p>'.trim($text).'</p>';
        $text = preg_replace('~\n{2,}~', "</p>\n\n<p>", $text);

        // The following regexes only need to be executed if the string contains html
        if ($html_found !== false)
        {
            // Remove p tags around $thi->blocks elements
            $text = preg_replace('~<p>(?=</?'.$this->blocks.'[^>]*+>)~i', '', $text);
            $text = preg_replace('~(</?'.$this->blocks.'[^>]*+>)</p>~i', '$1', $text);
        }

        // Add <br/> on lines ending with 2 spaces
        $text = preg_replace('~ {2}$~', "<br />\n", $text);

        if (!empty($this->preTags)) {
            $text = str_replace(array_keys($this->preTags), array_values($this->preTags), $text);
        }

        return $text;
    }

    /**
     * Reserves text inside <pre> tags
     *
     * @param string $text
     * @return string
     */
    protected function reserve($text)
    {
        // Do we need to save PREs or TEXTAREAs somewhere in placeholders?
        if (strpos($text, '<pre') !== false || strpos($text, '<textarea') !== false)
        {
            $text = preg_replace_callback('/\\s*(<textarea\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/i', array($this, 'ignore'), $text);
            $text = preg_replace_callback('/\\s*(<pre\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i', array($this, 'ignore'), $text);
        }

        return $text;
    }

    /**
     * This method is used to replace a matched tag
     * with a placeholder. The main use of this method
     * is to leave a portion of text with its original
     * white space.
     *
     * @param array $content
     * @return string
     */
    protected function ignore($content)
    {
        $placeholder = '<pre><code>%' . md5(time() . count($this->preTags) . mt_rand(0, 500)) . '%</code></pre>';
        $this->preTags[$placeholder] = $content['1'];
        return "\n\n" . $placeholder . "\n\n";
    }
}

?>
