# esaba

[In English here](README.md)

## esaba（餌場）とは

[esa.io](https://esa.io) 上の記事データをホストするためのPHP製のWebアプリケーションです。`/post/{記事ID}` というURLでesa.io上の任意の記事を公開できます。

| esa.io | esaba (デフォルトのcss) |
| --- | --- |
| ![image](https://user-images.githubusercontent.com/4360663/31869357-5c4cae84-b7e2-11e7-9c5f-2d37cb8b00e3.png) | ![image](https://user-images.githubusercontent.com/4360663/31869361-66ef4e8c-b7e2-11e7-8241-9195f2d8b16c.png) |

## esa.io標準の [Share Post](https://docs.esa.io/posts/110) との違い

- 記事の表示に独自のcss/jsを使うことができる（scss/webpack対応）
- カテゴリやタグごとに細かく公開/非公開を設定できる
- 社内のみに公開したい場合などに便利（オンプレなのでWebサーバーレベルでアクセス制限可能）
- 記事中に他の記事へのリンクがある場合は **esabaのURLに変換して出力してくれる** ので、記事本体のURLと公開用のURLを別々に管理する必要がない

## 環境要件

- PHP 5.6+
- [Composer](https://getcomposer.org/)
- [npm](https://www.npmjs.com/)

もしくは

- [Docker](https://www.docker.com/)
- Docker Compose

## インストール方法

### ネイティブインストール

```bash
$ composer create-project ttskch/esaba   # automatically npm install
$ cd esaba
$ cp config/config.secret.php{.placeholder,}
$ vi config/config.secret.php   # tailor to your env
```

事前に Personal access token を発行しておく必要があります。

![image](https://user-images.githubusercontent.com/4360663/31835239-c8ea9b60-b60b-11e7-9d83-ee40eebdfb6c.png)

### Dockerインストール

```bash
$ git clone git@github.com:ttskch/esaba.git
$ cd esaba
$ cp config/config.secret.php{.placeholder,}
$ vi config/config.secret.php   # tailor to your env
```

ネイティブインストールと同じく、事前に Personal access token を発行しておく必要があります。

## 使い方

### 開発中のローカルサーバー起動

```bash
$ COMPOSER_PROCESS_TIMEOUT=0 composer run
```

Dockerの場合は以下コマンドで起動できます。

```bash
$ docker-compose up # 初回起動時はcompose installなどで時間がかかる
```

ブラウザで http://localhost:8888/index_dev.php/post/:post_number へアクセス。

### 本番設定のサーバー起動

Dockerの場合は[kokuyouwind/esaba:latest](https://hub.docker.com/r/kokuyouwind/esaba/)を使用して本番設定のApacheサーバを起動できます。

`docker-compose.prod.yml` を使用する場合、`config`以下は名前付きボリュームになるため、`config.secret.php`を初回のみ設定することで、以降はコンテナを作り直しても設定が保持されます。

```bash
$ docker-compose -f docker-compose.prod.yml up -d
# 以下は初回起動時のみ設定
$ docker exec --it docker exec -it esaba_app_1 bash
$ cd /app/config
$ cp config.secret.php.placeholder config.secret.php
$ vim config.secret.php
# 設定を入力して保存
$ exit
```

ブラウザで http://localhost/ へアクセス。

### 設定

#### アクセス制限

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

#### HTMLの置換

記事中に他の記事へのリンクがあれば、esabaでその記事を閲覧するためのURLに自動で置き換えられます。また、それとは別に任意の置換ルールを設定しておくこともできます。例えば、すべての `target="_blank"` を削除したい場合は、以下のように設定します。

```php
// config/config.php

$app['config.esa.html_replacements'] = [
    // '/regex pattern/' => 'replacement',
    '/target=(\'|")_blank\1/' => '',
];
```

#### カテゴリ/タグに応じたcss/jsの切り替え

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

上記のように設定した上で、 `./web/css/post/your-own.css` および `./web/js/post/your-own.js` を設置します。 

### webpackによる独自アセットのビルド

esabaはscss/webpackに対応しています。`./assets/post/*.(scss|js)` が自動でビルド対象になり、以下のように `./web/(css|js)/post/*.(css|js)` として配置されます。

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

[esa Generic Webhook](https://docs.esa.io/posts/37) を使うことで、esa.io上で記事が作成/更新されたときに、esaba側のキャッシュを自動で更新させることができます。

![image](https://user-images.githubusercontent.com/4360663/32140978-d312be36-bcb6-11e7-84a4-133ab56506cd.png)

```php
// config/config.secret.php

$app['config.esa.webhook_secret'] = 'Secret here';
```

#### `/webhook/` へのアクセスの解放

もしWebサーバーレベルでのアクセス制限を設定している場合、 `/webhook/` へのアクセスはesa.ioからのwebhookリクエストを受け取るために解放しておく必要があります。

例えば、Apache 2.4の場合は以下のような設定が必要になります。

```
<Location />
    Require ip xxx.xxx.xxx.xxx
</Location>

<LocationMatch ^/(index.php|webhook/?)$>
    Require all granted
</LocationMatch>
```
