<?php
/**
 * FencedCodeblock.php
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
 * This is a token defintion for FencedCodeblocks
 */
class FencedCodeblock extends BlockAdapter
{
    /** inline {@inheritdoc} */
    public $priority = 80;

    /** inline {@inheritdoc} */
    protected $regex = '~```(?:{(?:.|#).*})?$~A';

    /** inline {@inheritdoc} */
    protected $allowsAttributes = true;

    /** inline {@inheritdoc} */
    public function getIndentation()
    {
        return 1;
    }

    /** inline {@inheritdoc} */
    public function getContext()
    {
        if (!empty($this->attr)) {
            $tag = '<pre ' . $this->attr . '><code>';
        } else {
            $tag = '<pre><code>';
        }

        $context = array(
            'indent' => $this->indent,
            'line' => $this->line,
            'type' => $this->getType(),
            'ignore' => array('CODEBLOCK:1'),
            'close_on' => array($this->getType()),
            'close_html' => "</code></pre>",
            'create_html' => "$tag",
            'to_raw' => array('*'),
        );

        return new Context($context);
    }
}

?>
