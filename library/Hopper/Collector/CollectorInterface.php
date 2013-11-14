<?php

namespace Hopper\Collector;

interface CollectorInterface {
    public function collect(\Exception $exception = null);
    public function getName();
}
