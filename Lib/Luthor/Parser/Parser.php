<?php
/**
 * Parser.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser;

/**
 * Class is the Parser
 */
class Parser
{
    protected $collection;
    protected $holder = array(
        'FOOTNOTE_DEFINITION' => array(),
        'ABBR_DEFINITION' => array(),
    );
    protected $operations = array(
        'LINE' => 'newLine',
        'H_SETEXT' => 'headerSetext',
        'H_ATX' => 'headerAtx',
        'INLINE_LINK' => 'links',
        'INLINE_IMG' => 'images',
        'INLINE_ELEMENT' => 'inline',
        'HR' => 'setHr',
        'BLOCKQUOTE_MK' => 'open',
        'CLOSE_BLOCKQUOTE_MK' => 'close',
        'CODEBLOCK_MK' => 'code',
        'CLOSE_CODEBLOCK_MK' => 'codeClose',
        'LISTBLOCK_MK' => 'listH',
        'CLOSE_LISTBLOCK_MK' => 'listClose',
        'LISTBLOCK_INDENT_MK' => 'listH',
        'CLOSE_LISTBLOCK_INDENT_MK' => 'listClose',
        'RAW' => '',
    );

    public function __construct(\Luthor\Lexer\TokenCollection $collection)
    {
        $this->collection = $collection;
    }

    public function parse()
    {
        $output = array();
        //print_r($this->collection);
        foreach ($this->collection as $token) {

            if (in_array($token->type, array('FOOTNOTE_DEFINITION', 'ABBR_DEFINITION'))) {
                $this->holder[$token->type][] = $token;
                continue ;
            }

            if (!isset($this->operations[$token->type])) {
                throw new \LogicException(sprintf('Missing operation for type "%s"', $token->type));
            }

            $operation = $this->operations[$token->type];
            $string = $this->run($operation, $token);

            if (isset($output[$token->line])){
                $output[$token->line] .= $string;
            } else {
                $output[$token->line] = $string;
            }
        }

        $output = implode("\n", $output);
        $output = $this->replaceHolders($output);

        return trim($output, "\n");
    }

    public function listH($token)
    {
        return "<ul><li>\n";
    }

    public function listClose($token)
    {
        return "</li></ul>\n";
    }

    public function code()
    {
        return '<pre><code>' . "\n";
    }

    public function codeClose()
    {
        return '</code></pre>' . "\n";
    }

    public function open($token)
    {
        $tag = strtolower(preg_replace('~_MK$~', '', $token->type));
        return '<' . trim($tag) . '>' . "\n";
    }

    public function close($token)
    {
        $tag = strtolower(preg_replace('~^CLOSE_|_MK$~', '', $token->type));
        return '</' . trim($tag) . '>';
    }

    protected function replaceHolders($text)
    {
        if (!empty($this->holder['FOOTNOTE_DEFINITION'])) {
            $append = array('<div class="footnotes"><hr /><ol>');
            foreach ($this->holder['FOOTNOTE_DEFINITION'] as $key => $token) {
                $key += 1;
                $id = '<sup id="fnref-' . $key . '"><a href="#fn-' . $key . '" rel="footnote">' . $key . '</a></sup>';
                $text = str_replace('[^' . $token->matches['2'] . ']', $id, $text);

                $append[] = '<li id="fn-' . $key . '">' . $token->matches['3'] . '
                    <a href="#fnref-' . $key . '" class="footnote-backref">&#8617;</a>
                    </li>';
            }

            $append[] = '</ol></div>';
            $text = $text . "\n\n" . preg_replace('~\s+~', ' ', implode('', $append));
        }

        foreach ($this->holder['ABBR_DEFINITION'] as $key => $token) {
            $def = '<abbr title="' . $token->matches['3'] . '">' . $token->matches['2'] . '</abbr>';
            $text = preg_replace('~\b' . preg_quote($token->matches['2'], '\b~'). '~', $def, $text);
        }

        return $text;
    }

    protected function run($operation, $token)
    {
        if (method_exists($this, $operation)) {
            return $this->{$operation}($token);
        } elseif (is_callable($operation)){
            return call_user_func($operation, $token);
        }

        return $token->content;
    }

    protected function newLine()
    {
        return "\n";
    }

    protected function headerSetext($token)
    {
        $h = (preg_match('~-$~', trim($token->content)) ? '2' : '1');
        if (empty($token->attr)) {
            return '<h' . $h . '>' . rtrim($token->content, ' -=') . '</h' . $h . '>';
        }

        return '<h' . $h . ' ' . $token->attr . '>' . rtrim($token->content, ' -=') . '</h' . $h . '>';
    }

    protected function headerAtx($token)
    {
        list($h, $content) = explode(' ', $token->content, 2);
        $h = min(strlen(trim($h)), 6);

        if (empty($token->attr)) {
            return '<h' . $h . '>' . trim($content, ' #') . '</h' . $h . '>';
        }

        return '<h' . $h . ' ' . $token->attr . '>' . rtrim($content, ' #') . '</h' . $h . '>';
    }

    protected function links($token)
    {
        $inner = trim($token->matches['2']);
        $href  = trim($token->matches['3']);
        $title = '';

        $href = preg_replace('~\s*\[(.+)\] ?\: ?~', '', $href);
        if (preg_match('~(\"|&quot;)(.*)(\"|&quot;)~', $href, $m)) {
            $href = trim(str_replace($m['0'], '', $href));
            $title = trim($m['2']);
        }

        if (empty($token->attr)) {
            return '<a href="' . $href . '" title="' . $title . '">' . $inner . '</a>';
        }

        return '<a href="' . $href . '" title="' . $title . '" ' . $token->attr . '>' . $inner . '</a>';
    }

    protected function images($token)
    {
        $alt = trim($token->matches['2']);
        $src = trim($token->matches['3']);
        $title = '';

        $src = preg_replace('~\s*\[(.+)\] ?\: ?~', '', $src);
        if (preg_match('~(\"|&quot;)(.*)(\"|&quot;)~', $src, $m)) {
            $src = trim(str_replace($m['0'], '', $src));
            $title = trim($m['2']);
        }

        if (empty($token->attr)) {
            return '<img src="' . $src . '" alt="' . $alt . '" title="' . $title . '" />';
        }

        return '<img src="' . $src . '" alt="' . $alt . '" title="' . $title . '" ' . $token->attr . ' />';
    }

    protected function inline($token)
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

    protected function setHr()
    {
        return '<hr/>';
    }
}

?>
