<?php

return [
    'navigation' => [
        'group' => 'Global Settings',
        'label' => 'Form Categories',
    ],
    'actions' => [
        'open_form' => 'Open Form',
    ],
    'fields' => [
        'name'        => 'Category Name',
        'slug'        => 'URL Slug',
        'slug_helper' => 'The slug becomes a public form URL. For example, retail becomes /form/retail.',
        'description' => 'Description',
        'is_active'   => 'Active',
    ],
    'columns' => [
        'name'      => 'Name',
        'slug'      => 'Public URL',
        'is_active' => 'Active',
    ],
    'filters' => [
        'is_active' => 'Active Status',
    ],
    'validation' => [
        'slug'            => 'The URL slug is invalid or uses a reserved system path.',
        'slug_unique'     => 'The URL slug has already been taken.',
        'built_in_slug'   => 'Built-in form category slugs cannot be changed.',
        'built_in_active' => 'Built-in form categories must stay active.',
        'built_in_delete' => 'Built-in form categories cannot be deleted.',
    ],
];
