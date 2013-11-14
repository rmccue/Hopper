<?php

namespace Hopper;

use Twig_LoaderInterface;
use Twig_Environment;
use Twig_SimpleFunction;

use Hopper\TemplateManager;
use Hopper\Profiler\Profiler;

class Console {
	protected $panels = array();

	protected $twig;

	protected $profiler;
	protected $profile;

	public function __construct(Twig_LoaderInterface $templateloader, array $collectors) {
		$options = apply_filters( 'hopper_console_twig_options', array( 'debug' => true ) );

		$this->twig = new Twig_Environment($templateloader, $options);
		$this->twig->addFunction(new Twig_SimpleFunction('path', array( $this, 'get_url' ) ));
		$this->twig->addFunction(new Twig_SimpleFunction('render', function ($url, $params = array()) {
			// echo wp_debug_backtrace_summary();
			$query = parse_url($url, PHP_URL_QUERY);
			parse_str($query, $qvs);
			// var_dump($qvs);
			// return $qvs['route'];
			return $this->route($qvs['route']);
			// var_dump($routed);
			// exit;
		}, array( 'is_safe' => array( 'html' ) )));

		$this->profiler = new Profiler();
		foreach ($collectors as $collector) {
			$this->profiler->add($collector);
		}
		$this->profile = $this->profiler->collect();
		$this->templatemanager = new TemplateManager( $this->profiler, $this->twig, array() );

		add_action( 'wp_after_admin_bar_render', array( $this, 'render' ), 1000 );
		add_action( 'wp_ajax_hopper',            array( $this, 'request' ) );
		add_action( 'shutdown',                  array( $this, 'save_profile' ), 1000 );
	}

	public function add_panels( array $panels ) {
		return;

		foreach ( $panels as $id => $panel ) {
			if ( is_string( $panel ) ) {
				$panel = new $panel( $this->twig, $this->templatemanager );
			}

			$this->panels[ $id ] = $panel;
		}
	}

	public function add_menu_item( $wp_admin_bar ) {
		$wp_admin_bar->add_menu( array(
			'id'        => 'hopper-open-console',
			'parent'    => 'top-secondary',
			'title'     => __( 'Console', 'hopper' ),
			'meta'      => array(
				'title'     => __( 'Open Hopper debug console', 'hopper' ),
			),
		) );
	}

	public function render() {
		$output = array();

		$request = null;

		$toolbar = $this->twig->render(
			'@Hopper/Console/toolbar_js.html.twig',
			array(
				'position' => 'bottom',
				'token' => $this->profile->getToken(),
			)
		);
		echo $toolbar;

		/*foreach ( $profile->all() as $collector ) {
			$data = $collector->collector();
			$data = array(
				'token' => $profile->getToken(),
				'profile' => $profile,
				'collector' => $profile->getCollector($panel),
				'panel' => $panel,
				'page' => $page,
				'request' => $request,
				'templates' => $this->getTemplateManager()->getTemplates($profile),
				'is_ajax' => $request->isXmlHttpRequest(),
			))
			echo $this->twig->render($this->templatemanager->getName($profile, $panel), $data);
		}*/
	}

	public function save_profile() {
		$this->profiler->saveProfile($this->profile);
	}

	public function request() {
		header('Content-Type: text/html');
		echo $this->route();
		exit;
	}

