<?php
namespace Sleek\PostTypes;

add_action('init', function () {
	if (!function_exists('acf_add_local_field_group')) {
	#	trigger_error("sleek/post_types/acf_fields acf_add_local_field_group() is not defined, unable to create acf fields (have you enabled ACF?)", E_USER_WARNING);

		return;
	}

	# Grab all post types
	$postTypes = get_post_types([], 'objects');

	foreach ($postTypes as $postType) {
		$className = \Sleek\Utils\convert_case($postType->name, 'pascal');
		$fullClassName = "Sleek\PostTypes\\$className";

		# Make sure a PostType class exists
		if (class_exists($fullClassName)) {
			$obj = new $fullClassName;

			###############################
			# And now create its ACF fields
			# TODO: Clean this up, same code four times...
			###############
			# Sticky fields
			if ($fields = $obj->fields()) {
				$groupKey = $postType->name . '_meta';
				$fieldGroup = apply_filters('sleek/post_types/field_group', [
					'key' => $groupKey,
					'title' => sprintf(__('%s information', 'sleek_admin'), $postType->labels->singular_name),
					'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => $postType->name]]],
					'position' => 'side',
					'fields' => \Sleek\Acf\generate_keys(apply_filters('sleek/post_types/fields', $fields, $postType), $groupKey)
				], $postType, $groupKey);

				acf_add_local_field_group($fieldGroup);
			}

			################
			# Sticky modules
			if ($stickyModules = $obj->sticky_modules()) {
				$groupKey = $postType->name . '_sticky_modules';
				$fieldGroup = [
					'key' => $groupKey,
					'title' => __('Sticky Modules', 'sleek_admin'),
					'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => $postType->name]]],
					'menu_order' => -2,
					'fields' => \Sleek\Acf\generate_keys(\Sleek\Modules\get_module_fields($stickyModules, 'tabs'), $groupKey)
				];

				acf_add_local_field_group($fieldGroup);
			}

			########################
			# Sticky archive modules
			if ($stickyModules = $obj->sticky_archive_modules()) {
				$groupKey = $postType->name . '_archive_sticky_modules';
				$fieldGroup = [
					'key' => $groupKey,
					'title' => __('Sticky Archive Modules', 'sleek_admin'),
					'location' => [[['param' => 'options_page', 'operator' => '==', 'value' => $postType->name . '_settings']]],
					'menu_order' => -1,
					'fields' => \Sleek\Acf\generate_keys(\Sleek\Modules\get_module_fields($stickyModules, 'tabs'), $groupKey)
				];

				acf_add_local_field_group($fieldGroup);
			}

			##################
			# Flexible modules
			if ($flexibleModules = $obj->flexible_modules()) {
				$groupKey = $postType->name . '_flexible_modules';
				$flexibleFields = [];

				# A sequential array of modules is passed in - create a module area called "flexible_modules"
				if (\Sleek\Utils\is_sequential_array($flexibleModules)) {
					$flexibleFields[] = [
						'key' => $groupKey,
						'name' => 'flexible_modules',
						'button_label' => __('Add a module', 'sleek_admin'),
						'type' => 'flexible_content',
						'layouts' => \Sleek\Acf\generate_keys(\Sleek\Modules\get_module_fields($flexibleModules, 'flexible'), $groupKey)
					];
				}
				# An associative array is passed in - create a module area for each area
				else {
					foreach ($flexibleModules as $moduleArea => $modules) {
						$flexGroupKey = $postType->name . '_' . $moduleArea;
						$flexibleFields[] = [
							'key' => $flexGroupKey . '_tab',
							'name' => $moduleArea . '_tab',
							'label' => \Sleek\Utils\convert_case($moduleArea, 'title'),
							'type' => 'tab'
						];
						$flexibleFields[] = [
							'key' => $flexGroupKey,
							'name' => $moduleArea,
							'button_label' => __('Add a module', 'sleek_admin'),
							'type' => 'flexible_content',
							'layouts' => \Sleek\Acf\generate_keys(\Sleek\Modules\get_module_fields($modules, 'flexible'), $flexGroupKey)
						];
					}
				}

				# Create the group
				$fieldGroup = [
					'key' => 'group_' . $groupKey,
					'title' => __('Modules', 'sleek_admin'),
					'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => $postType->name]]],
					'fields' => $flexibleFields
				];

				acf_add_local_field_group($fieldGroup);
			}

			##########################
			# Flexible archive modules
			if ($flexibleModules = $obj->flexible_archive_modules()) {
				$groupKey = $postType->name . '_settings_flexible_modules';
				$flexibleFields = [];

				# A sequential array of modules is passed in - create a module area called "flexible_modules"
				if (\Sleek\Utils\is_sequential_array($flexibleModules)) {
					$flexibleFields[] = [
						'key' => $groupKey,
						'name' => 'flexible_modules',
						'button_label' => __('Add a module', 'sleek_admin'),
						'type' => 'flexible_content',
						'layouts' => \Sleek\Acf\generate_keys(\Sleek\Modules\get_module_fields($flexibleModules, 'flexible'), $groupKey)
					];
				}
				# An associative array is passed in - create a module area for each area
				else {
					foreach ($flexibleModules as $moduleArea => $modules) {
						$flexGroupKey = $postType->name . '_settings_' . $moduleArea;
						$flexibleFields[] = [
							'key' => $flexGroupKey . '_tab',
							'name' => $moduleArea . '_tab',
							'label' => \Sleek\Utils\convert_case($moduleArea, 'title'),
							'type' => 'tab'
						];
						$flexibleFields[] = [
							'key' => $flexGroupKey,
							'name' => $moduleArea,
							'button_label' => __('Add a module', 'sleek_admin'),
							'type' => 'flexible_content',
							'layouts' => \Sleek\Acf\generate_keys(\Sleek\Modules\get_module_fields($modules, 'flexible'), $flexGroupKey)
						];
					}
				}

				# Create the group
				$fieldGroup = [
					'key' => 'group_' . $groupKey,
					'title' => __('Modules', 'sleek_admin'),
					'location' => [[['param' => 'options_page', 'operator' => '==', 'value' => $postType->name . '_settings']]],
					'fields' => $flexibleFields
				];

				acf_add_local_field_group($fieldGroup);
			}
		}
	}
}, 99); # NOTE: Make sure all post-types are registered
