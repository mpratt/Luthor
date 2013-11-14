<?php
/**
 * UrlEmail.php
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
 * Handles Url and Emails
 */
class UrlEmail
{
    /**
     * Autolinkifys urls
     *
     * @param object $token
     * @return string
     */
    public function linkify($token)
    {
        $url = trim($token->content, ' <>');
        return '<a href="' . $url . '" title="">' . $url . '</a>';
    }

    /**
     * Handles mailto/emails
     *
     * @param object $token
     * @return string
     */
    public function email($token)
    {
        $email = preg_replace('~mailto:~', '', trim($token->content, ' <>'));
        $email = $this->encodeEmail($email);
        return '<a href="mailto:' . $email . '" title="">' . $email . '</a>';
    }

    /**
     * Encodes an email address into html
     * decimal/hexadecimal entity.
     *
     * The first char is decimal, the second one hexadecimal, then
     * decimal again and so on.
     *
     * @param string $email
     * @return string
     */
    protected function encodeEmail($email)
    {
        $chars = str_split($email);
        $output = array();
        foreach ($chars as $key => $char) {
            $ord = ord($char);
            if (($key%2) == 0) {
                $output[] = '&#' . $ord . ';';
            } else {
                $output[] = '&#x' . dechex($ord) . ';';
            }
        }

        return implode('', $output);
    }
}

?>
