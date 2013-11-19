<?php

namespace Hopper\Components\Hooks;

use Hopper\Collector\LateCollectorInterface;

class Collector extends \Hopper\Collector\Collector implements LateCollectorInterface {
	private $name;
	private $version;
	protected $called = array();

	/**
	 * Constructor.
	 *
	 * @param string $name    The name of the application using the web profiler
	 * @param string $version The version of the application using the web profiler
	 */
	public function __construct($name = null, $version = null) {
		$this->name = $name;
		$this->version = $version;

		add_action( 'all', array( $this, 'recordCall' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function collect(\Exception $exception = null) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function lateCollect() {
		global $wp_action, $wp_filter;

		$this->data = array(
			'called' => array(),
			'notcalled' => array(),
		);

		foreach ($wp_filter as $hook => $prioritised) {
			$listeners = array();

			foreach ($prioritised as $priority => $callbacks) {
				foreach ($callbacks as $idx => $callback_data) {
					$listeners[] = new Listener($hook, $callback_data['function'], $priority, $callback_data['accepted_args']);
				}
			}

			// Note: check both did_action() and our internal check, as there
			// may be calls before we got called (plugins_loaded, e.g.)
			if ( did_action( $hook ) || isset( $this->called[ $hook ] ) ) {
				$this->data['called'] = array_merge( $this->data['called'], $listeners );
			}
			else {
				$this->data['notcalled'] = array_merge( $this->data['notcalled'], $listeners );
			}
		}
	}

	public function getCalledListeners() {
		return $this->data['called'];
	}

	public function getNotCalledListeners() {
		return $this->data['notcalled'];
	}

	public function recordCall($hook) {
		$this->called[$hook] = true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'hooks';
	}

	public function __sleep() {
		return array( 'name', 'version', 'data' );
	}
}
