<?php

namespace Hopper\Components\Logger;

use \Psr\Log\AbstractLogger;

class Logger extends AbstractLogger implements DebugLogger {
	/**
	 * Store the logged messages
	 * @var array
	 */
	protected $messages = array();

	public function __construct() {
		$this->messages = array();
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function log($level, $message, array $context = array()) {
		$this->messages[] = array(
			'message' => (string) $message,
			'context' => $context,
			'level' => $level,
		);
	}

	public function deprecated($message, array $context = array()) {
		return $this->log(Level::DEPRECATED, $message, $context);
	}

	public function getLogs($level = null) {
		if (empty($level)) {
			return $this->messages;
		}

		return array_filter($this->messages, function ($item) use ($level) {
			return ($item['level'] === $level);
		});
	}

	public function clear() {
		$this->messages = array();
	}
}