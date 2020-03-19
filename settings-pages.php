<?php
namespace Sleek\PostTypes;

add_action('init', function () {
	if (!function_exists('acf_add_options_page')) {
	#	trigger_error("sleek/post_types/settings_pages acf_add_options_page() is not defined, unable to create settings pages (have you enabled ACF?)", E_USER_WARNING);

		return;
	}

	# Grab all public post types
	$postTypes = get_post_types([], 'objects');

	foreach ($postTypes as $postType) {
		$hasArchive = (!(isset($postType->has_archive) and $postType->has_archive === false) or $postType->name === 'post');
		$hasSettings = (isset($postType->has_settings) and $postType->has_settings === true);
		$disableSettings = (isset($postType->has_settings) and $postType->has_settings === false);

		# Settings pages can be disabled using has_settings => false in CPT config
		if (($hasArchive or $hasSettings) and !$disableSettings) {
			# Create the options page
			acf_add_options_page([
				'page_title' => sprintf(__('%s Settings', 'sleek'), $postType->labels->singular_name),
				'menu_slug' => $postType->name . '_settings',
				'parent_slug' => $postType->name === 'post' ? 'edit.php' : 'edit.php?post_type=' . $postType->name,
				'icon_url' => 'dashicons-welcome-write-blog',
				'post_id' => $postType->name . '_settings'
			]);

			# Ignore post-types with no archives (built-in post post type has_archive = false but still has archives)
			if ($hasArchive) {
				# Add some standard fields (title, description, image)
				$groupKey = $postType->name . '_settings';
				$fields = \Sleek\Acf\generate_keys(apply_filters('sleek/post_types/archive_fields', [
					[
						'label' => __('Title', 'sleek'),
						'name' => 'title',
						'type' => 'text'
					],
					[
						'label' => __('Image', 'sleek'),
						'name' => 'image',
						'type' => 'image',
						'return_format' => 'id'
					],
					[
						'label' => __('Description', 'sleek'),
						'name' => 'description',
						'type' => 'wysiwyg'
					]
				], $postType->name), $groupKey);

				acf_add_local_field_group([
					'key' => $groupKey,
					'title' => __('Archive Settings', 'sleek'),
					'fields' => $fields,
					'location' => [[[
						'param' => 'options_page',
						'operator' => '==',
						'value' => $postType->name . '_settings'
					]]]
				]);
			}
		}
	}
}, 99); # NOTE: 99 = make sure all post-types are registered
