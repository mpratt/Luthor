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

    /** @var array with status information about open or closed blocks */
    protected $status = array(
        'blocks' => array(), // Currently opened blocks
        'closed' => array(), // Already closed blocks
        'remove' => array(), // Coordinates of tokens to be removed
        'lists'  => array(),
    );

    /** @var array With Tokens */
    protected $tokens = array();

    /**
     * Appends a new Token into the $tokens property
     *
     * @param object Instance of \Luthor\Lexer\Token
     * @return void
     */
    public function add(Token $token)
    {
        $coord = $token->line . '.' . $token->position;
        if (!empty($this->blocks)) {
            $this->appendToBlock($token);
        } else {
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
        $this->tokens[$coord] = new TokenBlock($token);
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

        // If the block is closed, lets seal it here too
        if (!$this->tokens[$lastBlock['coord']]->isOpen()) {
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
     * Returns a Token based on the given $key
     *
     * @param string $key
     * @return object Instance of \Luthor\Lexer\Token
     */
    public function get($key)
    {
        if (isset($this->tokens[$key])) {
            return $this->tokens[$key];
        }

        throw new \InvalidArgumentException(
            sprintf('Invalid Key "%s"', $key)
        );
    }

    /**
     * Required Method for the \IteratorAggregate Interface
     *
     * @return object Instance of ArrayIterator
     */
    public function getIterator()
    {
        // Lets make sure we close opened blocks/subblocks
        foreach ($this->blocks as $block) {
            if (!empty($this->tokens[$block['coord']])) {
                $this->tokens[$block['coord']]->close();
            }
        }

        return new \ArrayIterator($this->tokens);
    }
}

?>
