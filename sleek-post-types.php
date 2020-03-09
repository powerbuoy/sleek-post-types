<?php
namespace Sleek\PostTypes;

require_once __DIR__ . '/admin-bar-links.php';
require_once __DIR__ . '/register-fields.php';
require_once __DIR__ . '/register-taxonomies.php';
require_once __DIR__ . '/settings-pages.php';

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

################################################
# Add support for has_single in post type config
add_filter('template_redirect', function () {
	global $wp_query;

	$postTypes = get_post_types(['public' => true], 'objects');

	foreach ($postTypes as $postType) {
		if (isset($postType->has_single) and $postType->has_single === false and is_singular($postType->name)) {
			status_header(404); # Sets 404 header
			$wp_query->set_404(); # Shows 404 template
		}
	}
});

#######################################
# Remove !has_single from Yoast Sitemap
# NOTE: This removes the archive from the sitemap too... :/
add_filter('wpseo_sitemap_exclude_post_type', function ($value, $post_type) {
	$pt = get_post_type_object($post_type);

	if (isset($pt->has_single) and $pt->has_single === false) {
		return true;
	}

	return false;
}, 10, 2);

##################################
# Add support for hide_from_search
# because exclude_from_search has side effects
# https://core.trac.wordpress.org/ticket/20234
add_action('init', function () {
	$postTypes = get_post_types(['public' => true], 'objects');
	$hide = [];
	$show = [];

	foreach ($postTypes as $postType) {
		if (
			(isset($postType->hide_from_search) and $postType->hide_from_search === true) or
			(isset($postType->exclude_from_search) and $postType->exclude_from_search === true) # NOTE: Still respect exclude_from_search
		) {
			$hide[] = $postType->name;
		}
		else {
			$show[] = $postType->name;
		}
	}

	add_filter('pre_get_posts', function ($query) use ($show) {
		if ($query->is_main_query() and $query->is_search() and !$query->is_admin() and !isset($_GET['post_type'])) {
			$query->set('post_type', $show);
		}

		return $query;
	});
}, 11);
