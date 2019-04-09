# Craft 3 の変更点

[[toc]]

## リッチテキストフィールド

「リッチテキスト」フィールドタイプは Craft 3 から削除され、新しい [Redactor](https://github.com/craftcms/redactor) プラグインと [CKEditor](https://github.com/craftcms/ckeditor) プラグインが追加されました。

いくつかのリッチテキストフィールドがすでに存在する場合、Redactor プラグインをインストールした時点で自動的に Redactor フィールドに変換されます。

### Redactor 設定

Redactor プラグインをインストールする場合、`config/redactor/` に定義された Redactor 設定が有効な JSON であることを保証する必要があります。すなわち、

- コメントは使えません
- （コンフィグ設定名である）すべてのオブジェクトプロパティは、ダブルクォートで囲まれていなければなりません。
- すべての文字列は、シングルクォートではなくダブルクォートを使用しなければなりません。

```javascript
// Bad:
{
  /* interesting comment */
  buttons: ['bold', 'italic']
}

// Good:
{
  "buttons": ["bold", "italic"]
}
```

## 位置選択フィールド

「位置選択」フィールドタイプは Craft 3 で削除されました。位置選択フィールドがある場合、すべてのオプションを保持したままドロップダウンフィールドに変換されます。

位置選択フィールドが必要な場合、[Position Fieldtype](https://github.com/Rias500/craft-position-fieldtype) プラグインをインストールしてください。

## リモートボリューム

Amazon S3、Rackspace Cloud Files、および、Google Cloud Storage のサポートは、プラグインに移行されました。それらのサービスを Craft 2 で利用していたアセットボリュームがある場合、新しいプラグインをインストールする必要があります。

- [Amazon S3](https://github.com/craftcms/aws-s3)
- [Rackspace Cloud Files](https://github.com/craftcms/rackspace)
- [Google Cloud Storage](https://github.com/craftcms/google-cloud)

## コンフィギュレーション

### コンフィグ設定

次の設定は Craft 3 で非推奨となり、Craft 4 で完全に削除されます。

| ファイル | 旧設定 | 新設定 |
| ------------- | ---------------------------- | ----------------------------- |
| `general.php` | `activateAccountFailurePath` | `invalidUserTokenPath` |
| `general.php` | `backupDbOnUpdate` | `backupOnUpdate`<sup>1</sup> |
| `general.php` | `defaultFilePermissions` | `defaultFileMode`<sup>2</sup> |
| `general.php` | `defaultFolderPermissions` | `defaultDirMode` |
| `general.php` | `environmentVariables` | `aliases` <sup>3</sup> |
| `general.php` | `restoreDbOnUpdateFailure` | `restoreOnUpdateFailure` |
| `general.php` | `useWriteFileLock` | `useFileLocks` |
| `general.php` | `validationKey` | `securityKey`<sup>4</sup> |

*<sup>1</sup> `backupOnUpdate` を `false` にすると、PHP によるバックアップの生成が行われないため、パフォーマンスは大きな要因になりえません。*

*<sup>2</sup> `defaultFileMode` はデフォルトで `null` になりました。これは、現在の環境によって決定されることを意味します。*

*<sup>3</sup> Craft 2 で環境変数をサポートしていた設定は、Craft 3 の [エイリアス](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases) でサポートされるようになりました。Craft 3 にアップデートすると、サイト URL やローカルボリュームの設定は新しいエイリアス構文 （`{variable}` の代わりに `@variable`）へ自動的に変換されます。

*<sup>4</sup> `securityKey` は、もはやオプションではありません。まだ設定していない場合、（ファイルが存在していれば）`storage/runtime/validation.key` に設定します。自動生成された `validation.key` ファイルのバックアップは、Craft 4 で削除されるでしょう。*

次の設定は完全に削除されました。

| ファイル | 設定 |
| ------------- | ----------- |
| `db.php` | `collation` |
| `db.php` | `initSQLs` |
| `general.php` | `appId` |
| `general.php` | `cacheMethod` （[コンフィギュレーション > データキャッシュ設定](config/README.md#data-caching-config)を参照してください。） |

### `omitScriptNameInUrls` と `usePathInfo`

`omitScriptNameInUrls` 設定は、Craft 2 のデフォルトがそうであったように `'auto'` にすることはもはやできません。HTTP リクエストを `index.php` にルーティングするようにサーバーを設定した場合、`config/general.php` で明示的に `true` にする必要があることを意味しています。

同様に、`usePathInfo` 設定も `'auto'` にすることはできません。サーバーが [PATH_INFO](https://craftcms.com/support/enable-path-info) をサポートするよう設定されているならば、ここに `true` をセットできます。ただし、`omitScriptNameInUrls` を `true` にセットできない場合のみ、必要となります。

## URL ルール

`config/routes.php` に [URL ルール](config/README.md#url-rules)を保存しているならば、Yii 2 の [pattern-route 構文](https://www.yiiframework.com/doc/guide/2.0/en/runtime-routing#url-rules) にアップデートする必要があります。

- パターンの名前付けされたパラメータは、正規表現のサブパターン（`(?P<ParamName>RegExp)`）ではなく、フォーマット（`<ParamName:RegExp>`）を使用して定義する必要があります。
- 名前付けされていないパラメータ（例：`([^\/]+)`）は、もはや許可されません。新しい名前付けされたパラメータ構文（`<ParamName:RegExp>`）に変換しなければなりません。
- コントローラーアクションのルーティングは、`action` キーを持つ配列（`['action' => 'action/path']`）ではなく、文字列（`'action/path'`）として定義する必要があります。
- テンプレートのルーティングは、文字列（`'template/path'`）ではなく、`template` キーを持つ配列（`['template' => 'template/path']`）として定義する必要があります。

```php
// Old:
'dashboard' => ['action' => 'dashboard/index'],
'settings/fields/new' => 'settings/fields/_edit',
'settings/fields/edit/(?P<fieldId>\d+)' => 'settings/fields/_edit',
'blog/type/([^\/]+)' => 'blog/_type',

// New:
'dashboard' => 'dashboard/index',
'settings/fields/new' => ['template' => 'settings/fields/_edit'],
'settings/fields/edit/<fieldId:\d+>' => ['template' => 'settings/fields/_edit'],
'blog/type/<type:[^\/]+>' => ['template' => 'blog/_type'],
```

## PHP 定数

次の PHP 定数は Craft 3 で非推奨となり、Craft 4 で動作しなくなります。

| 旧 | 新 |
| ---------------- | ---------------------------------------- |
| `CRAFT_LOCALE` | [CRAFT_SITE](config/php-constants.md#craft-site) |
| `CRAFT_SITE_URL` | 代わりに、コンフィグ設定の <config:siteUrl> を使用してください |

## 静的な翻訳ファイル

Craft 3 でも[静的メッセージの翻訳](static-translations.md)をサポートしていますが、ディレクトリ構造が変わりました。`translations/` フォルダの中にローケルごとのサブディレクトリを作成し、それぞれに**翻訳カテゴリ**ごとの PHP ファイルを作成する必要があります。

受け入れられる翻訳カテゴリは、次の通りです。

| カテゴリ | 説明 |
| --------------- | ----------------------------------------- |
| `app` | Craft 向けの翻訳メッセージ |
| `yii` | Yii 向けの翻訳メッセージ |
| `site` | サイト固有の翻訳メッセージ |
| `plugin-handle` | プラグイン向けの翻訳メッセージ |

Craft 3 の `translations/` フォルダの構成は、次のようになります。

```
translations/
└── de/
    ├── app.php
    └── site.php
```

## ユーザーフォト

ユーザーフォトはアセットとして保存されるようになりました。Craft 3 にアップグレードすると、Craft は（ `<Username>/` サブフォルダを除く、Craft が事前にすべてのユーザー画像を保管している） `storage/userphotos/` を「User Photos」と呼ばれる新しいアセットボリュームとして自動的に作成します。しかしながら、このフォルダはウェブルートよりも上位階層にあるため、HTTP リクエストでアクセスできません。 そのため、このボリュームをアクセスできる状態にするまで、ユーサーフォトはフロントエンドで動作しません。

次の方法で解決してください。

1. `storage/userphotos/` フォルダをウェブルート下層のどこかに移動します。（例：`web/userphotos/`）
2. 「設定 > アセット > ボリューム > User Photos」に移動し、新しいフォルダのロケーションに基づいてボリュームを設定します。
   - 「ファイルシステムのパス」設定を新しいフォルダのロケーションにしてアップデートします。
   - 「このボリュームのアセットにはパブリック URL が含まれます」設定を有効化します。
   - フォルダに対応する正しい「ベース URL」を設定します。
   - ボリュームを保存します。

## Twig 2

Craft 3 では、テンプレート向けに独自の変更を加えた Twig 2 を使用しています。

### マクロ

Twig 2 では、利用先となる各テンプレートで明示的にマクロをインポートする必要があります。親テンプレートでインクルードしている場合だけでなく、同じテンプレートファイルで定義されているときでさえも、自動的に利用することはできません。

```twig
Old:
{% macro foo %}...{% endmacro %}
{{ _self.foo() }}

New:
{% macro foo %}...{% endmacro %}
{% import _self as macros %}
{{ macros.foo() }}
```

### 未定義のブロック

Twig 1 では、存在しないブロックでさえも `block()` で呼び出すことができます。

```twig
{% if block('foo') is not empty %}
    {{ block('foo') }}
{% endif %}
```

Twig 2 では、`defined` のテストでない限り、エラーを返します。

```twig
{% if block('foo') is defined %}
    {{ block('foo') }}
{% endif %}
```

## テンプレートタグ

次の Twig タグは削除されました。

| 旧 | 新 |
| ------------------- | ----------------------------------------- |
| `{% endpaginate %}` | *（置き換えが必要なのではなく、単に削除してください）* |

次の Twig テンプレートタグは Craft 3 で非推奨となり、Craft 4 で完全に削除されます。

| 旧 | 新 |
| ------------------------------- | --------------------------------------------- |
| `{% includecss %}` | `{% css %}` |
| `{% includehirescss %}` | `{% css %}` *（自身のメディアセレクタを記述します）* |
| `{% includejs %}` | `{% js %}` |
| `{% includecssfile url %}` | `{% do view.registerCssFile(url) %}` |
| `{% includejsfile url %}` | `{% do view.registerJsFile(url) %}` |
| `{% includecssresource path %}` | [アセットバンドル](extend/asset-bundles.md)を見てください |
| `{% includejsresource path %}` | [アセットバンドル](extend/asset-bundles.md)を見てください |

## テンプレートファンクション

次のテンプレートファンクションは削除されました。

| 旧 | 新 |
| ------------------------------------------- | ------------------------------------ |
| `craft.hasPackage()` | *（該当なし）* |
| `craft.entryRevisions.getDraftByOffset()` | *（該当なし）* |
| `craft.entryRevisions.getVersionByOffset()` | *（該当なし）* |
| `craft.fields.getFieldType(type)` | `craft.app.fields.createField(type)` |
| `craft.fields.populateFieldType()` | *（該当なし）* |
| `craft.tasks.areTasksPending()` | `craft.app.queue.getHasWaitingJobs()`<sup>1</sup> |
| `craft.tasks.getRunningTask()` | *（該当なし）* |
| `craft.tasks.getTotalTasks()` | *（該当なし）* |
| `craft.tasks.haveTasksFailed()` | *（該当なし）* |
| `craft.tasks.isTaskRunning()` | `craft.app.queue.getHasReservedJobs()`<sup>1</sup> |

*<sup>1</sup> `queue` コンポーネントが <api:craft\queue\QueueInterface> を実装している場合のみ、使用可能です。*

次のテンプレートファンクションは Craft 3 で非推奨となり、Craft 4 で完全に削除されます。

| 旧 | 新 |
| ------------------------------------------------------- | --------------------------------------------- |
| `round(num)` | `num|round` |
| `getCsrfInput()` | `csrfInput()` |
| `getHeadHtml()` | `head()` |
| `getFootHtml()` | `endBody()` |
| `getTranslations()` | `view.getTranslations()|json_encode|raw` |
| `craft.categoryGroups.getAllGroupIds()` | `craft.app.categories.allGroupIds` |
| `craft.categoryGroups.getEditableGroupIds()` | `craft.app.categories.editableGroupIds` |
| `craft.categoryGroups.getAllGroups()` | `craft.app.categories.allGroups` |
| `craft.categoryGroups.getEditableGroups()` | `craft.app.categories.editableGroups` |
| `craft.categoryGroups.getTotalGroups()` | `craft.app.categories.totalGroups` |
| `craft.categoryGroups.getGroupById(id)` | `craft.app.categories.getGroupById(id)` |
| `craft.categoryGroups.getGroupByHandle(handle)` | `craft.app.categories.getGroupByHandle(handle)` |
| `craft.config.[setting]` *(magic getter)* | `craft.app.config.general.[setting]` |
| `craft.config.get(setting)` | `craft.app.config.general.[setting]` |
| `craft.config.usePathInfo()` | `craft.app.config.general.usePathInfo` |
| `craft.config.omitScriptNameInUrls()` | `craft.app.config.general.omitScriptNameInUrls` |
| `craft.config.getResourceTrigger()` | `craft.app.config.general.resourceTrigger` |
| `craft.locale()` | `craft.app.language` |
| `craft.isLocalized()` | `craft.app.isMultiSite` |
| `craft.deprecator.getTotalLogs()` | `craft.app.deprecator.totalLogs` |
| `craft.elementIndexes.getSources()` | `craft.app.elementIndexes.sources` |
| `craft.emailMessages.getAllMessages()` | `craft.emailMessages.allMessages` |
| `craft.emailMessages.getMessage(key)` | `craft.app.emailMessages.getMessage(key)` |
| `craft.entryRevisions.getDraftsByEntryId(id)` | `craft.app.entryRevisions.getDraftsByEntryId(id)` |
| `craft.entryRevisions.getEditableDraftsByEntryId(id)` | `craft.entryRevisions.getEditableDraftsByEntryId(id)` |
| `craft.entryRevisions.getDraftById(id)` | `craft.app.entryRevisions.getDraftById(id)` |
| `craft.entryRevisions.getVersionsByEntryId(id)` | `craft.app.entryRevisions.getVersionsByEntryId(id)` |
| `craft.entryRevisions.getVersionById(id)` | `craft.app.entryRevisions.getVersionById(id)` |
| `craft.feeds.getFeedItems(url)` | `craft.app.feeds.getFeedItems(url)` |
| `craft.fields.getAllGroups()` | `craft.app.fields.allGroups` |
| `craft.fields.getGroupById(id)` | `craft.app.fields.getGroupById(id)` |
| `craft.fields.getFieldById(id)` | `craft.app.fields.getFieldById(id)` |
| `craft.fields.getFieldByHandle(handle)` | `craft.app.fields.getFieldByHandle(handle)` |
| `craft.fields.getAllFields()` | `craft.app.fields.allFields` |
| `craft.fields.getFieldsByGroupId(id)` | `craft.app.fields.getFieldsByGroupId(id)` |
| `craft.fields.getLayoutById(id)` | `craft.app.fields.getLayoutById(id)` |
| `craft.fields.getLayoutByType(type)` | `craft.app.fields.getLayoutByType(type)` |
| `craft.fields.getAllFieldTypes()` | `craft.app.fields.allFieldTypes` |
| `craft.globals.getAllSets()` | `craft.app.globals.allSets` |
| `craft.globals.getEditableSets()` | `craft.app.globals.editableSets` |
| `craft.globals.getTotalSets()` | `craft.app.globals.totalSets` |
| `craft.globals.getTotalEditableSets()` | `craft.app.globals.totalEditableSets` |
| `craft.globals.getSetById(id)` | `craft.app.globals.getSetById(id)` |
| `craft.globals.getSetByHandle(handle)` | `craft.app.globals.getSetByHandle(handle)` |
| `craft.i18n.getAllLocales()` | `craft.app.i18n.allLocales` |
| `craft.i18n.getAppLocales()` | `craft.app.i18n.appLocales` |
| `craft.i18n.getCurrentLocale()` | `craft.app.locale` |
| `craft.i18n.getLocaleById(id)` | `craft.app.i18n.getLocaleById(id)` |
| `craft.i18n.getSiteLocales()` | `craft.app.i18n.siteLocales` |
| `craft.i18n.getSiteLocaleIds()` | `craft.app.i18n.siteLocaleIds` |
| `craft.i18n.getPrimarySiteLocale()` | `craft.app.i18n.primarySiteLocale` |
| `craft.i18n.getEditableLocales()` | `craft.app.i18n.editableLocales` |
| `craft.i18n.getEditableLocaleIds()` | `craft.app.i18n.editableLocaleIds` |
| `craft.i18n.getLocaleData()` | `craft.app.i18n.getLocaleById(id)` |
| `craft.i18n.getDatepickerJsFormat()` | `craft.app.locale.getDateFormat('short', 'jui')` |
| `craft.i18n.getTimepickerJsFormat()` | `craft.app.locale.getTimeFormat('short', 'php')` |
| `craft.request.isGet()` | `craft.app.request.isGet` |
| `craft.request.isPost()` | `craft.app.request.isPost` |
| `craft.request.isDelete()` | `craft.app.request.isDelete` |
| `craft.request.isPut()` | `craft.app.request.isPut` |
| `craft.request.isAjax()` | `craft.app.request.isAjax` |
| `craft.request.isSecure()` | `craft.app.request.isSecureConnection` |
| `craft.request.isLivePreview()` | `craft.app.request.isLivePreview` |
| `craft.request.getScriptName()` | `craft.app.request.scriptFilename` |
| `craft.request.getPath()` | `craft.app.request.pathInfo` |
| `craft.request.getUrl()` | `url(craft.app.request.pathInfo)` |
| `craft.request.getSegments()` | `craft.app.request.segments` |
| `craft.request.getSegment(num)` | `craft.app.request.getSegment(num)` |
| `craft.request.getFirstSegment()` | `craft.app.request.segments|first` |
| `craft.request.getLastSegment()` | `craft.app.request.segments|last` |
| `craft.request.getParam(name)` | `craft.app.request.getParam(name)` |
| `craft.request.getQuery(name)` | `craft.app.request.getQueryParam(name)` |
| `craft.request.getPost(name)` | `craft.app.request.getBodyParam(name)` |
| `craft.request.getCookie(name)` | `craft.app.request.cookies.get(name)` |
| `craft.request.getServerName()` | `craft.app.request.serverName` |
| `craft.request.getUrlFormat()` | `craft.app.config.general.usePathInfo` |
| `craft.request.isMobileBrowser()` | `craft.app.request.isMobileBrowser()` |
| `craft.request.getPageNum()` | `craft.app.request.pageNum` |
| `craft.request.getHostInfo()` | `craft.app.request.hostInfo` |
| `craft.request.getScriptUrl()` | `craft.app.request.scriptUrl` |
| `craft.request.getPathInfo()` | `craft.app.request.getPathInfo(true)` |
| `craft.request.getRequestUri()` | `craft.app.request.url` |
| `craft.request.getServerPort()` | `craft.app.request.serverPort` |
| `craft.request.getUrlReferrer()` | `craft.app.request.referrer` |
| `craft.request.getUserAgent()` | `craft.app.request.userAgent` |
| `craft.request.getUserHostAddress()` | `craft.app.request.userIP` |
| `craft.request.getUserHost()` | `craft.app.request.userHost` |
| `craft.request.getPort()` | `craft.app.request.port` |
| `craft.request.getCsrfToken()` | `craft.app.request.csrfToken` |
| `craft.request.getQueryString()` | `craft.app.request.queryString` |
| `craft.request.getQueryStringWithoutPath()` | `craft.app.request.queryStringWithoutPath` |
| `craft.request.getIpAddress()` | `craft.app.request.userIP` |
| `craft.request.getClientOs()` | `craft.app.request.clientOs` |
| `craft.sections.getAllSections()` | `craft.app.sections.allSections` |
| `craft.sections.getEditableSections()` | `craft.app.sections.editableSections` |
| `craft.sections.getTotalSections()` | `craft.app.sections.totalSections` |
| `craft.sections.getTotalEditableSections()` | `craft.app.sections.totalEditableSections` |
| `craft.sections.getSectionById(id)` | `craft.app.sections.getSectionById(id)` |
| `craft.sections.getSectionByHandle(handle)` | `craft.app.sections.getSectionByHandle(handle)` |
| `craft.systemSettings.[category]` *(magic getter)* | `craft.app.systemSettings.getSettings('category')` |
| `craft.userGroups.getAllGroups()` | `craft.app.userGroups.allGroups` |
| `craft.userGroups.getGroupById(id)` | `craft.app.userGroups.getGroupById(id)` |
| `craft.userGroups.getGroupByHandle(handle)` | `craft.app.userGroups.getGroupByHandle(handle)` |
| `craft.userPermissions.getAllPermissions()` | `craft.app.userPermissions.allPermissions` |
| `craft.userPermissions.getGroupPermissionsByUserId(id)` | `craft.app.userPermissions.getGroupPermissionsByUserId(id)` |
| `craft.session.isLoggedIn()` | `not craft.app.user.isGuest` |
| `craft.session.getUser()` | `currentUser` |
| `craft.session.getRemainingSessionTime()` | `craft.app.user.remainingSessionTime` |
| `craft.session.getRememberedUsername()` | `craft.app.user.rememberedUsername` |
| `craft.session.getReturnUrl()` | `craft.app.user.getReturnUrl()` |
| `craft.session.getFlashes()` | `craft.app.session.getAllFlashes()` |
| `craft.session.getFlash()` | `craft.app.session.getFlash()` |
| `craft.session.hasFlash()` | `craft.app.session.hasFlash()` |

## 日付フォーマット

Craft によって拡張された DateTime クラスは Craft 3 で削除されました。ここに、テンプレート内で使用可能だったものと、Craft 3 で同様の働きをするもののリストを掲載します。（DateTime オブジェクトは、変数 `d` で表されます。実際には `entry.postDate` や `now` などになる可能性があります。）

| 旧 | 新 |
| --------------------------------- | ---------------------------------- |
| `{{ d }}` *（文字列として扱われる）* | `{{ d|date('Y-m-d') }}` |
| `{{ d.atom() }}` | `{{ d|atom }}` |
| `{{ d.cookie() }}` | `{{ d|date('l, d-M-y H:i:s T')}}` |
| `{{ d.day() }}` | `{{ d|date('j') }}` |
| `{{ d.iso8601() }}` | `{{ d|date('c') }}` |
| `{{ d.localeDate() }}` | `{{ d|date('short') }}` |
| `{{ d.localeTime() }}` | `{{ d|time('short') }}` |
| `{{ d.month() }}` | `{{ d|date('n') }}` |
| `{{ d.mySqlDateTime() }}` | `{{ d|date('Y-m-d H:i:s') }}` |
| `{{ d.nice() }}` | `{{ d|datetime('short') }}` |
| `{{ d.rfc1036() }}` | `{{ d|date('D, d M y H:i:s O') }}` |
| `{{ d.rfc1123() }}` | `{{ d|date('r') }}` |
| `{{ d.rfc2822() }}` | `{{ d|date('r') }}` |
| `{{ d.rfc3339() }}` | `{{ d|date('Y-m-d\\TH:i:sP') }}` |
| `{{ d.rfc822() }}` | `{{ d|date('D, d M y H:i:s O') }}` |
| `{{ d.rfc850() }}` | `{{ d|date('l, d-M-y H:i:s T') }}` |
| `{{ d.rss() }}` | `{{ d|rss }}` |
| `{{ d.uiTimestamp() }}` | `{{ d|timestamp('short') }}` |
| `{{ d.w3c() }}` | `{{ d|date('Y-m-d\\TH:i:sP') }}` |
| `{{ d.w3cDate() }}` | `{{ d|date('Y-m-d') }}` |
| `{{ d.year() }}` | `{{ d|date('Y') }}` |

## 通貨フォーマット

`|currency` フィルタは <api:craft\i18n\Formatter::asCurrency()> にマップされるようになりました。従来と同じ動きになりますが、引数 `stripZeroCents` は `stripZeros` にリネームされ、キーと値の両方が必要となっているため、この引数をセットしている場合はテンプレートを更新する必要があります。

```twig
Old:
{{ num|currency('USD', true) }}
{{ num|currency('USD', stripZeroCents = true) }}

New:
{{ num|currency('USD', stripZeros = true) }}
```

## エレメントクエリ

### クエリパラメータ

次のパラメータは削除されました。

| エレメントタイプ | 旧パラメータ | 新パラメータ |
| ------------ | ------------------ | ------------------------- |
| すべて | `childOf` | `relatedTo.sourceElement` |
| すべて | `childField` | `relatedTo.field` |
| すべて | `parentOf` | `relatedTo.targetElement` |
| すべて | `parentField` | `relatedTo.field` |
| すべて | `depth` | `level` |
| タグ | `name` | `title` |
| タグ | `setId` | `groupId` |
| タグ | `set` | `group` |
| タグ | `orderBy:"name"` | `orderBy:"title"` |

次のパラメータは Craft 3 で非推奨となり、Craft 4 で完全に削除されます。

| エレメントタイプ | 旧パラメータ | 新パラメータ |
| ------------ | ------------------------ | ---------------------------- |
| すべて | `order` | `orderBy` |
| すべて | `locale` | `siteId` または `site` |
| すべて | `localeEnabled` | `enabledForSite` |
| すべて | `relatedTo.sourceLocale` | `relatedTo.sourceSite` |
| アセット | `source` | `volume` |
| アセット | `sourceId` | `volumeId` |
| 行列ブロック | `ownerLocale` | `ownerSite` または `ownerSiteId` |

#### `limit` パラメータ

`limit` パラメータは、100ではなく、デフォルトで `null`（無制限）がセットされるようになりました。

#### パラメータを配列にセットする

パラメータ値を配列にセットする場合、配列の大括弧を記述**しなければなりません**。

```twig
Old:
{% set query = craft.entries()
    .relatedTo('and', 1, 2, 3) %}

New:
{% set query = craft.entries()
    .relatedTo(['and', 1, 2, 3]) %}
```

#### エレメントクエリの複製

Craft 2 では、パラメータ設定メソッド（例：`.type('article')`）を呼び出すときは、次のような手順になります。

1. `ElementCriteriaModel` オブジェクトを複製する
2. 複製したオブジェクトのパラメータ値を設定する
3. 複製したオブジェクトを返す

これによって、後続のクエリに影響を与えることなく、エレメントクエリのバリエーションを実行できるようにしています。例えば、

```twig
{% set query = craft.entries.section('news') %}
{% set articleEntries = query.type('article').find() %}
{% set totalEntries = query.total() %}
```

この `.type()` は `type` パラメータを `query` の _clone_ に適用しているため、 `query.total()` には影響を与えません。入力タイプに関わらず、News エントリの総数を返します。

しかし、この動作は Craft 3 で変更されました。今では、パラメータ設定メソッドを呼び出すときは、次のような手順になります。

1. 現在のエレメントクエリにパラメータ値を設定する
2. エレメントクエリを返す

つまり、上記のサンプルコードでは `type` パラメータが適用されてしまうため、`totalEntries` には _Article_ エントリの総数がセットされます。

Craft 2 動作に影響を与えるテンプレートがある場合、[clone()](dev/functions.md#clone-object) ファンクションを用いて修正できます。

```twig
{% set query = craft.entries.section('news') %}
{% set articleEntries = clone(query).type('article').all() %}
{% set totalEntries = query.count() %}
```

### クエリメソッド

次のメソッドは削除されました。

| 旧 | 新 |
| ----------------------------- | ------------- |
| `findElementAtOffset(offset)` | `nth(offset)` |

次のメソッドは Craft 3 で非推奨となり、Craft 4 で完全に削除されます。

| 旧 | 新 |
| --------------- | -------------------------------------------------------- |
| `ids(criteria)` | `ids()`（criteria パラメータは非推奨になりました） |
| `find()` | `all()` |
| `first()` | `one()` |
| `last()` | `inReverse().one()` _（[last()](#last) を見てください）_ |
| `total()` | `count()` |

### クエリを配列として扱う

エレメントクエリを配列のように扱うサポートは Craft 3 で非推奨になり、Craft 4 で完全に削除されます。

エレメントクエリをループする必要があるときは、明示的に `.all()` をコールし、データベースクエリを実行して得られる結果の配列を返す必要があります。

```twig
Old:
{% for entry in craft.entries.section('news') %}...{% endfor %}
{% for asset in entry.myAssetsField %}...{% endfor %}

New:
{% for entry in craft.entries.section('news').all() %}...{% endfor %}
{% for asset in entry.myAssetsField.all() %}...{% endfor %}
```

エレメントクエリから結果の総数を取得したいときは、`.count()` メソッドを呼び出す必要があります。

```twig
Old:
{% set total = craft.entries.section('news')|length %}

New:
{% set total = craft.entries.section('news').count() %}
```

代替方法として、実際のクエリ結果を事前にフェッチする必要があり、かつ `offset` や `limit` パラメータをセットしていない場合、  [length](https://twig.symfony.com/doc/2.x/filters/length.html) フィルタを使うことで、余分なデータベースクエリを必要とせず、結果の配列の合計サイズを確認できます。

```twig
{% set entries = craft.entries()
    .section('news')
    .all() %}
{% set total = entries|length %}
```

### `last()`

`last()` は Craft 3 で非推奨になりました。なぜなら、（`query.nth(query.count() - 1)` に相当する）2つのデータベースクエリを背後で実行する必要があることが明確ではないからです。

ほとんどのケースでは、`.last()` の呼び出しを `.inReverse().one()` に置き換えることで、余分なデータベースクエリを必要とせず、同じ結果を得ることができます。（`inReverse()` は、生成された SQL のすべての `ORDER BY` カラムのソート方向を反転させます。）

```twig
{# Channel entries are ordered by `postDate DESC` by default, so this will swap
   it to `postDate ASC`, returning the oldest News entry: #}

{% set oldest = craft.entries()
    .section('news')
    .inReverse()
    .one() %}
```

`inReverse()` が期待した通りに動作しないケースが2つあります。

- SQL に `ORDER BY` 句が存在しない場合、反転できるものがありません
- `orderBy` パラメータに <api:yii\db\Expression> オブジェクトが含まれている場合

このようなケースでは、 `.last()` の呼び出しを内部的な処理で置き換えることができます。

```twig
{% set query = craft.entries()
    .section('news') %}
{% set total = query.count() %}
{% set last = query.nth(total - 1) %}
```

## エレメント

次のエレメントプロパティは削除されました。

| エレメントタイプ | 旧プロパティ | 新プロパティ |
| ------------ | ------------ | ------------ |
| タグ | `name` | `title` |

次のエレメントプロパティは Craft 3 で非推奨となり、Craft 4 で完全に削除されます。

| 旧 | 新 |
| -------- | ------------------------------------------- |
| `locale` | `siteId`、`site.handle`、または、`site.language` |

## モデル

次のモデルメソッドは Craft 3 で非推奨となり、Craft 4 で完全に削除されます。

| 旧 | 新 |
| ------------------- | ------------------------ |
| `getError('field')` | `getFirstError('field')` |

## ロケール

次のロケールメソッドは Craft 3 で非推奨となり、Craft 4 で完全に削除されます。

| 旧 | 新 |
| ------------------ | ------------------------------------ |
| `getId()` | `id` |
| `getName()` | `getDisplayName(craft.app.language)` |
| `getNativeName()` | `getDisplayName()` |

## リクエストパラメータ

コントローラーアクションに送信するフロントエンドの `<form>`や JavaScript は、次の変更を加えてアップデートする必要があります。

### `action` パラメータ

`action` パラメータは `camelCase` ではなく `kebab-case` に書き換えなければなりません。

```twig
Old:
<input type="hidden" name="action" value="entries/saveEntry">

New:
<input type="hidden" name="action" value="entries/save-entry">
```

次のコントローラーアクションは削除されました。

| 旧 | 新 |
| ---------------------------- | -------------------------- |
| `categories/create-category` | `categories/save-category` |
| `users/validate` | `users/verify-email` |
| `users/save-profile` | `users/save-user` |

### `redirect` パラメータ

`redirect` パラメータは、ハッシュ値に変換しなければなりません。

```twig
Old:
<input type="hidden" name="redirect" value="foo/bar">

New:
<input type="hidden" name="redirect" value="{{ 'foo/bar'|hash }}">
```

`redirectInput()` ファンクションは、ショートカットとして提供されています。

```twig
{{ redirectInput('foo/bar') }}
```

次の `redirect` パラメータトークンは、サポートされなくなりました。

| コントローラーアクション | 旧トークン | 新トークン |
| ------------------------------- | ------------- | --------- |
| `entries/save-entry` | `{entryId}` | `{id}` |
| `entry-revisions/save-draft` | `{entryId}` | `{id}` |
| `entry-revisions/publish-draft` | `{entryId}` | `{id}` |
| `fields/save-field` | `{fieldId}` | `{id}` |
| `globals/save-set` | `{setId}` | `{id}` |
| `sections/save-section` | `{sectionId}` | `{id}` |
| `users/save-user` | `{userId}` | `{id}` |

### CSRF トークンパラメータ

CSRF プロテクションは、Craft 3 ではデフォルトで有効になりました。（コンフィグ設定の `enableCsrfProtection` で）有効化していなかった場合、 コントローラーアクションで送信するフロントエンドのすべての `<form>` と JavaScript に新しい CSRF トークンパラメータを追加するアップデートが必要です。 あわせて、コンフィグ設定の `csrfTokenName` をセットする必要があります（デフォルトは `'CRAFT_CSRF_TOKEN'` となります）。

```twig
{% set csrfTokenName = craft.app.config.general.csrfTokenName %}
{% set csrfToken = craft.app.request.csrfToken %}
<input type="hidden" name="{{ csrfTokenName }}" value="{{ csrfToken }}">
```

`csrfInput()` ファンクションは、ショートカットとして提供されています。

```twig
{{ csrfInput() }}
```

## Memcache

コンフィグ設定の <config:cacheMethod> に `memcache` を指定し、設定ファイル `config/memcache.php` で `useMemcached` に `true` をセットしていない場合、サーバーに memcached をインストールする必要があります。Craft 3 では、利用可能な memcache の PHP 7 互換バージョンがないため、それを使用します。

## DbCache

コンフィグ設定の <config:cacheMethod> に `db` を指定している場合、Craft 3 のアップデートを試す前に手動で SQL を実行する必要があります。

```sql
DROP TABLE IF EXISTS craft_cache;

CREATE TABLE craft_cache (
    id char(128) NOT NULL PRIMARY KEY,
    expire int(11),
    data BLOB,
    dateCreated datetime NOT NULL,
    dateUpdated datetime NOT NULL,
    uid char(36) NOT NULL DEFAULT 0
);
```

この例では、Craft 2 デフォルトの `craft` を DB コンフィグ設定の `tablePrefix` に設定している点に注意してください。コンフィグ設定を変更している場合、それに応じて前述のサンプルを調整してください。

## プラグイン

[Craft 3 のためのプラグインアップデート](extend/updating-plugins.md)を参照してください。

