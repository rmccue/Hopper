<?php
/**
 * Plugin Name: Hopper
 * Description: Debug your WordPress code with ease!
 */

namespace Hopper;

// Interface and basic setup
add_action( 'plugins_loaded',            __NAMESPACE__ . '\\setup' );
add_action( 'admin_bar_menu',            __NAMESPACE__ . '\\add_menu_item',  1000 );

// Default components
add_filter( 'hopper_register_collectors', __NAMESPACE__ . '\\default_collectors', -10 );
add_filter( 'hopper_templates',           __NAMESPACE__ . '\\default_templates', -10 );

// Public API
add_filter( 'hopper_logger', __NAMESPACE__ . '\\logger' );

function setup() {
	// Include Composer's autoloader
	include __DIR__ . '/vendor/autoload.php';

	// Set up our own autoloader
	$loader = new \Composer\Autoload\ClassLoader();
	$loader->add('Hopper', __DIR__ . '/library');

	do_action( 'hopper_autoload_register', $loader );

	$loader->register();

	// Load collectors
	$collectors = apply_filters( 'hopper_register_collectors', array() );

	// Load the console itself
	$loader = new \Twig_Loader_Filesystem( __DIR__ . '/resources/views', 'Hopper' );
	$loader->addPath( __DIR__ . '/resources/views', 'Hopper' );
	$console = new Console($loader, $collectors);
}

function add_menu_item( $wp_admin_bar ) {
	$wp_admin_bar->add_menu( array(
		'id'        => 'hopper-open-console',
		'parent'    => 'top-secondary',
		'title'     => __( 'Console', 'hopper' ),
		'meta'      => array(
			'title'     => __( 'Open Hopper debug console', 'hopper' ),
		),
	) );
}

function default_collectors( $panels ) {
	$panels[] = new Components\Config\Collector();
	$panels[] = new Components\Hooks\Collector();
	$panels[] = new Components\Logger\Collector( apply_filters( 'hopper_logger', null ) );
	$panels[] = new Components\Memory\Collector();

	return $panels;
}

function default_templates( $templates ) {
	$templates[] = array( 'config', '@Hopper/Collector/config.html.twig' );
	$templates[] = array( 'hooks', '@Hopper/Collector/hooks.html.twig' );
	$templates[] = array( 'logger', '@Hopper/Collector/logger.html.twig' );
	$templates[] = array( 'memory', '@Hopper/Collector/memory.html.twig' );

	return $templates;
}

/**
 * Get request logger instance
 *
 * @return Hopper\Component\Logger\Logger
 */
function logger() {
	static $logger = null;

	if ( empty( $logger ) ) {
		$logger = new Components\Logger\Logger();
	}

	return $logger;
}
