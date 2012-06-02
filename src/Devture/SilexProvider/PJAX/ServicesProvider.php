<?php
namespace Devture\SilexProvider\PJAX;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Devture\SilexProvider\PJAX\Twig\Extension\PjaxDetectionExtension;

class ServicesProvider implements ServiceProviderInterface {

	public function register(Application $app) {
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
		$app->after($app['pjax.listener.response_marker']);
		$app['twig']->addExtension(new PjaxDetectionExtension($app));
	}

}
