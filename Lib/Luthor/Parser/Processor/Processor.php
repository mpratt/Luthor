<?php
/**
 * Processor.php
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
 * Translates simple tokens
 */
class Processor
{
    /**
     * Returns the Token's raw input
     *
     * @param object $token
     * @return string
     */
    public function rawInput($token)
    {
        return $token->content;
    }

    /**
     * Returns a new line character
     *
     * @return string
     */
    public function newLine()
    {
        return "\n";
    }

    /**
     * Creates a HTML Horizontal line
     *
     * @return string
     */
    public function horizontalLine()
    {
        return "\n" . '<hr/>' . "\n";
    }
}

?>
