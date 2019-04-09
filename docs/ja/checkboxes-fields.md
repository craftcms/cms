# チェックボックスフィールド

チェックボックスフィールドでは、チェックボックスのグループが提供されます。

## 設定

チェックボックスの設定は、次の通りです。

* **チェックボックスのオプション** – フィールドで利用可能なチェックボックスを定義します。オプションの値とラベルを別々に設定したり、デフォルトでチェックしておくものを選択できます。

## テンプレートの実例

#### 選択されたチェックボックスをループ

```twig
{% for option in entry.checkboxFieldHandle %}
    Label: {{ option.label }}
    Value: {{ option }} or {{ option.value }}
{% endfor %}
```

#### 利用可能なすべてのチェックボックスをループ

```twig
{% for option in entry.checkboxFieldHandle.options %}
    Label:   {{ option.label }}
    Value:   {{ option }} or {{ option.value }}
    Checked: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

#### いずれかのチェックボックスが選択されているかを確認

```twig
{% if entry.checkboxFieldHandle|length %}
```

#### 特定のチェックボックスが選択されているかを確認

```twig
{% if entry.checkboxFieldHandle.contains('optionValue') %}
```

#### 投稿フォーム

```twig
{% set field = craft.app.fields.getFieldByHandle('checkboxFieldhandle') %}

{# Include a hidden input first so Craft knows to update the
   existing value, if no checkboxes are checked. #}
<input type="hidden" name="fields[checkboxFieldhandle]" value="">

<ul>
    {% for option in field.options %}

        {% set checked = entry is defined
            ? entry.checkboxFieldhandle.contains(option.value)
            : option.default %}

        <li><label>
            <input type="checkbox"
                name="fields[checkboxFieldHandle][]"
                value="{{ option.value }}"
                {% if checked %}checked{% endif %}>
            {{ option.label }}
        </label></li>
    {% endfor %}
</ul>
```

