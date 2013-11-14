<?php
/**
 * Headings.php
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
 * Manages HTML Headings
 */
class Headings
{
    /**
     * Converts a Setext type heading into an HTML
     * heading
     *
     * @param object $token Instance of \Luthor\Lexer\Token
     * @return string
     */
    public function setext(\Luthor\Lexer\Token $token)
    {
        $h = (preg_match('~-$~', trim($token->content)) ? '2' : '1');
        if (empty($token->attr)) {
            return '<h' . $h . '>' . rtrim($token->content, ' -=') . '</h' . $h . '>';
        }

        return '<h' . $h . ' ' . $token->attr . '>' . rtrim($token->content, ' -=') . '</h' . $h . '>' . "\n\n";
    }

    /**
     * Converts a Setext type heading into an HTML
     * heading
     *
     * @param object $token Instance of \Luthor\Lexer\Token
     * @return string
     */
    public function atx(\Luthor\Lexer\Token $token)
    {
        list($h, $content) = explode(' ', $token->content, 2);
        $h = min(strlen(trim($h)), 6);

        if (empty($token->attr)) {
            return '<h' . $h . '>' . trim($content, ' #') . '</h' . $h . '>';
        }

        return '<h' . $h . ' ' . $token->attr . '>' . rtrim($content, ' #') . '</h' . $h . '>' . "\n\n";
    }
}

?>
