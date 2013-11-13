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
        $this->reset();
    }

    /**
     * Maps tokens to their relevant operation/processor function.
     *
     * @return array with token -> operation relationship
     */
    protected function buildOperations()
    {
        $headings = new Processor\Headings();
        $inline = new Processor\Inline();
        $code = new Processor\CodeBlock();
        $blockquote = new Processor\Blockquote();

        return array(
            'RAW' => function ($token) {
                return $token->content;
            },
            'LINE' => function () {
                return "\n";
            },
            'HR' => function () {
                return "\n" . '<hr/>' . "\n";
            },
            'OPEN_LIST' => function ($token) {
                return '<ul>';
            },
            'OPEN_LIST_ELEMENT' => function ($token) {
                return '<li>';
            },
            'CLOSE_LIST_ELEMENT' => function ($token) {
                return '</li>';
            },
            'CLOSE_LIST' => function ($token) {
                return '</ul>';
            },
            'LISTBLOCK' => function ($token) {
                return '<li>';
            },
            'CLOSE_LISTBLOCK' => function ($token) {
                return '</li>';
            },
            'BLOCKQUOTE' => array($blockquote, 'open'),
            'CLOSE_BLOCKQUOTE' => array($blockquote, 'close'),
            'CODEBLOCK' => array($code, 'open'),
            'FENCED_CODEBLOCK' => array($code, 'openFencedBlock'),
            'CLOSE_CODEBLOCK' => array($code, 'close'),
            'H_SETEXT' => array($headings, 'setext'),
            'H_ATX' => array($headings, 'atx'),
            'INLINE_LINK' => array($inline, 'link'),
            'INLINE_IMG' => array($inline, 'image'),
            'INLINE_ELEMENT' => array($inline, 'span'),
        );
    }

    /**
     * Registers a new operation for a given token name
     *
     * @param string $token
     * @param mixed|callable $operation
     * @return void
     */
    public function addOperation($token, $operation)
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
        print_r($collection);
        foreach ($collection as $token) {

            if ($token instanceof \IteratorAggregate) {
                $output[] = $this->parse($token);
                continue ;
            }

            if ($token->type == 'ABBR_DEFINITION') {
                $this->addFilter(function ($text) use ($token) {
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

            // Remove indent markers from the token type
            $token->type = preg_replace('~_INDENT_(\d+)$~', '', $token->type);
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

        /*
        $this->addFilter(function ($text) {
            return preg_replace('~</li>\s*</li>~m', '</li>', $text);
        });
        $this->addFilter(function ($text) {
            return preg_replace('~<li>\s*</li>~m', '', $text);
        });*/
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
        } elseif ($token instanceof \Luthor\Lexer\Token) {
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
    public function addFilter(callable $func)
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
}

?>
