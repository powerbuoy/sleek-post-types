<?php
namespace Sleek\PostTypes;

require_once __DIR__ . '/admin-bar-links.php';
require_once __DIR__ . '/has-single.php';
require_once __DIR__ . '/hide-from-search.php';
require_once __DIR__ . '/register-fields.php';
require_once __DIR__ . '/register-taxonomies.php';
require_once __DIR__ . '/settings-pages.php';
require_once __DIR__ . '/make-cpt-strings.php';

#############################################
# Get array of file meta data in /post-types/
function get_file_meta () {
	$path = get_stylesheet_directory() . '/post-types/*.php';
	$files = [];

	foreach (glob($path) as $file) {
		$pathinfo = pathinfo($file);
		$name = $pathinfo['filename'];
		$snakeName = \Sleek\Utils\convert_case($name, 'snake');
		$className = \Sleek\Utils\convert_case($name, 'pascal');
		$label = \Sleek\Utils\convert_case($name, 'title');
		$labelPlural = \Sleek\Utils\convert_case($label, 'plural');
		$slug = \Sleek\Utils\convert_case($labelPlural, 'kebab');

		$files[] = (object) [
			'pathinfo' => $pathinfo,
			'name' => $name,
			'filename' => $pathinfo['filename'],
			'snakeName' => $snakeName,
			'className' => $className,
			'fullClassName' => "Sleek\PostTypes\\$className",
			'label' => $label,
			'labelPlural' => $labelPlural,
			'slug' => $slug,
			'path' => $file
		];
	}

	return $files;
}

#######################
# Create all post types
add_action('after_setup_theme', function () {
	if ($files = get_file_meta()) {
		foreach ($files as $file) {
			# Include the class
			require_once $file->path;

			# Create instance of class
			$obj = new $file->fullClassName;

			# Run callback
			$obj->init();

			# And get its config
			$objConfig = $obj->config();

			if (!is_array($objConfig)) {
				trigger_error("{$file->fullClassName}->config() did not return an array", E_USER_WARNING);

				$objConfig = [];
			}

			# Default post type config
			$defaultConfig = [
				'labels' => [
					'name' => __($file->labelPlural, 'sleek'),
					'singular_name' => __($file->label, 'sleek')
				],
				'rewrite' => [
					'with_front' => false,
					'slug' => _x($file->slug, 'url', 'sleek')
				],
				'exclude_from_search' => false, # NOTE: Don't exclude from search as it has side effects
				'has_archive' => true,
				'public' => true,
				'show_in_rest' => true,
				'supports' => [
					'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks',
					'custom-fields', 'revisions', 'page-attributes', 'comments'
				],

				# Simon ❤️
				'sleek' => [
					'sleek' => true
				]
			];

			# Merge object config
			$config = array_merge($defaultConfig, $objConfig);

			# If it already exists - just merge its config
			if (post_type_exists($file->snakeName)) {
				add_filter('register_post_type_args', function ($args, $type) use ($file, $objConfig) {
					if ($file->snakeName === $type) {
						$args = array_merge($args, $objConfig);
					}

					return $args;
				}, 10, 2);
			}
			# Otherwise create it
			else {
				add_action('init', function () use ($file, $config) {
					register_post_type($file->snakeName, $config);
				}, 10);
			}
		}
	}
});
