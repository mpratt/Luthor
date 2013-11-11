<?php
/**
 * Inline.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Processor;

/**
 * Manages Inline HTML
 */
class Inline
{
    /** @var array Array with simple html Templates **/
    protected $templates = array(
        'link' => '<a href="{src}" title="{title}"{attr}>{name}</a>',
        'image'  => '<img src="{src}" alt="{name}" title="{title}"{attr} />',
    );

    /**
     * Converts link tokens into html links
     *
     * @param object $token Instance of \Luthor\Lexer\Token
     * @return string
     */
    public function link($token)
    {
        $replace = $this->getReplacements($token);
        return str_replace(array_keys($replace), array_values($replace), $this->templates['link']);
    }

    /**
     * Converts image tokens into html images
     *
     * @param object $token Instance of \Luthor\Lexer\Token
     * @return string
     */
    public function image($token)
    {
        $replace = $this->getReplacements($token);
        return str_replace(array_keys($replace), array_values($replace), $this->templates['image']);
    }

    /**
     * Converts inline tokens into their relevant html
     *
     * @param object $token Instance of \Luthor\Lexer\Token
     * @return string
     */
    public function span($token)
    {
        $tag = $token->matches['2'];
        $content = $token->matches['3'];

        if (trim($tag, ' `') == '') {
            $tag = 'code';
        } else if (trim($tag, ' ~') == ''){
            $tag = 'del';
        } elseif (strlen(trim($tag)) >= 2) {
            $tag = 'strong';
        } else {
            $tag = 'em';
        }

        return '<' . $tag . '>' . $content . '</' . $tag . '>';
    }

    /**
     * Creates the template replacements for a given token
     *
     * @param object $token Instance of \Luthor\Lexer\Token
     * @return array
     */
    protected function getReplacements($token)
    {
        $name = trim($token->matches['2']);
        $src  = trim($token->matches['3']);
        $title = $attr = '';

        // Remove [ .. ] from the src and extract posible title/alt
        $src = preg_replace('~\s*\[(.+)\] ?\: ?~', '', $src);
        if (preg_match('~(\"|&quot;)(.*)(\"|&quot;)~', $src, $m)) {
            $src = trim(str_replace($m['0'], '', $src));
            $title = trim($m['2']);
        }

        if (!empty($token->attr)) {
            $attr = ' ' . $token->attr;
        }

        return array(
            '{src}' => $src,
            '{name}' => $name,
            '{title}'  => $title,
            '{attr}'  => $attr,
        );
    }
}

?>