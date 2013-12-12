<?php
/**
 * InlineReference.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Filters;

use Luthor\Parser\Extensions\Reference,
    Luthor\Parser\Extensions\ImagesLinks;

/**
 * Parses Inline References
 */
class InlineReference extends ImagesLinks
{
    public function translate($text)
    {
        // Reference definitions
        if (preg_match_all('~(\s*(?:[^\*])\[([^\]\^]+)\] ?\: ?(.+)(?:{(?:.|#).*})?)~', $text, $matches, PREG_SET_ORDER)) {
            $text = $this->ref($matches, $text);
        }

        // ABBR
        if (preg_match_all('~(\s*\*\[([^\]]+)\] ?\: ?(.+)(?:{(?:.|#).*})?)~', $text, $matches, PREG_SET_ORDER)) {
            $text = $this->abbr($matches, $text);
        }

        // Footnotes
        if (preg_match_all('~(\s*\[\^([^\^ \]]+)\] ?\: ?(.+))~', $text, $matches, PREG_SET_ORDER)) {
            $foot = new FootNotes($matches);
            $text = $foot->append($text);
        }

        return $text;
    }

    protected function abbr(array $matches, $text)
    {
        foreach ($matches as $m) {
            $ref = new Reference();
            $ref->setContent($m);
            $attr = $ref->getAttr();
            $m['3'] = trim(preg_replace('~{(.+)}~', '', $m['3']));
            if ($attr) {
                $def = '<abbr title="' . $m['3'] . '" ' . $attr . '>' . $m['2'] . '</abbr>';
            } else {
                $def = '<abbr title="' . $m['3'] . '">' . $m['2'] . '</abbr>';
            }

            $text = preg_replace('~' . preg_quote($m['0'], '~'). '~', '', $text);
            $text = preg_replace('~\b' . preg_quote($m['2'], '~'). '~', $def, $text);
        }

        return $text;
    }

    protected function ref(array $matches, $text)
    {
        foreach ($matches as $m) {
            $text = str_replace($m['0'], '', $text);
            $m = array(
                $m['0'],
                $m['2'],
                trim(preg_replace('~{(.+)}~', '', $m['3']))
            );

            $this->setContent($m);
            $q = preg_quote($m['1'], '~');
            if (preg_match('~\!\[' . $q . '\]\[\]|\!\[([^\]]+)\]\[' . $q . '\]~', $text)) {
                $this->content = '!' . $this->content;
            }

            if (preg_match('~\[([^]]+)\]\[' . $q . '\]~', $text, $id)) {
                $this->matches['1'] = $id['1'];
            }

            $text = preg_replace('~\!?(\[' . $q . '\]\[\]|\[([^\]]+)\]\[' . $q . '\])~', $this->parse(), $text);
        }

        return $text;
    }
}

?>
