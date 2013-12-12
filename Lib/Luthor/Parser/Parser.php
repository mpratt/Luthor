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

use \Luthor\Parser\Lexer,
    \Luthor\Parser\Filters\FootNotes,
    \Luthor\Parser\Extensions\Interfaces\InlineInterface,
    \Luthor\Parser\Extensions\Interfaces\ReferenceInterface,
    \Luthor\Parser\Extensions\Interfaces\BlockInterface;

/**
 * Class is the Parser
 */
class Parser
{
    /** @var array Array with configuration directives */
    protected $config = array();

    /** @var array Array with output content */
    protected $output = array();

    /** @var array open blocks found */
    protected $blocks = array();

    /** @var string The key where the current used block is located */
    protected $currentBlock = null;

    /**
     * Actually parses the available tokens
     *
     * @param object $lexer Instance of \Luthor\Parser\Lexer
     * @return string
     */
    public function parse(Lexer $lexer)
    {
        $this->output = array();
        foreach ($lexer as $token) {
            $this->findParser($token);
        }

        return implode("\n", $this->output);
    }

    /**
     * Parses inline extensions
     *
     * @param object Implementing InlineInterface
     * @return void
     */
    protected function findParser($token, $useOpenBlocks = true)
    {
        if (!empty($this->blocks) && $useOpenBlocks) {
            $this->addToBlock($token);
        } elseif ($token instanceof BlockInterface) {
            $this->markBlock($token);
        } else {
            $this->addToOutput($token->line, $token->parse());
        }
    }

    /**
     * Adds content to the output property
     *
     * @param int $line
     * @param string $content
     * @return void
     */
    protected function addToOutput($line, $content)
    {
        if (isset($this->output[$line])) {
            $this->output[$line] .= $content;
        } else {
            $this->output[$line] = $content;
        }
    }

    /**
     * Marks the start of a block
     *
     * @param object Implementing BlockInterface
     * @return bool
     */
    protected function markBlock(BlockInterface $token)
    {
        $id = $token->getId();
        if (!empty($this->blocks[$id])) {
            $this->addToOutput($token->line, $token->parse());
            return false;
        }

        if (!empty($this->currentBlock)) {
            $key = $this->currentBlock;
            if ($this->blocks[$key]->toRaw($token->getType())) {
                $raw = htmlspecialchars($token->raw(), ENT_QUOTES, 'UTF-8', false);
                $this->addToOutput($token->line, $raw);
                return true;
            }
        }

        $context = $token->getContext();
        if ($create = $context->create()) {
            $this->addToOutput($token->line, $create);
        }

        $this->blocks[$id] = $context;
        $this->currentBlock = $id;
        return true;
    }

    /**
     * Adds/parses $token in the context of a
     * previous opened block
     *
     * @param object $token
     * @return void
     */
    protected function addToBlock($token)
    {
        if (empty($this->blocks)) {
            return ;
        }

        if ($token instanceof BlockInterface && $this->markBlock($token)) {
            return ;
        }

        $key = $this->currentBlock;

        // Check if the current block is ignoring this type of tokens
        if ($this->blocks[$key]->ignore($token)) {
            return ;
        }

        // Check if this type of token triggers a close event
        if ($this->blocks[$key]->canClose($token)) {
            $this->addToOutput($token->line, $this->blocks[$key]->close());

            array_pop($this->blocks);
            $keys = array_keys($this->blocks);
            $this->currentBlock = end($keys);
            $this->addToBlock($token);
            return ;
        }

        // Prevent double lines on codeblocks
        if ($token->getType() == 'LINE' &&
            strpos($this->blocks[$key]->getType(), 'CODEBLOCK') !== false) {
            $this->addToOutput($token->line, "");
            return ;
        }

        // Wether or not the contents of this should be converted to raw
        if ($this->blocks[$key]->toRaw($token->getType())) {
            $raw = htmlspecialchars($token->raw(), ENT_QUOTES, 'UTF-8', false);
            $this->addToOutput($token->line, $raw);
        } else {
            $this->findParser($token, false);
        }
    }
}

?>
