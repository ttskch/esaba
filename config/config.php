<?php
require __DIR__.'/config.secret.php';
require __DIR__.'/config.translations.ja.php';

$app['esa.public'] = [
    'categories' => [
        // category names to be published.
        // empty to publish all.
    ],
    'tags' => [
        // tag names to be published.
        // empty to publish all.
    ],
];

$app['esa.private'] = [
    'categories' => [
        // category names to be unpublished.
        // this overwrites esa.public.categories config.
    ],
    'tags' => [
        // tag names to be unpublished.
        // this overwrites esa.public.tags config.
    ],
];

$app['esa.html_replacements'] = [
    // '/regex pattern/' => 'replacement',
    '/target=(\'|")_blank\1/' => '',
];
