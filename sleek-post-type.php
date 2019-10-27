<?php
namespace Sleek\PostTypes;

abstract class PostType {
	private $postId;

	public function __construct ($postId = null) {
		$this->postId = $postId;
	}

	# Lifecycle hook
	public function created () {

	}

	# PostType config
	public function config () {
		return [];
	}

	# Returns all fields and potential defaults for this module
	public function fields () {
		return [];
	}

	# Returns all fields before they're sent to ACF
	public function get_fields ($acfKey = null) {
		return apply_filters('sleek_post_type_fields', $this->fields());
	}
}
