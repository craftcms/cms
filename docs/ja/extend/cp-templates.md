# コントロールパネルのテンプレート

コントロールパネルは Twig テンプレートを使用して構築されているため、フロントエンドの Twig を操作していれば、新しいページでそれを拡張するのは慣れ親しんだ感じがするでしょう。

プラグインは、ベースソースフフォルダにある `templates/` フォルダ内のテンプレートを定義できます。そこに含まれるテンプレートは、プラグインのハンドルをテンプレートパス接頭辞として使用することで参照できます。

例えば、プラグインのハンドルが `foo` で  `templates/bar.twig` テンプレートを持つ場合、そのテンプレートは`/admin/foo/bar` にブラウザで移動するか、Twig から `foo/bar`（または、`foo/bar.twig`）を include / extends することによってアクセスできます。

モジュールもテンプレートを持つことができます。しかし、アクセスできるようにする前に[テンプレートルート](template-roots.md)を手動で定義する必要があります。

## ページのテンプレート

少なくとも、ページのテンプレートは Craft の [_layouts/cp](https://github.com/craftcms/cms/blob/develop/src/templates/_layouts/cp.html) レイアウトテンプレートを extends し、`title` 変数のセットと`content` ブロックを定義する必要があります。

```twig
{% extends "_layouts/cp" %}
{% set title = "Page Title"|t('plugin-handle') %}

{% block content %}
    <p>Page content goes here</p>
{% endblock %}
```

次のブロックも、ページの他の外観をカスタマイズするために定義できます。

- `header` – ページタイトルや他のヘッダー要素を含むページヘッダーの出力に使用されます。
- `pageTitle` – ページタイトルの出力にしようされます。
- `contextMenu` – ページタイトル脇のコンテクストメニューの出力に使用されます。（例：エントリ編集ページのエントリのリビジョンメニュー。）
- `actionButton` – プライマリのページアクションボタンの出力に使用されます。（例：エントリ編集ページの保存ボタン。）
- `sidebar` – ページのサイドバーコンテンツの出力に使用されます。
- `details` – 詳細ペインのコンテンツの出力に使用されます。

