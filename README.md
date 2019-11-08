Create post types by creating classes in `/post-types/`:

```
namespace Sleek\PostTypes;

class Office extends PostType {
	public function config () {
		return [
			'menu_icon' => 'dashicons-admin-multisite',
			'has_single' => false,
			'hide_from_search' => true,
			'taxonomies' => ['city']
		];
	}

	public function fields () {
		return [
			[
				'name' => 'address',
				'type' => 'text',
				'label' => __('Address', 'sleek')
			]
		];
	}
}
```

Also adds support for `has_single` (triggers 404 when visiting post, but archive still works) and `hide_from_search` (hides the post from search without the side effects of the built-in `exclude_from_search`). Also automatically creates any taxonomies set in `taxonomies`.

Return an array of ACF fields in `fields()` and they will be created for the post type.
