<?php
/**
 * SetterGetterAdapter.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Extensions\Adapters;

use Luthor\Parser\Extensions\Interfaces\SetterGetterInterface;

/**
 * Abstract class used as an adapter for inline extensions
 */
abstract class SetterGetterAdapter implements SetterGetterInterface
{
    /**
     * @var int number from 1 to 100 defining the priority of the token.
     * Higher numbers get executed earlier. Default is 50
     */
    public $priority = 50;

    /** @var bool wether or not this definition requires a real line start */
    public $lineStart = false;

    /** @var int The number of the line where the matches were found */
    public $line;

    /** @var bool wether or not this extension allows attributes as in {#id}, etc */
    protected $allowsAttributes = false;

    /** @var array with configuration directives */
    protected $config = array();

    /** @var string A regex string that defines this token */
    protected $regex = null;

    /** @var  string the raw content */
    protected $content = null;

    /** @var array Array with the matched content */
    protected $matches = array();

    /** @var string Attributes found on this token */
    protected $attr = null;

    /** @var int the indentation of the current block */
    protected $indent = null;

    /** inline {@inheritdoc} */
    public function setConfig(array $config = array())
    {
        $this->config = $config;
    }

    /** inline {@inheritdoc} */
    public function setContent(array $matches, $line = 0)
    {
        $this->content = $matches['0'];
        $this->matches = $matches;
        $this->line = $line;
        $this->attr = null;

        if ($this->allowsAttributes) {
            $this->findAttributes();
        }
    }

    /** inline {@inheritdoc} */
    public function getRegex()
    {
        return $this->regex;
    }

    /** inline {@inheritdoc} */
    public function getType()
    {
        // Get the absolute class name
        return strtoupper(basename(str_replace('\\', '/', get_class($this))));
    }

    /**
     * Finds markdown's classes/ids in the form of {#id} or {.class1 .class2}
     * or even {.class #id .class2}
     *
     * @return void
     */
    protected function findAttributes()
    {
        if (preg_match('~({(#|\.)([^}]+)}(?:[ ]*$| ?=*| ?-*))~', $this->content, $matches)) {
            $this->content = str_replace($matches['0'], '', $this->content);

            $attr = array();
            if (preg_match('~#([^ }]+)~', $matches['1'], $id)) {
                $attr[] = 'id="' . $id['1'] . '"';
            }

            if (preg_match_all('~\s*\.([^ }]+)~', $matches['1'], $classes)) {
                $attr[] = 'class="' . implode(' ', $classes['1']) . '"';
            }

            $this->attr = trim(implode(' ', $attr));
        }
    }
}

?>
