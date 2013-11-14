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
 * a markdown block, like code blocks, blockquotes and lists.
 *
 * TODO: This class is a little too complex for my taste, the C.R.A.P index
 * is pretty high on this one. It does the job, though, so lets establish good
 * tests before we tackle the refactoring of this class.
 */
class TokenBlock implements \IteratorAggregate
{
    /** @var bool Wether or not this block is an indentation **/
    public $isIndentation = false;

    /** @var int The current indentation level **/
    public $indentLevel = 0;

    /** @var bool The state of this block **/
    public $isOpen = true;

    /** @var array Configuration Directives */
    protected $config = array();

    /** @var string The initial type of this block */
    protected $type;

    /** @var int holds the last line number */
    protected $lastLine;

    /** @var array With Tokens */
    protected $subBlocks = array();

    /** @var array With Tokens */
    protected $tokens = array();

    /** @var array A big ass Array that holds general instructions/information about known blocks */
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
            'append_after' => array(),
            'transform_to' => array(
                'BLOCKQUOTE' => 'RAW',
                'CODEBLOCK' => 'RAW'
            ),
        ),
        'BLOCKQUOTE' => array(
            'ignore' => array('BLOCKQUOTE'),
            'close_on' => array('LINE'),
            'close_token' => array('CLOSE_BLOCKQUOTE'),
            'on_creation' => array(),
            'append_before' => array(),
            'append_after' => array(),
            'transform_to' => array(),
        ),
        'CODEBLOCK' => array(
            'ignore' => array('CODEBLOCK'),
            'close_on' => array('LINE'),
            'close_token' => array('CLOSE_CODEBLOCK'),
            'on_creation' => array(),
            'append_before' => array(),
            'append_after' => array(),
            'transform_to' => array(
                '*' => 'RAW',
            ),
        ),
        'FENCED_CODEBLOCK' => array(
            'ignore' => array(),
            'close_on' => array('FENCED_CODEBLOCK'),
            'close_token' => array('CLOSE_CODEBLOCK'),
            'on_creation' => array(),
            'append_before' => array(),
            'append_after' => array(),
            'transform_to' => array(
                '*' => 'RAW',
            ),
        ),
    );

    /**
     * Construct
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @param array $config
     * @param bool $hasParent Wether or not this particular subblock has a parent
     * @return void
     */
    public function __construct(Token $token, array $config = array(), $hasParent = false)
    {
        $this->type = $token->type;
        $this->config = $config;
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
     * Appends a new token into this block,
     * well it sounds simple, but its not quite that way, since it
     * works recursively..
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @param bool $appendOnClose, wether or not we should append the closing token at the end
     * @return void
     */
    public function append(Token $token, $appendOnClose = true)
    {
        if (!$this->isOpen){
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

        // Get the parent type
        $type = $this->getParentType();

        // Check if we are ignoring this type of tokens
        if (in_array($token->type, $this->blocks[$type]['ignore'])) {
            return ;
        }

        // Check if this type of token triggers a close event
        if (in_array($token->type, $this->getClosingMarkers())) {
            return $this->close($token, $appendOnClose);
        }

        // trigger append_before events
        $this->beforeAfterAdditions($token, 'append_before');

        // If the token is an indent token, different from this same type, open a new block
        if (preg_match('~_INDENT_~', $token->type) && $token->type != $this->type) {
            $this->tokens[] = new TokenBlock($token, $this->config, true);
            $this->subBlocks[] = (count($this->tokens) - 1);
        } else {

            // Apply transformation options
            $token = $this->transformToken($token);

            // Add the token into the array
            $this->tokens[] = $token;
        }

        // trigger append_after events
        $this->beforeAfterAdditions($token, 'append_after');
    }

    /**
     * Transforms a token into a different type. It
     * takes into account the rules from $this->blocks
     *
     * @param object $token Instance of \Luthor\Lexer\Token
     * @return object Instance of \Luthor\Lexer\Token
     */
    protected function transformToken(Token $token)
    {
        $type = $this->getParentType();
        foreach ($this->blocks[$type]['transform_to'] as $name => $newType) {
            if ($name === '*' || strpos($token->type, $name) !== false) {
                $token->type = $newType;
            }
        }

        return $token;
    }

    /**
     * Does events depending on the rules on $this->blocks
     *
     * @param object $token
     * @param string $index The index name to be used on $this->blocks
     * @return void
     */
    protected function beforeAfterAdditions(Token $token, $index = 'append_before')
    {
        if (!empty($this->blocks[$this->getParentType()][$index][$token->type])) {
            $rules = $this->blocks[$this->getParentType()][$index][$token->type];
            foreach ($rules as $type) {
                $this->tokens[] = new Token($index . '_trigger', $type, '', $token->position, $token->line);
            }
        }
    }

    /**
     * Gets the last opened subblock
     *
     * @return int|bool The number of the key where the subblock is found
     * returns false when none was found.
     */
    protected function getLatestOpenedBlock()
    {
        foreach (array_reverse($this->subBlocks) as $key) {
            if ($this->tokens[$key] instanceof TokenBlock && $this->tokens[$key]->isOpen) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Gets the last valid token on $this->tokens
     *
     * @return object Instance of \Luthor\Lexer\Token or false when none was found
     */
    public function getLastToken()
    {
        foreach (array_reverse($this->tokens) as $token) {
            if (!$token instanceof TokenBlock) {
                $token->line = $token->line - 1;
                return $token;
            } else {
                return $token->getLastToken();
            }
        }

        return false;
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
     * Creates/appends indentation rules for
     * a subblock with indentations .. duh!
     *
     * @return void
     */
    protected function createIndentationRules($level = 0)
    {
        $type = $this->getParentType();
        $options = array(
            'ignore' => array(),
            'close_on' => array('LINE'),
            'close_token' => array(),
            'on_creation' => array(),
            'append_before' => array(),
            'append_after' => array(),
            'transform_to' => '',
        );

        if (!empty($this->blocks[$type])) {
            $options = $this->blocks[$type];
        }

        if (preg_match('~BLOCKQUOTE~', $this->type)) {
            $options['ignore'] = array_merge(
                array_diff($options['ignore'], array($type)),
                array($this->type)
            );
        } else {
            $options['ignore'] = array_merge(
                array_diff($options['ignore'], array($this->type, $type))
            );
        }

        // Add lower indent leves to trigger the end of this block
        $options['close_on'][] = $type;
        if ($level > 1) {
            foreach (range(1, intval($level - 1)) as $num) {
                $options['close_on'][] = $type . '_INDENT_' . $num;
            }

            $options['close_on'] = array_unique($options['close_on']);
        }

        if (!empty($options['append_before'][$type])) {
            $options['append_before'][$this->type] = $options['append_before'][$type];
        }

        if (!empty($options['append_after'][$type])) {
            $options['append_after'][$this->type] = $options['append_after'][$type];
        }

        $this->addBlockRules($type, $options);
    }

    /**
     * Closes the current block and its childs
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
                if ($el instanceof TokenBlock) {
                    $el->close($token);
                }
            });

            foreach ($this->blocks[$this->getParentType()]['close_token'] as $type) {
                $closeToken = new Token('close_on_trigger', $type, '', 1, $token->line);
                $this->beforeAfterAdditions($closeToken, 'append_before');
                $this->tokens[] = $closeToken;
                $this->beforeAfterAdditions($closeToken, 'append_after');
            }

            if ($appendOnClose && !preg_match('~CODE~', $this->type)) {
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
