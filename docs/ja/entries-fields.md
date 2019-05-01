# エントリフィールド

エントリフィールドでは、[エントリ](sections-and-entries.md)を他のエレメントに関連付けることができます。

## 設定

エントリフィールドの設定は、次の通りです。

- **ソース** – フィールドが、どのエントリ（または、他のエントリインデックスソース）からエントリを関連付けられるか。
- **リミット** – フィールドと一度に関連付けできるエントリ数の上限（デフォルトは無制限です）
- **選択ラベル** – フィールドの選択ボタンのラベルに使用されます

### マルチサイト設定

マルチサイトがインストールされている場合、次の設定も有効になります。（「高度」のトグルボタンで表示されます）

- **特定のサイトから エントリ を関連付けますか?** – 特定のサイトのエントリとの関連付けのみを許可するかどうか。

   有効にすると、サイトを選択するための新しい設定が表示されます。

   無効にすると、関連付けられたエントリは常に現在のサイトから取得されます。

- **サイトごとにリレーションを管理** – それぞれのサイトが関連付けられたエントリの独自のセットを取得するかどうか。

## フィールド

エントリフィールドには、現在関連付けられているすべてのエントリのリストと、新しいエントリを追加するためのボタンがあります。

「エントリを追加」ボタンをクリックすると、すでに追加されているエントリの検索や選択ができるモーダルウィンドウが表示されます。このモーダルから新しいエントリを作るには、「新しいエントリの入力」ボタンをクリックします。

### インラインのエントリ編集

関連付けられたエントリをダブルクリックすると、エントリのタイトルやカスタムフィールドを編集できる HUD を表示します。

## テンプレート記法

### エントリフィールドによるエレメントの照会

エントリフィールドを持つ[エレメントを照会](dev/element-queries/README.md)する場合、フィールドのハンドルにちなんで名付けられたクエリパラメータを使用して、エントリフィールドのデータに基づいた結果をフィルタできます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエレメント
| - | -
| `':empty:'` | 関連付けられたエントリを持たない。
| `':notempty:'` | 少なくとも1つの関連付けられたエントリを持つ。

```twig
{# Fetch entries with a related entry #}
{% set entries = craft.entries()
    .<FieldHandle>(':notempty:')
    .all() %}
```

### エントリフィールドデータの操作

テンプレート内でエントリフィールドのエレメントを取得する場合、エントリフィールドのハンドルを利用して、関連付けられたエントリにアクセスできます。

```twig
{% set relatedEntries = entry.<FieldHandle> %}
```

これは、所定のフィールドで関連付けられたすべてのエントリを出力するよう定義された[エレメントクエリ](dev/element-queries/entry-queries.md)を提供します。

関連付けられたすべてのエントリをループするには、[all()](api:craft\db\Query::all()) を呼び出して、結果をループ処理します。

```twig
{% set relatedEntries = entry.<FieldHandle>.all() %}
{% if relatedEntries|length %}
    <ul>
        {% for rel in relatedEntries %}
            <li><a href="{{ rel.url }}">{{ rel.title }}</a></li>
        {% endfor %}
    </ul>
{% endif %}
```

関連付けられた最初のエントリだけが欲しい場合、代わりに [one()](api:craft\db\Query::one()) を呼び出して、何かが返されていることを確認します。

```twig
{% set rel = entry.<FieldHandle>.one() %}
{% if rel %}
    <p><a href="{{ rel.url }}">{{ rel.title }}</a></p>
{% endif %}
```

（取得する必要はなく）いずれかの関連付けられたエントリがあるかを確認したい場合、[exists()](api:craft\db\Query::exists()) を呼び出すことができます。

```twig
{% if entry.<FieldHandle>.exists() %}
    <p>There are related entries!</p>
{% endif %}
```

エントリクエリで[パラメータ](dev/element-queries/entry-queries.md#parameters)をセットすることもできます。例えば、`news` セクションに含まれるエントリだけを取得するには、[section](dev/element-queries/entry-queries.md#section) パラメータをセットしてください。

```twig
{% set relatedEntries = entry.<FieldHandle>
    .section('news')
    .all() %}
```

## 関連項目

* [エントリクエリ](dev/element-queries/entry-queries.md)
* <api:craft\elements\Entry>
* [リレーション](relations.md)

