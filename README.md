# esaba

[![Build Status](https://travis-ci.org/ttskch/esaba.svg?branch=master)](https://travis-ci.org/ttskch/esaba)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ttskch/esaba/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ttskch/esaba/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/ttskch/esaba/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ttskch/esaba/?branch=master)
[![Total Downloads](https://poser.pugx.org/ttskch/esaba/downloads)](https://packagist.org/packages/ttskch/esaba)

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

```bash
$ composer create-project ttskch/esaba # automatically npm install
$ cd esaba
$ vi config/esa.yaml # tailor to your env
```

事前に `https://{チーム名}.esa.io/user/tokens/new` にて [アクセストークン](https://docs.esa.io/posts/102#%E8%AA%8D%E8%A8%BC%E3%81%A8%E8%AA%8D%E5%8F%AF) を発行しておく必要があります。

![](https://tva1.sinaimg.cn/large/008i3skNgy1gyk90gdd96j31z00l4go4.jpg)

## 使い方

### 開発中のローカルサーバー起動

```bash
$ php -S localhost:8000 -t public
# or if symfony-cli is installed
$ symfony serve
```

ブラウザで `http://localhost:8000/post/:post_number` へアクセス。

### 設定

#### 最小限の設定

```yaml
# config/esa.yaml

parameters:
  esa.team_name: {チーム名}
  esa.access_token: {アクセストークン}
  esa.webhook_secret: ~
  esaba.public_categories: ~
  esaba.public_tags: ~
  esaba.private_categories: ~
  esaba.private_tags: ~
  esaba.html_replacements: ~
  esaba.asset_configs: ~
```

#### アクセス制限

```yaml
# config/esa.yaml

parameters:
  # ...
  
  esaba.public_categories: [
    # category names to be published.
    # empty to publish all.
  ]
  esaba.public_tags: [
    # tag names to be published.
  ]
  esaba.private_categories: [
    # category names to be hidden.
    # this overwrites esaba.public_categories config.
  ]
  esaba.private_tags: [
    # tag names to be hidden.
    # this overwrites esaba.public_tags config.
  ]
```

#### HTMLの置換

記事中に他の記事へのリンクがある場合は、esabaでその記事を閲覧するためのURLに自動で置き換えられます。

また、それとは別に任意の置換ルールを設定しておくこともできます。例えば、すべての `target="_blank"` を削除したい場合は、以下のように設定します。

```yaml
# config/esa.yaml

parameters:
  # ...

  esaba.html_replacements:
    # /regex pattern/: replacement
    /target=('|")_blank\1/: ''
```

#### カテゴリ/タグに応じたcss/jsの切り替え

```yaml
# config/esa.yaml

parameters:
  # ...

  esaba.asset_configs:
    # if post matches multiple conditions, tag based condition overwrites category based condition.
    # if post matches multiple category based conditions, condition based deeper category is enabled.
    # if post matches multiple tag based conditions, anyone is arbitrarily enabled.
    category/full/name:
      css: css/your-own.css
      js: js/your-own.js
    '#tag_name':
      css: css/your-own.css
      # if one of 'css' or 'js' is omitted, default.(css|js) is used.
```

上記のように設定した上で、 `./public/css/post/your-own.css` および `./public/js/post/your-own.js` を設置します。

### webpackによる独自アセットのビルド

esabaはscss/webpackに対応しています。

`./assets/post/user/{エントリー名}.(scss|js)` に独自アセットを配置し、

```bash
$ yarn build
# or
$ npm run build
```

を実行すると、以下のように `build/{エントリー名}.(css|js)` というパスで利用できるようになります。

```yaml
# config/esa.yaml

parameters:
  # ...

  esaba.asset_configs:
    category/full/name:
      css: build/your-own.css
      js: build/your-own.js
```

### Webhook

[esa Generic Webhook](https://docs.esa.io/posts/37) を使うことで、esa.io上で記事が作成/更新されたときに、esaba側のキャッシュを自動で更新させることができます。

![](https://tva1.sinaimg.cn/large/008i3skNgy1gyk9f1bvjrj30u00ufwgu.jpg)

```yaml
# config/esa.yaml

parameters:
  # ...

  esa.webhook_secret: {シークレット}

  # または、シークレットなしの場合は
  esa.webhook_secret: ~
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
