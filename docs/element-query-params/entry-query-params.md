# Entry Query Params

| Param                 | Accepts                              | Description
| --------------------- | ------------------------------------ | -----------------------------------------------------------------------------------------
| `addOrderBy`          | `string|array|Expression`            | Adds additional ORDER BY columns to the query
| `addSelect`           | `string|array|Expression`            | Add more columns to the SELECT part of the query
| `after`               | `DateTime|string`                    | Sets the `postDate` param to only allow entries whose Post Date is after the given value
| `ancestorDist`        | `int|null`                           | The maximum number of levels that results may be separated from `ancestorOf`
| `ancestorOf`          | `int|ElementInterface|null`          | The element (or its ID) that results must be an ancestor of
| `andWhere`            | `array`                              | Adds an additional WHERE condition to the existing one
| `archived`            | `bool`                               | Whether to return only archived elements
| `asArray`             | `bool`                               | Whether to return each element as an array
| `authorGroup`         | `string|string[]|null`               | Sets the `authorGroupId` param based on a given user group(s)’s handle(s)
| `authorGroupId`       | `int|int[]|null`                     | The user group ID(s) that the resulting entries’ authors must be in
| `authorId`            | `int|int[]|null`                     | The user ID(s) that the resulting entries’ authors must have
| `before`              | `DateTime|string`                    | Sets the `postDate` param to only allow entries whose Post Date is before the given value
| `contentTable`        | `string|null`                        | The content table that will be joined by this query
| `criteriaAttributes`  |                                      |
| `customFields`        | `FieldInterface[]|null`              | The fields that may be involved in this query
| `dateCreated`         | `mixed`                              | When the resulting elements must have been created
| `dateUpdated`         | `mixed`                              | When the resulting elements must have been last updated
| `descendantDist`      | `int|null`                           | The maximum number of levels that results may be separated from `descendantOf`
| `descendantOf`        | `int|ElementInterface|null`          | The element (or its ID) that results must be a descendant of
| `editable`            | `bool`                               | Whether to only return entries that the user has permission to edit
| `elementType`         | `string|null`                        | The name of the `ElementInterface` class
| `enabledForSite`      | `bool`                               | Whether the elements must be enabled for the chosen site
| `expiryDate`          | `mixed`                              | The Expiry Date that the resulting entries must have
| `fixedOrder`          | `bool`                               | Whether results should be returned in the order specified by `id`
| `getCriteria`         |                                      |
| `getRawSql`           | `YiiConnection|null`                 | Shortcut for `createCommand()->getRawSql()`
| `getTablesUsedInFrom` |                                      |
| `id`                  | `int|int[]|false|null`               | The element ID(s)
| `indexBy`             | `string|callable`                    | The name of the column by which the query results should be indexed by
| `level`               | `mixed`                              | The element’s level within the structure
| `limit`               | `int|Expression`                     | Maximum number of records to be returned
| `nextSiblingOf`       | `int|ElementInterface|null`          | The element (or its ID) that the result must be the next sibling of
| `offset`              | `int|Expression`                     | Zero-based offset from where the records are to be returned
| `orWhere`             | `array`                              | Adds an additional WHERE condition to the existing one
| `orderBy`             | `array`                              | How to sort the query results
| `positionedAfter`     | `int|ElementInterface|null`          | The element (or its ID) that the results must be positioned after
| `positionedBefore`    | `int|ElementInterface|null`          | The element (or its ID) that the results must be positioned before
| `postDate`            | `mixed`                              | The Post Date that the resulting entries must have
| `prevSiblingOf`       | `int|ElementInterface|null`          | The element (or its ID) that the result must be the previous sibling of
| `ref`                 | `string|string[]|null`               | The reference code(s) used to identify the element(s)
| `relatedTo`           | `int|array|ElementInterface|null`    | The element relation criteria
| `search`              | `string|array|SearchQuery|null`      | The search term to filter the resulting elements by
| `section`             | `string|string[]|Section|null`       | Sets the `sectionId` param based on a given section(s)’s handle(s)
| `sectionId`           | `int|int[]|null`                     | The section ID(s) that the resulting entries must be in
| `select`              | `array`                              | The columns being selected
| `siblingOf`           | `int|ElementInterface|null`          | The element (or its ID) that the results must be a sibling of
| `site`                | `string|Site`                        | Sets the `siteId` param based on a given site(s)’s handle
| `siteId`              | `int|null`                           | The site ID that the elements should be returned in
| `slug`                | `string|string[]|null`               | The slug that resulting elements must have
| `status`              | `string|string[]|null`               | The status(es) that the resulting elements must have
| `structureId`         | `int|false|null`                     | The structure ID that should be used to join in the structureelements table
| `title`               | `string|string[]|null`               | The title that resulting elements must have
| `type`                | `string|string[]|EntryType|null`     | Sets the `typeId` param based on a given entry type(s)’s handle(s)
| `typeId`              | `int|int[]|null`                     | The entry type ID(s) that the resulting entries must have
| `uid`                 | `string|string[]|null`               | The element UID(s)
| `uri`                 | `string|string[]|null`               | The URI that the resulting element must have
| `where`               | `string|array`                       | Query condition
| `with`                | `string|array|null`                  | The eager-loading declaration
| `withStructure`       | `bool|null`                          | Whether element structure data should automatically be left-joined into the query
