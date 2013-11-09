<?php
/**
 * Lexer.php
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
 * A pseudo Lexer that analizes a text
 * and splits it into Tokens
 */
class Lexer
{
    /** @var Array Configuration Directives */
    protected $config = array();

    /** @var Instance of \Luthor\Lexer\TokenCollection */
    protected $collection;

    /**
     * Construct
     *
     * @param array $config
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->collection = new TokenCollection();
        $this->config = array_replace_recursive(array(
            'map' => new TokenMap(),
            'force_line_start' => array(
                'LINE', 'HR', 'BLOCKQUOTE_MK', 'CODEBLOCK_MK', 'LIST_MK'
            )
        ), $config);
    }

    /**
     * Analizes the text and returns a collection
     * of tokens,
     *
     * @param string $text
     * @return object Instance of \Luthor\Lexer\TokenCollection
     */
    public function getTokens($text)
    {
        $lines = explode("\n", $text);
        foreach($lines as $line => $content)
        {
            if (trim($content) == '') {
                $token = new Token('', 'LINE', '', 0, $line);
                $this->collection->closeBlocks($token);
            }

            $offset = 0;
            while($offset < strlen($content)) {
                $token = $this->match($content, $offset, $line);
                $this->collection->add($token);
            }
        }

        $this->collection->clean();
        return $this->collection;
    }

    /**
     * Matches a text to the relevant token name
     *
     * @param string $content
     * @param int $offset Passed by Reference
     * @param int line
     * @return object Instance of \Luthor\Lexer\Token
     */
    protected function match($content, &$offset, $line)
    {
        $attr = '';
        foreach ($this->config['map'] as $regex => $tokenName)
        {
            if (preg_match($regex, $content, $matches, null, $offset)) {

                // This elements can only be valid on their own line, from begining to end
                // Or at the **real** start of the line
                if (in_array($tokenName, $this->config['force_line_start']) && $offset > 0) {
                    $tokenName = 'RAW';
                }

                $offset += strlen($matches['0']);
                if (!in_array($tokenName, array('RAW')) && strpos($matches['0'], '{') !== false) {
                    $attr = $this->findAttributes($matches['0']);
                }

                return new Token($matches, $tokenName, $attr, $offset, $line);
            }
        }

        $offset += (strlen($content) - $offset);
        return new Token($content, 'RAW', $attr, $offset, $line);
    }

    /**
     * Finds markdown's classes/ids in the form of {#id} or {.class1 .class2}
     *
     * @param string $content passed by reference
     * @return string
     */
    protected function findAttributes(&$content)
    {
        if (preg_match('~({([^}]+)}(?:[ ]*$| ?=*| ?-*))~', $content, $matches)) {
            $content = str_replace($matches['0'], '', $content);
            return $matches['0'];
        }

        return '';
    }
}

?>
