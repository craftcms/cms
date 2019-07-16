# マルチセレクトボックスフィールド

マルチセレクトボックスフィールドは、複数選択形式の入力を提供します。

## 設定

マルチセレクトボックスフィールドの設定は、次の通りです。

* **マルチセレクトボックスのオプション** – フィールドで利用可能なオプションを定義します。オプションの値とラベルを別々に設定したり、デフォルトで選択状態にしておくものを選択できます。

## テンプレート記法

### マルチセレクトボックスフィールドによるエレメントの照会

マルチセレクトボックスフィールドを持つ[エレメントを照会](dev/element-queries/README.md)する場合、フィールドのハンドルにちなんで名付けられたクエリパラメータを使用して、マルチセレクトボックスフィールドのデータに基づいた結果をフィルタできます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエレメント
| - | -
| `'*"foo"*'` | `foo` オプションが選択されている。
| `'not *"foo"*'` | `foo` オプションが選択されていない。

```twig
{# Fetch entries with the 'foo' option selected #}
{% set entries = craft.entries()
    .<FieldHandle>('*"foo"*')
    .all() %}
```

### マルチセレクトボックスフィールドデータの操作

テンプレート内でマルチセレクトボックスフィールドのエレメントを取得する場合、マルチセレクトボックスフィールドのハンドルを利用して、そのデータにアクセスできます。

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
    Label:    {{ option.label }}
    Value:    {{ option }} or {{ option.value }}
    Selected: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

いずれかのオプションが選択されているかを確認するには、[length](https://twig.symfony.com/doc/2.x/filters/length.html) フィルタを使用してください。

```twig
{% if entry.<FieldHandle>|length %}
```

特定のオプションが選択されているかを確認するには、[contains()](api:craft\fields\data\MultiOptionsFieldData::contains()) を使用してください。

```twig
{% if entry.<FieldHandle>.contains('foo') %}
```

### 投稿フォームでマルチセレクトボックスフィールドを保存

マルチセレクトボックスフィールドを含める必要がある[投稿フォーム](dev/examples/entry-form.md)がある場合、出発点としてこのテンプレートを使用してください。

```twig
{% set field = craft.app.fields.getFieldByHandle('<FieldHandle>') %}

{# Include a hidden input first so Craft knows to update the
   existing value, if no options are selected. #}
<input type="hidden" name="fields[<FieldHandle>]" value="">

<select multiple name="fields[<FieldHandle>][]">
    {% for option in field.options %}

        {% set selected = entry is defined
            ? entry.<FieldHandle>.contains(option.value)
            : option.default %}

        <option value="{{ option.value }}"
                {% if selected %}selected{% endif %}>
            {{ option.label }}
        </option>
    {% endfor %}
</select>
```

