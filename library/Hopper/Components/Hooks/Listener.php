<?php

namespace Hopper\Components\Hooks;

use Hopper\Exception;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionException;

class Listener {
	protected $callback;

	public function __construct($hook, $callback, $priority = 10, $num_args = 1) {
		$this->callback = $callback;

		$this->data = array(
			'event' => $hook,
			'priority' => $priority,
			'num_args' => $num_args,

			'type' => $this->callbackType(),
			'class' => null,
			'method' => null,
			'function' => null,
			'file' => null,
			'line' => null,
		);

		try {
			switch ($this->data['type']) {
				case 'Function':
					$this->data['function'] = $callback;
					// fall-through

				case 'Closure':
					$r = new ReflectionFunction($this->callback);
					break;

				case 'Method':
					if (is_object($this->callback[0])) {
						$this->data['class'] = get_class($this->callback[0]);
					}
					else {
						$this->data['class'] = $this->callback[0];
					}

					$this->data['method'] = $this->callback[1];

					$r = new ReflectionMethod($this->callback[0], $this->callback[1]);
					break;

				default:
					throw new Exception('Invalid callback type');
					break;
			}

			$this->data['file'] = $r->getFileName();
			$this->data['line'] = $r->getStartLine();
		}
		catch (ReflectionException $e) {
			// Ignore
		}
	}

	public function getClass() {
		return $this->data['class'];
	}

	public function getEvent() {
		return $this->data['event'];
	}

	public function getFile() {
		return $this->data['file'];
	}

	public function getFunction() {
		return $this->data['function'];
	}

	public function getLine() {
		return $this->data['line'];
	}

	public function getMethod() {
		return $this->data['method'];
	}

	public function getType() {
		return $this->data['type'];
	}

	public function __sleep() {
		return array( 'data' );
	}

	protected function callbackType() {
		if ( is_string( $this->callback ) ) {
			return 'Function';
		}

		if ( is_object( $this->callback ) ) {
			return 'Closure';
		}

		if ( is_array( $this->callback ) ) {
			return 'Method';
		}

		return 'Unknown';
	}
}