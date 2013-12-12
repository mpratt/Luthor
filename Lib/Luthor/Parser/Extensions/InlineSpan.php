<?php
/**
 * InlineSpan.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Extensions;

use Luthor\Parser\Extensions\Adapters\InlineAdapter;

/**
 * This is a extension for InlineSpan input.
 */
class InlineSpan extends InlineAdapter
{
    /** inline {@inheritdoc} */
    public function getRegex()
    {
        $regex = array(
            '(([`]{1})([^`]+?)(?:[`]{1}))', // Catches `hi`
            '(([\*]{1,2})([^\*]+?)(?:[\*]{1,2}))', // Catches **hi**
            '(([_]{1,2})([^_]+?)(?:[_]{1,2}))', // Catches __hi__
            '(([\~]{2})([^\~]+?)(?:[\~]{2}))' // Catches ~~hi~~
        );

        return '~(' . implode('|', $regex) . ')~A';
    }

    /** inline {@inheritdoc} */
    public function parse()
    {
        $count = count($this->matches);
        $content = $this->matches[($count - 1)];
        $tag = $this->matches[($count - 2)];

        if (trim($tag, ' `') == '') {
            $tag = 'code';
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8', false);
        } else if (trim($tag, ' ~') == ''){
            $tag = 'del';
        } elseif (strlen(trim($tag)) >= 2) {
            $tag = 'strong';
        } else {
            $tag = 'em';
        }

        return sprintf('<%s>%s</%s>', $tag, $content, $tag);
    }
}

?>
