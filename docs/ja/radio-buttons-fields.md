# ラジオボタンフィールド

ラジオボタンフィールドでは、ラジオボタンのグループが提供されます。

## 設定

ラジオボタンフィールドの設定は、次の通りです。

* **ラジオボタンのオプション** – フィールドで利用可能なラジオボタンを定義します。オプションの値とラベルを別々に設定したり、デフォルトで選択状態にしておくものを選択できます。

## テンプレートの実例

#### 選択されたラジオボタンの値を出力

```twig
{{ entry.radioFieldHandle }} or {{ entry.radioFieldHandle.value }}
```

#### 選択されたラジオボタンのラベルを出力

```twig
{{ entry.radioFieldHandle.label }}
```

#### 利用可能なすべてのラジオボタンをループ

```twig
{% for option in entry.radioFieldHandle.options %}
    Label:    {{ option.label }}
    Value:    {{ option }} or {{ option.value }}
    Selected: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

#### 投稿フォーム

```twig
{% set field = craft.app.fields.getFieldByHandle('radioFieldhandle') %}

<ul>
    {% for option in field.options %}

        {% set selected = entry is defined
            ? entry.radioFieldHandle.value == option.value
            : option.default %}

        <li><label>
            <input type="radio"
                name="fields[radioFieldHandle]"
                value="{{ option.value }}"
                {% if selected %}checked{% endif %}>
            {{ option.label }}
        </label></li>
    {% endfor %}
</ul>
```

