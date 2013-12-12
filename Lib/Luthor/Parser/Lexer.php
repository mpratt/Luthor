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

namespace Luthor\Parser;

use \Luthor\Parser\Extensions\Interfaces\SetterGetterInterface;

/**
 * Lets call this 'pseudo' Lexer. It analizes a text
 * and extensionizes it.
 */
class Lexer implements \Iterator
{
    /** @var int current line key */
    protected $line = 0;

    /** @var int current line offset */
    protected $offset = 0;

    /** @var Array The text splitted by lines */
    protected $text = array();

    /** @var Array Configuration Directives */
    protected $config = array();

    /** @var Array With tokens */
    protected $extensions = array();

    /**
     * Construct
     *
     * @param array $config
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->config = $config;
        $this->extensions = $this->getDefaultExtensions();
    }

    /**
     * Loads the default extensions
     *
     * @return array
     */
    protected function getDefaultExtensions()
    {
        $classes = array(
            'Raw', 'Escaped', 'Blockquote', 'Lists', 'Codeblock', 'FencedCodeblock',
            'Heading', 'HorizontalRule', 'InlineSpan', 'UrlEmail', 'ImagesLinks'
        );

        $extensions = array();
        foreach ($classes as $class) {
            $class = new \ReflectionClass('\Luthor\Parser\Extensions\\' . $class);
            $extensions[] = $class->newInstance();
        }

        return $extensions;
    }

    /**
     * Sets the text for the lexer
     *
     * @return void
     */
    public function setText($text)
    {
        $lines = explode("\n", $text);
        if (count(array_filter($lines)) > 0) {
            $this->text = $lines;
        }
    }

    /**
     * Adds a new extensions to the Lexer.
     *
     * @param object $extension
     * @return void
     */
    public function addExtension(SetterGetterInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * Matches a text to the relevant extension object
     *
     * @param string $content
     * @return object Instance of \Luthor\Lexer\Token
     */
    protected function findToken($content)
    {
        foreach ($this->extensions as $extension)
        {
            /**
             * Some extensions can only be valid when the
             * match starts from the real start of the line.
             * So lets skip them on higher offsets.
             */
            if ($extension->lineStart && $this->offset > 0) {
                continue ;
            }

            if (preg_match($extension->getRegex(), $content, $matches, null, $this->offset)) {
                $this->offset += mb_strlen($matches['0']);
                $extension->setContent($matches, $this->line);
                return clone $extension;
            }
        }

        $content = mb_substr($content, $this->offset, 1);
        $this->offset += 1;

        $raw = new \Luthor\Parser\Extensions\Raw();
        $raw->setConfig($this->config);
        $raw->setContent(array($content), $this->line);
        return $raw;
    }

    /**
     * Returns the current element/line
     *
     * @return string
     */
    public function current()
    {
        if (trim($this->text[$this->line]) === '') {
            $line = new \Luthor\Parser\Extensions\Line();
            $line->setConfig($this->config);
            $line->setContent(array("\n"), $this->line);
            return $line;
        }

        return $this->findToken($this->text[$this->line]);
    }

    /**
     * Moves the current position/line to the next
     * one.
     *
     * @return void
     */
    public function next()
    {
        if ($this->offset >= strlen($this->text[$this->line])) {
            $this->offset = 0;
            ++$this->line;
        }
    }

    /**
     * Returns the current key/line
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function key()
    {
        return $this->line;
    }

    /**
     * Checks if the current line/position
     * is valid for this Iterator
     *
     * @return bool
     */
    public function valid()
    {
        return (isset($this->text[$this->line]));
    }

    /**
     * Rewinds back to the first element of the Iterator.
     * It also organizes the extensions property based on their
     * priority
     *
     * @return void
     */
    public function rewind()
    {
        $this->line = $this->offset = 0;

        // Assigning to $config for PHP 5.3
        $config = $this->config;
        array_map(function ($extension) use ($config) {
            $extension->setConfig($config);
        }, $this->extensions);

        // Higher priority gets tested first
        usort($this->extensions, function ($a, $b) {
            return ($a->priority < $b->priority);
        });
    }
}

?>
