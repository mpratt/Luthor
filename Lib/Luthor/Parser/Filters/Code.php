<?php
/**
 * Code.php
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
 * Filter used to encode funny data with htmlspecialchars
 * inside codeblocks
 */
class Code
{
    /**
     * Uses htmlspecialchars inside <code> tags
     *
     * @param string $text
     * @return string
     */
    public function encode($text)
    {
        if (strpos($text, '<code>') !== false) {
            $text = preg_replace_callback('~<code>(.*?)</code>~ms', function ($m) {
                return '<code>' . htmlspecialchars($m['1'], ENT_QUOTES, 'UTF-8', false) . '</code>';
            }, $text);
        }

        return $text;
    }
}

?>
