<?php
require __DIR__.'/config.secret.php';
require __DIR__.'/config.translations.ja.php';

$app['config.esa.public'] = [
    'categories' => [
        // category names to be published.
        // empty to publish all.
    ],
    'tags' => [
        // tag names to be published.
    ],
];

$app['config.esa.private'] = [
    'categories' => [
        // category names to be withheld.
        // this overwrites esa.public config.
    ],
    'tags' => [
        // tag names to be withheld.
        // this overwrites esa.public config.
    ],
];

$app['config.esa.html_replacements'] = [
    // '/regex pattern/' => 'replacement',
    '/target=(\'|")_blank\1/' => '',
];

$app['config.esa.asset'] = [
    // if post matches multiple conditions, tag based condition overwrites category based condition.
    // if post matches multiple category based conditions, condition based deeper category is enabled.
    // if post matches multiple tag based conditions, any one is arbitrarily enabled.
    'category/full/name' => [
        'css' => 'css/post/default.css',
        'js' => 'js/post/default.css',
    ],
    '#tag_name' => [
        'css' => 'css/post/default.css',
        // if one of 'css' or 'js' is omitted, default.(css|js) is used.
    ],
];
