# プラグインのエディション

プラグインストアは、まもなくマルチエディションプラグインの**限定的な**サポートを追加するでしょう。それは Craft の2つのエディション（Solo、および、Pro）と同様に機能します。

- マルチエディションをサポートするプラグインは、依然として単一のコンポーザーパッケージで構成されます。
- プラグインのアクティブなエディションは、[プロジェクトコンフィグ](../project-config.md)に記録されます。
- プラグインはアクティブなエディションをチェックすることにより、機能トグルを実装できます。

::: warning
すべてのプラグインがエディションをサポートできるわけではありません。あなたのプラグインが許可されるかどうかを確認するため、エディションサポートを追加しはじめる前に、Pixel & Tonic に[問い合わせ](https://craftcms.com/contact)してください。
:::

## エディションの定義

プラグインにエディションサポートを追加するには、<api:craft\base\Plugin::editions()> を上書きして、有効なエディションを（昇順で）定義することからはじめます。

```php
class Plugin extends \craft\base\Plugin;
{
    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';

    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    // ...
}
```

## 機能トグルの追加

機能トグルはプラグインの [is()](api:craft\base\Plugin::is()) メソッドで呼び出すことができます。

::: code

```php
if (Plugin::getInstance()->is(Plugin::EDITION_PRO) {
    // Pro edition-only code goes here...
}
```

```twig
{% if plugin('plugin-handle').is('pro') %}
    {# Pro edition-only code goes here... #}
{% endif %}
```

:::

`is()` は2つの引数 `$edition`、および、`$operator` を受け入れます。

`$edition` はあなたが関心を寄せているエディションの名前です。

`$operator` はそのエディションとインストールされているエディションをどのように比較するかの方法です。デフォルトでは `=` にセットされていて、バージョンが等しいかどうかをテストします。

次の演算子もサポートされています。

演算子 | アクティブなエディションが与えられたエディションより◯◯かテスト
- | -
`<` または `lt` | 未満
`<=` または `le` | 以下
`>` または `gt` | より大きい
`>=` または `ge` | 以上
`==` または `eq` | 等しい（デフォルトと同じ振る舞い）
`!=`、`<>` または `ne` | 等しくない

::: tip
エディションの変更は常に可逆的な操作であり、エディション変更の結果としてプラグインデータが変わるべきではありません。エディションはいつでも交互に変更することができ、プラグインがそれによる問題を持つべきではありません。
:::

## テスト

`config/project.yaml` の `plugins.<plugin-handle>.edition` プロパティを変更することにより、アクティブなエディションを切り替えることができます。

::: tip
`config/project.yaml` ファイルを持たない場合、コンフィグ設定の <config:useProjectConfigFile> を有効にする必要があります。
:::

（プラグインの `editions()` メソッドで返される）有効なエディションハンドルの値に変更すると、Craft は `project.yaml` の変更を読み込まれたプロジェクトコンフィグと同期するよう促します。それが完了すると、プラグインのアクティブなエディションが新しいエディションにセットされ、機能トグルはそれに応じて動作しはじめます。

