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
    /** @var Instance of \Luthor\Lexer\TokenCollection */
    protected $collection;

    /** @var array Array with filters */
    protected $filters = array();

    /** @var array Array with footnote tokens to append at the end */
    protected $footnotes = array();

    /** @var array The mapping for Token -> operation/processor */
    protected $operations = array();

    /**
     * Construct
     *
     * @param array $config
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->config = array_replace_recursive(array(
        ), $config);

        $this->operations = $this->buildOperations();
        $this->filters = $this->footnotes = array();
    }

    /**
     * Maps tokens to their relevant operation/processor function.
     *
     * @return array with token -> operation relationship
     */
    protected function buildOperations()
    {
        return array(
            'RAW' => function ($token) { return $token->content; },
            'LINE' => function () { return "\n"; },
            'HR' => function () { return '<hr/>'; },
            'H_SETEXT' => 'headerSetext',
            'H_ATX' => 'headerAtx',
            'INLINE_LINK' => 'links',
            'INLINE_IMG' => 'images',
            'INLINE_ELEMENT' => 'inline',
            'BLOCKQUOTE' => 'open',
            'CLOSE_BLOCKQUOTE' => 'close',
            'CODEBLOCK' => 'code',
            'CLOSE_CODEBLOCK' => 'codeClose',
        );
    }

    /**
     * Registers a new operation for a given token name
     *
     * @param string $token
     * @param mixed|callable $operation
     * @return void
     */
    public function registerOperation($token, $operation)
    {
        $this->operations[strtoupper($token)] = $operation;
    }

    /**
     * Actually parses the available tokens
     *
     * @param object $collection Instance of \IteratorAggregate
     * @return string
     * @throws LogicException When there is no available operation for a token
     */
    public function parse(\IteratorAggregate $collection)
    {
        $output = array();
        foreach ($collection as $token) {

            if ($token instanceof \IteratorAggregate) {
                $output[] = $this->parse($token);
                continue ;
            }

            if ($token->type == 'ABBR_DEFINITION') {
                $this->addFilter(function ($text)  use ($token) {
                    $def = '<abbr title="' . $token->matches['3'] . '">' . $token->matches['2'] . '</abbr>';
                    return preg_replace('~\b' . preg_quote($token->matches['2'], '\b~'). '~', $def, $text);
                });

                continue ;
            }

            if ($token->type == 'FOOTNOTE_DEFINITION') {
                $this->footnotes[] = $token;
                continue ;
            }

            if ($token->type == 'INLINE_REFERENCE') {
                $token = $collection->getDefinition($token);
            }

            if (!isset($this->operations[$token->type])) {
                throw new \LogicException(sprintf('Missing operation for type "%s"', $token->type));
            }

            $string = $this->run($this->operations[$token->type], $token);
            if (isset($output[$token->line])){
                $output[$token->line] .= $string;
            } else {
                $output[$token->line] = $string;
            }
        }

        $output = implode("\n", $output);
        $output = $this->appendFootnotes($output);

        return trim($this->runFilters($output), "\n");
    }

    /**
     * Determines the operation to be run for the given token
     *
     * @param mixed $operation Closure, method name or other callable function
     * @param object $token
     * @return string
     */
    protected function run($operation, $token)
    {
        if (is_callable($operation)) {
            return $operation($token);
        } elseif (is_string($operation) && method_exists($this, $operation)) {
            return $this->{$operation}($token);
        } else if ($token instanceof \Luthor\Lexer\Token) {
            return $token->content;
        }

        return $token;
    }

    /**
     * Runs filters on the processed text
     *
     * @param string $text
     * @return string
     */
    protected function runFilters($text)
    {
        foreach ($this->filters as $filter) {
            $text = $this->run($filter, $text);
        }

        return $text;
    }

    /**
     * Adds a new filter
     *
     * @param mixed $func A Callable function/method to be used as a filter
     * @return void
     */
    public function addFilter(Callable $func)
    {
        $this->filters[] = $func;
    }

    /**
     * Resets the filters/footnotes properties
     *
     * @return void
     */
    public function reset()
    {
        $this->filters = $this->footnotes = array();
    }

    /**
     * Appends footnotes at the end of the text when available
     *
     * @param string $text
     * @return string
     */
    protected function appendFootnotes($text)
    {
        if (!empty($this->footnotes)) {
            $append = array('<div class="footnotes"><hr /><ol>');
            foreach ($this->footnotes as $key => $token) {
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

        return $text;
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
}

?>
