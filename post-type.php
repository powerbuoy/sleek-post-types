<?php
namespace Sleek\PostTypes;

class PostType {
	private $postId;

	public function __construct ($postId) {
		$this->postId = $postId;
	}

	public function getField ($name) {
		return get_field($name, $this->postId);
	}
}
