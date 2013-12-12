<?php
/**
 * Setup the environment
 */
require __DIR__ . '/../Lib/Luthor/Autoload.php';

/**
 * @codeCoverageIgnore
 */
class Mentions extends \Luthor\Parser\Extensions\Adapters\InlineAdapter
{
    protected $regex = '~@([^ ]+)~A';
    public function parse()
    {
        return '<link>' . $this->matches['1'] . '</link>';
    }
}

?>
