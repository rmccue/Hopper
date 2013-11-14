<?php

namespace Hopper\Profiler;

use Hopper\Collector\CollectorInterface;
use Hopper\Collector\LateCollectorInterface;

class Profiler {
	protected $collectors = array();

	/**
	 * @var boolean
	 */
	protected $enabled;

	/**
	 * Enable the profiler
	 */
	public function enable() {
		$this->enabled = true;
	}

	/**
	 * Disable the profiler
	 */
	public function disable() {
		$this->enabled = false;
	}

	/**
	 * Load the profile for the given token
	 * @param string $token Profile token
	 * @return Profile
	 */
	public function loadProfile($token) {
		$key = sprintf( 'hopper_profile_%s', $token );
		return get_transient( $key );
	}

	/**
	 * Save a profile
	 * @param Profile $profile
	 * @return boolean
	 */
	public function saveProfile(Profile $profile) {
		foreach ($profile->getCollectors() as $collector) {
			if ($collector instanceof LateCollectorInterface) {
				$collector->lateCollect();
			}
		}

		$key = sprintf( 'hopper_profile_%s', $profile->getToken() );
		$expiration = HOUR_IN_SECONDS;
		return set_transient( $key, $profile, $expiration );
	}

	public function collect(\Exception $exception = null) {
		$token = substr(hash('sha256', uniqid(mt_rand(), true)), 0, 6);
		$profile = new Profile($token);

		$profile->setIp( $_SERVER['REMOTE_ADDR'] );
		$profile->setMethod( $_SERVER['REQUEST_METHOD'] );
		$profile->setUrl( $_SERVER['REQUEST_URI'] );
		$profile->setTime( time() );

		foreach ($this->collectors as $collector) {
			$collector->collect($exception);

			$profile->addCollector($collector);
		}

		return $profile;
	}

	public function all() {
		return $this->collectors;
	}

	public function set(array $collectors = array()) {
		$this->collectors = array();
		foreach ($collectors as $collector) {
			$this->add($collector);
		}
	}

	public function add(CollectorInterface $collector) {
		$this->collectors[$collector->getName()] = $collector;
	}

	public function has($name) {
		return isset($this->collectors[$name]);
	}

	public function get($name) {
		if ( ! isset( $this->collectors[ $name ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Collector "%s" does not exist.', $name ) );
		}

		return $this->collectors[ $name ];
	}
}