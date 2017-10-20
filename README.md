# esaba

[![Build Status](https://travis-ci.org/ttskch/esaba.svg?branch=master)](https://travis-ci.org/ttskch/esaba)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ttskch/esaba/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ttskch/esaba/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/ttskch/esaba/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ttskch/esaba/?branch=master)
[![Total Downloads](https://poser.pugx.org/ttskch/esaba/downloads)](https://packagist.org/packages/ttskch/esaba)

Host your markdown docs on [esa.io]() with your own css.

| on esa.io | on esaba (with default css) |
| --- | --- |
| ![image](https://user-images.githubusercontent.com/4360663/31835836-1a715242-b60e-11e7-9090-18bbad54d7a6.png) | ![image](https://user-images.githubusercontent.com/4360663/31834314-7b9f8878-b608-11e7-96a6-3a46873227a7.png) |

## Requirements

- PHP 5.6+
- [Composer](https://getcomposer.org/)
- [npm](https://www.npmjs.com/)

## Installation

```bash
$ composer create-project ttskch/esaba   # automatically npm install
$ cd esaba
$ cp config/config.secret.php{.placeholder,}
$ vi config/config.secret.php   # tailor to your env
```

You must to issue personal access token in advance.

![image](https://user-images.githubusercontent.com/4360663/31835239-c8ea9b60-b60b-11e7-9d83-ee40eebdfb6c.png)


## Usage

### Running in dev

```bash
$ COMPOSER_PROCESS_TIMEOUT=0 composer run
```

And go to http://localhost:8888/index_dev.php/post/:post_number

### Configuration

#### Access restriction

```php
// config/config.php

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
```

#### Html replacements

esaba replaces links to other post in content html of post with links to see the post on esaba automatically. And you can also fix content before rendering with arbitrary replacements. For example, you can remove all `target="_blank"` by following.

```php
// config/config.php

$app['config.esa.html_replacements'] = [
    // '/regex pattern/' => 'replacement',
    '/target=(\'|")_blank\1/' => '',
];
```

#### Switching css/js according to categories/tags

```php
// config/config.php

$app['config.esa.asset'] = [
    // if post matches multiple conditions, tag based condition overwrites category based condition.
    // if post matches multiple category based conditions, condition based deeper category is enabled.
    // if post matches multiple tag based conditions, any one is arbitrarily enabled.
    'category/full/name' => [
        'css' => 'css/post/your-own.css',
        'js' => 'js/post/your-own.js',
    ],
    '#tag_name' => [
        'css' => 'css/post/your-own.css',
        // if one of 'css' or 'js' is omitted, default.(css|js) is used.
    ],
];
```

And deploy `./web/css/post/your-own.css` and `./web/js/post/your-own.js`. 

### Building your own assets with webpack

esaba is scss/webpack ready. `./assets/scss/post/*.scss` and `./assets/js/post/*.js` can be built and deploy to `./web/css/post/**.css` and `./web/js/post/*.js` by webpack just like below.

```bash
$ vi assets/scss/post/your-own.scss
$ npm run build
  :
$ tree web/css/post
web/css/post
├── default.css
└── your-own.css

0 directories, 2 files
```

### Webhook

You can configure to automatically warm-up caches for created/updated posts using [esa Generic Webhook](https://docs.esa.io/posts/37).

![image](https://user-images.githubusercontent.com/4360663/31834149-01aafeee-b608-11e7-8b63-84dd6f04920e.png)

```php
// config/config.secret.php

$app['config.esa.webhook_secret'] = 'Secret here';
```
