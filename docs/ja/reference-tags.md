# リファレンスタグ

リファレンスタグを利用して、サイト内の様々なエレメントへの参照を作成できます。テーブルフィールド内のテキストセルを含む、あらゆるテキストフィールドで使用できます。

リファレンスタグの構文は、次のようになります。

```twig
{<Type>:<Identifier>:<Property>}
```

ご覧の通り、それらは3つのセグメントで構成されています。

1. `<Type>` – 参照を作成するエレメントのタイプ。これは完全修飾のエレメントクラス名（例：`craft\elements\Entry`）、または、エレメントタイプの「リファレンスハンドル」です。

   コアのエレメントタイプは、次のリファレンスハンドルを持っています。
   - `entry`
   - `asset`
   - `tag`
   - `user`
   - `globalset`

2. `<Identifier>` – エレメントの ID、または、エレメントタイプによってサポートされているカスタム識別子。

   エントリは次のカスタム識別子をサポートしています。
   - `entry-slug`
   - `sectionHandle/entry-slug`

3. `<Property>` _（オプション）_ – リファレンスタグが返すべきエレメントのプロパティ。省略した場合、エレメントの URL が返されます。

   利用可能なプロパティのリストは、エレメントタイプのクラスリファレンスを参照してください。
   - [api:craft\elements\Entry](craft\elements\Entry#public-properties)
   - [api:craft\elements\Asset](craft\elements\Asset#public-properties)
   - [api:craft\elements\Tag](craft\elements\Tag#public-properties)
   - [api:craft\elements\User](craft\elements\User#public-properties)
   - [api:craft\elements\GlobalSet](craft\elements\GlobalSet#public-properties)

   カスタムフィールドのハンドルもサポートされています。フィールドタイプは文字列として表すことができる値を持っています。

### 実例

有効なリファレンスタグは、次の通りです。

- `{asset:123:filename}` – ID が `123` のアセットのファイル名を（<api:craft\elements\Asset::getFilename()> 経由で）返します。
- `{entry:about-us:intro}` – スラグが `about-us` のエントリのカスタムフィールド `intro` の値を返します。
- `{entry:blog/whats-on-tap}` – スラグが `whats-on-tap` の `blog` セクションのエントリの URL を返します。
- `{craft\commerce\Variant:123:price}` – ID が `123` の Commerce Variant オブジェクトの price を返します。

## リファレンスタグの解析

[parseRefs](dev/filters.md#parserefs) フィルタを利用して、テンプレート内のリファレンスタグの文字列を解析できます。

```twig
{{ entry.body|parseRefs|raw }}
```

