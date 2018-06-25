# タグフィールド

タグフィールドでは、[タグ](tags.md) を作成し、それを親エレメントに関連付けることができます。

## 設定

タグフィールドの設定は、次の通りです。

* **ソース** – タグを関連付けるタググループ
* **ターゲットロケール** – 作成・関連付けされるタグのロケール（この設定は複数のサイトロケールを持つ Craft Pro が実行されている場合のみ、表示されます）
* **選択ラベル** – タグの検索と入力を行うフィールドのラベルに使用されます

## フィールド

タグフィールドには、現在選択されているすべてのタグのリストと、新しいタグを追加するための入力欄があります。

テキスト入力欄に入力すると、タグフィールドはそのタググループに属する既存のタグを検索し、入力欄の下のメニューにタグのサジェストを表示します。完全に一致するものが見つからない場合、メニューの最初のオプションからあなたが入力したタイトルの新しいタグを作成できます。

### タグコンテンツの編集

選択されたタグをダブルクリックすると、タグのタイトルや「設定 > タグ > フィールド」でタグに関連付けられたフィールドを編集できるモーダルウィンドウが開きます。

## テンプレート記法

テンプレート内でタグフィールドのエレメントを取得する場合、タグフィールドのハンドルを利用して、選択されたタグにアクセスできます。

```twig
{% set tags = entry.tagsFieldHandle %}
```

これは、所定のフィールドで選択されたすべてのタグを出力するよう準備された[エレメントクエリ](element-queries.md)を提供します。言い換えれば、上の行は次のコードのショートカットとなります。

```twig
{% set tags = craft.tags({
 relatedTo: { sourceElement: entry, field: "tagsFieldHandle" },
 order:"sortOrder",
 limit:null
}) %}
```

（`relatedTo` パラメータの詳細は、[リレーション](relations.md)を見てください。）

### 実例

タグフィールドに選択されたタグがあるかどうかを調べるには、`length` フィルタを利用します。

```twig
{% if entry.tagsFieldHandle|length %}
 ...
{% endif %}
```

選択されたタグをループするには、フィールドを配列のように扱います。

```twig
{% for tag in entry.tagsFieldHandle %}
 ...
{% endfor %}
```

常に「`entry.tagsFieldHandle`」を記述するよりもむしろ、一度呼び出して別の変数にセットしましょう。

```twig
{% set tags = entry.tagsFieldHandle %}

{% if tags|length %}

 <h3>Some great tags</h3>
 {% for tag in tags %}
 ...
 {% endfor %}

{% endif %}
```

ElementCriteriaModel オブジェクトにパラメータを追加することもできます。

```twig
{% set tags = entry.tagsFieldHandle.order('title') %}
```

意図的にタグフィールドへ1つだけセットしている場合でも、タグフィールドを呼び出すと、選択されたタグではなく、同じ ElementCriteriaModel として提供されることを覚えておいてください。選択された最初の（1つだけの）タグを取得するには、`first()` を利用します。

```twig
{% set tag = entry.myTagsField.first() %}

{% if tag %}
 ...
{% endif %}
```

### 関連項目

* [エレメントクエリ](element-queries.md)
* [タグのクエリパラメータ](element-query-params/tag-query-params.md)
* <api:craft\elements\Tag>
* [リレーション](relations.md)

