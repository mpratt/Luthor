<?php
/**
 * Heading.php
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
 * This is a extension for Heading input.
 */
class Heading extends InlineAdapter
{
    /** inline {@inheritdoc} */
    public $priority = 40;

    /** inline {@inheritdoc} */
    public $lineStart = true;

    /** inline {@inheritdoc} */
    protected $allowsAttributes = true;

    /** inline {@inheritdoc} */
    protected $regex = '~((.+(?:=+|-+))|(#{1,}(?:.+)))$~A';

    /** inline {@inheritdoc} */
    public function parse()
    {
        if ($this->isSetext()) {
            return $this->setext();
        }

        return $this->atx();
    }

    /**
     * Detects wether or not the heading is a Setext
     *
     * @return bool
     */
    protected function isSetext()
    {
        return (
            !preg_match('~^\s*#~', $this->content) &&
            preg_match('~(-|=)$~', $this->content)
        );
    }

    /**
     * Converts a Setext type heading into an HTML
     * heading
     *
     * @return string
     */
    protected function setext()
    {
        $level = (preg_match('~-$~', trim($this->content)) ? '2' : '1');
        if (empty($this->attr)) {
            return '<h' . $level . '>' . rtrim($this->content, ' -=') . '</h' . $level . '>';
        }

        return '<h' . $level . ' ' . $this->attr . '>' . rtrim($this->content, ' -=') . '</h' . $level . '>' . "\n\n";
    }

    /**
     * Converts an ATX type heading into an HTML
     * heading
     *
     * @return string
     */
    protected function atx()
    {
        list($level, $content) = explode(' ', $this->content, 2);
        $level = min(strlen(trim($level)), 6);

        if (empty($this->attr)) {
            return '<h' . $level . '>' . rtrim($content, ' #') . '</h' . $level . '>';
        }

        return '<h' . $level . ' ' . $this->attr . '>' . rtrim($content, ' #') . '</h' . $level . '>' . "\n\n";
    }
}

?>
