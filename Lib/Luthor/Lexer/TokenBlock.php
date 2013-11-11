<?php
/**
 * TokenBlock.php
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
 * This class holds block tokens, that is, tokens that represent
 * a markdown block
 */
class TokenBlock implements \IteratorAggregate
{
    /** @var bool Wether or not this block is an indentation **/
    protected $isIndentation = false;

    /** @var bool The state of this block **/
    protected $isOpen = true;

    /** @var string The initial type of this block */
    protected $type;

    /** @var int holds the last line number */
    protected $lastLine;

    /** @var array With Tokens */
    protected $tokens = array();

    /** @var array A big ass Array that holds general information about all blocks */
    protected $blocks = array(
        'BLOCKQUOTE' => array(
            'ignore' => array('BLOCKQUOTE'),
            'close_on' => array('LINE'),
            'close_token' => 'CLOSE_BLOCKQUOTE',
            'on_line_start' => '',
            'transform_to' => '',
        ),
        'CODEBLOCK' => array(
            'ignore' => array('CODEBLOCK'),
            'close_on' => array('LINE'),
            'close_token' => 'CLOSE_CODEBLOCK',
            'on_line_start' => '',
            'transform_to' => 'RAW',
        ),
        'FENCED_CODEBLOCK' => array(
            'ignore' => array(),
            'close_on' => array('FENCED_CODEBLOCK'),
            'close_token' => 'CLOSE_CODEBLOCK',
            'on_line_start' => '',
            'transform_to' => 'RAW',
        ),
    );

    /**
     * Construct
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @return void
     */
    public function __construct(Token $token, $indent = false)
    {
        $this->type = $token->type;
        $this->lastLine = $token->line;
        $this->isIndentation = $indent;
        $this->isOpen = true;

        if ($indent && strpos($this->type, '_INDENT_')) {
            $token->type = $this->getType();
            $this->createIndentationRules();
        }

        $this->tokens[] = $token;
    }

    /**
     * Maps new block rules into this block
     *
     * @param string $type
     * @param array $options
     * @return void
     */
    public function addBlockRules($type, array $options = array())
    {
        $type = strtoupper($type);
        $this->blocks[$type] = array_merge(array(
            'ignore' => array(),
            'close_on' => array('LINE'),
            'close_token' => 'CLOSE_' . $type,
            'on_line_start' => '',
            'transform_to' => '',
        ), $options);
    }

    /**
     * Creates/appends indentation rules
     *
     * @return void
     */
    protected function createIndentationRules()
    {
        $options = array();
        $type = $this->getType();

        // Add the token with __INDENT_# to the ignore list of this subblock
        $options['ignore'] = array_merge(
            array_diff($this->blocks[$type]['ignore'], array($type)),
            array($this->type)
        );

        // Add lower indent leves to trigger the end of this block
        if (preg_match('~_INDENT_(\d+)$~', $this->type, $m)) {
            $close = array($type);
            foreach (range(0, intval($m['1'] - 1)) as $num) {
                $close[] = $type . '_INDENT_' . $num;
            }

            $options['close_on'] = array_merge($this->blocks[$type]['close_on'], $close);
        }

        $this->addBlockRules($type, $options);
    }

    /**
     * Appends a new token into this block
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @return void
     * @throws InvalidArgumentException when the block is closed and this method is called.
     */
    public function append(Token $token)
    {
        $type = $this->getType();
        $this->lastLine = $token->line;

        // Check if there is a subblock available
        $lastToken = end($this->tokens);
        if ($lastToken instanceof TokenBlock && $lastToken->isOpen()) {
            return $this->tokens[(count($this->tokens) - 1)]->append($token);
        }

        // Check if it is sinful to append the token
        if (!in_array($token->type, $this->blocks[$type]['ignore'])) {

            // If the token is an indentation, lets build a new block
            if (preg_match('~_INDENT_~', $token->type)) {
                $this->tokens[] = new TokenBlock($token, true);
            } else {

                if (in_array($token->type, $this->getClosingMarkers())) {
                    return $this->close();
                }

                if (!empty($this->blocks[$type]['transform_to'])) {
                    $token->type = $this->blocks[$type]['transform_to'];
                }

                $this->tokens[] = $token;
            }
        }
    }

    /**
     * Gets a list of Token types that when found
     * should trigger the closing of this block
     *
     * @return array
     */
    public function getClosingMarkers()
    {
        return $this->blocks[$this->getType()]['close_on'];
    }

    /**
     * Checks the status of this current block
     * If its open or closed.
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->isOpen;
    }

    /**
     * Gets the current type of this block
     *
     * @return string
     */
    protected function getType()
    {
        if ($this->isIndentation) {
            return preg_replace('~_INDENT_(\d+)$~', '', $this->type);
        }

        return $this->type;
    }

    /**
     * Closes the current block and found
     * subblocks
     *
     * @return void
     */
    public function close()
    {
        if ($this->isOpen) {
            array_walk($this->tokens, function ($token) {
                if ($token instanceof TokenBlock) {
                    $token->close();
                }
            });

            $type = $this->blocks[$this->getType()]['close_token'];
            $this->tokens[] = new Token('', $type, '', 0, ++$this->lastLine);
            $this->isOpen = false;
        }
    }

    /**
     * Required Method for the \IteratorAggregate Interface
     *
     * @return object Instance of ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->tokens);
    }
}

?>
