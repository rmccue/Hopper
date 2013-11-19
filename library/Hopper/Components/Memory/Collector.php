<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hopper\Components\Memory;

use Hopper\Collector\LateCollectorInterface;

/**
 * MemoryDataCollector.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Collector extends \Hopper\Collector\Collector implements LateCollectorInterface {
	public function __construct() {
		$this->data = array(
			'memory'       => 0,
			'memory_limit' => wp_convert_hr_to_bytes(ini_get('memory_limit')),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function collect(\Exception $exception = null) {
		$this->updateMemoryUsage();
	}

	/**
	 * {@inheritdoc}
	 */
	public function lateCollect() {
		$this->updateMemoryUsage();
	}

	/**
	 * Gets the memory.
	 *
	 * @return integer The memory
	 */
	public function getMemory()
	{
		return $this->data['memory'];
	}

	/**
	 * Gets the PHP memory limit.
	 *
	 * @return integer The memory limit
	 */
	public function getMemoryLimit() {
		return $this->data['memory_limit'];
	}

	/**
	 * Updates the memory usage data.
	 */
	public function updateMemoryUsage() {
		$this->data['memory'] = memory_get_peak_usage(true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'memory';
	}
}
