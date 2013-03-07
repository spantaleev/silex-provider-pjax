<?php
namespace Devture\SilexProvider\PJAX\Twig\Extension;

class PjaxHelperExtension extends \Twig_Extension {

	private $container;

	public function __construct(\Pimple $container) {
		$this->container = $container;
	}

	public function getName() {
		return 'devture_pjax_helper_extension';
	}

	public function getFunctions() {
		return array('is_pjax' => new \Twig_Function_Method($this, 'isPjax'),);
	}

	public function getFilters() {
		return array(
			'strip_pjax_param' => new \Twig_Filter_Method($this, 'stripPjaxQueryStringParam'),
		);
	}

	public function isPjax() {
		return $this->container['devture_pjax.request_detect']($this->container['request']);
	}

	public function stripPjaxQueryStringParam($url) {
		return $this->container['devture_pjax.url_clean']($url);
	}

}
