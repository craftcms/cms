# Asset Query Params

| Param                 | Accepts                              | Description
| --------------------- | ------------------------------------ | ---------------------------------------------------------------------------------
| `addOrderBy`          | `string|array|Expression`            | Adds additional ORDER BY columns to the query
| `addSelect`           | `string|array|Expression`            | Add more columns to the SELECT part of the query
| `ancestorDist`        | `int|null`                           | The maximum number of levels that results may be separated from `ancestorOf`
| `ancestorOf`          | `int|ElementInterface|null`          | The element (or its ID) that results must be an ancestor of
| `andWhere`            | `array`                              | Adds an additional WHERE condition to the existing one
| `archived`            | `bool`                               | Whether to return only archived elements
| `asArray`             | `bool`                               | Whether to return each element as an array
| `contentTable`        | `string|null`                        | The content table that will be joined by this query
| `criteriaAttributes`  |                                      |
| `customFields`        | `FieldInterface[]|null`              | The fields that may be involved in this query
| `dateCreated`         | `mixed`                              | When the resulting elements must have been created
| `dateModified`        | `mixed`                              | The Date Modified that the resulting assets must have
| `dateUpdated`         | `mixed`                              | When the resulting elements must have been last updated
| `descendantDist`      | `int|null`                           | The maximum number of levels that results may be separated from `descendantOf`
| `descendantOf`        | `int|ElementInterface|null`          | The element (or its ID) that results must be a descendant of
| `elementType`         | `string|null`                        | The name of the `ElementInterface` class
| `enabledForSite`      | `bool`                               | Whether the elements must be enabled for the chosen site
| `filename`            | `string|string[]|null`               | The filename(s) that the resulting assets must have
| `fixedOrder`          | `bool`                               | Whether results should be returned in the order specified by `id`
| `folderId`            | `int|int[]|null`                     | The asset folder ID(s) that the resulting assets must be in
| `getCriteria`         |                                      |
| `getRawSql`           | `YiiConnection|null`                 | Shortcut for `createCommand()->getRawSql()`
| `getTablesUsedInFrom` |                                      |
| `height`              | `int|null`                           | The height (in pixels) that the resulting assets must have
| `id`                  | `int|int[]|false|null`               | The element ID(s)
| `includeSubfolders`   | `bool`                               | Whether the query should search the subfolders of `folderId`
| `indexBy`             | `string|callable`                    | The name of the column by which the query results should be indexed by
| `kind`                | `string|string[]|null`               | The file kind(s) that the resulting assets must be
| `level`               | `mixed`                              | The element’s level within the structure
| `limit`               | `int|Expression`                     | Maximum number of records to be returned
| `nextSiblingOf`       | `int|ElementInterface|null`          | The element (or its ID) that the result must be the next sibling of
| `offset`              | `int|Expression`                     | Zero-based offset from where the records are to be returned
| `orWhere`             | `array`                              | Adds an additional WHERE condition to the existing one
| `orderBy`             | `array`                              | How to sort the query results
| `positionedAfter`     | `int|ElementInterface|null`          | The element (or its ID) that the results must be positioned after
| `positionedBefore`    | `int|ElementInterface|null`          | The element (or its ID) that the results must be positioned before
| `prevSiblingOf`       | `int|ElementInterface|null`          | The element (or its ID) that the result must be the previous sibling of
| `ref`                 | `string|string[]|null`               | The reference code(s) used to identify the element(s)
| `relatedTo`           | `int|array|ElementInterface|null`    | The element relation criteria
| `search`              | `string|array|SearchQuery|null`      | The search term to filter the resulting elements by
| `select`              | `array`                              | The columns being selected
| `siblingOf`           | `int|ElementInterface|null`          | The element (or its ID) that the results must be a sibling of
| `site`                | `string|Site`                        | Sets the `siteId` param based on a given site(s)’s handle
| `siteId`              | `int|null`                           | The site ID that the elements should be returned in
| `size`                | `int|null`                           | The size (in bytes) that the resulting assets must have
| `slug`                | `string|string[]|null`               | The slug that resulting elements must have
| `status`              | `string|string[]|null`               | The status(es) that the resulting elements must have
| `structureId`         | `int|false|null`                     | The structure ID that should be used to join in the structureelements table
| `title`               | `string|string[]|null`               | The title that resulting elements must have
| `uid`                 | `string|string[]|null`               | The element UID(s)
| `uri`                 | `string|string[]|null`               | The URI that the resulting element must have
| `volume`              | `string|string[]|Volume|null`        | Sets the `volumeId` param based on a given volume(s)’s handle(s)
| `volumeId`            | `int|int[]|null`                     | The volume ID(s) that the resulting assets must be in
| `where`               | `string|array`                       | Query condition
| `width`               | `int|null`                           | The width (in pixels) that the resulting assets must have
| `with`                | `string|array|null`                  | The eager-loading declaration
| `withStructure`       | `bool|null`                          | Whether element structure data should automatically be left-joined into the query
| `withTransforms`      | `string|array|null`                  | The asset transform indexes that should be eager-loaded, if they exist
