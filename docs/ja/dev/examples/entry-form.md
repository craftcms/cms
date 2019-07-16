# エントリの投稿フォーム

次のコードを利用して、サイトのフロントエンド向けに新しいエントリの投稿フォームを作成できます。

```twig
{% macro errorList(errors) %}
    {% if errors %}
        <ul class="errors">
            {% for error in errors %}
                <li>{{ error }}</li>
            {% endfor %}
        </ul>
    {% endif %}
{% endmacro %}

{% from _self import errorList %}

<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="entries/save-entry">
    {{ redirectInput('viewentry/{slug}') }}
    <input type="hidden" name="sectionId" value="2">
    <input type="hidden" name="enabled" value="1">

    <label for="title">Title</label>
    <input id="title" type="text" name="title"
        {%- if entry is defined %} value="{{ entry.title }}"{% endif -%}>

    {% if entry is defined %}
        {{ errorList(entry.getErrors('title')) }}
    {% endif %}

    <label for="body">Body</label>
    <textarea id="body" name="fields[body]">
        {%- if entry is defined %}{{ entry.body }}{% endif -%}
    </textarea>

    {% if entry is defined %}
        {{ errorList(entry.getErrors('body')) }}
    {% endif %}

    <input type="submit" value="Publish">
</form>
```

「sectionId」は必ずエントリを保存したいセクションの実際の ID に調整してください。

エントリを送信するユーザーは、そのセクションのエントリを作成するための権限を持っている必要があります。

### エントリの編集フォーム

不可視項目の「entryId」を追加すると、既存のエントリを保存するためのフォームに変更できます。

```twig
<input type="hidden" name="entryId" value="{{ entry.id }}">
```

