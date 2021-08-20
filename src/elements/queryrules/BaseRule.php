<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\queryrules;

use craft\elements\db\ElementQuery;
use yii\base\BaseObject;

/**
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5
 */
abstract class BaseRule extends BaseObject
{
    public $elementClass;

    /**
     * Determines the basic template for conditionals based on this rule.
     * Each type has some basic defaults that can be overridden.
     * You can include a custom component as long as you provide a vue component that interacts properly with v-model (see more details in the Vue docs here: https://vuejs.org/v2/guide/components.html#Form-Input-Components-using-Custom-Events).
     *
     * @return string Possible values include "text", "numeric", "custom", "radio", "checkbox", "select", "custom-component"
     */
    public function getType()
    {
        return 'string';
    }

    /**
     * Optional. Override the operators for the rule type. If not defined, sensible defaults are provided.
     *
     * @return array
     */
    public function getOperators(): array
    {
        // We leave this as '=' since we will be applying the value in `modifyQuery()`
        return ['='];
    }

    /**
     * Required for checkbox, radio, and select rule types.
     * Defines the individual radio and checkbox inputs, or select options.
     * Select inputs will default to the first item in the `choices` list; if you want an empty option you must provide it yourself.
     *
     * @return array|\string[][]
     */
    public function getChoices(): array
    {
        // optional
        // return [];
        return [
            ['label' => 'Foo', 'value' => '1'],
            ['label' => 'Foo', 'value' => '1'],
            ['label' => 'Foo', 'value' => '1']
        ];
    }

    /**
     * Optional. Allows you to set a dropdown as the left side of the conditional. For example, you may have a rule for 'Address' that lets the user choose which kind of address the rule is referring to:
     * operands: ['Home Address','Work Address']
     *
     * @return int[]|string[]
     */
    public function getOperands(): array
    {
        return [];
    }

    /**
     * Optional. Override the HTML input type for a rule type.
     *
     * @return string|null
     */
    public function inputType()
    {
        return null;
    }

    /**
     * TODO: Need to be able to call json_encode on this?
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return '';
    }

    /**
     * If this type is custom-component, provide the vue component
     *
     * @return string|null
     */
    public function getComponent()
    {
        return null;
    }
}