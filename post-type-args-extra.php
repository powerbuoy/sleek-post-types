<?php
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

# TODO: Add support for hide_from_search
