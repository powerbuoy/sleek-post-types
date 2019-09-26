<?php
namespace Sleek\PostTypes;

use ICanBoogie\Inflector;

$inflector = Inflector::get('en');

# Make sure we have some post types
# TODO: Make /post-types/ configurable
if (file_exists(get_stylesheet_directory() . '/post-types/')) {
	#########################
	# Register each post type
	foreach (glob(get_stylesheet_directory() . '/post-types/*.php') as $file) {
		# Include their class
		require_once $file;

		# Work out the post type name (snake_case) and class name (PascalCase) from filename (kebab-case)
		$postType = str_replace('-', '_', substr(basename($file), 0, -4));
		$className = $inflector->camelize($postType);

		# Create post type labels and slug
		$postTypeLabel = $inflector->titleize($postType);
		$postTypeLabelPlural = $inflector->pluralize($postTypeLabel);
		$slug = str_replace('_', '-', $inflector->underscore($postTypeLabelPlural));

		# Store full class name
		$fullClassName = "Sleek\PostTypes\\$className";

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
		$config = array_merge($defaultConfig, $ptConfig);

		# If post type already exists - just merge its config
		if (post_type_exists($postType)) {
			add_filter('register_post_type_args', function ($args, $pType) use ($postType, $ptConfig) {
				if ($postType === $pType) {
					$args = array_merge($args, $ptConfig);
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
				],
				'fields' => $fields
			];

		#	acf_add_local_field_group($fieldGroupConfig);
		}
	}

	################################################
	# Add support for has_single in post type config
	add_filter('register_post_type_args', function ($args, $postType) {
		if (isset($args['has_single']) and $args['has_single'] === false) {
			# Trigger 404 when trying to access this post type
			add_filter('template_redirect', function () use ($postType) {
				global $wp_query;

				if (is_singular($postType)) {
					status_header(404); # Sets 404 header
					$wp_query->set_404(); # Shows 404 template
				}
			});
		}

		return $args;
	}, 10, 2);

	##################################
	# Add support for hide_from_search
	add_action('init', function () {
		$postTypes = get_post_types(['public' => true], 'objects');
		$hide = [];
		$show = [];

		foreach ($postTypes as $postType) {
			if (isset($postType->hide_from_search) and $postType->hide_from_search === true) {
				$hide[] = $postType->name;
			}
			else {
				$show[] = $postType->name;
			}
		}

		add_filter('pre_get_posts', function ($query) use ($show) {
			if ($query->is_search() and !$query->is_admin() and $query->is_main_query() and !isset($_GET['post_type'])) {
				$query->set('post_type', $show);
			}

			return $query;
		});
	});

	#################################
	# Automatically create taxonomies
	add_filter('register_post_type_args', function ($args, $postType) use ($inflector) {
		if (isset($args['taxonomies']) and count($args['taxonomies'])) {
			# Register all the specified taxonomies and assign them to the post type
			foreach ($args['taxonomies'] as $taxonomy) {
				if (!taxonomy_exists($taxonomy)) {
					$taxonomyLabel = $inflector->titleize($taxonomy);
					$taxonomyLabelPlural = $inflector->pluralize($taxonomyLabel);
					$slug = str_replace('_', '-', $inflector->underscore($taxonomyLabelPlural));
					$hierarchical = preg_match('/_tag$/', $taxonomy) ? false : true; # If taxonomy name ends in tag (eg product_tag) assume non-hierarchical

					register_taxonomy($taxonomy, $postType, [
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
					]);
				}
			}
		}

		return $args;
	}, 10, 2);
}
