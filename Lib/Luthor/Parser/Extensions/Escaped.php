<?php
/**
 * Escaped.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Extensions;

/**
 * This is a extension for Escaped input.
 * Meaning, it detects escaped reserved chars.
 */
class Escaped extends Raw
{
    /** inline {@inheritdoc} */
    public $priority = 2;

    /** inline {@inheritdoc} */
    public function parse()
    {
        return trim($this->content, '\\');
    }

    /** inline {@inheritdoc} */
    public function raw()
    {
        return $this->parse();
    }

    /** inline {@inheritdoc} */
    public function getRegex()
    {
        return '~\\\\([' . implode('|', $this->getReservedChars()) . '])~A';
    }
}

?>
