# アセットのクエリパラメータ

| パラメータ | 受け入れる値 | 説明 |
| --------------------- | ------------------------------------ | --------------------------------------------------------------------------------- |
| `addOrderBy` | `string|array|Expression` | 追加の ORDER BY カラムをクエリに付加 |
| `addSelect` | `string|array|Expression` | クエリの SELECT パートにカラムを追加 |
| `ancestorDist` | `int|null` | `ancestorOf` によって分割される結果の最大レベル |
| `ancestorOf` | `int|ElementInterface|null` | 祖先にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `andWhere` | `array` | 既成のものに、追加の WHERE 条件文を付加 |
| `archived` | `bool` | アーカイブされたエレメントだけを返すかどうか |
| `asArray` | `bool` | それぞれのエレメントを配列として返すかどうか |
| `contentTable` | `string|null` | このクエリによって結合されるコンテンツテーブル |
| `criteriaAttributes` |  |
| `customFields` | `FieldInterface[]|null` | このクエリに関係しているかもしれないフィールド |
| `dateCreated` | `mixed` | 結果となるエレメントが作成されていなければならないとき |
| `dateModified` | `mixed` | 結果となるアセットが持つべき変更日 |
| `dateUpdated` | `mixed` | 結果となるエレメントが最後に更新されていなければならないとき |
| `descendantDist` | `int|null` | `descendantOf` によって分割される結果の最大レベル |
| `descendantOf` | `int|ElementInterface|null` | 子孫にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `elementType` | `string|null` | `ElementInterface` クラスの名前 |
| `enabledForSite` | `bool` | 選択したサイトでエレメントが利用可能かどうか |
| `filename` | `string|string[]|null` | 結果となるアセットが持つべきファイル名 |
| `fixedOrder` | `bool` | 結果を `id` で指定された並び順で返すかどうか |
| `folderId` | `int|int[]|null` | 結果となるアセットが含まれるべきアセットフォルダ ID |
| `getCriteria` |  |
| `getRawSql` | `YiiConnection|null` | `createCommand()->getRawSql()` のショートカット |
| `getTablesUsedInFrom` |  |
| `height` | `int|null` | 結果となるアセットが持つべき（ピクセル単位の）高さ |
| `id` | `int|int[]|false|null` | エレメントの ID |
| `includeSubfolders` | `bool` | クエリが `folderId` のサブフォルダを検索するかどうか |
| `indexBy` | `string|callable` | クエリ結果のインデックスに利用するカラム名 |
| `kind` | `string|string[]|null` | 結果となるアセットのファイルの種類 |
| `level` | `mixed` | ストラクチャーに含まれるエレメントのレベル |
| `limit` | `int|Expression` | 返されるレコードの最大数 |
| `nextSiblingOf` | `int|ElementInterface|null` | 次の兄弟にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `offset` | `int|Expression` | レコードが返される場所からのゼロベースのオフセット |
| `orWhere` | `array` | 既成のものに、追加の WHERE 条件文を付加 |
| `orderBy` | `array` | クエリ結果をどのようにソートするか |
| `positionedAfter` | `int|ElementInterface|null` | 後のポジションにあたるものを結果として受け取りたいエレメント（または、その ID） |
| `positionedBefore` | `int|ElementInterface|null` | 前のポジションにあたるものを結果として受け取りたいエレメント（または、その ID） |
| `prevSiblingOf` | `int|ElementInterface|null` | 前の兄弟にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `ref` | `string|string[]|null` | エレメントの識別に利用する参照コード |
| `relatedTo` | `int|array|ElementInterface|null` | エレメントのリレーションの判定基準 |
| `search` | `string|array|SearchQuery|null` | 結果となるエレメントをフィルタするための検索用語 |
| `select` | `array` | 選択されているカラム |
| `siblingOf` | `int|ElementInterface|null` | 兄弟にあたるものを結果として受け取りたいエレメント（または、その ID） |
| `site` | `string|Site` | 指定されたサイトのハンドルに基づき、`siteId` パラメータをセットÍ |
| `siteId` | `int|null` | 返されるべきエレメントのサイト ID |
| `size` | `int|null` | 結果となるアセットが持つべき（バイト単位の）サイズ |
| `slug` | `string|string[]|null` | 結果となるエレメントが持つべきスラグ |
| `status` | `string|string[]|null` | 結果となるエレメントが持つべきステータス |
| `structureId` | `int|false|null` | structureelements デーブルの結合に利用されるストラクチャー ID |
| `title` | `string|string[]|null` | 結果となるエレメントが持つべきタイトル |
| `uid` | `string|string[]|null` | エレメントの UID |
| `uri` | `string|string[]|null` | 結果となるエレメントが持つべき URI |
| `volume` | `string|string[]|Volume|null` | 指定されたボリュームのハンドルに基づき、`volumeId` パラメータをセット |
| `volumeId` | `int|int[]|null` | 結果となるアセットが含まれるべきアセット ID |
| `where` | `string|array` | クエリの条件 |
| `width` | `int|null` | 結果となるアセットが持つべき（ピクセル単位の）幅 |
| `with` | `string|array|null` | eager-loading の宣言 |
| `withStructure` | `bool|null` | クエリ内のエレメントの構造データが自動的に LEFT JOIN されるべきかどうか |
| `withTransforms` | `string|array|null` | 存在する場合に、 eager-loaded する必要があるアセット変換インデックス |

