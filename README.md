# [Sleek Post Types](https://github.com/powerbuoy/sleek-post-types/)

[![Packagist](https://img.shields.io/packagist/vpre/powerbuoy/sleek-post-types.svg?style=flat-square)](https://packagist.org/packages/powerbuoy/sleek-post-types)
[![GitHub license](https://img.shields.io/github/license/powerbuoy/sleek-post-types.svg?style=flat-square)](https://github.com/powerbuoy/sleek-post-types/blob/master/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/powerbuoy/sleek-post-types.svg?style=flat-square)](https://github.com/powerbuoy/sleek-post-types/issues)
[![GitHub forks](https://img.shields.io/github/forks/powerbuoy/sleek-post-types.svg?style=flat-square)](https://github.com/powerbuoy/sleek-post-types/network)
[![GitHub stars](https://img.shields.io/github/stars/powerbuoy/sleek-post-types.svg?style=flat-square)](https://github.com/powerbuoy/sleek-post-types/stargazers)

Create post types by creating classes in `/post-types/`.

## Theme Support

N/A

## Hooks

### `sleek/post_types/field_group`

Filter the ACF field group for post types before they're added.

### `sleek/post_types/fields`

Filter the ACF fields for post types before they're added.

### `sleek/post_types/archive_fields`

Filter the ACF fields for the archive settings before they're added.

## Functions

### `Sleek\PostTypes\get_file_meta()`

Return information about all files in `/post-types/` (internal use).

## Classes

### `Sleek\PostTypes\PostType`

Extend this class to create a post type.

#### `PostType::init()`

This method is called once on every page load. It allows you to add hooks or do whatever you like related to your post type.

#### `PostType::config()`

Return an array of post type configuration here. The array is passed directly to [register_post_type](https://developer.wordpress.org/reference/functions/register_post_type/). A few additional properties are available:

##### `taxonomies`

This is a native WordPress property but unlike when calling `register_post_type()` any taxonomy set in here will be automatically created if it doesn't already exist.

##### `has_single`

Set this to false to disable single pages for the post type.

##### `hide_from_search`

Hides the post type from search without the [side effects](https://core.trac.wordpress.org/ticket/20234) of the built-in `exclude_from_search`.

##### `has_settings`

Set this to false to _not_ add a "Settings" page for the post type.

##### `has_archive`

If this is false the settings page will be empty, if not it will have a "Title", "Image" and "Description".

#### `PostType::fields()`

Return an array of ACF fields from here and they will be added to the post type.

#### `PostType::sticky_modules()`

Return an array of module names and they will be added to the post type. Render a sticky module using `Sleek\Modules\render('name-of-module')`.

#### `PostType::flexible_modules()`

Return an array of module names and they will be available in a flexible content field named `flexible_modules`. An associative array can be used to create multiple flexible content fields;

```
[
	'left_column' => ['text-block', 'text-blocks'],
	'right_column' => ['related-posts', 'recent-comments']
]
```

Render a flexible module field using `Sleek\Modules\render_flexible('flexible_modules')` or `Sleek\Modules\render_flexible('left_column')` etc.

#### `PostType::sticky_archive_modules()`

Return an array of module names and they will be added to the post type's settings page. Render a sticky module using `Sleek\Modules\render('name-of-module', 'mycpt_settings')`.

#### `PostType::flexible_archive_modules()`

Return an array of module names and they will be available in a flexible content field named `flexible_modules` on the post type's settings page. An associative array can be used here too.

Render modules on the settings page using `Sleek\Modules\render_flexible('flexible_modules', 'mycpt_settings')`.
