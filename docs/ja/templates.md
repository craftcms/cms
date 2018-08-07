# テンプレート

Craft では、テンプレートを利用してサイトの HTML 出力を定義します。

テンプレートは craft/templates フォルダ内に存在するファイルです。テンプレートの構造は、完全にあなた次第です。 – テンプレートは、フォルダのルート、サブディレクトリ内、またはサブディレクトリのサブディレクトリ内（さらに続く）などに置くことができます。サイトに必要なものは、何でも機能します。

Craft はテンプレートを解析するために [Twig](https://twig.sensiolabs.org/) を利用します。Twig は、エレガントで、パワフルで、 かつ、とても高速です。Twig をはじめて使う方は、その構文に慣れ親しむために[ドキュメント](twig-primer.md)へ目を通してください。

テンプレート内で PHP コードは使用できません。Craft や Twig でそのまますぐに使うことのできない何かが必要になった場合、新しい[Twig 拡張](https://twig.symfony.com/doc/2.x/advanced.html#creating-an-extension)を提供する[プラグイン](plugin-intro.md)を作成することができます。

## テンプレートのパス

テンプレートのパスを入力する必要があるときが、いくつかあります。

* [エントリ](sections-and-entries.md)や[カテゴリ](categories.md)で読み込む、テンプレートの URL を選択するとき
* テンプレートを[ルート](routing.md#dynamic-routes)に割り当てるとき
* Within [include](https://twig.sensiolabs.org/doc/tags/include.html), [extends](https://twig.sensiolabs.org/doc/tags/extends.html), and [embed](https://twig.sensiolabs.org/doc/tags/embed.html) template tags

Craft には、これらのケース（ `craft/templates` ディレクトリからそのテンプレートファイルまでの相対的な Unix スタイルのファイルシステムのパス）それぞれに当てはまる、標準的なテンプレートパスのフォーマットがあります。

例えば、`craft/templates/recipes/entry.html` にテンプレートがある場合、次のテンプレートパスで指し示すことができます。

* `recipes/entry`
* `recipes/entry.html`

### インデックステンプレート

テンプレートの名前が `index.html` の場合、テンプレートパスで明示的に記述する必要はありません。

例えば、`craft/templates/recipes/ingredients/index.html` にテンプレートがある場合、次のテンプレートパスで指し示すことができます。

* `recipes/ingredients`
* `recipes/ingredients/index`
* `recipes/ingredients/index.html`

もし、`craft/templates/recipes/ingredients.html` *と* `craft/templates/recipes/ingredients/index.html` の両方にテンプレートがあれば、`recipes/ingredients` は `ingredients.html` と一致します。

### 不可視テンプレート

Craft は、`recipes/_entry.html` のように、名前の接頭辞にアンダースコアが付いたテンプレートを直接アクセスできない不可視テンプレートとして扱います。

`recipes/entry` にあるテンプレートを利用した、`http://mysite.com/recipes/gin-tonic` でアクセスできるレシピのエントリがある場合、誰でも `http://mysite.com/recipes/entry` で直接テンプレートにアクセスできてしまいます。

この例では、エントリ URL の一部としてのみ利用されるため、テンプレートへダイレクトにアクセスする理由がありません。そこで、Craft が不可視ファイルだとみなすようファイル名を `_entry.html` に変更し、セクションの設定をアップデートします。

これで `http://mysite.com/recipes/entry` にアクセスすると、Craft がテンプレートのレンダリングを試みる代わりに、404 エラーを返します。

## テンプレートのローカライゼーション

Craft でマルチサイトを運用している場合、特定のサイトだけで利用可能なテンプレートを含むサイト固有のサブディレクトリを `craft/templates/` 内に作成できます。

例えば、ドイツのカスタマーを歓迎するための特別なテンプレートを作成したいものの、英語版サイトで必要ない場合、`craft/templates/de/welcome.html` に保存します。そのテンプレートは http://example.de/welcome からアクセスできるでしょう。

Craft は、通常のテンプレートを探す_前に_ローカライズ用のテンプレートを探します。それによって、ローカライズされていないテンプレートを上書きすることができます。詳細については、[ローカライゼーションガイド](localization.md)を参照してください。

