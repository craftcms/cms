# エントリのクエリパラメータ

| パラメータ | 受け入れる値 | 説明 |
| --------------------- | ------------------------------------ | ----------------------------------------------------------------------------------------- |
| `addOrderBy` | `string|array|Expression` | 追加の ORDER BY カラムをクエリに付加 |
| `addSelect` | `string|array|Expression` | クエリの SELECT パートにカラムを追加 |
| `after` | `DateTime|string` | 指定した投稿日以降のエントリだけを許可するよう `postDate` パラメータをセット |
| `ancestorDist` | `int|null` | `ancestorOf` によって分割される結果の最大レベル |
| `ancestorOf` | `int|ElementInterface|null` | 祖先にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `andWhere` | `array` | 既成のものに、追加の WHERE 条件文を付加 |
| `archived` | `bool` | アーカイブされたエレメントだけを返すかどうか |
| `asArray` | `bool` | それぞれのエレメントを配列として返すかどうか |
| `authorGroup` | `string|string[]|null` | 指定されたユーザーグループのハンドルに基づき、`authorGroupId` パラメータをセット |
| `authorGroupId` | `int|int[]|null` | 結果となるエントリの投稿者が含まれるべきユーザーグループ ID |
| `authorId` | `int|int[]|null` | 結果となるエントリの投稿者が持つべきユーザー ID |
| `before` | `DateTime|string` | 指定した投稿日以前のエントリだけを許可するよう `postDate` パラメータをセット |
| `contentTable` | `string|null` | このクエリによって結合されるコンテンツテーブル |
| `criteriaAttributes` |  |
| `customFields` | `FieldInterface[]|null` | このクエリに関係しているかもしれないフィールド |
| `dateCreated` | `mixed` | 結果となるエレメントが作成されていなければならないとき |
| `dateUpdated` | `mixed` | 結果となるエレメントが最後に更新されていなければならないとき |
| `descendantDist` | `int|null` | `descendantOf` によって分割される結果の最大レベル |
| `descendantOf` | `int|ElementInterface|null` | 子孫にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `editable` | `bool` | ユーザーが編集権限を持つエントリだけを返すかどうか |
| `elementType` | `string|null` | `ElementInterface` クラスの名前 |
| `enabledForSite` | `bool` | 選択したサイトでエレメントが利用可能かどうか |
| `expiryDate` | `mixed` | 結果となるエントリが持つべき有効期限日 |
| `fixedOrder` | `bool` | 結果を `id` で指定された並び順で返すかどうか |
| `getCriteria` |  |
| `getRawSql` | `YiiConnection|null` | `createCommand()->getRawSql()` のショートカット |
| `getTablesUsedInFrom` |  |
| `id` | `int|int[]|false|null` | エレメントの ID |
| `indexBy` | `string|callable` | クエリ結果のインデックスに利用するカラム名 |
| `level` | `mixed` | ストラクチャーに含まれるエレメントのレベル |
| `limit` | `int|Expression` | 返されるレコードの最大数 |
| `nextSiblingOf` | `int|ElementInterface|null` | 次の兄弟にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `offset` | `int|Expression` | レコードが返される場所からのゼロベースのオフセット |
| `orWhere` | `array` | 既成のものに、追加の WHERE 条件文を付加 |
| `orderBy` | `array` | クエリ結果をどのようにソートするか |
| `positionedAfter` | `int|ElementInterface|null` | 後のポジションにあたるものを結果として受け取りたいエレメント（または、その ID） |
| `positionedBefore` | `int|ElementInterface|null` | 前のポジションにあたるものを結果として受け取りたいエレメント（または、その ID） |
| `postDate` | `mixed` | 結果となるエントリが持つべき投稿日 |
| `prevSiblingOf` | `int|ElementInterface|null` | 前の兄弟にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `ref` | `string|string[]|null` | エレメントの識別に利用する参照コード |
| `relatedTo` | `int|array|ElementInterface|null` | エレメントのリレーションの判定基準 |
| `search` | `string|array|SearchQuery|null` | 結果となるエレメントをフィルタするための検索用語 |
| `section` | `string|string[]|Section|null` | 指定されたセクションのハンドルに基づき、`sectionId` パラメータをセット |
| `sectionId` | `int|int[]|null` | 結果となるエントリが含まれるべきセクション ID |
| `select` | `array` | 選択されているカラム |
| `siblingOf` | `int|ElementInterface|null` | 兄弟にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `site` | `string|Site` | 指定されたサイトのハンドルに基づき、`siteId` パラメータをセットÍ |
| `siteId` | `int|null` | 返されるべきエレメントのサイト ID |
| `slug` | `string|string[]|null` | 結果となるエレメントが持つべきスラグ |
| `status` | `string|string[]|null` | 結果となるエレメントが持つべきステータス |
| `structureId` | `int|false|null` | structureelements デーブルの結合に利用されるストラクチャー ID |
| `title` | `string|string[]|null` | 結果となるエレメントが持つべきタイトル |
| `type` | `string|string[]|EntryType|null` | 指定された入力タイプのハンドルに基づき、`typeId` パラメータをセット |
| `typeId` | `int|int[]|null` | 結果となるエントリが持つべき入力タイプ ID |
| `uid` | `string|string[]|null` | エレメントの UID |
| `uri` | `string|string[]|null` | 結果となるエレメントが持つべき URI |
| `where` | `string|array` | クエリの条件 |
| `with` | `string|array|null` | eager-loading の宣言 |
| `withStructure` | `bool|null` | クエリ内のエレメントの構造データが自動的に LEFT JOIN されるべきかどうか |

