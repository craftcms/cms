# `{% requirePermission %}`

このタグは、現在のユーザーが特定の権限を持つアカウントでログインしていることを保証します。

```twig
{% requirePermission "spendTheNight" %}

<h1>Slumber Party</h1>
```

ユーザーは、直接またはユーザーグループの1つを通して権限を持つことができます。もし権限を持っていないなら、403（Forbidden）エラーが提供されます。

### 利用可能な権限

Craft の権限は次の通りです。

| 権限 | ハンドル |
| ---------- | ------ |
| システムがオフの場合にサイトにアクセスする | `accessSiteWhenSystemIsOff` |
| 管理画面にアクセスする | `accessCp` |
| ↳  システムがオフの場合に管理画面にアクセスする | `accessCpWhenSystemIsOff` |
| ↳  Craft CMS起動とプラグインのアップデート | `performUpdates` |
| ↳  _「プラグイン名」_ のアクセス  | `accessPlugin-[PluginHandle]` |
| ユーザーを編集する | `editUsers` |
| ↳  ユーザーを登録する | `registerUsers` |
| ↳  ユーザー権限を割り当てる | `assignUserPermissions` |
| ↳  ユーザーを管理 | `administrateUsers` |
| ユーザーを削除する | `deleteUsers` |
| _「サイト名」_ を編集する | `editLocale:[LocaleID]` |
| エントリを編集する | `editEntries:[SectionID]` |
| ↳  エントリを作る | `createEntries:[SectionID]` |
| ↳  ライブの変更を発表する | `publishEntries:[SectionID]` |
| ↳  エントリを削除する | `deleteEntries:[SectionID]` |
| ↳  他の投稿者のエントリを編集する | `editPeerEntries:[SectionID]` |
|       ↳  他の作成者の入力のためライブを変更する。 | `publishPeerEntries:[SectionID]` |
|       ↳  他の投稿者のエントリを削除する。 | `deletePeerEntries:[SectionID]` |
| ↳  他の投稿者の下書きを編集する | `editPeerEntryDrafts:[SectionID]` |
|       ↳  他の投稿者の下書きを投稿する | `publishPeerEntryDrafts:[SectionID]` |
|       ↳  他の投稿者の下書きを削除する | `deletePeerEntryDrafts:[SectionID]` |
| _「グローバル設定名」_ を編集する | `editGlobalSet:[GlobalSetID]` |
| _「カテゴリグループ名」_ を編集する | `editCategories:[CategoryGroupID]` |
| _「アセットソース名」_ を表示する | `viewVolume:[VolumeID]` |
| ↳  アップロード | `saveAssetInVolume:[VolumeID]` |
| ↳  サブフォルダを作成する | `createFoldersInVolume:[VolumeID]` |
| ↳  ファイルとフォルダーを削除 | `deleteFilesAndFoldersInVolume:[VolumeID]` |

