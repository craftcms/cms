# チェックボックスフィールド

チェックボックスフィールドでは、チェックボックスのグループが提供されます。

## 設定

チェックボックスの設定は、次の通りです。

* **チェックボックスのオプション** – フィールドで利用可能なチェックボックスを定義します。オプションの値とラベルを別々に設定したり、デフォルトでチェックしておくものを選択できます。

## フィールド

チェックボックスフィールドでは、フィールド設定で定義された各チェックボックスのオプションが表示されます。

## テンプレート記法

チェックボックスを1つしかもたつ、それが選択されているかを知りたい場合、`length` フィルタを利用して確認できます。

```twig
{% if entry.checkboxFieldHandle|length %}
```

選択されたオプションを次のようにループすることができます。

```twig
<ul>
    {% for option in entry.checkboxFieldHandle %}
        <li>{{ option }}</li>
    {% endfor %}
</ul>
```

または、選択されたものだけでなく、利用可能なすべてのオプションをループすることもできます。

```twig
<ul>
    {% for option in entry.checkboxFieldHandle.options %}
        <li>{{ option }}</li>
    {% endfor %}
</ul>
```

いずれの場合も、オプションのラベルを出力するには `{{ option.label }}` と記述します。オプションが選択されているかどうかは `option.selected` で知ることができます。

オプションのループのスコープ外でも、次のように特定のオプションが選択されているかを知ることができます。

```twig
{% if entry.checkboxFieldHandle.contains('tequila') %}
    <p>Really?</p>
{% endif %}
```

フロントエンドの[エントリフォーム](dev/examples/entry-form.md)にチェックボックスフィールドを含める場合、チェックボックスの前に不可視項目を含め、チェックボックスがチェックされなかった場合でも、空の値が送信されるようにしてください。

```twig
<input type="hidden" name="fields[checkboxFieldhandle]" value="">

<ul>
    <li><input type="checkbox" name="fields[checkboxFieldHandle][]" value="foo">{{ checkboxOption.label }}</li>
    <li><input type="checkbox" name="fields[checkboxFieldHandle][]" value="bar">{{ checkboxOption.label }}</li>
</ul>
```

