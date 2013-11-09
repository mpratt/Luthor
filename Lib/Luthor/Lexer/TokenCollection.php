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
 * of tokens and in some cases organize them appropiately.
 *
 * I know its ugly...
 * TODO: REFACTOR REFACTOR REFACTOR!
 */
class TokenCollection implements \IteratorAggregate
{
    /** @var Array with the reference map */
    protected $refs = array();

    /** @var Array with status information about open or closed blocks */
    protected $status = array(
        'opened' => array(), // Currently opened blocks
        'closed' => array(), // Already closed blocks
        'remove' => array(), // Coordinates of tokens to be removed
        'lists'  => array(),
    );

    /** @var Array With Tokens */
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
        if ($token->type == 'REFERENCE') {
            $this->storeReference($token, $coord);
        } elseif ($token->type == 'REFERENCE_DEFINITION' && !empty($this->refs[$token->matches['2']]['coord'])) {
            return $this->mergeTokenReferenceDefinition($token);
        } elseif (strpos($token->type, 'BLOCK') !== false) {
            $this->updateStatus($token);
        }

        $this->tokens[$coord] = $token;
    }

    protected function updateStatus(Token $token)
    {
        $previous = $token->line - 1;
        if (!empty($this->status['opened'][$previous])) {
            if ($this->status['opened'][$previous] == $token->type) {
                $this->status['remove'][] = $token->line . '.' . $token->position;
                $this->status['opened'][$token->line] = $this->status['opened'][$previous];
                unset($this->status['opened'][$previous]);
            } else {
                $this->status['opened'][$token->line] = $token->type;
            }
        } else {
            $this->status['opened'][$token->line] = $token->type;
        }
    }

    public function flagList(Token $token)
    {
        if (empty($this->status['lists'])) {
            $coord = $token->line . '.' . ($token->position - 1);
            $this->tokens[$coord] = new Token($token->content, 'OPEN_LIST', '', $token->line, $token->position);
            $this->status['lists'][($token->line + 1)] = 'close';
        }

        if (isset($this->status['lists'][$token->line])) {
            $this->tokens[$token->line] = new Token('', 'CLOSE_LIST_ELEMENT', '', $token->line, $token->position);
            $this->status['lists'][($token->line + 1)] = 'close';
            unset($this->status['lists'][$token->line]);
        }

        $coord = $token->line . '.' . $token->position;
        $this->tokens[$coord] = new Token('', 'LIST_ELEMENT', '', $token->line, $token->position);
    }

    public function closeBlocks(Token $token)
    {
        $originalPosition = $token->position;

        if (!empty($this->status['lists'])) {

            unset($this->status['lists'][$token->line]);
            foreach ($this->status['lists'] as $line => $name) {
                $token = new Token('', 'CLOSE_LIST_ELEMENT', '', $token->line, $token->position);
                $this->tokens[$token->line . $line . '.50000'] = $token;
                unset($this->status['opened'][$line]);
            }

            $coord = $token->line . '.' . --$token->position . '800';
            $this->tokens[$coord] = new Token('', 'CLOSE_LIST', '', $token->line, $token->position);

            $coord = $token->line . '.' . --$token->position . '800';
            $this->tokens[$coord] = new Token('', 'LINE', '', $token->line, $token->position);
        }

        foreach ($this->status['opened'] as $line => $name) {
            $coord = $token->line . '.' . --$token->position;
            $this->tokens[$coord] = new Token('', 'CLOSE_' . $name, '', $token->line, $token->position);
            $this->status['closed'][] = $name;
            unset($this->status['opened'][$line]);
        }

        // Store the new line
        $coord = $token->line . '.' . $originalPosition;
        $this->tokens[$coord] = $token;
    }

    protected function storeReference(Token $token, $coord)
    {
        $id = (!empty($token->matches['3']) ? $token->matches['3'] : $token->matches['2']);
        $this->refs[$id] = array(
            'coord' => $coord,
            'name'  => $token->matches['2']
        );
    }

    protected function mergeTokenReferenceDefinition(Token $token)
    {
        $coord = $this->refs[$token->matches['2']]['coord'];
        $name = $this->refs[$token->matches['2']]['name'];
        $oldToken = $this->get($coord);

        if ($oldToken->content['0'] == '!') {
            $type = 'INLINE_IMG';
        } else {
            $type = 'INLINE_LINK';
        }

        $matches = array(
            '0' => $token->matches['2'],
            '1' => $token->matches['3'],
            '2' => $name,
            '3' => $token->content,
        );

        $this->tokens[$coord] = new Token($matches, $type, $token->attr, $oldToken->position, $oldToken->line);
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

    public function clean()
    {
        // Strip irrelevant stuff from the tokens
        foreach ($this->status['remove'] as $coord) {
            unset($this->tokens[$coord]);
        }

        // Close still opened blocks
        foreach ($this->status['opened'] as $line => $name) {
            $coord = $line . '.' . 1000000;
            $this->tokens[$coord] = new Token('', 'CLOSE_' . $name, '', $line, 1000000);
            $this->status['closed'][] = $name . ' : forced';
            unset($this->status['opened'][$line]);
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
