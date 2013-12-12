<?php
/**
 * Reference.php
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
 * This is a extension for Reference input.
 */
class Reference extends InlineAdapter
{
    /** inline {@inheritdoc} */
    protected $allowsAttributes = true;

    /** inline {@inheritdoc} */
    public function getAttr()
    {
        return $this->attr;
    }
}

?>
