<?php
namespace Sleek\PostTypes;

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

# Remove permalink below title and preview button
add_filter('admin_head', function ($return) {
	$currentScreen = get_current_screen();

	if (isset($currentScreen->post_type)) {
		$ptObj = get_post_type_object($currentScreen->post_type);

		if (isset($ptObj->has_single) and $ptObj->has_single === false) {
			?>
			<style>
				#edit-slug-box,
				#minor-publishing-actions #preview-action {
					display: none;
				}
			</style>
			<?php
		}
	}
});

# Remove slug metabox
add_action('add_meta_boxes', function () {
	$postTypes = get_post_types(['public' => true], 'objects');

	foreach ($postTypes as $postType) {
		if (isset($postType->has_single) and $postType->has_single === false) {
			remove_meta_box('slugdiv', $postType->name, 'normal');
		}
	}
});

# Remove "View post" from admin-bar
add_action('admin_bar_menu', function ($adminBar) {
	if (is_admin()) {
		$currentScreen = get_current_screen();

		if (isset($currentScreen->post_type)) {
			$ptObj = get_post_type_object($currentScreen->post_type);

			if (isset($ptObj->has_single) and $ptObj->has_single === false) {
				$adminBar->remove_node('view');
			}
		}
	}
}, 99);

# Remove "View" link from admin archive
add_filter('post_row_actions', function ($actions) {
	$currentScreen = get_current_screen();

	if (isset($currentScreen->post_type)) {
		$ptObj = get_post_type_object($currentScreen->post_type);

		if (isset($ptObj->has_single) and $ptObj->has_single === false) {
			unset($actions['view']);
		}
	}

	return $actions;
});

#######################################
# Remove !has_single from Yoast Sitemap
# NOTE: Using wpseo_sitemap_exclude_post_type excludes the archive pages too
add_filter('wpseo_exclude_from_sitemap_by_post_ids', function () {
	$postTypes = get_post_types(['public' => true], 'objects');
	$ids = [];

	foreach ($postTypes as $postType) {
		if (isset($postType->has_single) and $postType->has_single === false) {
			$ids = array_merge($ids, get_posts([
				'post_type' => $postType->name,
				'numberposts' => -1,
				'fields' => 'ids'
			]));
		}
	}

	return $ids;
}, 99);

########################################
# Remove Yoast meta box from !has_single
add_action('add_meta_boxes', function () {
	$postTypes = get_post_types(['public' => true], 'objects');

	foreach ($postTypes as $postType) {
		if (isset($postType->has_single) and $postType->has_single === false) {
			remove_meta_box('wpseo_meta', $postType->name, 'normal');
		}
	}
}, 100);
