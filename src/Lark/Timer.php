<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark;

/**
 * Timer
 *
 * @author Shay Anderson
 */
class Timer
{
	/**
	 * Last time
	 *
	 * @var float
	 */
	private float $last;

	/**
	 * Start time
	 *
	 * @var float
	 */
	private float $start;

	/**
	 * Init
	 *
	 * @param float|null $startMicrotime
	 */
	public function __construct(float $startMicrotime = null)
	{
		$this->start = $startMicrotime ? $startMicrotime : microtime(true);
		$this->last = $this->start;
	}

	/**
	 * Elapsed time since start getter
	 *
	 * @param integer $precision
	 * @return string
	 */
	public function elapsed(int $precision = 4): string
	{
		$this->last = microtime(true);
		return $this->format(microtime(true) - $this->start, $precision);
	}

	/**
	 * Elapsed time since last getter
	 *
	 * @param integer $precision
	 * @return string
	 */
	public function elapsedSinceLast(int $precision = 4): string
	{
		$elapsed = $this->format(microtime(true) - $this->last, $precision);
		$this->last = microtime(true);
		return $elapsed;
	}

	/**
	 * Format elapsed time
	 *
	 * @param float $seconds
	 * @param integer $precision
	 * @return string
	 */
	private function format(float $seconds, int $precision): string
	{
		return number_format(round($seconds, $precision), $precision) . 's';
	}
}
