# esaba

[![Build Status](https://travis-ci.org/ttskch/esaba.svg?branch=master)](https://travis-ci.org/ttskch/esaba)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ttskch/esaba/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ttskch/esaba/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/ttskch/esaba/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ttskch/esaba/?branch=master)
[![Total Downloads](https://poser.pugx.org/ttskch/esaba/downloads)](https://packagist.org/packages/ttskch/esaba)

[日本語はこちら](README.ja.md)

## What's this?

esaba hosts your markdown docs on [esa.io](https://esa.io). Url like `/post/:post_number` shows the post publicly.

| on esa.io | on esaba (with default css) |
| --- | --- |
| ![image](https://user-images.githubusercontent.com/4360663/31869357-5c4cae84-b7e2-11e7-9c5f-2d37cb8b00e3.png) | ![image](https://user-images.githubusercontent.com/4360663/31869361-66ef4e8c-b7e2-11e7-8241-9195f2d8b16c.png) |

## Advantages compared to built-in "Share Post" feature

- Can show posts with your own css/js (scss/webpack ready)
- Flexible setting of access restriction for each category/tag
- Useful for company internal publishing because it's on-premise
- No need to know the special sharing urls for each post because of auto replacement of link to other post with corresponding esaba url

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

esaba is scss/webpack ready. `./assets/post/*.(scss|js)` will be built and deploy to `./web/(css|js)/post/*.(css|js)` by webpack automatically just like below.

```bash
$ vi assets/post/your-own.scss
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

#### Unrestricting access to `/webhook`

If you set some access restrictions on web server layer, you must unrestrict access to `/webhook` for webhook request from esa.io.
 
For example, on Apache 2.4, config like below.

```
<Location />
    Require ip xxx.xxx.xxx.xxx
</Location>

<LocationMatch ^/(index.php|webhook)$>
    Require all granted
</LocationMatch>
```
