# esaba

[![Test Status](https://github.com/ttskch/esaba/actions/workflows/test.yaml/badge.svg)](https://github.com/ttskch/esaba/actions/workflows/test.yaml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ttskch/esaba/badges/quality-score.png)](https://scrutinizer-ci.com/g/ttskch/esaba)
[![Code Coverage](https://scrutinizer-ci.com/g/ttskch/esaba/badges/coverage.png)](https://scrutinizer-ci.com/g/ttskch/esaba)
[![Latest Stable Version](https://poser.pugx.org/ttskch/esaba/version?format=flat-square)](https://packagist.org/packages/ttskch/esaba)
[![Total Downloads](https://poser.pugx.org/ttskch/esaba/downloads?format=flat-square)](https://packagist.org/packages/ttskch/esaba)

## esabaとは

[esa.io](https://esa.io) 上の記事データをホストするためのPHP製のWebアプリケーションです。`/post/{記事ID}` というURLでesa.io上の任意の記事を公開できます。

| esa.io | esaba (デフォルトのcss) |
| --- | --- |
| ![](https://tva1.sinaimg.cn/large/008i3skNgy1gyk8uwxnz1j31qf0u0q7e.jpg) | ![](https://tva1.sinaimg.cn/large/008i3skNgy1gyk8wcfgfqj31jl0u075s.jpg) |

## esa.io標準の [記事の外部公開](https://docs.esa.io/posts/110) との違い

- 記事の表示に独自のcss/jsを使うことができる（scss/webpack対応）
- カテゴリやタグごとに細かく公開/非公開を設定できる
- 社内のみに公開したい場合などに便利（オンプレなのでWebサーバーレベルでアクセス制限可能）
- 記事中に他の記事へのリンクがある場合は **esabaのURLに変換して出力してくれる** ので、記事本体のURLと公開用のURLを別々に管理する必要がない

## 環境要件

- PHP >=8.0.2
- Node >=12
- [Composer](https://getcomposer.org/)
- [npm](https://www.npmjs.com/)

## インストール方法

### アクセストークンの発行

事前に `https://{チーム名}.esa.io/user/tokens/new` にてRead権限を持った [アクセストークン](https://docs.esa.io/posts/102#%E8%AA%8D%E8%A8%BC%E3%81%A8%E8%AA%8D%E5%8F%AF) を発行しておく必要があります。

![](https://tva1.sinaimg.cn/large/008i3skNgy1gyk90gdd96j31z00l4go4.jpg)

### 任意のサーバーへのインストール

```bash
$ composer create-project ttskch/esaba # automatically npm install
$ cd esaba
$ cp .env{,.local}
$ vi .env.local # tailor to your env

# and serve under ./public with your web server
```

## 使い方

### 設定

設定は `.env.local` または `config/esaba.php` で行います。

#### 最小限の設定

```
# .env.local

ESA_TEAM_NAME={チーム名}
ESA_ACCESS_TOKEN={アクセストークン}
```

#### アクセス制限

カテゴリ/タグに応じて公開/非公開を設定することができます。設定値はJSON形式の文字列とする必要があり `.env.local` ではエスケープなどが面倒なので、`config/esaba.php` で設定するのがおすすめです。

```php
<?php
// config/esaba.php

return json_encode([
    // ...

    // empty to publish all
    'ESABA_PUBLIC_CATEGORIES' => json_encode([
//        'path/to/category1',
//        'path/to/category2',
    ]),

    'ESABA_PUBLIC_TAGS' => json_encode([
//        'tag1',
//        'tag2',
    ]),

    // takes priority of over ESABA_PUBLIC_CATEGORIES
    'ESABA_PRIVATE_CATEGORIES' => json_encode([
//        'path/to/category1/subcategory1',
//        'path/to/category1/subcategory2',
    ]),

    // takes priority of over ESABA_PUBLIC_TAGS
    'ESABA_PRIVATE_TAGS' => json_encode([
//        'tag3',
//        'tag4',
    ]),
]);
```

#### HTMLの置換

記事中に他の記事へのリンクがある場合は、esabaでその記事を閲覧するためのURLに自動で置き換えられます。

また、それとは別に任意の置換ルールを設定しておくこともできます。例えば、すべての `target="_blank"` を削除したい場合は、以下のように設定します。

```php
<?php

return json_encode([
    // ...

    'ESABA_HTML_REPLACEMENTS' => json_encode([
//        '/regex pattern/' => 'replacement',
        '/target=(\'|")_blank\1/' => '',
    ]),
]);
```

#### カテゴリ/タグに応じたcss/jsの切り替え

```php
<?php
// config/esaba.php

return json_encode([
    // ...

    // if post matches multiple conditions, tag based condition taks priority of over category based condition
    // if post matches multiple category based conditions, condition for deeper category is enabled
    // if post matches multiple tag based conditions, any arbitrarily one is enabled
    'ESABA_USER_ASSETS' => json_encode([
        'path/to/category' => [
            'css' => 'css/your-own.css',
            'js' => 'js/your-own.js',
        ],
        '#tag_name' => [
            'css' => 'css/your-own.css',
            // if one of "css" or "js" is omitted, default.(css|js) is used
        ],
    ]),
]);
```

上記のように設定した上で、 `./public/css/post/your-own.css` および `./public/js/post/your-own.js` を設置することで、`path/to/category` カテゴリや `#tag_name` タグに該当する記事に対して指定したcss/jsを適用させることができます。

### Webhook

[esa Generic Webhook](https://docs.esa.io/posts/37) を使うことで、esa.io上で記事が作成/更新されたときに、esaba側のキャッシュを自動で更新させることができます。

![](https://tva1.sinaimg.cn/large/008i3skNgy1gyk9f1bvjrj30u00ufwgu.jpg)

```
# .env.local

ESA_WEBHOOK_SECRET={シークレット} # シークレットなしの場合は設定不要
```

#### `/webhook` へのアクセスの解放

もしWebサーバーレベルでのアクセス制限を設定している場合、 `/webhook` へのアクセスはesa.ioからのwebhookリクエストを受け取るために解放しておく必要があります。

例えば、Apache 2.4の場合は以下のような設定が必要になります。

```
<Location />
    Require ip xxx.xxx.xxx.xxx
</Location>

<LocationMatch ^/(index.php|webhook?)$>
    Require all granted
</LocationMatch>
```

## 開発

### ローカルサーバー起動

```bash
$ php -S localhost:8000 -t public
# or if symfony-cli is installed
$ symfony serve
```

ブラウザで `http://localhost:8000/post/:post_number` へアクセス。

### webpackによる独自アセットのビルド

esabaはscss/webpackに対応しています。

`./assets/post/user/{エントリー名}.(scss|js)` に独自アセットを配置し、

```bash
$ yarn build
# or
$ npm run build
```

を実行すると、以下のように `build/{エントリー名}.(css|js)` というパスで利用できるようになります。

```php
<?php
// config/esaba.php

return json_encode([
    // ...

    'ESABA_USER_ASSETS' => json_encode([
        'path/to/category' => [
            'css' => 'build/your-own.css',
            'js' => 'build/your-own.js',
        ],
    ]),
]);
```
