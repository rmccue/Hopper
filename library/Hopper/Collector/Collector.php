<?php
namespace Hopper\Collector;

abstract class Collector implements CollectorInterface, \Serializable {
	protected $data;

	public function serialize() {
		return serialize($this->data);
	}

	public function unserialize($data) {
		$this->data = unserialize($data);
	}
}
