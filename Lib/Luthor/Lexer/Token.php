<?php
/**
 * Token.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Lexer;

/**
 * Class responsable of holding a single Token and
 * the relevant information about it.
 */
class Token
{
    /** @var string Content */
    public $content;

    /** @var string attributes, as in classes or id for this token */
    public $attr;

    /** @var string The name of the token */
    public $type;

    /** @var int line */
    public $line;

    /** @var int position */
    public $position;

    /** @var int length */
    public $length;

    /** @var Array Matched placeholders */
    public $matches = array();

    /**
     * Construct
     *
     * @param mixed $content
     * @param string $type
     * @param string $attr
     * @param int $position
     * @param int $line
     * @return void
     */
    public function __construct($content, $type, $attr, $position, $line)
    {
        if (is_array($content)) {
            $this->content = $content['0'];
            $this->matches = $content;
        } else {
            $this->content = $content;
        }

        $this->type  = $type;
        $this->position = $position;
        $this->line = $line;
        $this->attr = $this->normalizeAttr($attr);
        $this->length = strlen($this->content);
    }

    /**
     * Normalizes ids or classes passed to the token
     *
     * @param string $attr
     * @return string
     */
    protected function normalizeAttr($attr)
    {
        $return = '';
        if (preg_match('~^(id=|class=)~', $attr)) {
            return $attr;
        } elseif (preg_match('~{(.+)}~', $attr, $m)) {

            if (preg_match('~#([^ ]+)~', $m['1'], $id)) {
                $return .= ' id="' . $id['1'] . '"';
            }

            if (preg_match_all('~\.([^ ]+)~', $m['1'], $classes)) {
                $return .= ' class="' . implode(' ', $classes['1']) . '"';
            }

            return trim($return);
        }

        return $return;
    }

    /**
     * When the token is casted into a string
     * return the real content
     *
     * @return string;
     */
    public function __toString()
    {
        return $this->content;
    }
}

?>
