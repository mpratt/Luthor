<?php
/**
 * Lists.php
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
 * Manages Lists in HTML
 */
class Lists
{
    /**
     * Opens a new list
     *
     * @return string
     */
    public function openList()
    {
        return '<ul>';
    }

    /**
     * Closes a list
     *
     * @return string
     */
    public function closeList()
    {
        return '</ul>';
    }

    /**
     * Opens a new list Element
     *
     * @return string
     */
    public function openElement()
    {
        return '<li>';
    }

    /**
     * Opens a new list Element
     *
     * @return string
     */
    public function closeElement()
    {
        return '</li>';
    }
}

?>