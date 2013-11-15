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
    public function testEscapingAndParagraph()
    {
        $lex = new \Luthor\Luthor(array('escape' => true, 'auto_p' => false));
        $text = '<i>This is italic</i>, and this is **strong**';
        $result = $lex->parse($text);
        $this->assertEquals('&lt;i&gt;This is italic&lt;/i&gt;, and this is <strong>strong</strong>', $result);

        $lex = new \Luthor\Luthor(array('escape' => true));
        $text = '&gt; This should be a blockquote, even thought the char was escaped';
        $result = str_replace("\n", '', $lex->parse($text));
        $this->assertEquals('<blockquote><p>This should be a blockquote, even thought the char was escaped</p></blockquote>', $result);

        $lex = new \Luthor\Luthor(array('escape' => true, 'auto_p_strategy' => 'autoParagraph2'));
        $text = '&gt; This should be a blockquote, even thought the char was escaped';
        $result = str_replace("\n", '', $lex->parse($text));
        $this->assertEquals('<blockquote><p>This should be a blockquote, even thought the char was escaped</p></blockquote>', $result);

        $lex = new \Luthor\Luthor();
        $text = 'this is an <object>, used to test stupid paragraph conditional';
        $result = str_replace("\n", '', $lex->parse($text));
        $this->assertEquals('<p>this is an <object>, used to test stupid paragraph conditional</p>', $result);
    }

    public function testExtension()
    {
        $lex = new \Luthor\Luthor();
        $lex->overwriteOperation('RAW', function ($token) {
            if ($token->content == 'Hello') {
                return 'World';
            }
            return $token->content;
        });

        $text = 'Hello!';
        $result = $lex->parse($text);
        $this->assertEquals('<p>World!</p>', $result);

        $lex = new \Luthor\Luthor();
        $lex->addTokenOperation('(\.\.(\d+)\.\.)', 'HOUSE', function ($token) {
            return $token->matches['2'] . 'th';
        });

        $text = 'Hi friends today is the ..14.. of november';
        $result = $lex->parse($text);
        $this->assertEquals('<p>Hi friends today is the 14th of november</p>', $result);
    }

    public function testFilters()
    {
        $lex = new \Luthor\Luthor();
        $lex->addFilter(function ($text) {
            return str_replace('a', 'b', $text);
        });

        $text = 'Hola Amigos, como están? Feliz Navidad';
        $result = $lex->parse($text);
        $this->assertEquals('<p>Holb Amigos, como están? Feliz Nbvidbd</p>', $result);
    }

    public function testFilterExceptions()
    {
        $this->setExpectedException('InvalidArgumentException');
        $lex = new \Luthor\Luthor();
        $lex->addFilter('A_non_existant_function_name');
    }

    public function testUnclosedBlocks()
    {
        $lex = new \Luthor\Luthor();

        $text = "```\n This is a code";
        $result = $lex->parse($text);
        $this->assertEquals("<pre><code>\n This is a code\n</code></pre>", $result);
    }

    public function testOverwriteOperation()
    {
        $lex = new \Luthor\Luthor();

        // Test that when an uncallable is given, the content is returned untouched
        $lex->overwriteOperation('RAW', '');
        $result = $lex->parse('Hello friends');
        $this->assertEquals('<p>Hello friends</p>', $result);

        $lex->overwriteOperation('RAW', function ($token) {
            return str_replace('l', 'w', $token->content);
        });

        $result = $lex->parse('Hello friends');
        $this->assertEquals('<p>Hewwo friends</p>', $result);
    }

    public function testNestingAndIndents()
    {
        $lex = new \Luthor\Luthor(array(
            'max_nesting' => 0,
            'indent_trigger' => 2
        ));

        $result = $lex->parse('  This should be inside a code block');
        $this->assertEquals("<pre><code>\nThis should be inside a code block\n</code></pre>", $result);

        $result = $lex->parse("- level 1\n    - level 2");
        $this->assertEquals("<ul>\n<li>\n<p>level 1\n- level 2</p>\n</li>\n</ul>", $result);
    }
}

?>