	public function route($route = null) {
		if (empty($route)) {
			if ( empty( $_REQUEST['route'] ) ) {
				return '';
			}

			$route = $_REQUEST['route'];
		}
		$routes = array(
			'/search'                 => '\\Hopper\\Controller\\Profiler:searchAction',
			'/search_bar'             => '\\Hopper\\Controller\\Profiler:searchBarAction',
			'/purge'                  => '\\Hopper\\Controller\\Profiler:purgeAction',
			'/info/:about'            => '\\Hopper\\Controller\\Profiler:infoAction',
			'/import'                 => '\\Hopper\\Controller\\Profiler:importAction',
			'/export/:token.txt'      => '\\Hopper\\Controller\\Profiler:exportAction',
			'/phpinfo'                => '\\Hopper\\Controller\\Profiler:phpinfoAction',
			'/:token/search/results'  => '\\Hopper\\Controller\\Profiler:searchResultsAction',
			'/:token'                 => '\\Hopper\\Controller\\Profiler:panelAction',
			'/wdt/:token'             => '\\Hopper\\Controller\\Profiler:toolbarAction',
			'/'                       => '\\Hopper\\Controller\\Profiler:homeAction',
			// '/{token}/router'         => '\\Hopper\\Controller\\router:panelAction',
			// '/{token}/exception'      => '\\Hopper\\Controller\\exception:showAction',
			// '/{token}/exception.css'  => '\\Hopper\\Controller\\exception:cssAction',
		);
		$routes = apply_filters( 'hopper_routes', $routes );

		$parts = explode('/', ltrim($route, '/'));
		foreach ($routes as $route_url => $callback) {
			$vars = array();
			$ext = '';
			$route_url = ltrim($route_url, '/');
			if (strpos($route_url, '.') !== false) {
				list($route_url, $ext) = explode('.', $route_url, 2);
				$ext = '.' . $ext;
			}
			$route_parts = explode('/', $route_url);

			if (count($route_parts) != count($parts)) {
				continue;
			}

			for ($i = 0; $i < count($route_parts); $i++) {
				$real = $parts[$i];
				$wanted = $route_parts[$i];

				if (strpos($wanted, ':') === 0) {
					$wanted = substr($wanted, 1);
					if (!empty($ext)) {
						if (strpos($real, $ext) !== (strlen($real) - strlen($ext))) {
							continue 2;
						}

						if (strlen($ext) <= strlen($real) && substr_compare($real, $ext, -strlen($ext), strlen($ext)) === 0) {
							$real = substr($real, 0, -strlen($ext));
						}
					}
					$vars[$wanted] = urldecode($real);
				}
				else {
					if ($real !== $wanted) {
						continue 2;
					}
				}
			}

			return self::dispatch($callback, $vars);
		}

		throw new Exception('No route found -- are you sure this is a valid URL?', 404);
	}

	public function dispatch($callback, $vars = array()) {
		list( $controller, $method ) = explode( ':', $callback, 2 );
		$controller = new $controller( $this->profiler, $this->twig );
		$callback = array( $controller, $method );

		if ( ! is_callable( $callback ) ) {
			return '';
		}

		$params = $this->sort_callback_params( $callback, $_GET + $vars );
		$response = call_user_func_array( $callback, $params );

		if (is_wp_error($response)) {
			return '';
		}

		return $response;
	}

	/**
	 * Sort parameters by order specified in method declaration
	 *
	 * Takes a callback and a list of available params, then filters and sorts
	 * by the parameters the method actually needs, using the Reflection API
	 *
	 * @param callback $callback
	 * @param array $params
	 * @return array
	 */
	protected function sort_callback_params( $callback, $provided ) {
		if ( is_array( $callback ) )
			$ref_func = new \ReflectionMethod( $callback[0], $callback[1] );
		else
			$ref_func = new \ReflectionFunction( $callback );

		$wanted = $ref_func->getParameters();
		$ordered_parameters = array();

		foreach ( $wanted as $param ) {
			if ( isset( $provided[ $param->getName() ] ) ) {
				// We have this parameters in the list to choose from
				$ordered_parameters[] = $provided[ $param->getName() ];
			}
			elseif ( $param->isDefaultValueAvailable() ) {
				// We don't have this parameter, but it's optional
				$ordered_parameters[] = $param->getDefaultValue();
			}
			else {
				// We don't have this parameter and it wasn't optional, abort!
				throw new \InvalidArgumentException( sprintf( __( 'Missing parameter %s' ), $param->getName() ) );
			}
		}
		return $ordered_parameters;
	}

	public static function get_url( $route, array $parameters = array() ) {
		$url = admin_url( 'admin-ajax.php' );

		// Replace {key} with its corresponding value
		$replacer = function ($key) {
			return '{' . $key . '}';
		};
		$tokens = array_map($replacer, array_keys( $parameters ) );
		$route = str_replace($tokens, array_values( $parameters ), $route);

		$parameters['action'] = 'hopper';
		$parameters['route'] = $route;
		$url = add_query_arg( $parameters, $url );

		return apply_filters( 'hopper_route_url', $url, $route, $parameters );
	}

}
