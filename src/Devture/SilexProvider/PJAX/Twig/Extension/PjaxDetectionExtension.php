<?php
namespace Devture\SilexProvider\PJAX\Twig\Extension;
/**
 * Extension that provides a way for templates to detect
 * if they're doing work for a PJAX request.
 */
class PjaxDetectionExtension extends \Twig_Extension {

	private $container;

	public function __construct(\Pimple $container) {
		$this->container = $container;
	}

	public function getName() {
		return 'pjax_detection_extension';
	}

	public function getFunctions() {
		return array('is_pjax' => new \Twig_Function_Method($this, 'isPjax'),);
	}

	public function isPjax() {
		return $this->container['request']->headers->has('x-pjax');
	}

}

