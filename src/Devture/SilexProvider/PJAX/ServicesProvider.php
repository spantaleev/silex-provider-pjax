<?php
namespace Devture\SilexProvider\PJAX;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Devture\SilexProvider\PJAX\Twig\Extension\PjaxHelperExtension;

class ServicesProvider implements ServiceProviderInterface {

	public function register(Application $app) {
		/**
		 * Strips the _pjax query string parameter from PJAX requests.
		 *
		 * PJAX uses that parameter to make the URL unique for regular requests
		 * and PJAX requests (partial).
		 *
		 * This extra parameter is not needed in our application and can even
		 * cause certain problems when it's there.
		 */
		$app['pjax.listener.request_cleaner'] = function () {
			return function (Request $request) {
				if ($request->headers->has('x-pjax')) {
					$request->query->remove('_pjax');
				}
			};
		};

		/**
		 * Marks PJAX responses with the current request URI,
		 * to make PJAX handle redirects properly
		 * (see GitHub issue #85 for defunkt/jquery-pjax for more).
		 */
		$app['pjax.listener.response_marker'] = function () {
			return function (Request $request, Response $response) {
				if ($request->headers->has('x-pjax')) {
					$response->headers->set('x-pjax-url', $request->getRequestUri());
				}
			};
		};
	}

	public function boot(Application $app) {
		$app->before($app['pjax.listener.request_cleaner']);
		$app->after($app['pjax.listener.response_marker']);
		$app['twig']->addExtension(new PjaxHelperExtension($app));
	}

}
