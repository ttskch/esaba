# esaba

Host your markdown docs on [esa.io]() with your own css.

## Requirements

- PHP 5.5.9+
- [composer](https://getcomposer.org/)
- [npm](https://www.npmjs.com/)

## Installation

```bash
$ composer create-project ttskch/esaba
$ cd esaba
$ cp config/config.secret.php{.placeholder,}
$ vi config/config.secret.php   # tailor to your env
```

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

$app['esa.public_categories'] = [
     // empty to publish all
];

$app['esa.private_categories'] = [
     // overwrite public_categories config
];
```

#### Html replacements

You can fix content html of post before rendering with arbitrary replacements. For example, you can remove all `target="_blank"` by following.

```php
// config/config.php

$app['esa.html_replacements'] = [
    // '/regex pattern/' => 'replacement',
    '/target\s*=\s*(\'|")_blank\1/' => '',
];
```

### Customizing styles

```bash
$ vi web/scss/esa-content.scss   # customize this file
$ npm run build                  # build into web/css
```

Or

```bash
$ npm run watch &
$ vi web/scss/esa-content.scss   # will be automatically built
```
