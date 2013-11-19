<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hopper\Components\Logger;

use Hopper\Collector\LateCollectorInterface;

/**
 * LogDataCollector.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Collector extends \Hopper\Collector\Collector implements LateCollectorInterface {
	private $logger;

	public function __construct($logger = null) {
		if (null !== $logger && $logger instanceof DebugLogger) {
			$this->logger = $logger;

			add_action( 'deprecated_function_run', array( $this, 'deprecatedFunction' ), 10, 3 );
			add_action( 'deprecated_file_included', array( $this, 'deprecatedFile' ), 10, 4 );
			add_action( 'deprecated_argument_run', array( $this, 'deprecatedArgument' ), 10, 3 );
			add_action( 'doing_it_wrong_run', array( $this, 'doingItWrong' ), 10, 3 );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function collect(\Exception $exception = null) {
		// everything is done as late as possible
	}

	/**
	 * {@inheritdoc}
	 */
	public function lateCollect() {
		if (null !== $this->logger) {
			$this->data = array(
				'error_count'       => $this->computeErrorCount(),
				'logs'              => $this->sanitizeLogs($this->logger->getLogs()),
				'deprecation_count' => $this->computeDeprecationCount()
			);
		}
	}

	/**
	 * Gets the called events.
	 *
	 * @return array An array of called events
	 *
	 * @see TraceableEventDispatcherInterface
	 */
	public function countErrors() {
		return isset($this->data['error_count']) ? $this->data['error_count'] : 0;
	}

	/**
	 * Gets the logs.
	 *
	 * @return array An array of logs
	 */
	public function getLogs() {
		return isset($this->data['logs']) ? $this->data['logs'] : array();
	}

	public function countDeprecations() {
		return isset($this->data['deprecation_count']) ? $this->data['deprecation_count'] : 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'logger';
	}

	public function deprecatedFunction($function, $version, $replacement) {
		if ( ! is_null( $replacement ) )
			$message = sprintf( __('%1$s is deprecated since version %2$s! Use %3$s instead.'), $function, $version, $replacement );
		else
			$message = sprintf( __('%1$s is deprecated since version %2$s with no alternative available.'), $function, $version );

		$this->logger->deprecated( $message, $this->getDeprecatedContext( '_deprecated_function' ) );
	}

	public function deprecatedFile($file, $version, $replacement, $message) {
		if ( ! is_null( $replacement ) )
			$message = sprintf( __('%1$s is deprecated since version %2$s! Use %3$s instead.'), $file, $version, $replacement ) . ' ' . $message;
		else
			$message = sprintf( __('%1$s is deprecated since version %2$s with no alternative available.'), $file, $version ) . ' ' . $message;

		$this->logger->deprecated( trim( $message ), $this->getDeprecatedContext( '_deprecated_file' ) );
	}

	public function deprecatedArgument($function, $version, $message) {
		if ( ! is_null( $message ) )
			$message = sprintf( __('%1$s was called with an argument that is deprecated since version %2$s! %3$s'), $function, $version, $message );
		else
			$message = sprintf( __('%1$s was called with an argument that is deprecated since version %2$s with no alternative available.'), $function, $version );

		$this->logger->deprecated( trim( $message ), $this->getDeprecatedContext( '_deprecated_argument' ) );
	}

	public function doingItWrong($function, $message, $version) {
		$version = is_null( $version ) ? '' : sprintf( __( '(This message was added in version %s.)' ), $version );
		$message = sprintf( __( '%1$s was called incorrectly. %2$s %3$s' ), $function, $message, $version );

		$this->logger->deprecated( trim( $message ), $this->getDeprecatedContext( '_doing_it_wrong' ) );
	}

	protected function getDeprecatedContext($caller) {
		$trace = debug_backtrace();

		// Skip all calls after the caller
		while ( !empty($trace) && $trace[0]['function'] !== $caller ) {
			array_shift($trace);
		}

		if (empty($trace)) {
			return array();
		}

		// Exclude the caller itself too
		array_shift($trace);

		if (empty($trace)) {
			return array();
		}

		return array(
			'stack' => $trace,
		);
	}

	private function sanitizeLogs($logs) {
		foreach ($logs as $i => $log) {
			$logs[$i]['context'] = $this->sanitizeContext($log['context']);
		}

		return $logs;
	}

	private function sanitizeContext($context) {
		if (is_array($context)) {
			foreach ($context as $key => $value) {
				$context[$key] = $this->sanitizeContext($value);
			}

			return $context;
		}

		if (is_resource($context)) {
			return sprintf('Resource(%s)', get_resource_type($context));
		}

		if (is_object($context)) {
			return sprintf('Object(%s)', get_class($context));
		}

		return $context;
	}

	private function computeErrorCount() {
		$count = 0;

		$levels = array(Level::ERROR, Level::CRITICAL, Level::ALERT, Level::EMERGENCY);
		foreach ($levels as $level) {
			$count += count( $this->logger->getLogs( $level ) );
		}

		return $count;
	}

	private function computeDeprecationCount() {
		$count = count( $this->logger->getLogs(Level::DEPRECATED) );
		return $count;
	}
}
