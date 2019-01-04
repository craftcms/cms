# ヘッドレス化

Craft のテンプレートシステムは、Craft のコンテンツを取得する唯一の方法ではありません。

Craft を通常のウェブサイト（または、それに加える）の代わりにコンテンツ API として動作するという意味でのヘッドレス CMS として使用する場合、いくつかの方法があります。

::: tip
Craft をヘッドレス CMS として使用する方法について学ぶために、CraftQuest の [Headless Craft CMS](https://craftquest.io/courses/headless-craft) コースをチェックしてください。
:::

## GraphQL

Mark Huot 氏による [CraftQL](https://github.com/markhuot/craftql) プラグンは、設定なしの [GraphQL](https://graphql.org/) サーバーをインストールされた Craft に追加します。

## JSON API

ファーストパーティの [Element API](https://github.com/craftcms/element-api) は、コンテンツのための読み取り専用の [JSON API](http://jsonapi.org/) を作成する簡単な方法です。

## REST API

Craft で REST API を作成する方法を詳しく知るために、Nate Iler 氏の Dot All 2017 のプレゼンテーション [How to Create a Full REST API](http://dotall.com/sessions/how-to-create-a-full-rest-api-with-craft-3) を見てください。

## カスタムなもの

モジュールやプラグインは、新しい HTTP エンドポイントを提供するためのカスタムのフロントエンド[コントローラー](https://www.yiiframework.com/doc/guide/2.0/en/structure-controllers)を定義できます。 はじめるには、[Craft の拡張](../extend/README.md)を参照してください。

