<?php
/**
 * Plugin Name: Hopper
 * Description: Debug your WordPress code with ease!
 */

namespace Hopper;

add_action( 'plugins_loaded',            __NAMESPACE__ . '\\setup' );
add_action( 'admin_bar_menu',            __NAMESPACE__ . '\\add_menu_item',  1000 );

add_filter( 'hopper_register_collectors', __NAMESPACE__ . '\\default_collectors', -10 );
add_filter( 'hopper_templates',           __NAMESPACE__ . '\\default_templates', -10 );

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
	$loader->addPath( __DIR__ . '/resources/views-symfony', 'WebProfiler' );
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

function output_console() {
	$loader = new \Twig_Loader_Filesystem( __DIR__ . '/resources/views' );

	$console = new Console( $loader );
	$panels = apply_filters( 'hopper_register_panels', array() );

	$output = $console->render();

	echo $output;
}

function default_collectors( $panels ) {
	$panels[] = new Components\Config\Collector();

	return $panels;
}

function default_templates( $templates ) {
	$templates[] = array( 'config', '@Hopper/Collector/config.html.twig' );

	return $templates;
}

function exception_handler($exception) {
	var_dump($exception->getMessage());
	var_dump($exception->getTraceAsString());
	exit;
}
/**
 * Return a comma separated string of functions that have been called to get to the current point in code.
 *
 * @link http://core.trac.wordpress.org/ticket/19589
 * @since 3.4.0
 *
 * @param string $ignore_class A class to ignore all function calls within - useful when you want to just give info about the callee
 * @param int $skip_frames A number of stack frames to skip - useful for unwinding back to the source of the issue
 * @param bool $pretty Whether or not you want a comma separated string or raw array returned
 * @return string|array Either a string containing a reversed comma separated trace or an array of individual calls.
 */
function debug_backtrace_summary( $trace, $pretty = true ) {
	$caller = array();
	$check_class = ! is_null( $ignore_class );

	foreach ( $trace as $call ) {
		if ( isset( $call['class'] ) ) {
			if ( $check_class )
				continue; // Filter out calls

			$caller[] = "{$call['class']}{$call['type']}{$call['function']}";
		} else {
			if ( in_array( $call['function'], array( 'do_action', 'apply_filters' ) ) ) {
				$caller[] = "{$call['function']}('{$call['args'][0]}')";
			} elseif ( in_array( $call['function'], array( 'include', 'include_once', 'require', 'require_once' ) ) ) {
				$caller[] = $call['function'] . "('" . str_replace( array( WP_CONTENT_DIR, ABSPATH ) , '', $call['args'][0] ) . "')";
			} else {
				$caller[] = $call['function'];
			}
		}
	}
	if ( $pretty )
		return join( ', ', array_reverse( $caller ) );
	else
		return $caller;
}

set_exception_handler(__NAMESPACE__ . '\\exception_handler');
