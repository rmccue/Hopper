<?php

namespace Hopper;

interface Panel {
	public function __construct();
	public function getName(); 
	public function getData();
}
