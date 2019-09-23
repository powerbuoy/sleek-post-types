<?php
namespace Sleek\PostTypes;

add_action('init', function () {
	$postTypes = [];

	# Include all PostType classes
	if (file_exists(get_stylesheet_directory() . '/post-types/')) {
		foreach (glob(get_stylesheet_directory() . '/post-types/*.php') as $file) {
			require_once $file;
		}
	}

	# Go through
});
