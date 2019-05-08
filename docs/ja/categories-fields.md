# カテゴリフィールド

カテゴリフィールドでは、[カテゴリ](categories.md)をたのエレメントに関連付けることができます。

## 設定

カテゴリフィールドの設定は、次の通りです。

- **ソース** – フィールドが、どのカテゴリグループ（または、他のカテゴリインデックスソース）からカテゴリを関連付けられるか。

- **ブランチ制限** – フィールドと一度に関連付けできるカテゴリ数の上限。（デフォルトは無制限です）

   例えば、次のカテゴリグループがあるとします。

   ```
    Food
    ├── Fruit
    │   ├── Apples
    │   ├── Bananas
    │   └── Oranges
    └── Vegetables
        ├── Brussels sprouts
        ├── Carrots
        └── Celery
   ```

   そして、ブランチ制限が `1` にセットされていれば、Fruit、Vegetables、または、その子孫の1つだけを関連付けられます。

- **選択ラベル** – フィールドの選択ボタンのラベルに使用されます

### マルチサイト設定

マルチサイトがインストールされている場合、次の設定も有効になります。（「高度」のトグルボタンで表示されます）

- **特定のサイトから カテゴリ を関連付けますか?** – 特定のサイトのカテゴリとの関連付けのみを許可するかどうか。

   有効にすると、サイトを選択するための新しい設定が表示されます。

   無効にすると、関連付けられたカテゴリは常に現在のサイトから取得されます。

- **サイトごとにリレーションを管理** – それぞれのサイトが関連付けられたカテゴリの独自のセットを取得するかどうか。

## フィールド

カテゴリフィールドには、現在関連付けられているすべてのカテゴリのリストと、新しいカテゴリを追加するためのボタンがあります。

「カテゴリーを追加」ボタンをクリックすると、すでに追加されているカテゴリの検索や選択ができるモーダルウィンドウが表示されます。このモーダルから新しいカテゴリを作るには、「新しいカテゴリー」ボタンをクリックします。

ネストされたカテゴリを選択すると、そのカテゴリに至るすべての先祖カテゴリも自動的に関連付けられます。同様に、メインフィールドの入力からカテゴリを削除すると、そのすべての子孫カテゴリも削除されます。

### インラインのカテゴリ編集

関連付けられたカテゴリをダブルクリックすると、カテゴリのタイトルやカスタムフィールドを編集できる HUD を表示します。

## テンプレート記法

### カテゴリフィールドによるエレメントの照会

カテゴリフィールドを持つ[エレメントを照会](dev/element-queries/README.md)する場合、フィールドのハンドルにちなんで名付けられたクエリパラメータを使用して、カテゴリフィールドのデータに基づいた結果をフィルタできます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエレメント
| - | -
| `':empty:'` | 関連付けられたカテゴリを持たない。
| `':notempty:'` | 少なくとも1つの関連付けられたカテゴリを持つ。

```twig
{# Fetch entries with a related category #}
{% set entries = craft.entries()
    .<FieldHandle>(':notempty:')
    .all() %}
```

### カテゴリフィールドデータの操作

テンプレート内でカテゴリフィールドのエレメントを取得する場合、カテゴリフィールドのハンドルを利用して、関連付けられたカテゴリにアクセスできます。

```twig
{% set relatedCategories = entry.<FieldHandle> %}
```

これは、所定のフィールドで関連付けられたすべてのカテゴリを出力するよう準備された[カテゴリクエリ](dev/element-queries/category-queries.md)を提供します。

関連付けられたすべてのカテゴリをループするには、[all()](api:craft\db\Query::all()) を呼び出して、結果をループ処理します。

```twig
{% set relatedCategories = entry.<FieldHandle>.all() %}
{% if relatedCategories|length %}
    <ul>
        {% for rel in relatedCategories %}
            <li><a href="{{ rel.url }}">{{ rel.title }}</a></li>
        {% endfor %}
    </ul>
{% endif %}
```

または、[nav](dev/tags/nav.md) タグで階層リストとして表示することもできます。

```twig
{% set relatedCategories = entry.<FieldHandle.all() %}
{% if relatedCategories|length %}
    <ul>
        {% nav rel in relatedCategories %}
            <li>
                <a href="{{ rel.url }}">{{ rel.title }}</a>
                {% ifchildren %}
                    <ul>
                        {% children %}
                    </ul>
                {% endifchildren %}
            </li>
        {% endnav %}
    </ul>
{% endif %}
```

関連付けられた最初のカテゴリだけが欲しい場合、代わりに [one()](api:craft\db\Query::one()) を呼び出して、何かが返されていることを確認します。

```twig
{% set rel = entry.<FieldHandle>.one() %}
{% if rel %}
    <p><a href="{{ rel.url }}">{{ rel.title }}</a></p>
{% endif %}
```

（取得する必要はなく）いずれかの関連付けられたカテゴリがあるかを確認したい場合、[exists()](api:craft\db\Query::exists()) を呼び出すことができます。

```twig
{% if entry.<FieldHandle>.exists() %}
    <p>There are related categories!</p>
{% endif %}
```

カテゴリクエリで[パラメータ](dev/element-queries/category-queries.md#parameters)をセットすることもできます。例えば、“leaves”（子を持たないカテゴリ）だけを取得するには、[leaves](dev/element-queries/category-queries.md#leaves) パラメータをセットします。

```twig
{% set relatedCategories = entry.<FieldHandle>
    .leaves()
    .all() %}
```

## 関連項目

* [カテゴリクエリ](dev/element-queries/category-queries.md)
* <api:craft\elements\Category>
* [リレーション](relations.md)

