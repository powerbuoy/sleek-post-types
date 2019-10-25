<?php
namespace Sleek\PostTypes;

abstract class PostType {
	private $postId;

	public function __construct ($postId = null) {
		$this->postId = $postId;
	}

	public function created () {

	}

	public function config () {
		return [];
	}

	public function fields () {
		return [];
	}

	public function get_fields ($acfKey = null) {
		return apply_filters('sleek_post_type_fields', $this->fields());
	}
}
