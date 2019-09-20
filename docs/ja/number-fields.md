# 数字フィールド

数字フィールドでは、数値を受け入れるテキスト入力を提供します。

## 設定

数字フィールドの設定は、次の通りです。

* **初期値** – 新しいエレメントに適用するデフォルト値
* **最小値** – フィールドに入力できる最小の数字
* **最大値** – フィールドに入力できる最大の数字
* **小数点** – フィールドに入力できる小数点以下の最大の桁数
* **Prefix** – 入力欄の前に表示するテキスト
* **Suffix** – 入力欄の後に表示するテキスト

## テンプレート記法

### 数字フィールドによるエレメントの照会

数字フィールドを持つ[エレメントを照会](dev/element-queries/README.md)する場合、フィールドのハンドルにちなんで名付けられたクエリパラメータを使用して、数字フィールドのデータに基づいた結果をフィルタできます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエレメント
| - | -
| `100` | 値が 100。
| `'>= 100'` | 少なくとも、値が 100。
| `['>= 100', '<= 1000']` | 値が 100 から 1,000 の間。

```twig
{# Fetch entries with a Numbber field set to at least 100 #}
{% set entries = craft.entries()
    .<FieldHandle>('>= 100')
    .all() %}
```

### 数字フィールドデータの操作

テンプレート内で数字フィールドのエレメントを取得する場合、数字フィールドのハンドルを利用して、そのデータにアクセスできます。

```twig
{% set value = entry.<FieldHandle> %}
```

それは、フィールドの数値を提供します。値がない場合、`null` になります。

適切な千単位の区切り文字（例：`,`）でフォーマットするには、[number](./dev/filters.md#number) フィルタを使用してください。

```twig
{{ entry.<FieldHandle>|number }}
```

数字を常に整数とする場合、小数点なしで数字をフォーマットするために `decimals=0` を渡してください。

```twig
{{ entry.<FieldHandle>|number(decimals=0) }}
```

### 投稿フォームで数字フィールドを保存

数字フィールドを含む[投稿フォーム](dev/examples/entry-form.md)が必要な場合、出発点としてこのテンプレートを使用してください。

```twig
{% set field = craft.app.fields.getFieldByHandle('<FieldHandle>') %}

{% set value = entry is defined
    ? entry.<FieldHandle>
    : field.defaultValue %}

<input type="number"
    name="fields[<FieldHandle>]"
    min="{{ field.min }}"
    max="{{ field.max }}"
    value="{{ value }}">
```

