# ユーザーフィールド

ユーザーフィールドでは、[ユーザー](users.md)を親エントリに関連付けることができます。

## 設定

ユーザーフィールドの設定は、次の通りです。

* **ソース** – ユーザーを関連付けるユーザーグループ（デフォルトは「すべて」です）
* **リミット** – フィールドと一度に関連付けできるユーザー数の上限（デフォルトは無制限です）
* **選択ラベル** – フィールドの選択ボタンのラベルに使用されます

## フィールド

ユーザーフィールドには、現在選択されているすべてのユーザーのリストと、新しいユーザーを追加するためのボタンがあります。

「ユーザーを追加」ボタンをクリックすると、すでに追加されているユーザーの検索や選択ができるモーダルウィンドウが表示されます。

## テンプレート記法

テンプレート内でユーザーフィールドのエレメントを取得する場合、ユーザーフィールドのハンドルを利用して、選択されたユーザーにアクセスできます。

```twig
{% set users = entry.usersFieldHandle %}
```

これは、所定のフィールドで選択されたすべてのユーザーを出力するよう準備された[エレメントクエリ](element-queries.md)を提供します。言い換えれば、上の行は次のコードのショートカットとなります。

```twig
{% craft.users({
 relatedTo: { sourceElement: entry, field: "usersFieldHandle" },
 orderBy:"sortOrder",
 limit:null
}) %}
```

（`relatedTo` パラメータの詳細は、[リレーション](relations.md)を見てください。）

### 実例

ユーザーフィールドに選択されたユーザーがあるかどうかを調べるには、`length` フィルタを利用します。

```twig
{% if entry.usersFieldHandle|length %}
 ...
{% endif %}
```

選択されたユーザーをループするには

```twig
{% for user in entry.usersFieldHandle.all() %}
 ...
{% endfor %}
```

常に「`entry.usersFieldHandle`」を記述するよりもむしろ、一度呼び出して別の変数にセットしましょう。

```twig
{% set users = entry.usersFieldHandle %}

{% if users|length %}

 <h3>Some great users</h3>
 {% for user in users %}
 ...
 {% endfor %}

{% endif %}
```

ElementCriteriaModel オブジェクトにパラメータを追加することもできます。

```twig
{% set authors = entry.usersFieldHandle.group('authors') %}
```

意図的にユーザーフィールドへ1つだけセットしている場合でも、ユーザーフィールドを呼び出すと、選択されたユーザーではなく、同じ ElementCriteriaModel が提供されることを覚えておいてください。選択された最初の（1つだけの）ユーザーを取得するには、`one()` を利用します。

```twig
{% set user = entry.myUsersField.one() %}

{% if user %}
 ...
{% endif %}
```

### 関連項目

* [エレメントクエリ](element-queries.md)
* [ユーザーのクエリパラメータ](element-query-params/user-query-params.md)
* <api:craft\elements\User>
* [リレーション](relations.md)

