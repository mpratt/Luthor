<?php
/**
 * InlineAdapter.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Extensions\Adapters;

use Luthor\Parser\Extensions\Interfaces\InlineInterface;

/**
 * Abstract class used as an adapter for inline extensions
 */
abstract class InlineAdapter extends SetterGetterAdapter implements InlineInterface
{
    /** inline {@inheritdoc} */
    public function raw()
    {
        return $this->content;
    }

    /** inline {@inheritdoc} */
    public function parse()
    {
        return ;
    }
}

?>
