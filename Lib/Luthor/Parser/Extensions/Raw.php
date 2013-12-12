<?php
/**
 * Raw.php
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
 * This is a extension for RAW input.
 * It handles everything that doesnt seem to be markdown
 * code/chars.
 */
class Raw extends InlineAdapter
{
    /** inline {@inheritdoc} */
    public $priority = 1;

    /** @var array Array with escaped chars **/
    protected $charRegistry = null;

    /** inline {@inheritdoc} */
    public function parse()
    {
        return $this->raw();
    }

    /** inline {@inheritdoc} */
    public function getRegex()
    {
        if (is_null($this->regex)) {
            // Dont search for closing markers
            $chars = array_filter($this->getReservedChars(), function ($c) {
                return !in_array($c, array(']', '}', ')', '>'));
            });

            $this->regex = '~([^' . implode('', $chars) . ']|(\.$))+~A';
        }

        return $this->regex;
    }

    /**
     * Returns an array with all the reserved chars, escaped
     * and ready to be used inside a regex.
     *
     * @return array
     */
    protected function getReservedChars()
    {
        if (!is_null($this->charRegistry)) {
            return $this->charRegistry;
        }

        return $this->charRegistry = array_map(function ($char) {
            return preg_quote($char, '~');
        }, str_split($this->config['reserve_chars']));
    }
}

?>
