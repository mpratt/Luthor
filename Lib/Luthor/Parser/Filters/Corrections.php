<?php
/**
 * Corrections.php
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
 * Filter used to make minor html corrections
 */
class Corrections
{
    /**
     * Minor corrections to the finished html
     *
     * @param string $text
     * @return string
     */
    public function correct($text)
    {
        // Remove empty lists
        if (strpos($text, '<li>') !== false) {
            $text = preg_replace("|<li>\s*</li>|m", "", $text);
            $text = preg_replace("|\n</li>|m", "</li>", $text);
            $text = preg_replace("|\s*</li>\s*</li>|m", "</li>", $text);
        }

        // Remove double </code></pre><pre></code>
        if (strpos($text, '<pre') !== false) {
            $text = preg_replace("~^</code></pre>\s*<pre><code>$~ms", "", $text);
        }

        return $text;
    }
}

?>
