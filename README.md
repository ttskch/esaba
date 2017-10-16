# esaba

[![Latest Stable Version](https://poser.pugx.org/ttskch/esaba/v/stable)](https://packagist.org/packages/ttskch/esaba)
[![Latest Unstable Version](https://poser.pugx.org/ttskch/esaba/v/unstable)](https://packagist.org/packages/ttskch/esaba)
[![Total Downloads](https://poser.pugx.org/ttskch/esaba/downloads)](https://packagist.org/packages/ttskch/esaba)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ttskch/esaba/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ttskch/esaba/)
[![Code Coverage](https://scrutinizer-ci.com/g/ttskch/esaba/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ttskch/esaba/)


Host your markdown docs on [esa.io]() with your own css.

| on esa.io | on esaba (with default css) |
| --- | --- |
| ![image](https://user-images.githubusercontent.com/4360663/31591393-2a4d6726-b25a-11e7-8959-d386a8085fc1.png) | ![image](https://user-images.githubusercontent.com/4360663/31598235-727b3c58-b287-11e7-92ec-170972d68469.png) |

## Requirements

- PHP 5.6+
- [Composer](https://getcomposer.org/)
- [npm](https://www.npmjs.com/)

## Installation

```bash
$ composer create-project ttskch/esaba:@dev
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
    '/target=(\'|")_blank\1/' => '',
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
