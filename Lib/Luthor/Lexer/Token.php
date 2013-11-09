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
     * Normalizes Attributes passed to this token
     *
     * @param string $attr
     * @return string
     */
    protected function normalizeAttr($attr)
    {
        $return = '';
        if (preg_match('~^(id=|class=)~', $attr)) {
            return $attr;
        } else if (preg_match('~{(.+)}~', $attr, $m)) {
            $hasId = false;
            $classes = array();
            $attributes = explode(' ', $m['1']);
            foreach ($attributes as $a) {
                $a = trim($a);
                if (!$hasId && substr($a, 0, 1) == '#') {
                    $return .= ' id="' . trim($a, '#') . '"';
                    $hasId = true;
                } else {
                    $classes[] = trim($a, '.');
                }
            }

            if (!empty($classes)) {
                $return .= ' class="' . implode(' ', $classes) . '"';
            }

            $attr = preg_replace('~{(.+)}~', '', $attr);
            $this->content .= $attr;

            return trim($return);
        }

        return $return;
    }
}

?>
