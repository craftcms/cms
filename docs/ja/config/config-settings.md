# 一般設定

Craft は、その振る舞いを制御するためのいくつかのコンフィギュレーション設定をサポートしています。

新しいコンフィグ設定をセットするには `config/general.php` を開き、設定を適用したい環境に応じて環境設定の配列の1つを定義してください。

例えば、staging または production 環境ではなく、dev 環境のみ Craft のアップデートを許可したい場合、次のようにします。

```php{4,10}
return [
    // Global settings
    '*' => [
        'allowUpdates' => false,
        // ...
    ],

    // Dev environment settings
    'dev' => [
        'allowUpdates' => true,
        // ...
    ],

    // Staging environment settings
    'staging' => [
        // ...
    ],

    // Production environment settings
    'production' => [
        // ...
    ],
];
```

Craft がサポートするコンフィグ設定の完全なリストは、次の通りです。

<!-- BEGIN SETTINGS -->

### `actionTrigger`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'actions'`

定義元

:   [GeneralConfig::$actionTrigger](api:craft\config\GeneralConfig::$actionTrigger)

現在のリクエストを最初にコントローラーアクションにルーティングするかどうかを決定するとき、Craft が探す URI セグメント。

### `activateAccountSuccessPath`

許可される型

:   `mixed`

デフォルト値

:   `''`

定義元

:   [GeneralConfig::$activateAccountSuccessPath](api:craft\config\GeneralConfig::$activateAccountSuccessPath)

コントロールパネルにアクセスできないユーザーが、アカウントをアクティベートしたときにリダイレクトする URI。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

### `addTrailingSlashesToUrls`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$addTrailingSlashesToUrls](api:craft\config\GeneralConfig::$addTrailingSlashesToUrls)

自動生成された URL にスラッシュをつけるかどうか。

### `aliases`

許可される型

