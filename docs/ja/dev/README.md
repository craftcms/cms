# フロントエンド開発

Craft では、テンプレートを利用してサイトの HTML 出力を定義します。

テンプレートは `templates/` フォルダ内に存在するファイルです。テンプレートの構造は、完全にあなた次第です。 – テンプレートは、フォルダのルート、サブディレクトリ内、またはサブディレクトリのサブディレクトリ内（さらに続く）などに置くことができます。サイトに必要なものは、何でも機能します。

Craft はテンプレートを解析するために [Twig](https://twig.symfony.com/) を利用します。Twig は、エレガントで、パワフルで、 かつ、とても高速です。Twig をはじめて使う方は、その構文に慣れ親しむために [Twig 入門書](twig-primer.md)に目を通してください。

::: tip
PHP コードはテンプレート内で使用できませんが、Craft はニーズに合わせて様々な方法で [Twig を拡張する](../extend/extending-twig.md)手段を提供しています。
:::

## テンプレートのパス

テンプレートのパスを入力する必要があるときが、いくつかあります。

* [エントリ](../sections-and-entries.md)や[カテゴリ](../categories.md)で読み込む、テンプレートの URL を選択するとき
* テンプレートを[ルート](../routing.md#dynamic-routes)に割り当てるとき
* [include](https://twig.symfony.com/doc/tags/include.html)、[extends](https://twig.symfony.com/doc/tags/extends.html)、および、[embed](https://twig.symfony.com/doc/tags/embed.html) テンプレートタグ内

Craft には、テンプレートへの Unix スタイルのファイルシステムのパスや `templates` フォルダからの相対パスという、それぞれのケースで適用される標準的なテンプレートパスのフォーマットがあります。

例えば、`templates/recipes/entry.twig` にテンプレートがある場合、次のテンプレートパスで指し示すことができます。

* `recipes/entry`
* `recipes/entry.twig`

### インデックステンプレート

テンプレートの名前が `index.twig` の場合、テンプレートパスで明示的に記述する必要はありません。

例えば、`templates/recipes/ingredients/index.twig` にテンプレートがある場合、次のテンプレートパスで指し示すことができます。

* `recipes/ingredients`
* `recipes/ingredients/index`
* `recipes/ingredients/index.twig`

`templates/recipes/ingredients.twig` *と* `templates/recipes/ingredients/index.twig` の両方にテンプレートがある場合、`recipes/ingredients` は `ingredients.twig` にマッチします。

### 不可視テンプレート

Craft は、`recipes/_entry.twig` のように、名前の接頭辞にアンダースコアが付いたテンプレートを直接アクセスできない不可視テンプレートとして扱います。

`recipes/entry` にあるテンプレートを利用した、`http://mysite.com/recipes/gin-tonic` でアクセスできるレシピのエントリがある場合、誰でも `http://mysite.com/recipes/entry` で直接テンプレートにアクセスできてしまいます。

この例では、エントリ URL の一部としてのみ利用されるため、テンプレートへダイレクトにアクセスする理由がありません。そこで、Craft が不可視ファイルだとみなすようファイル名を `_entry.twig` に変更し、セクションの設定をアップデートします。

これで `http://mysite.com/recipes/entry` にアクセスすると、Craft がテンプレートのレンダリングを試みる代わりに、404 エラーを返します。

## テンプレートのローカライゼーション

Craft でマルチサイトを運用している場合、特定のサイトだけで利用可能なテンプレートを含むサイト固有のサブフォルダを `templates/` フォルダ内に作成できます。

例えば、ドイツのカスタマーを歓迎するための特別なテンプレートを作成したいものの、英語版サイトで必要ない場合、`templates/de/welcome.twig` に保存します。そのテンプレートは `http://example.de/welcome` からアクセスできるでしょう。

Craft は、通常のテンプレートを探す_前に_ローカライズ用のテンプレートを探します。それによって、ローカライズされていないテンプレートを上書きすることができます。詳細については、[ローカライゼーションガイド](../localization.md)を参照してください。

