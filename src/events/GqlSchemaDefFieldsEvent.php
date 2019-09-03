<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * GqlSchemaDefFieldsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3
 */
class GqlSchemaDefFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The Type being defined in the Schema
     */
    public $typeInSchema = 'unset';

    /**
     * @var array The Schema fields being defined for the Type
     */
    public $fields = [];

    /**
     * @var array The Schema fields being additionally defined for the Type
     */
    public $fieldsToChange = [];
}
