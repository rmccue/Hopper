<?php

namespace Hopper\Profiler;

use Hopper\Collector\CollectorInterface;

class Profile {
	protected $token;
	protected $collectors = array();
	protected $ip;
	protected $method;
	protected $url;
	protected $time;

	public function __construct($token) {
		$this->token = $token;
	}

	public function getToken() {
		return $this->token;
	}

	public function getIp() {
		return $this->ip;
	}

	public function setIp($ip) {
		$this->ip = $ip;
	}

	public function getMethod() {
		return $this->method;
	}

	public function setMethod($method) {
		$this->method = $method;
	}

	public function getUrl() {
		return $this->url;
	}

	public function setUrl($url) {
		$this->url = $url;
	}

	public function getTime() {
		return $this->time;
	}

	public function setTime($time) {
		$this->time = $time;
	}

	public function getCollectors() {
		return $this->collectors;
	}

	public function getCollector($name) {
		if ( ! isset( $this->collectors[ $name ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Collector "%s" does not exist.', $name ) );
		}

		return $this->collectors[ $name ];
	}

	public function setCollectors(array $collectors) {
		$this->collectors = array();
		foreach ($collectors as $collector) {
			$this->addCollector($collector);
		}
	}

	public function addCollector(CollectorInterface $collector) {
		$this->collectors[$collector->getName()] = $collector;
	}

	public function hasCollector($name) {
		return isset( $this->collectors[ $name ] );
	}

	public function __sleep() {
		return array('token', /*'parent', 'children',*/ 'collectors', 'ip', 'method', 'url', 'time');
	}
}
