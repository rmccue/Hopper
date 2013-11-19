<?php

namespace Hopper\Components\Logger;

use \Psr\Log\LoggerInterface;

interface DebugLogger extends LoggerInterface {
	public function deprecated($message, array $context = array());
	public function getLogs($level = null);
}