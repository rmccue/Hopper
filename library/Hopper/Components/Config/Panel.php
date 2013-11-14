<?php

namespace Hopper\Components\Config;

use Hopper\TemplateManager;
use Twig_Environment;

class Panel implements \Hopper\Panel {
	public function __construct() {
	}

	public function getName() {
		return __( 'Config', 'hopper' );
	}

	public function getData() {
		/*$data = array(
            'token'     => $token,
            'profile'   => $profile,
            'collector' => $profile->getCollector($panel),
            'panel'     => $panel,
            'page'      => $page,
            'request'   => $request,
            'templates' => $this->getTemplateManager()->getTemplates($profile),
            'is_ajax'   => $request->isXmlHttpRequest(),
        );*/
		$data = array();
        return $this->twig->render($this->templatemanager->getName($profile, $panel), $data);
	}
}
