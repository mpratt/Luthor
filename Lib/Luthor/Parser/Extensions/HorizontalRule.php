<?php
/**
 * HorizontalRule.php
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
 * This is a extension for HorizontalRule input.
 */
class HorizontalRule extends InlineAdapter
{
    /** inline {@inheritdoc} */
    public $priority = 1000;

    /** inline {@inheritdoc} */
    public $lineStart = true;

    /** inline {@inheritdoc} */
    protected $regex = '~((?:([\*] ?){3,})|(?:([\-] ?){3,}))$~A';

    /** inline {@inheritdoc} */
    public function parse()
    {
        return "\n\n" . '<hr/>' . "\n\n";
    }
}

?>
