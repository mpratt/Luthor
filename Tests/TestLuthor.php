<?php
/**
 * TestLuthor.php
 *
 * @author  Michael Pratt <pratt@hablarmierda.net>
 * @link    http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class TestLuthorOutput extends PHPUnit_Framework_TestCase
{
    protected function get($file)
    {
        $out = array();
        foreach (array($file . '.md', $file . '.html') as $f) {
            $out[] = trim(file_get_contents(__DIR__ . '/Samples/' . $f), "\n");
        }

        return $out;
    }

    public function testBlockquotes()
    {
        list($input, $expected) = $this->get('Blockquote');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);

        $lex = new \Luthor\Luthor(array('allow_html' => false));
        $result = preg_replace("~\n~", '', $lex->parse('&gt; Hi'));
        $this->assertEquals('<blockquote><p>Hi</p></blockquote>', $result);
    }

    public function testLists()
    {
        list($input, $expected) = $this->get('ListTypes');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);

        list($input, $expected) = $this->get('ListLevels');
        $lex = new \Luthor\Luthor(array('max_nesting' => 4));
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testCodeblock()
    {
        list($input, $expected) = $this->get('RegularCodeblock');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);

        list($input, $expected) = $this->get('FencedCodeblock');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testHeading()
    {
        list($input, $expected) = $this->get('Heading');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testHorizontalRule()
    {
        list($input, $expected) = $this->get('Hr');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testInlineSpan()
    {
        list($input, $expected) = $this->get('InlineSpan');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testUrlEmail()
    {
        list($input, $expected) = $this->get('UrlEmail');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testImagesLinks()
    {
        list($input, $expected) = $this->get('Images');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);

        list($input, $expected) = $this->get('Links');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testAbbr()
    {
        list($input, $expected) = $this->get('Abbr');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testFootNote()
    {
        list($input, $expected) = $this->get('Footnote');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testReference()
    {
        list($input, $expected) = $this->get('Reference');
        $lex = new \Luthor\Luthor();
        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testExtensionAndFilters()
    {
        $lex = new \Luthor\Luthor(array('allow_html' => false, 'auto_p' => false));
        $lex->addExtension(new Mentions());
        $lex->addFilter(function ($text) {
            return str_replace('pratt', 'mike', $text);
        });

        $result = $lex->parse('Hi @pratt');
        $this->assertEquals('Hi <link>mike</link>', $result);
    }

    public function testBadFilter()
    {
        $this->setExpectedException('InvalidArgumentException');

        $lex = new \Luthor\Luthor();
        $lex->addFilter('hi');
    }

    public function testAutoP()
    {
        $lex = new \Luthor\Luthor();
        $result = preg_replace("~\n~", '', $lex->parse("<object><embed><param/></embed></object>"));
        $this->assertEquals('<p><object><embed><param/></embed></object></p>', $result);
    }
}

?>
