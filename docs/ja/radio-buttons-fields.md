# ラジオボタンフィールド

ラジオボタンフィールドでは、ラジオボタンのグループが提供されます。

## 設定

ラジオボタンフィールドの設定は、次の通りです。

* **ラジオボタンのオプション** – フィールドで利用可能なラジオボタンを定義します。オプションの値とラベルを別々に設定したり、デフォルトで選択状態にしておくものを選択できます。

## テンプレート記法

### ラジオボタンフィールドによるエレメントの照会

ラジオボタンフィールドを持つ[エレメントを照会](dev/element-queries/README.md)する場合、フィールドのハンドルにちなんで名付けられたクエリパラメータを使用して、ラジオボタンフィールドのデータに基づいた結果をフィルタできます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエレメント
| - | -
| `'foo'` | `foo` オプションが選択されている。
| `'not foo'` | `foo` オプションが選択さていない。
| `['foo', 'bar']` | `foo` または `bar` オプションのいずれかが選択されている。
| `['not', 'foo', 'bar']` | `foo` または `bar` オプションのいずれかが選択されていない。

```twig
{# Fetch entries with the 'foo' option selected #}
{% set entries = craft.entries()
    .<FieldHandle>('foo')
    .all() %}
```

### ラジオボタンフィールドデータの操作

テンプレート内でラジオボタンフィールドのエレメントを取得する場合、ラジオボタンフィールドのハンドルを利用して、そのデータにアクセスできます。

```twig
{% set value = entry.<FieldHandle> %}
```

それは、フィールドデータを含む <api:craft\fields\data\SingleOptionFieldData> オブジェクトを提供します。

選択されたオプションを表示するには、それを文字列として出力するか、[value](api:craft\fields\data\SingleOptionFieldData::$value) プロパティを出力してください。

```twig
{{ entry.<FieldHandle> }} or {{ entry.<FieldHandle>.value }}
```

任意のオプションが選択されているかを確認するには、[value](api:craft\fields\data\SingleOptionFieldData::$value) プロパティを使用してください。

```twig
{% if entry.<FieldHandle>.value %}
```

選択されたオプションのラベルを表示するには、[label](api:craft\fields\data\SingleOptionFieldData::$label) プロパティを出力してください。

```twig
{{ entry.<FieldHandle>.label }}
```

利用可能なオプションすべてをループするには、[options](api:craft\fields\data\SingleOptionFieldData::getOptions()) プロパティを反復してください。

```twig
{% for option in entry.<FieldHandle>.options %}
    Label:    {{ option.label }}
    Value:    {{ option }} or {{ option.value }}
    Selected: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

### 投稿フォームでラジオボタンフィールドを保存

ラジオボタンフィールドを含める必要がある[投稿フォーム](dev/examples/entry-form.md)がある場合、出発点としてこのテンプレートを使用してください。

```twig
{% set field = craft.app.fields.getFieldByHandle('<FieldHandle>') %}

<ul>
    {% for option in field.options %}

        {% set selected = entry is defined
            ? entry.<FieldHandle>.value == option.value
            : option.default %}

        <li><label>
            <input type="radio"
                name="fields[<FieldHandle>]"
                value="{{ option.value }}"
                {% if selected %}checked{% endif %}>
            {{ option.label }}
        </label></li>
    {% endfor %}
</ul>
```

