<?php
if (class_exists('WP_CLI')) {
	WP_CLI::add_command('sleek make-cpt-strings', function ($args, $argsAssoc) {
		$src = __DIR__ . '/../../../post-types/*.php';
		$dest = __DIR__ . '/../../../dist/lang/cpt-strings.php';
		$strings = [
			'<?php'
		];

		foreach (get_post_types([], 'objects') as $cpt) {
			if (isset($cpt->sleek)) {
				$strings[] = '# Translators: The name of the "' . $cpt->labels->singular_name . '" post type';
				$strings[] = "_e(" . json_encode($cpt->labels->singular_name) . ", 'sleek');\n";

				$strings[] = '# Translators: The plural name of the "' . $cpt->labels->singular_name . '" post type';
				$strings[] = "_e(" . json_encode($cpt->labels->name) . ", 'sleek');\n";

				if (isset($cpt->rewrite['slug'])) {
					$strings[] = '# Translators: The URL of the "' . $cpt->labels->singular_name . '" post type archive';
					$strings[] = "_ex(" . json_encode($cpt->rewrite['slug']) . ", 'url', 'sleek');\n";
				}

				# Taxonomies
				foreach (get_object_taxonomies($cpt->name, 'objects') as $tax) {
					if (isset($tax->sleek)) {
						$strings[] = '# Translators: The name of the "' . $tax->labels->singular_name . '" taxonomy';
						$strings[] = "_e(" . json_encode($tax->labels->singular_name) . ", 'sleek');\n";

						$strings[] = '# Translators: The plural name of the "' . $tax->labels->singular_name . '" taxonomy';
						$strings[] = "_e(" . json_encode($tax->labels->name) . ", 'sleek');\n";

						if (isset($tax->rewrite['slug'])) {
							$strings[] = '# Translators: The URL of the "' . $tax->labels->singular_name . '" taxonomy archive';
							$strings[] = "_ex(" . json_encode($tax->rewrite['slug']) . ", 'url', 'sleek');\n";
						}
					}
				}
			}
		}

		file_put_contents($dest, implode("\n", $strings));
	});
}
