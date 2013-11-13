<?php
/**
 * TokenList.php
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
 * This class holds a block of tokens that represent
 * a list
 */
class TokenList implements \IteratorAggregate
{
    /** @var bool Wether or not this block is an indentation **/
    public $isIndentation = false;

    /** @var int The current indentation level **/
    public $indentLevel = 0;

    /** @var bool The state of this block **/
    public $isOpen = true;

    /** @var string The initial type of this block */
    protected $type;

    /** @var int holds the last line number */
    protected $lastLine;

    /** @var array With Tokens */
    protected $subBlocks = array();

    /** @var array With Tokens */
    protected $tokens = array();

    /** @var array A big ass Array that holds general information about all blocks */
    protected $blocks = array(
        'LISTBLOCK' => array(
            'ignore' => array(),
            'close_on' => array('LINE'),
            'close_token' => array('CLOSE_LIST'),
            'on_creation' => array('OPEN_LIST'),
            'append_before' => array(
                'LISTBLOCK' => array('CLOSE_LIST_ELEMENT'),
                'LINE' => array('CLOSE_LIST_ELEMENT'),
                'CLOSE_LIST' => array('CLOSE_LIST_ELEMENT'),
            ),
            'transform' => array(),
        ),
    );

    /**
     * Construct
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @return void
     */
    public function __construct(Token $token, $hasParent = false)
    {
        $this->type = $token->type;
        $this->hasParent = $hasParent;
        $this->isIndentation = preg_match('~_INDENT_(\d+)$~', $this->type, $m);
        $this->indentLevel = (!empty($m['1']) ? $m['1'] : 0);
        $this->isOpen = true;

        if ($this->isIndentation) {
            $this->createIndentationRules($this->indentLevel);
        }

        foreach ($this->blocks[$this->getParentType()]['on_creation'] as $type) {
            $this->tokens[] = new Token('on_creation_trigger', $type, '', $token->position, $token->line);
        }

        $this->tokens[] = $token;
    }

    /**
     * Appends a new token into this block
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @return void
     * @throws InvalidArgumentException when the block is closed and this method is called.
     */
    public function append(Token $token, $appendOnClose = true)
    {
        if (!$this->isOpen){
            return ;
        }

        $type = $this->getParentType();

        // Check if we are ignoring this type of blocks
        if (in_array($token->type, $this->blocks[$type]['ignore'])) {
            return ;
        }

        // Check if we have an open subblock
        if ($key = $this->getLatestOpenedBlock()) {
            $this->tokens[$key]->append($token, false);

            // Well lets look if this token can also close its parent block
            if (!$this->tokens[$key]->isOpen) {
                $this->append($token, $appendOnClose);
            }

            return ;
        }

        // Check if this type of tokens trigger a close event
        if (in_array($token->type, $this->getClosingMarkers())) {
            return $this->close($token, $appendOnClose);
        }

        // trigger append_before events
        $this->beforeAfterAdditions($token, 'append_before');

        // If the token is an indent token, different from this same type, open a new block
        if (preg_match('~_INDENT_~', $token->type) && $token->type != $this->type) {
            $this->tokens[] = new TokenList($token, true);
            $this->subBlocks[] = (count($this->tokens)- 1);
        } else {

            // Apply transformation options
            if (!empty($this->blocks[$type]['transform_to'])) {
                $token->type = $this->blocks[$type]['transform_to'];
            }

            // Add the token into the array
            $this->tokens[] = $token;
        }

        // trigger append_after events
        $this->beforeAfterAdditions($token, 'append_after');
    }

    protected function beforeAfterAdditions(Token $token, $index = 'append_before')
    {
        if (!empty($this->blocks[$this->getParentType()][$index][$token->type])) {
            $rules = $this->blocks[$this->getParentType()][$index][$token->type];
            foreach ($rules as $type) {
                $this->tokens[] = new Token($index . '_trigger', $type, '', $token->position, $token->line);
            }
        }
    }

    protected function getLatestOpenedBlock()
    {
        foreach (array_reverse($this->subBlocks) as $key) {
            if ($this->tokens[$key] instanceof TokenList && $this->tokens[$key]->isOpen) {
                return $key;
            }
        }

        return false;
    }

    public function getLastToken()
    {
        foreach (array_reverse($this->tokens) as $token) {
            if (!$token instanceof TokenList) {
                $token->line = $token->line - 1;
                return $token;
            } else {
                return $token->getLastToken();
            }
        }

        return false;
    }

    public function getLastLine()
    {
        $token = $this->getLastToken();
        return $token->line;
    }

    /**
     * Gets the current type of this block
     *
     * @return string
     */
    public function getParentType($real = false)
    {
        if ($this->isIndentation && !$real) {
            return preg_replace('~_INDENT_(\d+)$~', '', $this->type);
        }

        return $this->type;
    }

    /**
     * Creates/appends indentation rules
     *
     * @return void
     */
    protected function createIndentationRules($level = 0)
    {
        $type = $this->getParentType();
        if (!empty($this->blocks[$type])) {
            $options = $this->blocks[$type];
        } else {
            $options = array(
                'ignore' => array(),
                'close_on' => array('LINE'),
                'close_token' => array(),
                'on_creation' => array(),
                'append_before' => array(),
                'append_after' => array(),
                'transform_to' => '',
            );
        }

        $options['ignore'] = array_merge(
            array_diff($options['ignore'], array($this->type, $type))
        );

        // Add lower indent leves to trigger the end of this block
        $options['close_on'][] = $type;
        if ($level > 0) {
            foreach (range(1, intval($level - 1)) as $num) {
                $options['close_on'][] = $type . '_INDENT_' . $num;
            }
        }

        if (!empty($options['append_before'][$type])) {
            $options['append_before'][$this->type] =$options['append_before'][$type];
        }

        if (!empty($options['append_after'][$type])) {
            $options['append_after'][$this->type] =$options['append_after'][$type];
        }

        $this->addBlockRules($type, $options);
    }

    /**
     * Closes the current block and found
     * subblocks
     *
     * @return void
     */
    public function close($token = null, $appendOnClose = true)
    {
        if ($this->isOpen) {
            if (!$token) {
                $token = $this->getLastToken();
            }

            array_walk($this->tokens, function ($el) use ($token) {
                if ($el instanceof TokenList) {
                    $el->close($token);
                }
            });

            foreach ($this->blocks[$this->getParentType()]['close_token'] as $type) {
                $closeToken = new Token('close_on_trigger', $type, '', 1, $token->line);
                $this->beforeAfterAdditions($closeToken, 'append_before');
                $this->tokens[] = $closeToken;
                $this->beforeAfterAdditions($closeToken, 'append_after');
            }

            if ($appendOnClose) {
                $this->tokens[] = $token;
            }

            $this->isOpen = false;
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
        return $this->blocks[$this->getParentType()]['close_on'];
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
            'close_token' => array('CLOSE_' . $type),
            'on_creation' => array(),
            'transform_to' => '',
        ), $options);
    }

    /**
     * Required Method for the \IteratorAggregate Interface
     *
     * @return object Instance of ArrayIterator
     */
    public function getIterator()
    {
        $this->close();
        reset($this->tokens);
        return new \ArrayIterator($this->tokens);
    }
}

?>
