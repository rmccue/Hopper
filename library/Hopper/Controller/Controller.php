<?php

namespace Hopper\Controller;

abstract class Controller {
	protected function getUrl( $route, $parameters ) {
		return \Hopper\Console::get_url($route, $parameters);
	}

	protected function redirect($url, $code, array $headers = array()) {
		status_header( $code );
		foreach ($headers as $key => $value) {
			var_dump($key, $value);
			header($key, $value);
		}

		wp_redirect( $url );
		exit;
	}

	protected function response($data, $code, array $headers = array()) {
		status_header( $code );
		foreach ($headers as $key => $value) {
			header($key, $value);
		}

		return $data;
	}

	protected function get($key, $default = null) {
		if ( ! isset( $_GET[ $key ] ) ) {
			return $default;
		}

		// Remove magic quotes, since WP sucks.
		$value = stripslashes_deep( $_GET[ $key ] );
		return $value;
	}

	protected function notFound($message) {
		return $this->response($message, 404);
	}
}
