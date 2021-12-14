<?php
namespace Sleek\PostTypes;

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
		if (
			$query->is_main_query() and
			$query->is_search() and
			!$query->is_admin() and
			!$query->is_home() and
			!$query->is_post_type_archive() and
			!isset($_GET['post_type'])
		) {
			$query->set('post_type', $show);
		}

		return $query;
	});
}, 11);
