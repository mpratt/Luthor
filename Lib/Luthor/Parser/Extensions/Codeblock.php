<?php
/**
 * Codeblock.php
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
 * This is a token defintion for Codeblocks
 */
class Codeblock extends BlockAdapter
{
    /** inline {@inheritdoc} */
    public $priority = 40;

    /** inline {@inheritdoc} */
    public function getIndentation()
    {
        return 1;
    }

    /** inline {@inheritdoc} */
    public function getRegex()
    {
        return '~ {' . abs($this->config['tab_to_spaces']) . '}~A';
    }

    /** inline {@inheritdoc} */
    public function getContext()
    {
        $context = array(
            'indent' => $this->indent,
            'line' => $this->line,
            'type' => $this->getType(),
            'close_on' => array('LINE'),
            'close_html' => "</code></pre>",
            'create_html' => "<pre><code>\n" . str_repeat(' ', abs($this->config['tab_to_spaces'])),
            'to_raw' => array('*'),
        );

        return new Context($context);
    }
}

?>
