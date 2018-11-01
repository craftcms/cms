# ユーザー

Craft はシステムのすべてのメンバーアカウントを「ユーザー」と呼びます。

最初のユーザーアカウントは、[インストール](installation.md)中に作成されます。Solo エディションを使い続けるなら、あなたが作成可能な唯一のアカウントとなります。さらに必要であれば、追加のユーザーアカウントを提供する Pro エディションにアップグレードできます。

## 管理者アカウント

管理者アカウントは、明示的な権限がない次のことを含め、 Craft 内のすべての操作を確実に行うことができる特別なアカウントです。

* 設定セクションに含まれるすべてのこと
* 他のユーザーを管理者にする（Craft Pro のみ）
* 他の管理者を管理する（Craft Pro のみ）

インストール中に作成したユーザーアカウントが、デフォルトで管理者になります。

> 管理者が行うことができるダメージの量を考えると、新しい管理者アカウントの作成は慎重に行うことを強くお勧めします。彼（彼女）らが自分が実行することの意味を理解できていると確信できる場合のみに留めてください。

## ユーザーグループ

Craft Pro を使っている場合、サイトのユーザーアカウントを整理したり、権限を一括設定するためにユーザーグループを作成することができます。

新しいユーザーグループを作るには、「設定 > ユーザー」に移動し、「新しいユーザーグループ」ボタンをクリックします。グループには、名前とハンドルに加え、グループに含まれるすべてのユーザーに与える権限をセットすることができます。

グループの作成後は、アカウント設定の「権利」タブをクリックして、ユーザーをグループに割り当てることができます。

## 権限

Craft Pro では、コントロールパネルにアクセスしたり、特定セクションのコンテンツを編集するといった権限をユーザーやグループに許可できます。これらの権限はユーザーアカウントと同様にユーザーグループにも直接適用できます。ユーザーグループに権限を適用すると、そのグループに所属するすべてのユーザーがそれを継承します。

Craft の権限は次の通りです。

| 権限 | ハンドル |
| ---------- | ------ |
| システムがオフの場合にサイトにアクセスする | `accessSiteWhenSystemIsOff` |
| 管理画面にアクセスする | `accessCp` |
| ↳&nbsp; システムがオフの場合に管理画面にアクセスする | `accessCpWhenSystemIsOff` |
| ↳&nbsp; Craft CMS 起動とプラグインのアップデート | `performUpdates` |
| ↳&nbsp; _「プラグイン名」_ のアクセス | `accessPlugin-[PluginHandle]` |
| ユーザーを編集する | `editUsers` |
| ↳&nbsp; ユーザーを登録する | `registerUsers` |
| ↳&nbsp; ユーザー権限を割り当てる | `assignUserPermissions` |
| ↳&nbsp; ユーザーを管理 | `administrateUsers` |
| ユーザーを削除する | `deleteUsers` |
| _「サイト名」_ を編集する | `editSite:[SiteID]` |
| エントリを編集する | `editEntries:[SectionID]` |
| ↳&nbsp; エントリを作る | `createEntries:[SectionID]` |
| ↳&nbsp; ライブの変更を発表する | `publishEntries:[SectionID]` |
| ↳&nbsp; エントリを削除する | `deleteEntries:[SectionID]` |
| ↳&nbsp; 他の投稿者のエントリを編集する | `editPeerEntries:[SectionID]` |
| &nbsp;&nbsp;&nbsp; ↳&nbsp; 他の作成者の入力のためライブを変更する | `publishPeerEntries:[SectionID]` |
| &nbsp;&nbsp;&nbsp; ↳&nbsp; 他の投稿者のエントリを削除する | `deletePeerEntries:[SectionID]` |
| ↳&nbsp; 他の投稿者の下書きを編集する | `editPeerEntryDrafts:[SectionID]` |
| &nbsp;&nbsp;&nbsp; ↳&nbsp; 他の投稿者の下書きを投稿する | `publishPeerEntryDrafts:[SectionID]` |
| &nbsp;&nbsp;&nbsp; ↳&nbsp; 他の投稿者の下書きを削除する | `deletePeerEntryDrafts:[SectionID]` |
| _「グローバル設定名」_ を編集する | `editGlobalSet:[GlobalSetID]` |
| _「カテゴリグループ名」_ を編集する | `editCategories:[CategoryGroupID]` |
| _「アセットソース名」_ を表示する | `viewVolume:[VolumeID]` |
| ↳&nbsp; アップロード | `saveAssetInVolume:[VolumeID]` |
| ↳&nbsp; サブフォルダを作成する | `createFoldersInVolume:[VolumeID]` |
| ↳&nbsp; ファイルとフォルダーを削除 | `deleteFilesAndFoldersInVolume:[VolumeID]` |

## 一般登録

Craft Pro には、一般ユーザーの登録を許可するオプションがあり、デフォルトで無効化されています。

一般登録を有効にするには、「設定 > ユーザー > 設定」に移動し、「一般登録を許可しますか？」をチェックします。チェックすると、Craft が一般登録したユーザーを割り当てるデフォルトのユーザーグループを選択できるようになります。

サイトに一般ユーザーの登録を許可する設定を行ったら、最後のステップとしてフロントエンドに[ユーザー登録フォーム](dev/examples/user-registration-form.md)を作成します。

