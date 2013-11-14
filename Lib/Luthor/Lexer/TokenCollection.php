<?php
/**
 * TokenCollection.php
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
 * This class is used to hold a collection
 * of tokens and token blocks
 */
class TokenCollection implements \IteratorAggregate
{
    /** @var array with the reference definition map */
    protected static $refs = array();

    /** @var array with currently opened blocks */
    protected $blocks = array();

    /** @var array With Tokens */
    protected $tokens = array();

    /** @var array Configuration Directives */
    protected $config = array();

    /**
     * Construct
     *
     * @param array $config
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->config = $config;
    }

    /**
     * Appends a new Token into the $tokens property
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @return void
     */
    public function add(Token $token)
    {
        if (!empty($this->blocks)) {
            $this->appendToBlock($token);
        } else {
            $coord = $token->line . '.' . $token->position;
            $this->tokens[$coord] = $token;
        }
    }

    /**
     * Creates a new Token-Block
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @return void
     */
    public function createBlock(Token $token)
    {
        if (!empty($this->blocks)) {
            return $this->appendToBlock($token);
        }

        $coord = $token->line . '.' . $token->position;
        $this->tokens[$coord] = new TokenBlock($token, $this->config);
        $this->blocks[$token->line] = array(
            'type' => $token->type,
            'coord' => $coord,
        );
    }

    /**
     * Appends a token into the last opened block
     * If the block is closed, remove it from the list
     * of available token-blocks.
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @return void
     */
    public function appendToBlock(Token $token)
    {
        if (empty($this->blocks)) {
            return $this->add($token);
        }

        $lastBlock = end($this->blocks);
        $this->tokens[$lastBlock['coord']]->append($token);

        // If the subblock is closed, lets remove the reference we have here too
        if (!$this->tokens[$lastBlock['coord']]->isOpen) {
            array_pop($this->blocks);
        }
    }

    /**
     * Stores a reference definition for later use
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @return void
     */
    public function storeDefinition(Token $token)
    {
        self::$refs[$token->matches['2']] = $token;
    }

    /**
     * Gets a definition for a reference token
     *
     * @param  object Instance of \Luthor\Lexer\Token
     * @return object Instance of \Luthor\Lexer\Token
     */
    public function getDefinition(Token $token)
    {
        $key = (!empty($token->matches['3']) ? $token->matches['3'] : $token->matches['2']);
        if (empty(self::$refs[$key])) {
            $token->type = 'RAW';
            return $token;
        }

        $type = ($token->content['0'] == '!' ? 'INLINE_IMG' : 'INLINE_LINK');
        $definition = self::$refs[$key];
        $matches = array(
            '0' => $token->matches['2'],
            '1' => $token->matches['3'],
            '2' => $token->matches['2'],
            '3' => $definition->content,
        );

        return new Token($matches, $type, $definition->attr, $token->position, $token->line);
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
