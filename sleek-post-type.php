<?php
namespace Sleek\PostTypes;

abstract class PostType {
	protected $postId;

	public function __construct ($postId = null) {
		$this->postId = $postId;
	}

	# Lifecycle hook
	public function init () {

	}

	# PostType config
	public function config () {
		return [];
	}

	# Returns all fields
	public function fields () {
		return [];
	}

	# Returns all sticky modules
	public function sticky_modules () {
		return [];
	}

	# Returns all flexible modules
	public function flexible_modules () {
		return [];
	}

	# Returns all sticky archive modules
	public function sticky_archive_modules () {
		return [];
	}

	# Returns all flexible archive modules
	public function flexible_archive_modules () {
		return [];
	}
}
