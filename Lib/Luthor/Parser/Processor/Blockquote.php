<?php
/**
 * Blockquote.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Processor;

/**
 * Manages Blockquotes
 */
class Blockquote
{
    /**
     * Opens a new blockquote
     *
     * @return string
     */
    public function open()
    {
        return '<blockquote>' . "\n";
    }

    /**
     * Closes a new blockquote
     *
     * @return string
     */
    public function close()
    {
        return '</blockquote>';
    }
}

?>
