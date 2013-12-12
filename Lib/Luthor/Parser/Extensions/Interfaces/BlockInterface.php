<?php
/**
 * BlockInterface.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Extensions\Interfaces;

/**
 * This is the interface that all block extensions
 * should follow
 */
interface BlockInterface extends InlineInterface
{
    /**
     * Determines the indentation of the current block
     *
     * @return int
     */
    public function getIndentation();

    /**
     * Gets a unique id for this block
     *
     * @return string
     */
    public function getId();

}

?>
