<?php
namespace Sleek\PostTypes;

abstract class PostType {
	protected $postId;

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
}
