<?php
/**
 * Blockquote.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Extensions;

use Luthor\Parser\Extensions\Adapters\BlockAdapter;

/**
 * This is a token defintion for Blockquotes
 */
class Blockquote extends BlockAdapter
{
    /** inline {@inheritdoc} */
    public $priority = 60;

    /** inline {@inheritdoc} */
    public $lineStart = true;

    /** inline {@inheritdoc} */
    protected $regex = '~\s*(> ?)* ?> ?~A';

    /** inline {@inheritdoc} */
    public function getIndentation()
    {
        if (!is_null($this->indent)) {
            return $this->indent;
        }

        $indent = substr_count($this->content, '>');
        if ($indent > $this->config['max_nesting']) {
            $indent = $this->config['max_nesting'];
        }

        return $this->indent = (int) $indent;
    }

    /** inline {@inheritdoc} */
    public function parse()
    {
        return ;
    }

    /** inline {@inheritdoc} */
    public function getContext()
    {
        $context = array(
            'indent' => $this->indent,
            'line' => $this->line,
            'type' => $this->getType(),
            'close_on' => array('LINE'),
            'close_html' => "\n\n</blockquote>",
            'create_html' => "<blockquote>\n\n",
        );

        if ($this->indent > 1) {
            foreach (range(1, intval($this->indent - 1)) as $num) {
                $context['close_on'][] = 'BLOCKQUOTE:' . $num;
            }
        }

        return new Context($context);
    }
}

?>
