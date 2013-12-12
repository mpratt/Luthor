<?php
/**
 * FootNotes.php
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
 * Manages FootNotes
 */
class FootNotes
{
    /** @var array Array with footnote tokens to append at the end */
    protected $footnotes = array();

    /**
     * Construct
     *
     * @param array $footnotes
     * @return void
     */
    public function __construct(array $footnotes = array())
    {
        foreach ($footnotes as $f)
        {
            $this->footnotes[] = $f;
        }
    }

    /**
     * Appends footnotes at the end of the text when available
     *
     * @param string $text
     * @return string
     */
    public function append($text)
    {
        if (!empty($this->footnotes)) {
            $append = array('<div class="footnotes"><hr /><ol>');
            foreach ($this->footnotes as $key => $matches) {
                $text = preg_replace('~' . preg_quote($matches['0'], '~') . '~', '', $text);
                $key += 1;
                $id = '<sup id="fnref-' . $key . '"><a href="#fn-' . $key . '" rel="footnote">' . $key . '</a></sup>';
                $text = str_replace('[^' . $matches['2'] . ']', $id, $text);

                $append[] = '<li id="fn-' . $key . '">' . $matches['3'] . '
                    <a href="#fnref-' . $key . '" class="footnote-backref">&#8617;</a>
                    </li>';
            }

            $append[] = '</ol>';
            $append[] = '</div>';

            $notes = preg_replace('~^[ \t]+\<~m', '<', preg_replace('~ +~', ' ', implode("\n", $append)));
            $text = $text . $notes;
        }

        return $text;
    }

}

?>
