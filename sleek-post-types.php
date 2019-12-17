<?php
namespace Sleek\PostTypes;

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

			# And now create its ACF fields
			$groupKey = $file->snakeName . '_meta';
			$fields = $obj->fields();

			if ($fields and function_exists('acf_add_local_field_group')) {
				$fields = \Sleek\Acf\generate_keys($fields, $groupKey);
				$fieldGroup = [
					'key' => $groupKey,
					'title' => sprintf(__('%s information', 'sleek'), ($config['labels']['singular_name'] ?? __($file->label, 'sleek'))),
					'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => $file->snakeName]]],
					'position' => 'side',
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
}, 11);

#################################
# Automatically create taxonomies
add_action('init', function () {
	$postTypes = get_post_types([], 'objects');

	foreach ($postTypes as $postType) {
		if (isset($postType->taxonomies)) {
			foreach ($postType->taxonomies as $taxonomy) {
				# Tax already exists - just assign it to the post type
				if (taxonomy_exists($taxonomy)) {
					register_taxonomy_for_object_type($taxonomy, $postType->name);
				}
				# Tax doesn't exist - create it
				else {
					$taxonomyLabel = \Sleek\Utils\convert_case($taxonomy, 'title');
					$taxonomyLabelPlural = \Sleek\Utils\convert_case($taxonomyLabel, 'plural');
					$slug = \Sleek\Utils\convert_case($taxonomyLabelPlural, 'kebab');
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
						'hierarchical' => $hierarchical,
						'sort' => true, # NOTE: will have WordPress retain the order in which terms are added to objects https://developer.wordpress.org/reference/functions/register_taxonomy/#comment-2687
						'show_in_rest' => true,
						'show_admin_column' => true,
						'public' => $postType->public, # Inherit public and show_ui from postType
						'show_ui' => $postType->show_ui
					];

					register_taxonomy($taxonomy, $postType->name, $config);
				}

				# Make it filterable
				add_action('restrict_manage_posts', function ($pt, $which) use ($postType, $taxonomy, $hierarchical) {
					if ($pt === $postType->name) {
						wp_dropdown_categories([
							'taxonomy' => $taxonomy,
							'show_option_all' => sprintf(
								__('All %s', 'sleek'),
								\Sleek\Utils\convert_case(
									\Sleek\Utils\convert_case($taxonomy, 'title'),
									'plural'
								)
							),
							'hide_empty' => false,
							'hierarchical' => $hierarchical,
							'name' => $taxonomy,
							'value_field' => 'slug',
							'selected' => $_GET[$taxonomy] ?? 0,
							'hide_if_empty' => true,
							'show_count' => true
						]);
					}
				}, 10, 2);
			}
		}
	}
}, 11);
