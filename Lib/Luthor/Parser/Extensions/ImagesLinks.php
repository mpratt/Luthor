<?php
/**
 * ImagesLinks.php
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
 * This is a extension for Images or Links input.
 */
class ImagesLinks extends InlineAdapter
{
    /** inline {@inheritdoc} */
    protected $allowsAttributes = true;

    /** @var array Array with simple html Templates **/
    protected $templates = array(
        'link' => '<a href="{src}" title="{title}"{attr}>{name}</a>',
        'image'  => '<img src="{src}" alt="{name}" title="{title}"{attr} />',
    );

    /** inline {@inheritdoc} */
    public function getRegex()
    {
        $regex = array(
            '((\[\!\[([^\[]+)\]\(([^\)]+)\)\]\(([^\)]+\))(?:{(?:.|#).*})?))', // Images inside links
            '((!?\[([^\[]+)\]\(([^\)]+)\)(?:{(?:.|#).*})?))', // Images or links
        );

        return '~(' . implode('|', $regex) . ')~A';
    }

    /** inline {@inheritdoc} */
    public function parse()
    {
        $this->matches = array_values(array_unique(array_filter($this->matches)));
        if (count($this->matches) > 3) {
            return $this->linkImages($this->matches, $this->attr);
        } elseif (preg_match('~^\s*!~', $this->content)) {
            return $this->image($this->matches, $this->attr);
        }

        return $this->link($this->matches, $this->attr);
    }

    /**
     * Converts images inside a link into the correct html
     *
     * @param array $matches
     * @param string $attr
     * @return string
     */
    protected function linkImages(array $matches, $attr = null)
    {
        $imageToken = array(
            '0' => $this->content,
            '1' => $matches['1'],
            '2' => $matches['2'],
        );

        $linkToken = array(
            '0' => $this->content,
            '1' => '{img}',
            '2' => rtrim($matches['3'], ')'),
        );

        $image = $this->image($imageToken, $attr);
        $link = $this->link($linkToken);
        return str_replace('{img}', $image, $link);
    }

    /**
     * Converts link tokens into html links
     *
     * @param array $matches
     * @param string $attr
     * @return string
     */
    public function link(array $matches, $attr = null)
    {
        $replace = $this->getReplacements($matches, $attr);
        return str_replace(array_keys($replace), array_values($replace), $this->templates['link']);
    }

    /**
     * Converts image tokens into html images
     *
     * @param array $matches
     * @param string $attr
     * @return string
     */
    public function image(array $matches, $attr = null)
    {
        $replace = $this->getReplacements($matches, $attr);
        return str_replace(array_keys($replace), array_values($replace), $this->templates['image']);
    }

    /**
     * Creates the template replacements for a given match set
     *
     * @param array $matches
     * @param string $attr
     * @return array
     */
    protected function getReplacements(array $matches, $attr = null)
    {
        $name = trim($matches['1']);
        $src  = trim($matches['2']);
        $title = '';

        // Remove [ .. ] from the src and extract posible title/alt
        $src = preg_replace('~\s*\[(.+)\] ?\: ?~', '', $src);
        if (preg_match('~(\"|&quot;)(.*)(\"|&quot;)~', $src, $m)) {
            $src = trim(str_replace($m['0'], '', $src));
            $title = trim($m['2']);
        }

        if (!empty($attr)) {
            $attr = ' ' . $attr;
        }

        return array(
            '{src}' => $src,
            '{name}' => $name,
            '{title}'  => $title,
            '{attr}'  => $attr,
        );
    }
}

?>
