<?php
namespace Sleek\PostTypes;

use ICanBoogie\Inflector;

$inflector = Inflector::get('en');

# Make sure we have some post types
if (file_exists(get_stylesheet_directory() . '/post-types/')) {
	foreach (glob(get_stylesheet_directory() . '/post-types/*.php') as $file) {
		# Include their classes
		require_once $file;

		# Work out the post type (snake_case) and class name (PascalCase) from filename (kebab-case)
		$postType = str_replace('-', '_', substr(basename($file), 0, -4));
		$className = $inflector->camelize($postType);

		# Store full class name
		$fullClassName = "Sleek\PostTypes\\$className";

		# Create post type labels and slug
		$postTypeLabel = $inflector->titleize($postType);
		$postTypeLabelPlural = $inflector->pluralize($postTypeLabel);
		$slug = str_replace('_', '-', $inflector->underscore($postTypeLabelPlural));

		# Create instance of PostType class
		$pt = new $fullClassName;

		# And get its config
		$ptConfig = $pt->config();

		# Default post type config
		$defaultConfig = [
			'labels' => [
				'name' => __($postTypeLabelPlural, 'sleek'),
				'singular_name' => __($postTypeLabel, 'sleek')
			],
			'rewrite' => [
				'with_front' => false,
				'slug' => _x($slug, 'url', 'sleek')
			],
			'exclude_from_search' => false, # Never exclude from search because it prevents taxonomy archives for this post type (https://core.trac.wordpress.org/ticket/20234)
			'has_archive' => true,
			'public' => true,
			'show_in_rest' => true,
			'supports' => [
				'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks',
				'custom-fields', 'revisions', 'page-attributes', 'comments'
			]
		];

		# Merge post type class config
		$config = array_merge_recursive($defaultConfig, $ptConfig);

		# If post type already exists - just merge its config
		if (post_type_exists($postType)) {
			add_filter('register_post_type_args', function ($args, $pType) use ($postType, $ptConfig) {
				if ($postType === $pType) {
					$args = array_merge_recursive($args, $ptConfig);
				}

				return $args;
			}, 10, 2);
		}
		# Otherwise create the post type
		else {
			add_action('init', function () use ($postType, $config) {
				register_post_type($postType, $config);
			});
		}

		# TODO: And now create its ACF fields (NOTE: Should use Sleek\Acf\generateKeys($config, $prefix))
		if ($fields = $pt->fields() and function_exists('acf_add_local_field_group')) {
			$fieldGroupConfig = [
				'key' => 'group_' . $postType . '_meta',
				'title' => __('Information', 'sleek'),
				'location' => [
					[
						[
							'param' => 'post_type',
							'operator' => '==',
							'value' => $postType
						]
					]
				]
			];

		#	acf_add_local_field_group($fieldGroupConfig);
		}
	}
}
