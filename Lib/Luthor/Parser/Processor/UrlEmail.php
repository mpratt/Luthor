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
     * Opens a new codeblock
     *
     * @return string
     */
    public function linkify($token)
    {
        $url = trim($token->content, ' <>');
        return '<a href="' . $url . '" title="">' . $url . '</a>';
    }
}

?>
