<?php
/**
 * Lists.php
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
 * This is a token defintion for Lists
 */
class Lists extends BlockAdapter
{
    /** inline {@inheritdoc} */
    public $priority = 80;

    /** inline {@inheritdoc} */
    protected $regex = '~\s*([ >]*)?(([\-\+\*]|(\d)+\.)\s+)~A';

    /** inline {@inheritdoc} */
    public function getIndentation()
    {
        if (!is_null($this->indent)) {
            return $this->indent;
        }

        if (strpos($this->content, '>') !== false) {
            $this->content = str_replace('>', '', $this->content);
        }

        return parent::getIndentation();
    }

    /**
     * Give this block parsing habilities
     *
     * @return string
     */
    public function parse()
    {
        return '</li><li>';
    }

    /** inline {@inheritdoc} */
    public function getContext()
    {
        $tag = 'ul';
        if (preg_match('~\d+\.$~', trim($this->content))) {
            $tag = 'ol';
        }

        $context = array(
            'indent' => $this->indent,
            'line' => $this->line,
            'type' => $this->getType(),
            'close_on' => array('LINE', 'BLOCKQUOTE:1'),
            'close_html' => "</li></$tag>\n",
            'create_html' => "<$tag><li>",
            'to_raw' => array('CODEBLOCK'),
        );

        if ($this->indent > 1) {
            $context['close_on'][] = 'LISTS:0';
            foreach (range(1, intval($this->indent - 1)) as $num) {
                $context['close_on'][] = 'BLOCKQUOTE:' . $num;
                $context['close_on'][] = 'LISTS:' . $num;
            }
        } elseif ($this->indent == 1) {
            $context['close_on'][] = 'LISTS:0';
        }

        return new Context($context);
    }
}

?>
