# ガベージコレクション

Craft は古いデータを削除するためにいくつかのガベージコレクションルーチンを実行することがあります。

- （コンフィグ設定の <config:purgePendingUsersDuration> ごとに）期限切れの保留中のユーザーアカウントを削除します。
- （コンフィグ設定の <config:softDeleteDuration> ごとに）期限切れのソフトデリート行を完全に削除します。
- 古いユーザーセッションデータを削除します。

デフォルトでは、すべてのウェブリクエストがガベージコレクションを発動する 100,000 分の 1 のチャンスを持っています。それは <api:craft\services\Gc::$probability> を上書きすることによって `config/app.php` から設定できます。

```php
return [
    'components' => [
        'gc' => [
            'probability' => 0,     // no chance
            'probability' => 1,     // 1 in 1,000,000
            'probability' => 10,    // 1 in 100,000 (default)
            'probability' => 100,   // 1 in 10,000
            'probability' => 1000,  // 1 in 1,000
            'probability' => 10000, // 1 in 100
        ],
    ],
];
```

## 強制的なガベージコレクション

ターミナルコマンドを使用して、任意のタイミングでガベージコレクションを強制的に実行できます。

ターミナル上で Craft プロジェクトに移動し、次のコマンドを実行してください。

```bash
./craft gc
```

シェルが対話型である場合、Craft がすべての破棄済み項目を削除すべきかどうか尋ねられます。プロンプトで `yes` を入力した場合、まだ [softDeleteDuration](config:softDeleteDuration) に満たないものでも、ソフトデリートされたすべてのデータベース行が即座に完全に削除されます。

`delete-all-trashed` オプションを使用して、すべてのソフトデリート行を強制削除することもできます。

```bash
./craft gc --delete-all-trashed=1
```

