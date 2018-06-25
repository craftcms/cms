# エントリフィールド

エントリフィールドでは、[エントリ](sections-and-entries.md)を親エレメントに関連付けることができます。

## 設定

エントリフィールドの設定は、次の通りです。

* **ソース** – エントリを関連付けるセクション（デフォルトは「すべて」です）
* **ターゲットロケール** – 関連付けするエントリーのロケール（この設定は複数のサイトロケールを持つ Craft Pro が実行されている場合のみ、表示されます）
* **リミット** – フィールドと一度に関連付けできるエントリ数の上限（デフォルトは無制限です）
* **選択ラベル** – フィールドの選択ボタンのラベルに使用されます

## フィールド

エントリフィールドには、現在選択されているすべてのエントリのリストと、新しいエントリを追加するためのボタンがあります。

「エントリを追加」ボタンをクリックすると、すでに追加されているエントリの検索や選択ができるモーダルウィンドウが表示されます。

### エントリコンテンツの編集

選択されたエントリをダブルクリックすると、エントリのタイトルやカスタムフィールドを編集できるモーダルウィンドウが開きます。

## テンプレート記法

テンプレート内でエントリフィールドのエレメントを取得する場合、エントリフィールドのハンドルを利用して、選択されたエントリにアクセスできます。

```twig
{% set entries = entry.entriesFieldHandle %}
```

これは、所定のフィールドで選択されたすべてのエントリを出力するよう定義された[エレメントクエリ](element-queries.md)を提供します。言い換えれば、上の行は次のコードのショートカットとなります。

```twig
{% set entries = craft.entries({
 relatedTo: { sourceElement: entry, field: "entriesFieldHandle" },
 orderBy:"sortOrder",
 limit:null
}) %}
```

（`relatedTo` パラメータの詳細は、[リレーション](relations.md)を見てください。）

### 実例

エントリフィールドに選択されたエントリがあるかどうかを調べるには、`length` フィルタを利用します。

```twig
{% if entry.entriesFieldHandle|length %}
 ...
{% endif %}
```

選択されたエントリをループするには

```twig
{% for entry in entry.entriesFieldHandle.all() %}
 ...
{% endfor %}
```

常に「`entry.entriesFieldHandle`」を記述するよりもむしろ、一度呼び出して別の変数にセットしましょう。

```twig
{% set entries = entry.entriesFieldHandle %}

{% if entries|length %}

 <h3>Some great entries</h3>
 {% for entry in entries %}
 ...
 {% endfor %}

{% endif %}
```

ElementCriteriaModel オブジェクトにパラメータを追加することもできます。

```twig
{% set newsEntries = entry.entriesFieldHandle.section('news') %}
```

意図的にエントリフィールドへ1つだけセットしている場合でも、エントリフィールドを呼び出すと、選択されたエントリではなく、同じ ElementCriteriaModel として提供されることを覚えておいてください。選択された最初の（1つだけの）エントリを取得するには、`one()` を利用します。

```twig
{% set entry = entry.myEntriesField.one() %}

{% if entry %}
 ...
{% endif %}
```

### 関連項目

* [エレメントクエリ](element-queries.md)
* [エントリのクエリパラメータ](element-query-params/entry-query-params.md)
* <api:craft\elements\Entry>
* [リレーション](relations.md)

