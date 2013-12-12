<?php
/**
 * SetterGetterInterface.php
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
 * This is the interface that all extensions should use
 */
interface SetterGetterInterface
{
    /**
     * Setter method for configuration array
     *
     * @param array $config
     * @return void
     */
    public function setConfig(array $config = array());

    /**
     * Sets the contents of this extension
     *
     * @param array $matches Array with the matches found by the definition token
     * @param int $line The line where the token was found
     * @return void
     */
    public function setContent(array $matches, $line);

    /**
     * Returns the regex used by the extension
     *
     * @return string
     */
    public function getRegex();

    /**
     * Returns the current extension Type
     *
     * @return string
     */
    public function getType();
}

?>
