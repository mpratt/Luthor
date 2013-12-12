<?php
/**
 * InlineInterface.php
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
 * This is the interface that all inline extensions
 * should follow
 */
interface InlineInterface extends SetterGetterInterface
{
    /**
     * Returns the raw content of the extension
     *
     * @return string
     */
    public function raw();

    /**
     * Returns the parsed content
     *
     * @return string
     */
    public function parse();
}

?>
