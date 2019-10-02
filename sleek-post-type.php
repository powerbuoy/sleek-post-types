<?php
namespace Sleek\PostTypes;

abstract class PostType {
	private $postId;

	public function __construct ($postId = null) {
		$this->postId = $postId;
	}

	# TODO: Use magic getter instead?
	public function get_field ($name) {
		return get_field($name, $this->postId);
	}

	public function created () {}

	public function config () {
		return [];
	}

	public function fields () {
		return [];
	}
}
