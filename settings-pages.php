<?php
namespace Sleek\PostTypes;

add_action('init', function () {
	if (!function_exists('acf_add_options_page')) {
		return;
	}

	# Grab all public post types
	$postTypes = get_post_types([], 'objects');

	foreach ($postTypes as $postType) {
		# Settings pages can be disabled using has_settings => false in CPT config
		if (!(isset($postType->has_settings) and $postType->has_settings === false)) {
			# Create the options page
			acf_add_options_page([
				'page_title' => sprintf(__('%s Settings', 'sleek'), $postType->labels->singular_name),
				'menu_slug' => $postType->name . '_settings',
				'parent_slug' => $postType->name === 'post' ? 'edit.php' : 'edit.php?post_type=' . $postType->name,
				'icon_url' => 'dashicons-welcome-write-blog',
				'post_id' => $postType->name . '_settings'
			]);

			$isPublic = !(isset($postType->public) and $postType->public === false);
			$hasArchive = !(isset($postType->has_archive) and $postType->has_archive === false);

			# Ignore post-types with no archives (built-in post post type has_archive = false but still has archives)
			if ($postType->name === 'post' or ($isPublic and $hasArchive)) {
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
}, 99);
