# カテゴリフィールド

カテゴリフィールドでは、[カテゴリ](categories.md)を親エレメントに関連付けることができます。

## 設定

カテゴリフィールドの設定は、次の通りです。

* **ソース** – カテゴリを関連付けるカテゴリグループ
* **ターゲットロケール** – 関連付けするカテゴリのロケール（この設定は複数のサイトロケールを持つ Craft Pro が実行されている場合のみ、表示されます）
* **ブランチ制限** – フィールドと一度に関連付けできるカテゴリ数の上限（デフォルトは無制限です） カテゴリグループが複数の階層を持つ場合、ここに親カテゴリも含まれます。
* **選択ラベル** – フィールドの選択ボタンのラベルに使用されます

## フィールド

カテゴリフィールドには、現在選択されているカテゴリのリストと、新しいカテゴリを追加するためのボタンがあります。

「カテゴリーを追加」ボタンをクリックすると、すでに追加されているカテゴリの検索や選択ができるモーダルウィンドウが表示されます。

ネストされたカテゴリを選択すると、そのカテゴリに至るすべての先祖カテゴリも自動的に選択されます。同様に、メインフィールドの入力からカテゴリを選択解除すると、そのすべての子孫カテゴリも選択解除されます。

## テンプレート記法

テンプレート内でカテゴリフィールドのエレメントを取得する場合、カテゴリフィールドのハンドルを利用して、選択されたカテゴリにアクセスできます。

```twig
{% set categories = entry.categoriesFieldHandle %}
```

これは、所定のフィールドで選択されたすべてのカテゴリを出力するよう準備された[エレメントクエリ](element-queries.md)を提供します。言い換えれば、上の行は次のコードのショートカットとなります。

```twig
{% set categories = craft.categories({
 relatedTo: { sourceElement: entry, field: "categoriesFieldHandle" },
 limit:null
}) %}
```

（`relatedTo` パラメータの詳細は、[リレーション](relations.md)を見てください。）

### 実例

カテゴリフィールドに選択されたカテゴリがあるかどうかを調べるには、`length` フィルタを利用します。

```twig
{% if entry.categoriesFieldHandle|length %}
 ...
{% endif %}
```

選択されたカテゴリをループするには

```twig
{% nav category in entry.categoriesFieldHandle.all() %}
 ...
{% endnav %}
```

常に「`entry.categoriesFieldHandle`」を記述するよりもむしろ、一度呼び出して別の変数にセットしましょう。

```twig
{% set categories = entry.categoriesFieldHandle %}

{% if categories|length %}

 <h3>Some great categories</h3>
 {% nav category in categories %}
 ...
 {% endnav %}

{% endif %}
```

ElementCriteriaModel オブジェクトにパラメータを追加することもできます。

```twig
{% set categories = entry.categoriesFieldHandle.orderBy('name') %}
```

意図的にカテゴリフィールドへ1つだけセットしている場合でも、カテゴリフィールドを呼び出すと、選択されたカテゴリではなく、同じ ElementCriteriaModel として提供されることを覚えておいてください。選択された最初の（1つだけの）カテゴリを取得するには、`one()` を利用します。

```twig
{% set category = entry.myCategoriesField.one() %}

{% if category %}
 ...
{% endif %}
```

### 関連項目

* [エレメントクエリ](element-queries.md)
* [カテゴリのクエリパラメータ](element-query-params/category-query-params.md)
* <api:craft\elements\Category>
* [リレーション](relations.md)

