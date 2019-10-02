<?php
namespace Sleek\PostTypes;

#############################
# Get array of file meta data
# about post type files
function get_file_meta () {
	$path = get_stylesheet_directory() . apply_filters('sleek_post_types_path', '/post-types/') . '*.php';
	$inflector = \ICanBoogie\Inflector::get('en');
	$files = [];

	foreach (glob($path) as $file) {
		$pathinfo = pathinfo($file);
		$filename = $pathinfo['filename'];
		$snakeName = $inflector->underscore($filename);
		$className = $inflector->camelize($filename);
		$label = $inflector->titleize($filename);
		$labelPlural = $inflector->pluralize($label);
		$slug = str_replace('_', '-', $snakeName);

		$files[] = (object) [
			'pathinfo' => $pathinfo,
			'filename' => $pathinfo['filename'],
			'snakeName' => $snakeName,
			'className' => $className,
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
			$fullClassName = "Sleek\PostTypes\\$file->className";

			$obj = new $fullClassName;

			# Run callback
			$obj->created();

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
				'exclude_from_search' => false,
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
				});
			}

			# And now create its ACF fields
			if ($fields = $obj->fields() and function_exists('acf_add_local_field_group')) {
				$groupKey = 'group_' . $file->snakeName . '_meta';
				$fields = \Sleek\Acf\generate_keys(apply_filters('sleek_post_type_fields', $fields), 'field_' . $groupKey);
				$fieldGroup = [
					'key' => $groupKey,
					'title' => sprintf(__('%s information', 'sleek'), ($config['labels']['singular_name'] ?? __($file->label, 'sleek'))),
					'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => $file->snakeName]]],
					'fields' => $fields
				];

				add_action('acf/init', function () use ($fieldGroup) {
					acf_add_local_field_group($fieldGroup);
				});
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
});

#################################
# Automatically create taxonomies
add_action('init', function () {
	$inflector = \ICanBoogie\Inflector::get('en');
	$postTypes = get_post_types(['public' => true], 'objects');

	foreach ($postTypes as $postType) {
		if (isset($postType->taxonomies)) {
			foreach ($postType->taxonomies as $taxonomy) {
				# Only if it doesn't already exist
				if (!taxonomy_exists($taxonomy)) {
					$taxonomyLabel = $inflector->titleize($taxonomy);
					$taxonomyLabelPlural = $inflector->pluralize($taxonomyLabel);
					$slug = str_replace('_', '-', $inflector->underscore($taxonomyLabelPlural));
					$hierarchical = preg_match('/_tag$/', $taxonomy) ? false : true; # If taxonomy name ends in tag (eg product_tag) assume non-hierarchical
					$config = [
						'labels' => [
							'name' => __($taxonomyLabelPlural, 'sleek'),
							'singular_name' => __($taxonomyLabel, 'sleek')
						],
						'rewrite' => [
							'with_front' => false,
							'slug' => _x($slug, 'url', 'sleek'),
							'hierarchical' => $hierarchical
						],
						'sort' => true,
						'hierarchical' => $hierarchical,
						'show_in_rest' => true
					];

					if (isset($postType->taxonomy_config[$taxonomy])) {
						$config = array_merge($config, $postType->taxonomy_config[$taxonomy]);
					}

					register_taxonomy($taxonomy, $postType->name, $config);
				}
			}
		}
	}
});
