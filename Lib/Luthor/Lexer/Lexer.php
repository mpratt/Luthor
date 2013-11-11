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
 * Lets call this 'pseudo' Lexer. It analizes a text
 * and tokenizes it.
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
            'ignore_attr' => array('RAW'),
            'force_line_start' => array(
                'HR', 'BLOCKQUOTE', 'CODEBLOCK', 'FENCED_CODEBLOCK', 'REFERENCE_DEFINITION',
                'FOOTNOTE_DEFINITION', 'ABBR_DEFINITION'
            ),
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
                $this->collection->add(new Token('', 'LINE', '', 0, $line));
                continue ;
            }

            $offset = 0;
            while($offset < strlen($content)) {
                $token = $this->match($content, $offset, $line);

                if (strpos($token->type, 'BLOCK') !== false) {
                    $this->collection->createBlock($token);
                } elseif ($token->type == 'REFERENCE_DEFINITION') {
                    $this->collection->storeDefinition($token);
                } else {
                    $this->collection->add($token);
                }
            }
        }

        return $this->collection;
    }

    /**
     * Registers a new regex => token relation to the class.
     *
     * @param string $rule Regex
     * @param string $token token name
     * @return void
     */
    public function addToken($rule, $token)
    {
        $this->config['map']->add($rule, $token);
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
        foreach ($this->config['map'] as $regex => $tokenName)
        {
            $attr = '';
            if (preg_match($regex, $content, $matches, null, $offset)) {

                /**
                 * Dirty hack!
                 * Some tokens can only be valid when they start on their own line.
                 * If the offset is bigger than 0, convert this tokens into RAW type
                 */
                if (in_array($tokenName, $this->config['force_line_start']) && $offset > 0) {
                    $tokenName = 'RAW';
                }

                $offset += strlen($matches['0']);

                // Find attributes on elements that are not in $this->config['ignore_attr']
                if (!in_array($tokenName, $this->config['ignore_attr']) && strpos($matches['0'], '{') !== false) {
                    $attr = $this->findAttributes($matches['0']);
                }

                return new Token($matches, $tokenName, $attr, $offset, $line);
            }
        }

        $offset += (strlen($content) - $offset);
        return new Token($content, 'RAW', '', $offset, $line);
    }

    /**
     * Finds markdown's classes/ids in the form of {#id} or {.class1 .class2}
     * or even {.class #id .class2}
     *
     * @param string $content passed by reference!!
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
