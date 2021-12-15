<?php
namespace Sleek\PostTypes;

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
						'show_ui' => $postType->show_ui,

						# Simon ❤️
						'sleek' => [
							'sleek' => true
						]
					];

					register_taxonomy($taxonomy, $postType->name, $config);

					# Make it filterable
					add_action('restrict_manage_posts', function ($pt, $which) use ($postType, $taxonomy, $hierarchical) {
						if ($pt === $postType->name) {
							wp_dropdown_categories([
								'taxonomy' => $taxonomy,
								'show_option_all' => sprintf(
									# Translators: For example "All [blog categories]"
									__('All %s', 'sleek_admin'),
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
	}
}, 11);
