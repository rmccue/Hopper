<?php

namespace Hopper\Components\Config;

class Collector extends \Hopper\Collector\Collector {
	private $name;
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $name    The name of the application using the web profiler
	 * @param string $version The version of the application using the web profiler
	 */
	public function __construct($name = null, $version = null) {
		$this->name = $name;
		$this->version = $version;
	}

	/**
	 * {@inheritdoc}
	 */
	public function collect(\Exception $exception = null) {
		$headers = $this->getHeaders();

		include ABSPATH . WPINC . '/version.php';

		$this->data = array(
			'app_name'             => $this->name,
			'token'                => $headers['X-Debug-Token'],
			'wp_version'           => $wp_version,
			'name'                 => isset($this->kernel) ? $this->kernel->getName() : 'n/a',
			'env'                  => isset($this->kernel) ? $this->kernel->getEnvironment() : 'n/a',
			'debug'                => WP_DEBUG,
			'php_version'          => PHP_VERSION,
			'xdebug_enabled'       => extension_loaded('xdebug'),
			'eaccel_enabled'       => extension_loaded('eaccelerator') && ini_get('eaccelerator.enable'),
			'apc_enabled'          => extension_loaded('apc') && ini_get('apc.enabled'),
			'xcache_enabled'       => extension_loaded('xcache') && ini_get('xcache.cacher'),
			'wincache_enabled'     => extension_loaded('wincache') && ini_get('wincache.ocenabled'),
			'zend_opcache_enabled' => extension_loaded('Zend OPcache') && ini_get('opcache.enable'),
			'bundles'              => array(),
			'sapi_name'            => php_sapi_name()
		);
	}

	public function getApplicationName() {
		return $this->data['app_name'];
	}

	/**
	 * Gets the token.
	 *
	 * @return string The token
	 */
	public function getToken() {
		return $this->data['token'];
	}

	/**
	 * Gets the Symfony version.
	 *
	 * @return string The Symfony version
	 */
	public function getWPVersion() {
		return $this->data['wp_version'];
	}

	/**
	 * Gets the PHP version.
	 *
	 * @return string The PHP version
	 */
	public function getPhpVersion() {
		return $this->data['php_version'];
	}

	/**
	 * Gets the application name.
	 *
	 * @return string The application name
	 */
	public function getAppName() {
		return $this->data['name'];
	}

	/**
	 * Gets the environment.
	 *
	 * @return string The environment
	 */
	public function getEnv() {
		return $this->data['env'];
	}

	/**
	 * Returns true if the debug is enabled.
	 *
	 * @return Boolean true if debug is enabled, false otherwise
	 */
	public function isDebug() {
		return $this->data['debug'];
	}

	/**
	 * Returns true if the XDebug is enabled.
	 *
	 * @return Boolean true if XDebug is enabled, false otherwise
	 */
	public function hasXDebug() {
		return $this->data['xdebug_enabled'];
	}

	/**
	 * Returns true if EAccelerator is enabled.
	 *
	 * @return Boolean true if EAccelerator is enabled, false otherwise
	 */
	public function hasEAccelerator() {
		return $this->data['eaccel_enabled'];
	}

	/**
	 * Returns true if APC is enabled.
	 *
	 * @return Boolean true if APC is enabled, false otherwise
	 */
	public function hasApc() {
		return $this->data['apc_enabled'];
	}

	/**
	 * Returns true if Zend OPcache is enabled
	 *
	 * @return Boolean true if Zend OPcache is enabled, false otherwise
	 */
	public function hasZendOpcache() {
		return $this->data['zend_opcache_enabled'];
	}

	/**
	 * Returns true if XCache is enabled.
	 *
	 * @return Boolean true if XCache is enabled, false otherwise
	 */
	public function hasXCache() {
		return $this->data['xcache_enabled'];
	}

	/**
	 * Returns true if WinCache is enabled.
	 *
	 * @return Boolean true if WinCache is enabled, false otherwise
	 */
	public function hasWinCache() {
		return $this->data['wincache_enabled'];
	}

	/**
	 * Returns true if any accelerator is enabled.
	 *
	 * @return Boolean true if any accelerator is enabled, false otherwise
	 */
	public function hasAccelerator() {
		return $this->hasApc() || $this->hasZendOpcache() || $this->hasEAccelerator() || $this->hasXCache() || $this->hasWinCache();
	}

	public function getBundles() {
		return $this->data['bundles'];
	}

	/**
	 * Gets the PHP SAPI name.
	 *
	 * @return string The environment
	 */
	public function getSapiName() {
		return $this->data['sapi_name'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'config';
	}

	protected function getHeaders() {
		if ( function_exists( 'apache_response_headers' ) ) {
			return apache_response_headers();
		}

		$headers = array();
		$data = headers_list();
		if ( ! empty( $data ) ) {
			foreach ($data as $header) {
				if (strpos($header, 'HTTP/') === 0) {
					continue;
				}

				list( $key, $value ) = explode( ':', $header, 2 );
				if ( ! empty( $headers[ $key ] ) )
					$value = $headers[ $key ] . ',' . $value;

				$headers[ $key ] = $value;
			}
		}

		return $headers;
	}
}
