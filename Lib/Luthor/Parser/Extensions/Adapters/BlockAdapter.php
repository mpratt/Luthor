<?php
/**
 * BlockAdapter.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Extensions\Adapters;

use Luthor\Parser\Extensions\Interfaces\BlockInterface;

/**
 * Abstract class used as an adapter for inline extensions
 */
abstract class BlockAdapter extends InlineAdapter implements BlockInterface
{
    /** inline {@inheritdoc} */
    protected $indent = null;

    /** inline {@inheritdoc} */
    public $lineStart = true;

    /** inline {@inheritdoc} */
    public function getIndentation()
    {
        $spaces = (strlen($this->content) - strlen(ltrim($this->content)));
        $indent = ceil(($spaces/$this->config['tab_to_spaces']));

        if ($indent <= 0) {
            $indent = 0;
        } elseif ($indent > ($this->config['max_nesting'] - 1)) {
            $indent = ($this->config['max_nesting'] - 1);
        }

        return $this->indent = (int) $indent;
    }

    /** inline {@inheritdoc} */
    public function getId()
    {
        return $this->getType() . ':' . $this->getIndentation();
    }

    /** inline {@inheritdoc} */
    public function getType()
    {
        return parent::getType() . ':' . $this->getIndentation();
    }
}

?>
