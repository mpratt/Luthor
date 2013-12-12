<?php
/**
 * Context.php
 *
 * @package Luthor
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Luthor\Parser\Extensions;

/**
 * Gives context on block elements
 */
class Context
{
    /** @var array Event Context */
    protected $context = array();

    /**
     * Construct
     *
     * @param array $context
     * @return void
     */
    public function __construct(array $context)
    {
        $this->context = array_merge(array(
            'iterations' => 0,
            'indent' => 0,
            'line' => 0,
            'type' => '',
            'ignore' => array(),
            'to_raw' => array(),
            'close_on' => array('LINE'),
            'close_html' => "",
            'create' => "",
        ), $context);
    }

    /**
     * Wether or not the token can close this block
     * context
     *
     * @param object $token
     * @return bool
     */
    public function canClose($token)
    {
        if (in_array($token->getType(), $this->context['close_on'])) {
            return true;
        }

        return false;
    }

    /**
     * Returns the type of the context
     *
     *
     * @return string;
     */
    public function getType()
    {
        return $this->context['type'];
    }

    /**
     * When the first time this event is registered, then this is run
     *
     * @return string
     */
    public function create()
    {
        return $this->context['create_html'];
    }

    /**
     * Checks if a token type should be ignore
     *
     * @param string $type
     * @return bool
     */
    public function ignore($token)
    {
        return (in_array($token->getType(), $this->context['ignore']));
    }

    /**
     * Checks if a token should be transformed to raw
     *
     * @param string $type
     * @return bool
     */
    public function toRaw($type)
    {
        return (in_array($type, $this->context['to_raw']) || in_array('*', $this->context['to_raw']));
    }

    /**
     * Closes this event
     *
     * @return string
     */
    public function close()
    {
        $return = $this->context['close_html'];
        $this->isAlive = false;
        $this->context = array();
        return $return;
    }
}

?>
