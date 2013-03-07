<?php
namespace Devture\SilexProvider\PJAX;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServicesProvider implements ServiceProviderInterface {

	public function register(Application $app) {
		/**
		 * Tells whether the given request is a PJAX request.
		 *
		 * The PJAX documentation says that an `x-pjax` header is enough to detect a request,
		 * but that shouldn't be enough for anyone that has a cache (like Varnish) in front
		 * of the webserver.
		 *
		 * Serving PJAX (partial) responses, because of a correct header, but on
		 * a URL that does not include the `_pjax` mark can poison the cache.
		 */
		$app['devture_pjax.request_detect'] = $app->protect(function (Request $request) {
			return $request->headers->has('x-pjax') && $request->query->has('_pjax');
		});

		/**
		 * Removes the `_pjax=..` parameter from a URL.
		 * This URL parameter is put there by PJAX to make PJAX requests unique,
		 * so that browser and server caches (like Varnish) would see them as other resources (which they are).
		 */
		$app['devture_pjax.url_clean'] = $app->protect(function ($url) use ($app) {
			$url = preg_replace("/\?_pjax=[^&]+&?/", "?", $url);
			$url = preg_replace("/_pjax=[^&]+&?/", "", $url);
			$url = preg_replace("/[\?&]$/", "", $url);
			return $url;
		});

		/**
		 * The opposite of the URL-clean function (above).
		 * It gets the `_pjax` query-string parameter from the current request and injects it into the given url.
		 */
		$app['devture_pjax.url_pjaxify'] = $app->protect(function (Request $request, $url) use ($app) {
			if (!$request->query->has('_pjax')) {
				return $url;
			}

			$pjaxContainer = urlencode($request->query->get('_pjax'));

			if (strpos($url, '_pjax=') !== false) {
				return $url;
			}

			if (strpos($url, '?') === false) {
				return $url . '?_pjax=' . $pjaxContainer;
			}

			return $url . '&_pjax=' . $pjaxContainer;
		});

		/**
		 * Marks PJAX responses with the current request URL, to make PJAX handle redirects properly.
		 *
		 * When PJAX follows a redirect, it may get confused as to which URL to put in the
		 * browser's address bar, and will use the original one, instead of the redirected,
		 * unless we explicitly tell it which URL to use.
		 *
		 * See GitHub issue #85 for defunkt/jquery-pjax for more.
		 */
		$app['devture_pjax.listener.response_url_marker'] = $app->protect(function (Request $request, Response $response) use ($app) {
			//Only mark unmarked responses.
			//If the developer already marked the response, he knows what he's doing.
			if ($app['devture_pjax.request_detect']($request) && !$response->headers->has('x-pjax-url')) {
				$response->headers->set('x-pjax-url', $app['devture_pjax.url_clean']($request->getRequestUri()));
			}
		});

		/**
		 * Rewrites the Location header for redirect responses to PJAX requests.
		 * This is meant to correct problems with browser and server caches.
		 *
		 * If an initial request is made to `/first`, PJAX uses `/first?_pjax=#container`,
		 * to keep the URL unique, so that caches can see it as a separate resource.
		 *
		 * If the response there issues a redirect to `/second`, the user's browser will follow it.
		 * There's nothing in place that will add the `_pjax=..` parameter to that URL,
		 * unless the developer handles it explicitly for PJAX requests (no way..).
		 *
		 * When a PJAX (AJAX) request leads to a redirect response, the browser remakes the request
		 * to the new location, but keeps the same custom headers as the one it sent for the original request.
		 * This will lead to a request to `/second` (no `_pjax` parameter), which
		 * include the special `x-pjax` header.
		 * The detector (above) will not consider this a valid PJAX request (because of the missing `_pjax` param)
		 * and will not return a partial (PJAX) response, but a full response.
		 * PJAX won't like that and will in turn trigger a full page reload.
		 *
		 * UNLESS, something rewrites the redirect location from `/second` to `/second?_pjax=..`,
		 * which is going to make everything work. And that's what we do here.
		 */
		$app['devture_pjax.listener.redirect_response_rewriter'] = $app->protect(function (Request $request, Response $response) use ($app) {
			if ($response->isRedirection() && $app['devture_pjax.request_detect']($request)) {
				$location = $response->headers->get('location');
				$location = $app['devture_pjax.url_pjaxify']($request, $location);
				$response->headers->set('location', $location);
			}
		});
	}

	public function boot(Application $app) {
		$app->after($app['devture_pjax.listener.response_url_marker']);
		$app->after($app['devture_pjax.listener.redirect_response_rewriter']);
		$app['twig']->addExtension(new Twig\Extension\PjaxHelperExtension($app));
	}

}
