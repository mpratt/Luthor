<?php
/**
 * CodeBlock.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Processor;

/**
 * Manages Code HTML
 */
class CodeBlock
{
    /**
     * Opens a new codeblock
     *
     * @return string
     */
    public function open()
    {
        return '<pre><code>' . "\n";
    }

    /**
     * Opens a new code block with attributes
     *
     * @param object $token Instance of \Luthor\Lexer\Token
     * @return string
     */
    public function openFencedBlock($token)
    {
        if (!empty($token->attr)) {
            return "\n" . '<pre ' . $token->attr . '><code>';
        }

        return "\n" . '<pre><code>';
    }

    /**
     * Closes a code block
     *
     * @return string
     */
    public function close()
    {
        return '</code></pre>' . "\n\n";
    }
}

?>
