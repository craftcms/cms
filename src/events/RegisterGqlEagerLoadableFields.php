<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterGqlEagerLoadableFields class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class RegisterGqlEagerLoadableFields extends Event
{
    /**
     * @var array List of additional eager-loadable fields to be used as reference, when parsing the GraphQL query and building the eager-load condition array.
     *
     * The field list is an array, where the key is the field name to be allowed. The value is a list of allowed occurrences for the node in the form of an array.
     * Occurrence is a class name of a relational field that the containing field must be an instance of. For example, the "uploader" field can be eager-loaded only
     * for Asset fields, so the occurrence will be `craft\fields\Assets`.
     *
     * If a field is encountered in a GraphQL query outside of a relational field scope, it is always allowed.
     *
     * There are two special values you can use:
     *  - '*' can be used both as a key or value and is used to configure the field to be allowed anywhere.
     *  - 'canBeAliased' is used to configure whether the GraphQL field alias (if any) can be used when constructing the eager-loading parameters. Defaults to `true`.
     *    To invert the behavior, use `canBeAliased` as a key and set the value to the required boolean value.
     */
    public array $fieldList = [];
}