:   [array](http://php.net/language.types.array)

デフォルト値

:   `[]`

定義元

:   [GeneralConfig::$aliases](api:craft\config\GeneralConfig::$aliases)

リクエストごとに定義される、カスタムの Yii [aliases](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases)。

### `allowAdminChanges`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$allowAdminChanges](api:craft\config\GeneralConfig::$allowAdminChanges)

管理者によるシステムへの管理上の変更を許可するかどうか。

これを無効にすると、設定およびプラグインストアのセクションは非表示になり、Craft 本体のエディションとプラグインのバージョンがロックされ、プロジェクトコンフィグは読み取り専用になります。

そのため、[useProjectConfigFile](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#useprojectconfigfile) が有効になっている production 環境のみ、これを無効にするべきです。そして、デプロイメントワークフローでデプロイ時に自動的に `composer install` を実行するようにします。

::: warning
**すべての**環境が Craft 3.1.0 以降にアップデートされるまで、この設定を無効にしないでください。
:::

### `allowSimilarTags`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$allowSimilarTags](api:craft\config\GeneralConfig::$allowSimilarTags)

ユーザーによる類似した名前のタグの作成を許可するかどうか。

### `allowUpdates`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$allowUpdates](api:craft\config\GeneralConfig::$allowUpdates)

コントロールパネルでのシステムとプラグインのアップデート、および、プラグインストアからのプラグインのインストールを Craft が許可するかどうか。

[allowAdminChanges](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#allowadminchanges) が無効になっている場合、この設定は自動的に無効になります。

### `allowUppercaseInSlug`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$allowUppercaseInSlug](api:craft\config\GeneralConfig::$allowUppercaseInSlug)

スラグに大文字を使うことを許可するかどうか。

### `allowedFileExtensions`

許可される型

:   [string](http://php.net/language.types.string)[]

デフォルト値

:   `['7z', 'aiff', 'asf', 'avi', 'bmp', 'csv', 'doc', 'docx', 'fla', 'flv', 'gif', 'gz', 'gzip', 'htm', 'html', 'jp2', 'jpeg', 'jpg', 'jpx', 'js', 'json', 'm2t', 'mid', 'mov', 'mp3', 'mp4', 'm4a', 'm4v', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 'ogg', 'ogv', 'pdf', 'png', 'potx', 'pps', 'ppsm', 'ppsx', 'ppt', 'pptm', 'pptx', 'ppz', 'pxd', 'qt', 'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 'sdc', 'sitd', 'svg', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif', 'tiff', 'txt', 'vob', 'vsd', 'wav', 'webm', 'webp', 'wma', 'wmv', 'xls', 'xlsx', 'zip']`

定義元

:   [GeneralConfig::$allowedFileExtensions](api:craft\config\GeneralConfig::$allowedFileExtensions)

ユーザーがファイルをアップロードする際に、Craft が許可するファイル拡張子。

### `autoLoginAfterAccountActivation`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$autoLoginAfterAccountActivation](api:craft\config\GeneralConfig::$autoLoginAfterAccountActivation)

ユーザーがアカウントを有効化、または、パスワードをリセットした後で、自動的にログインさせるかどうか。

### `backupCommand`

許可される型

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$backupCommand](api:craft\config\GeneralConfig::$backupCommand)

データベースのバックアップを作成するために Craft が実行するシェルコマンド。

ウェブサーバーを実行しているユーザーの `$PATH` 変数にライブラリが含まれている場合、デフォルトで Craft は `mysqldump` または `pg_dump` を実行します。

ランタイムで Craft がスワップアウトするために利用できるいくつかのトークンがあります。

- `{path}` - バックアップファイルのターゲットパス
- `{port}` -現在のデータベースポート
- `{server}` - 現在のデータベースホスト名
- `{user}` -データベースのに接続するユーザー
- `{database}` - 現在のデータベース名
- `{schema}` - （もしある場合）現在のデータベーススキーマ

データベースのバックアップを完全に無効化するために、`false` をセットすることもできます。

### `backupOnUpdate`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$backupOnUpdate](api:craft\config\GeneralConfig::$backupOnUpdate)

新しいシステムアップデートを適用する前に、Craft がデータベースのバックアップを作成するかどうか。

### `baseCpUrl`

許可される型

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$baseCpUrl](api:craft\config\GeneralConfig::$baseCpUrl)

コントロールパネルの URL を生成する際に、Craft が使用するベース URL。

空白の場合、自動的に決定されます。

::: tip
ベース CP URL に [CP トリガーワード](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#cptrigger)（例：`/admin`）を **含めない** でください。
:::

### `blowfishHashCost`

許可される型

:   [integer](http://php.net/language.types.integer)

デフォルト値

:   `13`

定義元

:   [GeneralConfig::$blowfishHashCost](api:craft\config\GeneralConfig::$blowfishHashCost)

コスト値が高いと、パスワードハッシュの生成とそれに対する検証に時間がかかります。そのため、より高いコストはブルートフォース攻撃を遅くさせます。

ブルートフォース攻撃に対するベストな保護のために、production サーバーで許容される最高の値をセットしてください。

この値が増加するごとに、ハッシュを計算するためにかかる時間は倍になります。
例えば、値が14のときハッシュの計算に1秒かかる場合、計算時間は「2^(値 - 14) 」秒で変化します。

### `cacheDuration`

許可される型

:   `mixed`

デフォルト値

:   `86400`

定義元

:   [GeneralConfig::$cacheDuration](api:craft\config\GeneralConfig::$cacheDuration)

Craft がデータ、RSS フィード、および、テンプレートキャッシュを保管する時間のデフォルトの長さ。

`0` をセットすると、データと RSS フィードのキャッシュは無期限に保管されます。テンプレートキャッシュは1年間保管されます。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `cacheElementQueries`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$cacheElementQueries](api:craft\config\GeneralConfig::$cacheElementQueries)

Craft が `{% cache %}` タグ内にエレメントクエリをキャッシュするかどうか。

### `convertFilenamesToAscii`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$convertFilenamesToAscii](api:craft\config\GeneralConfig::$convertFilenamesToAscii)

アップロードされたファイル名に含まれる ASCII 以外の文字を ASCII に変換するかどうか（例： `ñ` → `n`）。

### `cooldownDuration`

許可される型

:   `mixed`

デフォルト値

:   `300`

定義元

:   [GeneralConfig::$cooldownDuration](api:craft\config\GeneralConfig::$cooldownDuration)

あまりに多くのログイン試行の失敗によりアカウントがロックされた後、ユーザーが再試行するために待たなければならない時間。

`0` をセットするとアカウントは無期限にロックされます。管理者が手動でアカウントのロックを解除する必要があります。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `cpTrigger`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'admin'`

定義元

:   [GeneralConfig::$cpTrigger](api:craft\config\GeneralConfig::$cpTrigger)

現在のリクエストをフロントエンドのウェブサイトではなくコントロールパネルにルーティングするかどうかを決定するとき、Craft が探す URI セグメント。

### `csrfTokenName`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'CRAFT_CSRF_TOKEN'`

定義元

:   [GeneralConfig::$csrfTokenName](api:craft\config\GeneralConfig::$csrfTokenName)

[enableCsrfProtection](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#enablecsrfprotection) が `true` にセットされている場合、CSRF の検証に使用される CSRF トークン名。

### `defaultCookieDomain`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `''`

定義元

:   [GeneralConfig::$defaultCookieDomain](api:craft\config\GeneralConfig::$defaultCookieDomain)

Craft によって生成される Cookie が作成されるべきドメイン。空白の場合、使用するドメイン（ほとんどの場合、現在のもの）の決定はブラウザに任されます。すべてのサブドメインで機能する Cookie を望むなら、例えば、これを `'.domain.com'` にセットします。

### `defaultCpLanguage`

許可される型

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$defaultCpLanguage](api:craft\config\GeneralConfig::$defaultCpLanguage)

優先言語をまだセットしてないユーザー向けに、コントロールパネルが使用するデフォルトの言語。

### `defaultDirMode`

許可される型

:   `mixed`

デフォルト値

:   `0775`

定義元

:   [GeneralConfig::$defaultDirMode](api:craft\config\GeneralConfig::$defaultDirMode)

新しく生成されたディレクトリにセットされるデフォルトのパーミッション。

`null` をセットすると、パーミッションは現在の環境によって決定されます。

### `defaultFileMode`

許可される型

:   [integer](http://php.net/language.types.integer), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$defaultFileMode](api:craft\config\GeneralConfig::$defaultFileMode)

新しく生成されたファイルにセットされるデフォルトのパーミッション。

`null` をセットすると、パーミッションは現在の環境によって決定されます。

### `defaultImageQuality`

許可される型

:   [integer](http://php.net/language.types.integer)

デフォルト値

:   `82`

定義元

:   [GeneralConfig::$defaultImageQuality](api:craft\config\GeneralConfig::$defaultImageQuality)

JPG と PNG ファイルを保存する際に、Craft が使用する品質レベル。0（最低品質、最小ファイルサイズ）から100（最高品質、最大ファイルサイズ）までの範囲。

### `defaultSearchTermOptions`

許可される型

:   [array](http://php.net/language.types.array)

デフォルト値

:   `[]`

定義元

:   [GeneralConfig::$defaultSearchTermOptions](api:craft\config\GeneralConfig::$defaultSearchTermOptions)

それぞれの検索用語に適用されるデフォルトのオプション。

オプションは次のものを含みます。

- `attribute` – （もしある場合）用語が適用される属性（例：'title'）。（デフォルトは `null`）
- `exact` – 用語が完全一致でなければならないかどうか（`attribute` がセットされている場合のみ、適用されます）。（デフォルトは `false`）
- `exclude` – 検索結果でこの用語のレコードを *除外する* かどうか。（デフォルトは `false`）
- `subLeft` – それより前に追加の文字を持つ「用語を含むキーワード」を含めるかどうか。（デフォルトは `false`）
- `subRight` – それより後に追加の文字を持つ「用語を含むキーワード」を含めるかどうか。（デフォルトは `true`）

### `defaultTemplateExtensions`

許可される型

:   [string](http://php.net/language.types.string)[]

デフォルト値

:   `['html', 'twig']`

定義元

:   [GeneralConfig::$defaultTemplateExtensions](api:craft\config\GeneralConfig::$defaultTemplateExtensions)

フロントエンドでテンプレートパスとファイルの照合をする際に、Craft が探すテンプレートファイルの拡張子。

### `defaultTokenDuration`

許可される型

:   `mixed`

デフォルト値

:   `86400`

定義元

:   [GeneralConfig::$defaultTokenDuration](api:craft\config\GeneralConfig::$defaultTokenDuration)

トークンが期限切れになる前に使用できるデフォルトの時間。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `defaultWeekStartDay`

許可される型

:   [integer](http://php.net/language.types.integer)

デフォルト値

:   `1`

定義元

:   [GeneralConfig::$defaultWeekStartDay](api:craft\config\GeneralConfig::$defaultWeekStartDay)

新しいユーザーが「週の開始日」としてセットする必要があるデフォルトの曜日。

これは、次の整数の1つをセットしてください。

- `0` – 日曜日
- `1` – 月曜日
- `2` – 火曜日
- `3` – 水曜日
- `4` – 木曜日
- `5` – 金曜日
- `6` – 土曜日

### `deferPublicRegistrationPassword`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$deferPublicRegistrationPassword](api:craft\config\GeneralConfig::$deferPublicRegistrationPassword)

デフォルトでは、フロントエンドの一般ユーザー登録で「パスワード」フィールドを送信する必要があります。`true` をセットすると、最初の登録フォームでパスワードを必要としなくなります。

メールアドレスの確認が有効になっている場合、新しいユーザーは通知メールに記載されたリンクをクリックしてパスワードを設定できます。そうでなければ、「パスワードを忘れた」際のワークフローを経由することがパスワードをセットできる唯一の方法となります。

### `devMode`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$devMode](api:craft\config\GeneralConfig::$devMode)

システムを [Dev Mode](https://craftcms.com/support/dev-mode) で実行するかどうか。

### `disabledPlugins`

許可される型

:   [string](http://php.net/language.types.string)[]

デフォルト値

:   `[]`

定義元

:   [GeneralConfig::$disabledPlugins](api:craft\config\GeneralConfig::$disabledPlugins)

プロジェクトコンフィグの内容に関わらず無効にする、プラグインハンドルの配列。

```php
'dev' => [
    'disabledPlugins' => ['webhooks'],
],
```

### `elevatedSessionDuration`

許可される型

:   `mixed`

デフォルト値

:   `300`

定義元

:   [GeneralConfig::$elevatedSessionDuration](api:craft\config\GeneralConfig::$elevatedSessionDuration)

機密性の高い操作（例：ユーザーのグループや権限の割り当てなど）に必要な、ユーザーの昇格されたセッションの時間。

昇格されたセッションのサポートを無効化するには、`0` をセットしてください。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `enableCsrfCookie`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$enableCsrfCookie](api:craft\config\GeneralConfig::$enableCsrfCookie)

[enableCsrfProtection](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#enablecsrfprotection) が有効な場合、CSRF トークンを保持するために Cookie を使用するかどうか。false の場合、CSRF トークンはコンフィグ設定名 `csrfTokenName` 配下のセッション内に保管されます。セッションの CSRF トークンを保存することでセキュリティが向上している間は、CSRF トークンをすべてのページでセッションを開始する必要があるため、サイトのパフォーマンスが低下する可能性がある点に注意してください。

### `enableCsrfProtection`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$enableCsrfProtection](api:craft\config\GeneralConfig::$enableCsrfProtection)

Craft 経由で送信されるすべてのフォームで、不可視項目による CSRF 保護を有効にするかどうか。

### `enableTemplateCaching`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$enableTemplateCaching](api:craft\config\GeneralConfig::$enableTemplateCaching)

グローバル基準で Craft テンプレートの `{% cache %}` タグを有効にするかどうか。

### `errorTemplatePrefix`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `''`

定義元

:   [GeneralConfig::$errorTemplatePrefix](api:craft\config\GeneralConfig::$errorTemplatePrefix)

エラーテンプレートを探すためのパスを決定するときに、HTTP エラーステータスコードの前につける接頭辞。

例えば `'_'` がセットされている場合、サイトの 404 テンプレートは`templates/_404.html` となります。

### `extraAllowedFileExtensions`

許可される型

:   [string](http://php.net/language.types.string)[], [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$extraAllowedFileExtensions](api:craft\config\GeneralConfig::$extraAllowedFileExtensions)

コンフィグ設定 [allowedFileExtensions](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#allowedfileextensions) にマージされるファイル拡張子のリスト。

### `extraAppLocales`

許可される型

:   [string](http://php.net/language.types.string)[], [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$extraAppLocales](api:craft\config\GeneralConfig::$extraAppLocales)

アプリケーションがサポートすべき追加のロケール ID のリストで、ユーザーが優先言語として選択できる必要があります。

サーバーに Intl PHP エクステンションがあるか、対応する[ロケールデータ](https://github.com/craftcms/locales)を `config/locales/` フォルダに保存している場合のみ、この設定を使用してください。

### `extraFileKinds`

許可される型

:   [array](http://php.net/language.types.array)

デフォルト値

:   `[]`

定義元

:   [GeneralConfig::$extraFileKinds](api:craft\config\GeneralConfig::$extraFileKinds)

Craft がサポートすべき追加のファイル種類のリスト。この配列は `\craft\config\craft\helpers\Assets::_buildFileKinds()` 内で定義されたものとマージされます。

```php
'extraFileKinds' => [
    // merge .psb into list of Photoshop file kinds
    'photoshop' => [
        'extensions' => ['psb'],
    ],
    // register new "Stylesheet" file kind
    'stylesheet' => [
        'label' => 'Stylesheet',
        'extensions' => ['css', 'less', 'pcss', 'sass', 'scss', 'styl'],
    ],
],
```

::: tip
ここにリストされたファイル拡張子が、即座にアップロードを許可されるわけではありません。コンフィグ設定 [extraAllowedFileExtensions](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#extraallowedfileextensions) でそれらをリストする必要もあります。
:::

### `filenameWordSeparator`

許可される型

:   [string](http://php.net/language.types.string), [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `'-'`

定義元

:   [GeneralConfig::$filenameWordSeparator](api:craft\config\GeneralConfig::$filenameWordSeparator)

アセットをアップロードする際に、単語を区切るために使用する文字列。`false` の場合、空白だけが残ります。

### `generateTransformsBeforePageLoad`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$generateTransformsBeforePageLoad](api:craft\config\GeneralConfig::$generateTransformsBeforePageLoad)

ページの読み込み前に画像の変形によるサムネイルの生成をするかどうか。

### `imageDriver`

許可される型

:   `mixed`

デフォルト値

:   `self::IMAGE_DRIVER_AUTO`

定義元

:   [GeneralConfig::$imageDriver](api:craft\config\GeneralConfig::$imageDriver)

Craft が画像の削除や変形で使用するイメージドライバ。デフォルトでは、Craft はインストールされている ImageMagick を自動検出し、そうでない場合は GD をフォールバックします。明示的に `'imagick'` または `'gd'` をセットして、その振る舞いを上書きすることができます。

### `indexTemplateFilenames`

許可される型

:   [string](http://php.net/language.types.string)[]

デフォルト値

:   `['index']`

定義元

:   [GeneralConfig::$indexTemplateFilenames](api:craft\config\GeneralConfig::$indexTemplateFilenames)

フロントエンドでテンプレートパスとファイルの照合をする際に、Craft がディレクトリ内で探すディレクトリの「インデックス」テンプレートに相当するテンプレートファイル名。

### `invalidLoginWindowDuration`

許可される型

:   `mixed`

デフォルト値

:   `3600`

定義元

:   [GeneralConfig::$invalidLoginWindowDuration](api:craft\config\GeneralConfig::$invalidLoginWindowDuration)

Craft がアカウントをロックするかを決定するために、ユーザーの無効なログイン試行を追跡する時間。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `invalidUserTokenPath`

許可される型

:   `mixed`

デフォルト値

:   `''`

定義元

:   [GeneralConfig::$invalidUserTokenPath](api:craft\config\GeneralConfig::$invalidUserTokenPath)

ユーザートークンの検証が失敗した際に、Craft がリダイレクトする URI。トークンは、ユーザーアカウントのパスワードの設定やリセットで利用されます。フロントエンドサイトのリクエストのみに影響することに注意してください。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

### `ipHeaders`

許可される型

:   [string](http://php.net/language.types.string)[], [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$ipHeaders](api:craft\config\GeneralConfig::$ipHeaders)

プロキシが実際のクライアント IP を保管するヘッダーのリスト。

詳細については、[yii\web\Request::$ipHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$ipHeaders-detail) を参照してください。

設定されていない場合、デフォルトで [craft\web\Request::$ipHeaders](https://docs.craftcms.com/api/v3/craft-web-request.html#ipheaders) 値が使用されます。

### `isSystemLive`

許可される型

:   [boolean](http://php.net/language.types.boolean), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$isSystemLive](api:craft\config\GeneralConfig::$isSystemLive)

サイトが現在稼働しているかどうか。`true` または `false` をセットしている場合、「設定 > 一般」のシステムのステータス設定よりも優先されます。

### `limitAutoSlugsToAscii`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$limitAutoSlugsToAscii](api:craft\config\GeneralConfig::$limitAutoSlugsToAscii)

自動生成されたスラグの ASCII 以外の文字を ASCII に変換するかどうか（例： ñ → n）。

::: tip
これは JavaScript によって自動生成されるスラグのみ影響します。手動で入力した場合、ASCII 以外の文字をスラグに使用できます。
:::

### `loginPath`

許可される型

:   `mixed`

デフォルト値

:   `'login'`

定義元

:   [GeneralConfig::$loginPath](api:craft\config\GeneralConfig::$loginPath)

Craft がフロントエンドのユーザーログインに使用する URI。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

### `logoutPath`

許可される型

:   `mixed`

デフォルト値

:   `'logout'`

定義元

:   [GeneralConfig::$logoutPath](api:craft\config\GeneralConfig::$logoutPath)

Craft がフロントエンドのユーザーログアウトに使用する URI。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

### `maxCachedCloudImageSize`

許可される型

:   [integer](http://php.net/language.types.integer)

デフォルト値

:   `2000`

定義元

:   [GeneralConfig::$maxCachedCloudImageSize](api:craft\config\GeneralConfig::$maxCachedCloudImageSize)

変換で使用する外部ソースから画像をキャッシュする際に使用する最大の寸法サイズ。キャッシュを無効化するには、`0` をセットしてください。

### `maxInvalidLogins`

許可される型

:   [integer](http://php.net/language.types.integer)

デフォルト値

:   `5`

定義元

:   [GeneralConfig::$maxInvalidLogins](api:craft\config\GeneralConfig::$maxInvalidLogins)

ロックされる前のアカウントが指定期間内で Craft に許可される、無効なログイン試行の回数。

### `maxSlugIncrement`

許可される型

:   [integer](http://php.net/language.types.integer)

デフォルト値

:   `100`

定義元

:   [GeneralConfig::$maxSlugIncrement](api:craft\config\GeneralConfig::$maxSlugIncrement)

諦めてエラーにする前に、Craft がそれをユニークにするためにスラグへ追加する最高の数。

### `maxUploadFileSize`

許可される型

:   [integer](http://php.net/language.types.integer), [string](http://php.net/language.types.string)

デフォルト値

:   `16777216`

定義元

:   [GeneralConfig::$maxUploadFileSize](api:craft\config\GeneralConfig::$maxUploadFileSize)

許可される最大のアップロードファイルサイズ。

サポートされる値の種類は、[craft\helpers\ConfigHelper::sizeInBytes()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-sizeinbytes) のリストを参照してください。

### `omitScriptNameInUrls`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$omitScriptNameInUrls](api:craft\config\GeneralConfig::$omitScriptNameInUrls)

生成された URL が `index.php` を省略するかどうか（例：`http://domain.com/index.php/path` の代わりに `http://domain.com/path`）。

これは、例えば Craft に付属している `.htaccess` にリダイレクトが見つかるなど、404 を `index.php` にリダイレクトするようサーバーが設定されている場合のみ可能です。

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule (.+) /index.php?p= [QSA,L]
```

### `optimizeImageFilesize`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$optimizeImageFilesize](api:craft\config\GeneralConfig::$optimizeImageFilesize)

Craft が画質を著しく低下させることなく、画像のファイルサイズを減らす最適化をするかどうか。
（ImageMagick を使用している場合のみ、サポートされます。）

### `pageTrigger`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'p'`

定義元

:   [GeneralConfig::$pageTrigger](api:craft\config\GeneralConfig::$pageTrigger)

現在のリクエストがページ分割されたリストに含まれる特定ページのものかどうかを決定する際に、Craft が探す数値の前にある文字列。

| サンプル値 | サンプル URI |
| ------------- | ----------- |
| `p` | `/news/p5` |
| `page` | `/news/page5` |
| `page/` | `/news/page/5` |
| `?page` | `/news?page=5` |

::: tip
これを `?p`（例：`/news?p=5`）にセットしたい場合、デフォルトで `p` がセットされている [pathParam](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#pathparam) 設定も変更する必要があります。さらにサーバーが Apache で稼働している場合、新しい `pathParam` 値とマッチするよう `.htaccess` ファイル内のリダイレクトコードをアップデートする必要があります。
:::

### `pathParam`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'p'`

定義元

:   [GeneralConfig::$pathParam](api:craft\config\GeneralConfig::$pathParam)

リクエストのパスを決定する際に、Craft がチェックするクエリ文字列のパラメータ。

::: tip
これを変更し、かつ、サーバーが Apache で稼働している場合、新しい値とマッチするよう `.htaccess` ファイルをアップデートすることを忘れないでください。
:::

### `phpMaxMemoryLimit`

許可される型

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$phpMaxMemoryLimit](api:craft\config\GeneralConfig::$phpMaxMemoryLimit)

Craft が圧縮、展開、アップデートなどのメモリ集約型の操作中に確保しようと試みるメモリの最大量。デフォルトは空の文字列で、可能な限り多くのメモリを使用することを意味しています。

受け入れられる値については、<http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes> のリストを参照してください。

### `phpSessionName`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'CraftSessionId'`

定義元

:   [GeneralConfig::$phpSessionName](api:craft\config\GeneralConfig::$phpSessionName)

PHP セッション Cookie の名前。

### `postCpLoginRedirect`

許可される型

:   `mixed`

デフォルト値

:   `'dashboard'`

定義元

:   [GeneralConfig::$postCpLoginRedirect](api:craft\config\GeneralConfig::$postCpLoginRedirect)

コントロールパネルからログインした後にユーザーをリダイレクトするパス。

すでにログインしているユーザーが CP のログインページ（`/admin/login`）または、CP のルート URL（/admin）にアクセスした場合も、この設定が効力を発揮します。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

### `postLoginRedirect`

許可される型

:   `mixed`

デフォルト値

:   `''`

定義元

:   [GeneralConfig::$postLoginRedirect](api:craft\config\GeneralConfig::$postLoginRedirect)

フロントエンドサイトからログインした後にユーザーをリダイレクトするパス。

すでにログインしているユーザーがログインページ（コンフィグ設定の loginPath に明示されているとおり）にアクセスした場合も、効力を発揮します。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

### `postLogoutRedirect`

許可される型

:   `mixed`

デフォルト値

:   `''`

定義元

:   [GeneralConfig::$postLogoutRedirect](api:craft\config\GeneralConfig::$postLogoutRedirect)

フロントエンドサイトからログアウトした後にユーザーをリダイレクトするパス。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

### `preserveCmykColorspace`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$preserveCmykColorspace](api:craft\config\GeneralConfig::$preserveCmykColorspace)

画像を操作するとき、CMYK を色空間として保存するかどうか。

`true` をセットすると、Craft は CMYK イメージを sRGB に変換するのを防ぎます。ただし、 ImageMagick のバージョンによっては、イメージに色の歪みを生じることがあります。これは ImageMagick を使用している場合のみ、影響があります。

### `preserveExifData`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$preserveExifData](api:craft\config\GeneralConfig::$preserveExifData)

画像を操作するとき、EXIF データを保存するかどうか。

`true` をセットすると、画像ファイルのサイズが大きくなります。

これは ImageMagick を使用している場合のみ、影響があります。

### `preserveImageColorProfiles`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$preserveImageColorProfiles](api:craft\config\GeneralConfig::$preserveImageColorProfiles)

画像を操作するとき、埋め込まれたイメージカラープロファイル（ICC）を保存するかどうか。

`false` に設定すると画像サイズが少し小さくなります。ただし、ImageMagick のバージョンによっては正しくないガンマ値が保存され、とても暗い画像になることがあります。これは ImageMagick を使用している場合のみ、影響があります。

### `preventUserEnumeration`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$preventUserEnumeration](api:craft\config\GeneralConfig::$preventUserEnumeration)

`false` に設定され、コントロールパネルのログインページの「パスワードを忘れた」ワークフローを通過すると、ユーザー名 / メールアドレスが存在しないのか、または、次の手順のためのメール送信が成功し確認されたのかを示す別個のメッセージが表示されます。これは、レスポンスに基づいてユーザー名 / メールアドレスの列挙を許可します。`true` に設定すると、ユーザーを列挙するのが難しいエラーである場合も、常に正常なレスポンスを受け取るでしょう。

### `privateTemplateTrigger`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'_'`

定義元

:   [GeneralConfig::$privateTemplateTrigger](api:craft\config\GeneralConfig::$privateTemplateTrigger)

「プライベート」テンプレート（マッチする URL から直接アクセスできないテンプレート）を識別するために使用するテンプレートパスのセグメントの接頭辞。

公開テンプレートのルーティングを無効化するには、空の値をセットしてください。

### `purgePendingUsersDuration`

許可される型

:   `mixed`

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$purgePendingUsersDuration](api:craft\config\GeneralConfig::$purgePendingUsersDuration)

有効化されていない保留中のユーザーを Craft がシステムからパージするまでに待機する時間。

与えられた時間が経過すると、保留中のユーザーに割り当てられたコンテンツもすべて削除される点に注意してください。

この機能を無効化するには、`0` をセットしてください。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `rememberUsernameDuration`

許可される型

:   `mixed`

デフォルト値

:   `31536000`

定義元

:   [GeneralConfig::$rememberUsernameDuration](api:craft\config\GeneralConfig::$rememberUsernameDuration)

CP ログインページへ自動挿入するために、Craft がユーザー名を記憶しておく時間。

この機能を完全に無効化するには、`0` をセットしてください。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `rememberedUserSessionDuration`

許可される型

:   `mixed`

デフォルト値

:   `1209600`

定義元

:   [GeneralConfig::$rememberedUserSessionDuration](api:craft\config\GeneralConfig::$rememberedUserSessionDuration)

ログインページで「ログイン状態を維持する」がチェックされている場合、ユーザーがログインしたままになる時間。

「ログイン状態を維持する」機能を完全に無効化するには、`0` をセットしてください。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `requireMatchingUserAgentForSession`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$requireMatchingUserAgentForSession](api:craft\config\GeneralConfig::$requireMatchingUserAgentForSession)

Cookie からユーザーセッションを復元する際に、一致するユーザーエージェントの文字列を Craft が必要とするかどうか。

### `requireUserAgentAndIpForSession`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$requireUserAgentAndIpForSession](api:craft\config\GeneralConfig::$requireUserAgentAndIpForSession)

新しいユーザーセッションを作成する際に、ユーザーエージェントの文字列と IP アドレスの存在を Craft が必要とするかどうか。

### `resourceBasePath`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'@webroot/cpresources'`

定義元

:   [GeneralConfig::$resourceBasePath](api:craft\config\GeneralConfig::$resourceBasePath)

公開された CP リソースを保管するルートディレクトリのパス。

### `resourceBaseUrl`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'@web/cpresources'`

定義元

:   [GeneralConfig::$resourceBaseUrl](api:craft\config\GeneralConfig::$resourceBaseUrl)

公開された CP リソースを保管するルートディレクトリの URL。

### `restoreCommand`

許可される型

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$restoreCommand](api:craft\config\GeneralConfig::$restoreCommand)

データベースのバックアップを復元するために Craft が実行するシェルコマンド。

ウェブサーバーを実行しているユーザーの `$PATH` 変数にライブラリが含まれている場合、デフォルトで Craft は `mysql` または `psql` を実行します。

ランタイムで Craft がスワップアウトするために利用できるいくつかのトークンがあります。

- `{path}` - バックアップファイルのパス
- `{port}` -現在のデータベースポート
- `{server}` - 現在のデータベースホスト名
- `{user}` -データベースのに接続するユーザー
- `{database}` - 現在のデータベース名
- `{schema}` - （もしある場合）現在のデータベーススキーマ

データベースの復元を完全に無効化するために、`false` をセットすることもできます。

### `rotateImagesOnUploadByExifData`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$rotateImagesOnUploadByExifData](api:craft\config\GeneralConfig::$rotateImagesOnUploadByExifData)

アップロード時の EXIF データに従って、Craft が画像を回転するかどうか。

### `runQueueAutomatically`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$runQueueAutomatically](api:craft\config\GeneralConfig::$runQueueAutomatically)

HTTP リクエストを通して、Craft が保留中のキュージョブを自動的に実行するかどうか。

この設定は、サーバーが Win32 を実行している、または、Apache の mod_deflate/mod_gzip がインストールされている場合は、PHP の [flush()](http://php.net/manual/en/function.flush.php) メソッドが動作しないため、無効にする必要があります。

無効にした場合、代わりのキューランナーを別途セットアップ*しなければなりません*。

これは、1分ごとに実行される cron ジョブからキューランナーを設定する方法の例です。

```text
/1 * * * * /path/to/project/root/craft queue/run
```

### `sanitizeSvgUploads`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$sanitizeSvgUploads](api:craft\config\GeneralConfig::$sanitizeSvgUploads)

Craft がアップロードされた SVG ファイルをサニタイズし、潜在的な悪意のあるコンテンツを取り除くべきかどうか。

信頼できないソースから SVG アップロードを許可する場合は、これを確実に有効にするべきです。

### `secureHeaders`

許可される型

:   [array](http://php.net/language.types.array), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$secureHeaders](api:craft\config\GeneralConfig::$secureHeaders)

デフォルトで、信頼できるホスト設定の適用を受けるヘッダーのリスト。

詳細については、[yii\web\Request::$secureHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$secureHeaders-detail) を参照してください。

設定されていない場合、デフォルトで [yii\web\Request::$secureHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$secureHeaders-detail) 値が使用されます。

### `secureProtocolHeaders`

許可される型

:   [array](http://php.net/language.types.array), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$secureProtocolHeaders](api:craft\config\GeneralConfig::$secureProtocolHeaders)

HTTPS 経由で接続されるかどうかを決定するための確認を行うヘッダーのリスト。

詳細については、[yii\web\Request::$secureProtocolHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$secureProtocolHeaders-detail) を参照してください。

設定されていない場合、デフォルトで [yii\web\Request::$secureProtocolHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$secureProtocolHeaders-detail) 値が使用されます。

### `securityKey`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$securityKey](api:craft\config\GeneralConfig::$securityKey)

[craft\services\Security](api:craft\services\Security) のデータのハッシングや暗号化に使われる、非公開でランダムな暗号的に安全な鍵。

この値は、すべての環境で同じであるべきです。この鍵を変更した場合、暗号化されたいかなるデータにもアクセスできなくなることに注意してください。

### `sendPoweredByHeader`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$sendPoweredByHeader](api:craft\config\GeneralConfig::$sendPoweredByHeader)

`X-Powered-By: Craft CMS` ヘッダーを送信するかどうか。[BuiltWith](https://builtwith.com/) や [Wappalyzer](https://www.wappalyzer.com/) のようなサービスで、サイトが Craft で動作していると判別するのを手伝います。

### `setPasswordPath`

許可される型

:   `mixed`

デフォルト値

:   `'setpassword'`

定義元

:   [GeneralConfig::$setPasswordPath](api:craft\config\GeneralConfig::$setPasswordPath)

パスワードリセットのテンプレートパス。フロントエンドサイトのリクエストのみに影響することに注意してください。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

### `setPasswordSuccessPath`

許可される型

:   `mixed`

デフォルト値

:   `''`

定義元

:   [GeneralConfig::$setPasswordSuccessPath](api:craft\config\GeneralConfig::$setPasswordSuccessPath)

Craft がフロントエンドからパスワードを設定したユーザーをリダイレクトさせる URI。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

### `siteName`

許可される型

:   [string](http://php.net/language.types.string), [string](http://php.net/language.types.string)[]

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$siteName](api:craft\config\GeneralConfig::$siteName)

サイト名。セットされている場合、「設定 > サイト > 名前」で設定された名称よりも優先されます。

プライマリサイトの名前だけを上書きするための文字列、または、サイトのハンドルをキーとして使用する配列をセットできます。

### `siteUrl`

許可される型

:   [string](http://php.net/language.types.string), [string](http://php.net/language.types.string)[]

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$siteUrl](api:craft\config\GeneralConfig::$siteUrl)

サイトのベース URL。セットされている場合、「設定 > サイト > ベース URL」で設定されたベース URLよりも優先されます。

プライマリサイトのベース URL だけを上書きするための文字列、または、サイトのハンドルをキーとして使用する配列をセットできます。

URL は `http://`、`https://`、`//`（プロトコル相対）、または、[エイリアス](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#aliases)のいずれかではじまる必要があります。

```php
'siteUrl' => [
    'siteA' => 'https://site-a.com/',
    'siteB' => 'https://site-b.com/',
],
```

### `slugWordSeparator`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'-'`

定義元

:   [GeneralConfig::$slugWordSeparator](api:craft\config\GeneralConfig::$slugWordSeparator)

スラグの単語を区切るために使用する文字。

### `softDeleteDuration`

許可される型

:   `mixed`

デフォルト値

:   `2592000`

定義元

:   [GeneralConfig::$softDeleteDuration](api:craft\config\GeneralConfig::$softDeleteDuration)

ソフトデリートされたアイテムが、ガベージコレクションによって完全に削除されるまでの時間。

ソフトデリートされたアイテムを削除したくない場合、`0` をセットしてください。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `storeUserIps`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$storeUserIps](api:craft\config\GeneralConfig::$storeUserIps)

ユーザーの IP アドレスがシステムによって保存 / 記録されるべきかどうか。

### `suppressTemplateErrors`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$suppressTemplateErrors](api:craft\config\GeneralConfig::$suppressTemplateErrors)

Twig のランタイムエラーを抑制するかどうか。

`true` をセットすると、エラーは Craft のログファイルに記録されます。

### `testToEmailAddress`

許可される型

:   [string](http://php.net/language.types.string), [array](http://php.net/language.types.array), [false](http://php.net/language.types.boolean), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$testToEmailAddress](api:craft\config\GeneralConfig::$testToEmailAddress)

すべてのシステムメールをテスト目的の単一のメールアドレス、または、メールアドレスの配列へ送信するよう、Craft を設定します。

デフォルトでは受信者名は「テスト受信者」になりますが、`['email@address.com' => 'Name']` の形式で値をカスタマイズできます。

### `timezone`

許可される型

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$timezone](api:craft\config\GeneralConfig::$timezone)

サイトのタイムゾーン。セットされている場合、「設定 > 一般」で設定されたタイムゾーンよりも優先されます。

これは、PHP の [supported timezones](http://php.net/manual/en/timezones.php) の1つをセットできます。

### `tokenParam`

許可される型

:   [string](http://php.net/language.types.string)

デフォルト値

:   `'token'`

定義元

:   [GeneralConfig::$tokenParam](api:craft\config\GeneralConfig::$tokenParam)

Craft のトークンがセットされるクエリ文字列パラメータ名。

### `transformGifs`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$transformGifs](api:craft\config\GeneralConfig::$transformGifs)

GIF ファイルを綺麗にしたり、変形したりするかどうか。

### `translationDebugOutput`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$translationDebugOutput](api:craft\config\GeneralConfig::$translationDebugOutput)

`Craft::t()` または `|translate` フィルタを通して実行されていない文字列を見つけるために、翻訳されたメッセージを特殊文字で囲むかどうか。

### `trustedHosts`

許可される型

:   [array](http://php.net/language.types.array)

デフォルト値

:   `['any']`

定義元

:   [GeneralConfig::$trustedHosts](api:craft\config\GeneralConfig::$trustedHosts)

信頼されるセキュリティ関連のヘッダーの設定。

詳細については、[yii\web\Request::$trustedHosts](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$trustedHosts-detail) を参照してください。

デフォルトでは、すべてのホストが信頼されます。

### `useCompressedJs`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `true`

定義元

:   [GeneralConfig::$useCompressedJs](api:craft\config\GeneralConfig::$useCompressedJs)

可能な場合に、圧縮された JavaScript を Craft が使用するかどうか。

### `useEmailAsUsername`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$useEmailAsUsername](api:craft\config\GeneralConfig::$useEmailAsUsername)

ユーザー自身がユーザー名をセットするのではなく、Craft がユーザー名をメールアドレスに合わせるかどうか。

### `useFileLocks`

許可される型

:   [boolean](http://php.net/language.types.boolean), [null](http://php.net/language.types.null)

デフォルト値

:   `null`

定義元

:   [GeneralConfig::$useFileLocks](api:craft\config\GeneralConfig::$useFileLocks)

`LOCK_EX` フラグを使用して、書き込む際にファイルを排他ロックするかどうか。

NFS のような一部のファイルシステムでは、排他的なファイルロックをサポートしていません。

`true` または `false` をセットしていない場合、Craft は自動的に基礎となるファイルシステムが排他的なファイルロックをサポートしているかを検出し、結果をキャッシュします。

### `usePathInfo`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$usePathInfo](api:craft\config\GeneralConfig::$usePathInfo)

Craft が URL を生成する際、`PATH_INFO` を使用してパスを指定するか、クエリ文字列パラメータとして指定するかどうか。

この設定は、[omitScriptNameInUrls](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#omitscriptnameinurls) が false にセットされている場合のみ影響することに注意してください。

### `useProjectConfigFile`

許可される型

:   [boolean](http://php.net/language.types.boolean)

デフォルト値

:   `false`

定義元

:   [GeneralConfig::$useProjectConfigFile](api:craft\config\GeneralConfig::$useProjectConfigFile)

プロジェクトコンフィグを `config/project.yaml` に保存するかどうか。

`true` をセットすると、システムのプロジェクトコンフィグのハードコピーが `config/project.yaml` に保存され、`config/project.yaml` の変更はシステムに適用されます。それによって、別々のデータベースを持つにも関わらず、マルチ環境で同じプロジェクトコンフィグを共有することが可能になります。

::: warning
この設定を有効にする場合、必ず[プロジェクトコンフィグ](../project-config.html)ドキュメント全体を読み、「プロジェクトコンフィグファイルを有効にする」のステップに慎重に従ってください。
:::

### `useSecureCookies`

許可される型

:   [boolean](http://php.net/language.types.boolean), [string](http://php.net/language.types.string)

デフォルト値

:   `'auto'`

定義元

:   [GeneralConfig::$useSecureCookies](api:craft\config\GeneralConfig::$useSecureCookies)

`Cookie を作成するために Craft::cookieConfig()` を使用した際、Craft が保存する Cookie に "secure" フラグをセットするかどうか。

有効な値は `true`、`false`、および、`'auto'` です。デフォルトは `'auto'` で、現在のアクセスが `https://` 越しの場合に、secure フラグがセットされます。`true` はプロトコルに関係なく常にフラグをセットし、`false` は自動的にフラグをセットすることはありません。

### `useSslOnTokenizedUrls`

許可される型

:   [boolean](http://php.net/language.types.boolean), [string](http://php.net/language.types.string)

デフォルト値

:   `'auto'`

定義元

:   [GeneralConfig::$useSslOnTokenizedUrls](api:craft\config\GeneralConfig::$useSslOnTokenizedUrls)

トークン化された URL を生成する際に、Craft が使用するプロトコル / スキーマを決定します。`'auto'` をセットすると、Craft は現在のリクエストの siteUrl とプロトコルをチェックし、いずれかが https であればトークン化された URL で `https` を使用します。そうでなければ、`http` を使用します。

`false` をセットすると、Craft は常に `http` を使用します。そして、`true` をセットすると、Craft は常に `https` を使用します。

### `userSessionDuration`

許可される型

:   `mixed`

デフォルト値

:   `3600`

定義元

:   [GeneralConfig::$userSessionDuration](api:craft\config\GeneralConfig::$userSessionDuration)

ユーザーがアクティブではないためにログアウトするまでの時間。

事前に決定した時間ではなく、ユーザーがブラウザを開いている間はログインしたままにしておきたい場合は、`0` をセットします。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `verificationCodeDuration`

許可される型

:   `mixed`

デフォルト値

:   `86400`

定義元

:   [GeneralConfig::$verificationCodeDuration](api:craft\config\GeneralConfig::$verificationCodeDuration)

期限切れになる前に、ユーザー確認コードを使用できる時間。

サポートされる値の種類は、[craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) のリストを参照してください。

### `verifyEmailSuccessPath`

許可される型

:   `mixed`

デフォルト値

:   `''`

定義元

:   [GeneralConfig::$verifyEmailSuccessPath](api:craft\config\GeneralConfig::$verifyEmailSuccessPath)

コントロールパネルにアクセスできないユーザーが、新しいメールアドレスを確認したときにリダイレクトする URI。

サポートされる値の種類は、[craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) のリストを参照してください。

<!-- END SETTINGS -->

