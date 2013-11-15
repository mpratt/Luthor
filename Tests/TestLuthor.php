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
}

?>
