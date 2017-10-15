<?php
require __DIR__.'/config.secret.php';
require __DIR__.'/config.translations.ja.php';

$app['esa.public_categories'] = [
    // empty to publish all
];

$app['esa.private_categories'] = [
    // overwrite public_categories config
];
