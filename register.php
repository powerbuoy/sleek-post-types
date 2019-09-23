<?php
namespace Sleek\PostTypes;

add_action('init', function () {
	$postTypes = [];

	# Include all PostType classes
	if (file_exists(get_stylesheet_directory() . '/post-types/')) {
		foreach (glob(get_stylesheet_directory() . '/post-types/*.php') as $file) {
			$filename = substr(basename($file), 0, -4);
			$className = str_replace('-', '', ucwords($filename, '-'));
			$ptName = str_replace('-', '_', $filename);

			$postTypes[$ptName] = $className;

			require_once $file;
		}
	}

	# Go through
	foreach ($postTypes as $postType => $className) {
		$fullClassName = "Sleek\PostTypes\\$className";
		$pt = new $fullClassName(null);

		var_dump($pt->config());
	}
});
