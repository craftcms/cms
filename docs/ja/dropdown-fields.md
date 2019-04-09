# セレクトボックスフィールド

セレクトボックスフィールドは、ドロップダウン形式の入力を提供します。

## 設定

セレクトボックスフィールドの設定は、次の通りです。

* **セレクトボックスのオプション** – フィールドで利用可能なオプションを定義します。オプションの値とラベルを別々に設定したり、デフォルトで選択状態にしておくものを選択できます。

## テンプレートの実例

#### 選択されたオプションの値を出力

```twig
{{ entry.dropdownFieldHandle }} or {{ entry.dropdownFieldHandle.value }}
```

#### 選択されたオプションのラベルを出力

```twig
{{ entry.dropdownFieldHandle.label }}
```

#### 利用可能なすべてのオブションをループ

```twig
{% for option in entry.dropdownFieldHandle.options %}
    Label:    {{ option.label }}
    Value:    {{ option }} or {{ option.value }}
    Selected: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

#### 投稿フォーム

```twig
{% set field = craft.app.fields.getFieldByHandle('dropdownFieldHandle') %}

<select name="fields[dropdownFieldHandle]">
    {% for option in field.options %}

        {% set selected = entry is defined
            ? entry.dropdownFieldHandle.value == option.value
            : option.default %}

        <option value="{{ option.value }}"
                {% if selected %}selected{% endif %}>
            {{ option.label }}
        </option>
    {% endfor %}
</select>
```

