<?php
/**
 * Benchmark.php
 * This class is used to benchmark the framework
 *
 * @package Bolido
 * @author  Michael Pratt <pratt@hablarmierda.net>
 * @link    http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Luthor;

class Benchmark
{
    protected static $timers = array();
    protected static $memoryTrackers = array();
    public static $results = array();

    /**
     * Start Memory Tracker
     *
     * @param  string $name Unique memory tracker name
     * @return void
     *
     * @throws InvalidArgumentException when a tracker doesnt exist
     */
    public static function startMemoryTracker($name)
    {
        if (!empty(self::$memoryTrackers[$name]))
            throw new \InvalidArgumentException('A memory tracker named ' . $name . ' already exists');

        self::$memoryTrackers[$name] = memory_get_usage();
    }

    /**
     * Starts a Timer Tracker
     *
     * @param  string $name Unique timer tracker name
     * @return void
     *
     * @throws InvalidArgumentException when a tracker doesnt exist
     */
    public static function startTimer($name)
    {

        self::$timers[$name] = microtime(true);
    }

    /**
     * Stops a Timer Tracker
     *
     * @param  string $name Unique timer tracker name
     * @return int The time elapsed between the timer tracker $name and now
     *
     * @throws InvalidArgumentException when a tracker doesnt exist
     */
    public static function stopTimer($name)
    {
        if (!isset(self::$timers[$name]))
            throw new \InvalidArgumentException('The timer tracker named ' . $name . ' doesnt exist');

        return self::$results['timer_' . $name] = microtime(true) - self::$timers[$name];
    }

    /**
     * Stops a Memory Tracker
     *
     * @param  string $name Unique memory tracker name
     * @return int The memory elapsed
     *
     * @throws InvalidArgumentException when a tracker doesnt exist
     */
    public static function stopMemoryTracker($name)
    {
        if (!isset(self::$memoryTrackers[$name]))
            throw new \InvalidArgumentException('The memory tracker named ' . $name . ' doesnt exist');

        return self::$results['memory_' . $name] = number_format(((memory_get_usage() - self::$memoryTrackers[$name])/1024), 2);
    }

    /**
     * Stops all timers
     *
     * @return void
     */
    public static function stopAllWatches()
    {
        foreach(self::$timers as $name => $time)
            self::stopTimer($name);
    }

    /**
     * Stops all Memory Trackers
     *
     * @return void
     */
    public static function stopAllMemoryTrackers()
    {
        foreach(self::$memoryTrackers as $name => $trackers)
            self::stopMemoryTracker($name);
    }
}
?>
