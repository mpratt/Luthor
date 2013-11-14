Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod
tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At
vero eos et accusam et justo duo **dolores** et ea rebum. Stet clita kasd gubergren,
no sea takimata sanctus est Lorem ipsum dolor sit amet.

    Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod
    tempor invidunt ut labore et **dolore** magna aliquyam erat, sed diam voluptua. At
    vero eos et accusam et justo duo dolores et ea rebum. _Stet_ clita kasd gubergren,
    no sea takimata sanctus est Lorem ipsum dolor sit amet.

```
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod
tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At
vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren,
no sea takimata sanctus est Lorem ipsum dolor sit amet.

Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod
tempor invidunt ut labore et dolore **magna** aliquyam erat, sed diam voluptua. At
vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren,
no sea takimata sanctus est Lorem ipsum dolor sit amet.

Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod
tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At
vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren,
no sea takimata sanctus est Lorem ipsum dolor sit amet.
```

hey!

```{#id .class}
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod
tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At
vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren,
no sea takimata sanctus est Lorem ipsum dolor sit amet.
```

```
    /**
     * Automatically applies "p" and "br" markup to text.
     * Based on Wordpress's wpautop function.
     *
     * @param string $text
     * @return string
     * @link http://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
     */
    public function autoParagraph($text)
    {
        if (trim($text) === '') {
            return '';
        }

        // Reserve content that should not be trimmed
        $text = $this->reserve($text);

        // Trim starting whitespace on each line
        $text = preg_replace('~^[ \t]+~m', '', $text);

        // Space things out a little
        $text = preg_replace('!(<' . $this->blocks . '[^>]*>)!', "\n$1", $text);
        $text = preg_replace('!(</' . $this->blocks . '>)!', "$1\n\n", $text);

        // No "<p>" inside object/embed tags
        if (strpos($text, '<object') !== false ) {
            $text = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $text);
            $text = preg_replace('|\s*</embed>\s*|', '</embed>', $text);
        }

        // take care of duplicate line breaks
        $text = preg_replace("/\n\n+/", "\n\n", $text);

        // make paragraphs, including one at the end
        $content = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $text = '';
        foreach ($content as $c) {
            $text .= '<p>' . trim($c, "\n") . "</p>\n";
        }

        // Remove empty paragraphs
        $text = preg_replace('|<p>\s*</p>|', '', $text);
        $text = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $text);

        // Dont <p> all over a tag
        $text = preg_replace('!<p>\s*(</?' . $this->blocks . '[^>]*>)\s*</p>!', "$1", $text);

        // Manage nested <li>, <blockquote>, etc
        $text = preg_replace("|<p>(<li.+?)</p>|", "$1", $text);
        $text = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $text);
        $text = str_replace('</blockquote></p>', '</p></blockquote>', $text);
        $text = preg_replace('!<p>\s*(</?' . $this->blocks . '[^>]*>)!', "$1", $text);
        $text = preg_replace('!(</?' . $this->blocks . '[^>]*>)\s*</p>!', "$1", $text);
        $text = preg_replace('!(</?' . $this->blocks . '[^>]*>)\s*<br />!', "$1", $text);
        $text = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $text);
        $text = preg_replace("|\n</p>$|", '</p>', $text);

        if (!empty($this->preTags)) {
            $text = str_replace(array_keys($this->preTags), array_values($this->preTags), $text);
        }

        // Add <br/> on lines ending with 2 spaces
        $text = preg_replace('~ {2}$~', "<br />\n", $text);

        return $text;
    }
```
