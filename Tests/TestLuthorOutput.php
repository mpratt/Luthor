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

class TestLuthor extends PHPUnit_Framework_TestCase
{
    protected function get($file)
    {
        $out = array();
        foreach (array($file . '.md', $file . '.html') as $f) {
            $out[] = trim(file_get_contents(__DIR__ . '/Samples/' . $f), "\n");
        }

        return $out;
    }

    public function testHeadings()
    {
        list($input, $expected) = $this->get('Headings');
        $lex = new \Luthor\Luthor();

        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testLinks()
    {
        list($input, $expected) = $this->get('Links');
        $lex = new \Luthor\Luthor();

        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testImages()
    {
        list($input, $expected) = $this->get('Images');
        $lex = new \Luthor\Luthor();

        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testInline()
    {
        list($input, $expected) = $this->get('Inline');
        $lex = new \Luthor\Luthor();

        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testHr()
    {
        list($input, $expected) = $this->get('Hr');
        $lex = new \Luthor\Luthor();

        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testBlockquote()
    {
        list($input, $expected) = $this->get('Blockquote');
        $lex = new \Luthor\Luthor();

        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testCodeBlock()
    {
        list($input, $expected) = $this->get('CodeBlock');
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

    public function testFootnote()
    {
        list($input, $expected) = $this->get('Footnote');
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

    /*
    public function testList()
    {
        list($input, $expected) = $this->get('List');
        $lex = new \Luthor\Luthor();

        $result = $lex->parse($input);
        $this->assertEquals($expected, $result);
    }*/
}

?>
