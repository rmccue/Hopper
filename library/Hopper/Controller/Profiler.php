<?php

namespace Hopper\Controller;

use Hopper\TemplateManager;

class Profiler extends Controller {
	private $templateManager;
	private $generator;
	private $profiler;
	private $twig;
	private $toolbarPosition;

	public function __construct(\Hopper\Profiler\Profiler $profiler = null, \Twig_Environment $twig, $toolbarPosition = 'normal') {
		// $this->generator = $generator;
		$this->profiler = $profiler;
		$this->twig = $twig;
		$this->toolbarPosition = $toolbarPosition;
	}

	/**
	 * Redirects to the last profiles.
	 *
	 * @return RedirectResponse A RedirectResponse instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function homeAction() {
		if (null === $this->profiler) {
			return new WP_Error( 'hopper_profiler_disabled', 'The profiler must be enabled.', array( 'status' => 404) );
		}

		$this->profiler->disable();

		$url = $this->getUrl('/{token}/search/results', array('token' => 'empty', 'limit' => 10));
		return $this->redirect($url, 302, array('Content-Type' => 'text/html'));
	}

	/**
	 * Renders a profiler panel for the given token.
	 *
	 * @param Request $request The current HTTP request
	 * @param string  $token   The profiler token
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function panelAction($token) {
		if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		}

		$this->profiler->disable();

		$panel = $this->get('panel', 'config');
		$page = $this->get('page', 'home');

		if (!$profile = $this->profiler->loadProfile($token)) {
			return $this->response($this->twig->render('@Hopper/Console/info.html.twig', array('about' => 'no_token', 'token' => $token)), 200, array('Content-Type' => 'text/html'));
		}

		if (!$profile->hasCollector($panel)) {
			return $this->notFound(sprintf('Panel "%s" is not available for token "%s".', $panel, $token));
		}

		return $this->response($this->twig->render($this->getTemplateManager()->getName($profile, $panel), array(
			'token'     => $token,
			'profile'   => $profile,
			'collector' => $profile->getCollector($panel),
			'panel'     => $panel,
			'page'      => $page,
			// 'request'   => $request,
			'templates' => $this->getTemplateManager()->getTemplates($profile),
			// 'is_ajax'   => $request->isXmlHttpRequest(),
		)), 200, array('Content-Type' => 'text/html'));
	}

	/**
	 * Exports data for a given token.
	 *
	 * @param string $token The profiler token
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function exportAction($token)
	{
		if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		}

		$this->profiler->disable();

		if (!$profile = $this->profiler->loadProfile($token)) {
			return $this->notFound(sprintf('Token "%s" does not exist.', $token));
		}

		return $this->response($this->profiler->export($profile), 200, array(
			'Content-Type'        => 'text/plain',
			'Content-Disposition' => 'attachment; filename= '.$token.'.txt',
		));
	}

	/**
	 * Purges all tokens.
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function purgeAction()
	{
		if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		}

		$this->profiler->disable();
		$this->profiler->purge();

		$url = $this->getUrl('/info/{about}', array('about' => 'purge'));
		return $this->redirect($url, 302, array('Content-Type' => 'text/html'));
	}

	/**
	 * Imports token data.
	 *
	 * @param Request $request The current HTTP Request
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function importAction()
	{
		// if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		// }

		$this->profiler->disable();

		$file = $request->files->get('file');

		if (empty($file) || !$file->isValid()) {
			$url = $this->generator->generate('/info/{about}', array('about' => 'upload_error'));
			return $this->redirect($url, 302, array('Content-Type' => 'text/html'));
		}

		if (!$profile = $this->profiler->import(file_get_contents($file->getPathname()))) {
			$url = $this->generator->generate('/info/{about}', array('about' => 'already_exists'));
			return $this->redirect($url, 302, array('Content-Type' => 'text/html'));
		}

		$url = $this->generator->generate('/{token}', array('token' => $profile->getToken()));
		return $this->redirect($url, 302, array('Content-Type' => 'text/html'));
	}

	/**
	 * Displays information page.
	 *
	 * @param string $about The about message
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function infoAction($about) {
		if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		}

		$this->profiler->disable();

		$data = $this->twig->render('@Hopper/Console/info.html.twig', array(
			'about' => $about
		));
		return $this->response($data, 200, array('Content-Type' => 'text/html'));
	}

	/**
	 * Renders the Web Debug Toolbar.
	 *
	 * @param Request $request  The current HTTP Request
	 * @param string  $token    The profiler token
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function toolbarAction($token) {
		if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		}

		// $session = $request->getSession();

		// if (null !== $session && $session->getFlashBag() instanceof AutoExpireFlashBag) {
			// keep current flashes for one more request if using AutoExpireFlashBag
			// $session->getFlashBag()->setAll($session->getFlashBag()->peekAll());
		// }

		if (null === $token) {
			$this->response('', 200, array('Content-Type' => 'text/html'));
		}

		$this->profiler->disable();

		if (!$profile = $this->profiler->loadProfile($token)) {
			$this->response('', 200, array('Content-Type' => 'text/html'));
		}

		// the toolbar position (top, bottom, normal, or null -- use the configuration)
		if (null === $position = $this->get('position')) {
			$position = $this->toolbarPosition;
		}

		$url = null;
		try {
			$url = $this->getUrl('/{token}', array('token' => $token));
		} catch (\Exception $e) {
			// the profiler is not enabled
		}

		$data = $this->twig->render('@Hopper/Console/toolbar.html.twig', array(
			'position'     => $position,
			'profile'      => $profile,
			'templates'    => $this->getTemplateManager()->getTemplates($profile),
			'profiler_url' => $url,
			'token'        => $token,
		));
		return $this->response($data, 200, array('Content-Type' => 'text/html'));
	}

	/**
	 * Renders the profiler search bar.
	 *
	 * @param Request $request The current HTTP Request
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function searchBarAction() {
		// if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		// }

		$this->profiler->disable();

		if (null === $session = $request->getSession()) {
			$ip     =
			$method =
			$url    =
			$start  =
			$end    =
			$limit  =
			$token  = null;
		} else {
			$ip     = $session->get('_profiler_search_ip');
			$method = $session->get('_profiler_search_method');
			$url    = $session->get('_profiler_search_url');
			$start  = $session->get('_profiler_search_start');
			$end    = $session->get('_profiler_search_end');
			$limit  = $session->get('_profiler_search_limit');
			$token  = $session->get('_profiler_search_token');
		}

		$data = $this->twig->render('@Hopper/Console/search.html.twig', array(
			'token'  => $token,
			'ip'     => $ip,
			'method' => $method,
			'url'    => $url,
			'start'  => $start,
			'end'    => $end,
			'limit'  => $limit,
		));
		return $this->response($data, 200, array('Content-Type' => 'text/html'));
	}

	/**
	 * Search results.
	 *
	 * @param Request $request The current HTTP Request
	 * @param string  $token   The token
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function searchResultsAction($token)
	{
		if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		}

		$this->profiler->disable();

		$profile = $this->profiler->loadProfile($token);

		$ip     = $this->get('ip');
		$method = $this->get('method');
		$url    = $this->get('url');
		$start  = $this->get('start', null);
		$end    = $this->get('end', null);
		$limit  = $this->get('limit');

		$data = $this->twig->render('@Hopper/Console/results.html.twig', array(
			'token'     => $token,
			'profile'   => $profile,
			'tokens'    => $this->profiler->find($ip, $url, $limit, $method, $start, $end),
			'ip'        => $ip,
			'method'    => $method,
			'url'       => $url,
			'start'     => $start,
			'end'       => $end,
			'limit'     => $limit,
			'panel'     => null,
		));
		return $this->response($data, 200, array('Content-Type' => 'text/html'));
	}

	/**
	 * Narrow the search bar.
	 *
	 * @param Request $request The current HTTP Request
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function searchAction()
	{
		// if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		// }

		$this->profiler->disable();

		$ip     = preg_replace('/[^:\d\.]/', '', $this->get('ip'));
		$method = $this->get('method');
		$url    = $this->get('url');
		$start  = $this->get('start', null);
		$end    = $this->get('end', null);
		$limit  = $this->get('limit');
		$token  = $this->get('token');

		if (null !== $session = $request->getSession()) {
			$session->set('_profiler_search_ip', $ip);
			$session->set('_profiler_search_method', $method);
			$session->set('_profiler_search_url', $url);
			$session->set('_profiler_search_start', $start);
			$session->set('_profiler_search_end', $end);
			$session->set('_profiler_search_limit', $limit);
			$session->set('_profiler_search_token', $token);
		}

		if (!empty($token)) {
			$this->redirect($this->getUrl('/{token}', array('token' => $token)), 302, array('Content-Type' => 'text/html'));
		}

		$tokens = $this->profiler->find($ip, $url, $limit, $method, $start, $end);

		$url = $this->getUrl('/{token}/search/results', array(
			'token'  => $tokens ? $tokens[0]['token'] : 'empty',
			'ip'     => $ip,
			'method' => $method,
			'url'    => $url,
			'start'  => $start,
			'end'    => $end,
			'limit'  => $limit,
		));
		return $this->redirect($url, 302, array('Content-Type' => 'text/html'));
	}

	/**
	 * Displays the PHP info.
	 *
	 * @return Response A Response instance
	 *
	 * @throws NotFoundHttpException
	 */
	public function phpinfoAction()
	{
		if (null === $this->profiler) {
			return $this->notFound('The profiler must be enabled.');
		}

		$this->profiler->disable();

		ob_start();
		phpinfo();
		$phpinfo = ob_get_clean();

		return $this->response($phpinfo, 200, array('Content-Type' => 'text/html'));
	}

	/**
	 * Gets the Template Manager.
	 *
	 * @return TemplateManager The Template Manager
	 */
	protected function getTemplateManager()
	{
		if (null === $this->templateManager) {
			$this->templateManager = new TemplateManager($this->profiler, $this->twig);
		}

		return $this->templateManager;
	}
}