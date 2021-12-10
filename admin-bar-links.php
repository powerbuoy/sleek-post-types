<?php
namespace Sleek\PostTypes;

# Simon ♥️
add_action('admin_bar_menu', function ($adminBar) {
	if (!function_exists('acf_add_options_page')) {
		return;
	}

	global $wp_query;

	# Grab all post types
	$postTypes = get_post_types([], 'objects');

	foreach ($postTypes as $postType) {
		$hasArchive = (!(isset($postType->has_archive) and $postType->has_archive === false) or $postType->name === 'post');
		$hasSettings = (isset($postType->has_settings) and $postType->has_settings === true);
		$disableSettings = (isset($postType->has_settings) and $postType->has_settings === false);

		if (($hasArchive or $hasSettings) and !$disableSettings) {
			# Add admin bar button to admin (only if CPT has archive)
			if (is_admin() and $hasArchive) {
				$currentScreen = get_current_screen();

				# Built-in POST is of course special
				if ($postType->name === 'post') {
					$correctPage = ($currentScreen->id === 'posts_page_post_settings');
				}
				else {
					$correctPage = ($currentScreen->id === $postType->name . '_page_' . $postType->name . '_settings');
				}

				if ($correctPage) {
					$adminBar->add_menu([
						'id' => 'sleek-view-archive',
						'title' => __('View Archive', 'sleek_admin'),
						'href' => get_post_type_archive_link($postType->name)
					]);
				}
			}

			# Add admin bar button to archive page
			if (!is_admin() and $hasArchive and is_post_type_archive($postType->name)) {
				$adminBar->add_menu([
					'id' => 'sleek-edit-archive',
					'title' => __('Edit Archive', 'sleek_admin'),
					'href' => admin_url('edit.php?post_type=' . $postType->name . '&page=' . $postType->name . '_settings')
				]);
			}
			# Built-in POST is of course special
			elseif (!is_admin() and $postType->name === 'post' and is_home()) {
				$adminBar->remove_node('edit');
				$adminBar->add_menu([
					'id' => 'sleek-edit-archive',
					'title' => __('Edit Archive', 'sleek_admin'),
					'href' => admin_url('edit.php?page=post_settings')
				]);
			}

			# Single page
			if (!is_admin() and $postType->name === 'post' and is_singular($postType->name)) {
				$adminBar->add_menu([
					'id' => 'sleek-edit-settings',
					'title' => sprintf(__('Edit %s Settings', 'sleek_admin'), $postType->labels->singular_name),
					'href' => admin_url('edit.php?page=post_settings')
				]);
			}
			elseif (!is_admin() and is_singular($postType->name)) {
				$adminBar->add_menu([
					'id' => 'sleek-edit-settings',
					'title' => sprintf(__('Edit %s Settings', 'sleek_admin'), $postType->labels->singular_name),
					'href' => admin_url('edit.php?post_type=' . $postType->name . '&page=' . $postType->name . '_settings')
				]);
			}
		}
	}
}, 99);

add_action('admin_head', function () {
	?>
	<style>
		#wp-admin-bar-sleek-view-archive .ab-item::before {
			content: "\f480";
			top: 2px;
		}
	</style>
	<?php
});

add_action('wp_head', function () {
	if (is_user_logged_in()) {
		?>
		<style>
			#wp-admin-bar-sleek-edit-archive .ab-item::before {
				content: "\f464";
				top: 2px;
			}

			#wp-admin-bar-sleek-edit-settings .ab-item::before {
				content: "\f111";
				top: 2px;
			}
		</style>
		<?php
	}
});
