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

namespace Luthor\Parser\Extensions;

use Luthor\Parser\Extensions\Adapters\InlineAdapter;

/**
 * This is a extension for Url or Email input.
 */
class UrlEmail extends InlineAdapter
{
    /** inline {@inheritdoc} */
    public function getRegex()
    {
        $regex = array(
            '((?:mailto:)?[^ ]+@[^ ]+)', // <email@domain.com>
            '(https?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))', // <https://link.com>
        );

        return '~<(' . implode('|', $regex) . ')>~A';
    }

    /** inline {@inheritdoc} */
    public function parse()
    {
        $this->content = trim($this->content, ' <>');
        if ($this->isLink()) {
            return $this->linkify($this->content);
        }

        return $this->email($this->content);
    }

    /**
     * Checks if the content is a link
     *
     * @return bool
     */
    protected function isLink()
    {
        return (preg_match('~^http~', $this->content));
    }

    /**
     * Autolinkifys urls
     *
     * @param string $url
     * @return string
     */
    protected function linkify($url)
    {
        return '<a href="' . $url . '" title="">' . $url . '</a>';
    }

    /**
     * Handles mailto/emails
     *
     * @param string $email
     * @return string
     */
    protected function email($email)
    {
        $email = preg_replace('~mailto:~', '', $email);
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
