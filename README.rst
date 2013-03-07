Silex PJAX Provider
===================

`PJAX <https://github.com/defunkt/jquery-pjax>`_ integration for `Silex <http://silex.sensiolabs.org/>`_ micro-framework projects.

Usage
-----

Registration::

	<?php
	$app->register(new \Devture\SilexProvider\PJAX\ServicesProvider());


As a result, the following Twig functions/filters are provided:
	* ``is_pjax()`` - tells you if the current request is a PJAX request
	* ``|strip_pjax_param`` filter - removes the cache-busting ``_pjax`` query-string parameter from a URL

This provider also registers some "after-request" event handlers to handle some edge-cases,
regarding redirect responses. To learn more, read the comments in ``ServicesProvider.php``.

Note that you need to include the ``jquery.pjax.js`` file on your pages by some other means.
It's not included here.
