# チェックボックスフィールド

チェックボックスフィールドでは、チェックボックスのグループが提供されます。

## 設定

チェックボックスの設定は、次の通りです。

* **チェックボックスのオプション** – フィールドで利用可能なチェックボックスを定義します。オプションの値とラベルを別々に設定したり、デフォルトでチェックしておくものを選択できます。

## テンプレート記法

### チェックボックスフィールドによるエレメントの照会

チェックボックスフィールドを持つ[エレメントを照会](dev/element-queries/README.md)する場合、フィールドのハンドルにちなんで名付けられたクエリパラメータを使用して、チェックボックスフィールドのデータに基づいた結果をフィルタできます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエレメント
| - | -
| `'*"foo"*'` | `foo` オプションが選択されている。
| `'not *"foo"*'` | `foo` オプションが選択されていない。

```twig
{# Fetch entries with the 'foo' option checked #}
{% set entries = craft.entries()
    .<FieldHandle>('*"foo"*')
    .all() %}
```

### チェックボックスフィールドデータの操作

テンプレート内でチェックボックスフィールドのエレメントを取得する場合、チェックボックスフィールドのハンドルを利用して、そのデータにアクセスできます。

```twig
{% set value = entry.<FieldHandle> %}
```

それは、フィールドデータを含む <api:craft\fields\data\MultiOptionsFieldData> オブジェクトを提供します。

選択されたオプションすべてをループするには、フィールド値を反復してください。

```twig
{% for option in entry.<FieldHandle> %}
    Label: {{ option.label }}
    Value: {{ option }} or {{ option.value }}
{% endfor %}
```

利用可能なオプションすべてをループするには、[options](api:craft\fields\data\MultiOptionsFieldData::getOptions()) プロパティを反復してください。

```twig
{% for option in entry.<FieldHandle>.options %}
    Label:   {{ option.label }}
    Value:   {{ option }} or {{ option.value }}
    Checked: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

いずれかのチェックボックスが選択されているかを確認するには、[length](https://twig.symfony.com/doc/2.x/filters/length.html) フィルタを使用してください。

```twig
{% if entry.<FieldHandle>|length %}
```

特定のオプションが選択されているかを確認するには、[contains()](api:craft\fields\data\MultiOptionsFieldData::contains()) を使用してください。

```twig
{% if entry.<FieldHandle>.contains('foo') %}
```

### 投稿フォームでチェックボックスフィールドを保存

チェックボックスフィールドを含める必要がある[投稿フォーム](dev/examples/entry-form.md)がある場合、出発点としてこのテンプレートを使用してください。

```twig
{% set field = craft.app.fields.getFieldByHandle('<FieldHandle>') %}

{# Include a hidden input first so Craft knows to update the
   existing value, if no checkboxes are checked. #}
<input type="hidden" name="fields[<FieldHandle>]" value="">

<ul>
    {% for option in field.options %}

        {% set checked = entry is defined
            ? entry.<FieldHandle>.contains(option.value)
            : option.default %}

        <li><label>
            <input type="checkbox"
                name="fields[<FieldHandle>][]"
                value="{{ option.value }}"
                {% if checked %}checked{% endif %}>
            {{ option.label }}
        </label></li>
    {% endfor %}
</ul>
```

