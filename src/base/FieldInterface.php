<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\db\ElementQueryInterface;
use craft\models\GqlSchema;
use craft\records\FieldGroup;
use GraphQL\Type\Definition\Type;
use yii\validators\Validator;

/**
 * FieldInterface defines the common interface to be implemented by field classes.
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[FieldTrait]].
 *
 * @mixin FieldTrait
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface FieldInterface extends SavableComponentInterface
{
    /**
     * Returns whether this field has a column in the content table.
     *
     * ::: warning
     * If you set this to `false`, you will be on your own in terms of saving and retrieving your field values.
     * :::
     *
     * @return bool
     */
    public static function hasContentColumn(): bool;

    /**
     * Returns which translation methods the field supports.
     *
     * This method should return an array with at least one of the following values:
     * - 'none' (values will always be copied to other sites)
     * - 'language' (values will be copied to other sites with the same language)
     * - 'site' (values will never be copied to other sites)
     * - 'custom' (values will be copied/not copied depending on a custom translation key)
     *
     * @return string[]
     * @see getTranslationKey()
     */
    public static function supportedTranslationMethods(): array;

    /**
     * Returns the PHPDoc type this field’s values will have.
     *
     * It will be used by the generated `CustomFieldBehavior` class.
     *
     * If the values can be of more than one type, return multiple types separated by `|`s.
     *
     * ```php
     * public static function phpDocType()
     * {
     *      return 'int|mixed|\\craft\\elements\\db\\ElementQuery';
     * }
     * ```
     *
     * @return string
     * @since 3.2.0
     */
    public static function valueType(): string;

    /**
     * Returns the column type that this field should get within the content table.
     *
     * This method will only be called if [[hasContentColumn()]] returns true.
     *
     * @return string The column type. [[\yii\db\QueryBuilder::getColumnType()]] will be called
     * to convert the give column type to the physical one. For example, `string` will be converted
     * as `varchar(255)` and `string(100)` becomes `varchar(100)`. `not null` will automatically be
     * appended as well.
     * @see \yii\db\QueryBuilder::getColumnType()
     */
    public function getContentColumnType(): string;

    /**
     * Returns whether the field should be shown as translatable in the UI.
     *
     * Note this method has no effect on whether the field’s value will get copied over to other
     * sites when the element is actually getting saved. That is determined by [[getTranslationKey()]].
     *
     * @param ElementInterface|null $element The element being edited
     * @return bool
     */
    public function getIsTranslatable(ElementInterface $element = null): bool;

    /**
     * Returns the description of this field’s translation support.
     *
     * @param ElementInterface|null $element The element being edited
     * @return string|null
     * @since 3.4.0
     */
    public function getTranslationDescription(ElementInterface $element = null);

    /**
     * Returns the field’s translation key, based on a given element.
     *
     * When saving an element on a multi-site Craft install, if `$propagate` is `true` for [[\craft\services\Elements::saveElement()]],
     * then `getTranslationKey()` will be called for each custom field and for each site the element should be propagated to.
     * If the method returns the same value as it did for the initial site, then the initial site’s value will be copied over
     * to the target site.
     *
     * @param ElementInterface $element The element being saved
     * @return string The translation key
     */
    public function getTranslationKey(ElementInterface $element): string;

    /**
     * Returns whether the field should use a `<fieldset>` + `<legend>` instead of a `<div>` + `<label>`.
     *
     * @return bool
     * @since 3.6.0
     */
    public function useFieldset(): bool;

    /**
     * Returns the field’s input HTML.
     *
     * An extremely simple implementation would be to directly return some HTML:
     *
     * ```php
     * return '<textarea name="'.$name.'">'.$value.'</textarea>';
     * ```
     *
     * For more complex inputs, you might prefer to create a template, and render it via
     * [[\craft\web\View::renderTemplate()]]. For example, the following code would render a template located at
     * `path/to/myplugin/templates/_fieldinput.html`, passing the `$name` and `$value` variables to it:
     *
     * ```php
     * return Craft::$app->view->renderTemplate('myplugin/_fieldinput', [
     *     'name'  => $name,
     *     'value' => $value
     * ]);
     * ```
     *
     * If you need to tie any JavaScript code to your input, it’s important to know that any `name` and `id`
     * attributes within the returned HTML will probably get [[\craft\helpers\Html::namespaceHtml()|namespaced]],
     * however your JavaScript code will be left untouched.
     * For example, if getInputHtml() returns the following HTML:
     *
     * ```html
     * <textarea id="foo" name="foo"></textarea>
     * <script type="text/javascript">
     *     var textarea = document.getElementById('foo');
     * </script>
     * ```
     *
     * …then it might actually look like this before getting output to the browser:
     *
     * ```html
     * <textarea id="namespace-foo" name="namespace[foo]"></textarea>
     * <script type="text/javascript">
     *     var textarea = document.getElementById('foo');
     * </script>
     * ```
     *
     * As you can see, that JavaScript code will not be able to find the textarea, because the textarea’s `id`
     * attribute was changed from `foo` to `namespace-foo`.
     * Before you start adding `namespace-` to the beginning of your element ID selectors, keep in mind that the actual
     * namespace is going to change depending on the context. Often they are randomly generated. So it’s not quite
     * that simple.
     *
     * Thankfully, Craft provides a couple handy methods that can help you deal with this:
     *
     * - [[\craft\helpers\Html::id()]] will generate a valid element ID from an input name.
     * - [[\craft\web\View::namespaceInputId()]] will give you the namespaced version of a given ID.
     * - [[\craft\web\View::namespaceInputName()]] will give you the namespaced version of a given input name.
     *
     * So here’s what a getInputHtml() method that includes field-targeting JavaScript code might look like:
     *
     * ```php
     * public function getInputHtml($value, $element)
     * {
     *     // Generate a valid ID based on the input name
     *     $id = craft\helpers\Html::id($name);
     *     // Figure out what that ID is going to be namespaced into
     *     $namespacedId = Craft::$app->view->namespaceInputId($id);
     *     // Render and return the input template
     *     return Craft::$app->view->renderTemplate('myplugin/_fieldinput', [
     *         'name' => $name,
     *         'id' => $id,
     *         'namespacedId' => $namespacedId,
     *         'value' => $value,
     *     ]);
     * }
     * ```
     *
     * And the _fieldinput.html template might look like this:
     *
     * ```twig
     * <textarea id="{{ id }}" name="{{ name }}">{{ value }}</textarea>
     * <script type="text/javascript">
     *     var textarea = document.getElementById('{{ namespacedId }}');
     * </script>
     * ```
     *
     * The same principles also apply if you’re including your JavaScript code with
     * [[\craft\web\View::registerJs()]].
     *
     * @param mixed $value The field’s value. This will either be the [[normalizeValue()|normalized value]],
     * raw POST data (i.e. if there was a validation error), or null
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return string The input HTML.
     */
    public function getInputHtml($value, ElementInterface $element = null): string;

    /**
     * Returns a static (non-editable) version of the field’s input HTML.
     *
     * This function is called to output field values when viewing element drafts.
     *
     * @param mixed $value The field’s value
     * @param ElementInterface $element The element the field is associated with
     * @return string The static version of the field’s input HTML
     */
    public function getStaticHtml($value, ElementInterface $element): string;

    /**
     * Returns the validation rules for an element with this field.
     *
     * Rules should be defined in the array syntax required by [[\yii\base\Model::rules()]],
     * with one difference: you can skip the first argument (the attribute list).
     *
     * ```php
     * [
     *     // explicitly specify the field attribute
     *     [$this->handle, 'string', 'min' => 3, 'max' => 12],
     *     // skip the field attribute
     *     ['string', 'min' => 3, 'max' => 12],
     *     // you can only pass the validator class name/handle if not setting any params
     *     'bool',
     * ]
     * ```
     *
     * To register validation rules that should only be enforced for _live_ elements,
     * set the rule [scenario](https://www.yiiframework.com/doc/guide/2.0/en/structure-models#scenarios)
     * to `live`:
     *
     * ```php
     * [
     *     ['string', 'min' => 3, 'max' => 12, 'on' => \craft\base\Element::SCENARIO_LIVE],
     * ]
     * ```
     *
     * @return array
     */
    public function getElementValidationRules(): array;

    /**
     * Returns whether the given value should be considered “empty” to a validator.
     *
     * @param mixed $value The field’s value
     * @param ElementInterface $element The element the field is associated with, if there is one
     * @return bool Whether the value should be considered “empty”
     * @see Validator::$isEmpty
     */
    public function isValueEmpty($value, ElementInterface $element): bool;

    /**
     * Returns the search keywords that should be associated with this field.
     *
     * The keywords can be separated by commas and/or whitespace; it doesn’t really matter. [[\craft\services\Search]]
     * will be able to find the individual keywords in whatever string is returned, and normalize them for you.
     *
     * @param mixed $value The field’s value
     * @param ElementInterface $element The element the field is associated with, if there is one
     * @return string A string of search keywords.
     */
    public function getSearchKeywords($value, ElementInterface $element): string;

    /**
     * Normalizes the field’s value for use.
     *
     * This method is called when the field’s value is first accessed from the element. For example, the first time
     * `element.myFieldHandle` is called from a template, or right before [[getInputHtml()]] is called. Whatever
     * this method returns is what `element.myFieldHandle` will likewise return, and what [[getInputHtml()]]’s and
     * [[serializeValue()]]’s $value arguments will be set to.
     *
     * The value passed into this method will vary depending on the context.
     *
     * - If a new, unsaved element is being edited for the first time (such as an entry within a Quick Post widget
     *   on the Dashboard), the value will be `null`.
     * - If an element is currently being saved, the value will be the field’s POST data.
     * - If an existing element was retrieved from the database, the value will be whatever is stored in the field’s
     *   `content` table column. (Or if the field doesn’t have a `content` table column per [[hasContentColumn()]],
     *   the value will be `null`.)
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return mixed The prepared field value
     */
    public function normalizeValue($value, ElementInterface $element = null);

    /**
     * Prepares the field’s value to be stored somewhere, like the content table.
     *
     * Data types that are JSON-encodable are safe (arrays, integers, strings, booleans, etc).
     * Whatever this returns should be something [[normalizeValue()]] can handle.
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return mixed The serialized field value
     */
    public function serializeValue($value, ElementInterface $element = null);

    /**
     * Modifies an element query.
     *
     * This method will be called whenever elements are being searched for that
     * may have this field assigned to them. If the method returns `false`, the
     * query will be stopped before it ever gets a chance to execute.
     *
     * @param ElementQueryInterface $query The element query
     * @param mixed $value The value that was set on this field’s corresponding
     * element query param, if any.
     * @return null|false `false` in the event that the method is sure that no
     * elements are going to be found.
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value);

    /**
     * Modifies an element index query.
     *
     * This method will be called whenever an element index is being loaded,
     * which contains a column for this field.
     *
     * @param ElementQueryInterface $query The element query
     * @since 3.0.9
     */
    public function modifyElementIndexQuery(ElementQueryInterface $query);

    /**
     * Sets whether the field is fresh.
     *
     * @param bool|null $isFresh Whether the field is fresh.
     */
    public function setIsFresh(bool $isFresh = null);

    /**
     * Returns the field’s group.
     *
     * @return FieldGroup|null
     */
    public function getGroup();

    /**
     * Returns whether the field should be included in the given GraphQL schema.
     *
     * @param GqlSchema
     * @return bool
     * @since 3.6.0
     */
    public function includeInGqlSchema(GqlSchema $schema): bool;

    /**
     * Returns the GraphQL type to be used for this field type.
     *
     * @return Type|array
     * @since 3.3.0
     */
    public function getContentGqlType();

    /**
     * Returns the GraphQL type to be used as an argument in mutations for this field type.
     *
     * @return Type|array
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType();

    /**
     * Returns the GraphQL type to be used as an argument in queries for this field type.
     *
     * @return Type|array
     * @since 3.5.0
     */
    public function getContentGqlQueryArgumentType();

    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before an element is saved.
     *
     * @param ElementInterface $element The element that is about to be saved
     * @param bool $isNew Whether the element is brand new
     * @return bool Whether the element should be saved
     */
    public function beforeElementSave(ElementInterface $element, bool $isNew): bool;

    /**
     * Performs actions after the element has been saved.
     *
     * @param ElementInterface $element The element that was just saved
     * @param bool $isNew Whether the element is brand new
     */
    public function afterElementSave(ElementInterface $element, bool $isNew);

    /**
     * Performs actions after the element has been fully saved and propagated to other sites.
     *
     * @param ElementInterface $element The element that was just saved and propagated
     * @param bool $isNew Whether the element is brand new
     * @since 3.2.0
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew);

    /**
     * Performs actions before an element is deleted.
     *
     * @param ElementInterface $element The element that is about to be deleted
     * @return bool Whether the element should be deleted
     */
    public function beforeElementDelete(ElementInterface $element): bool;

    /**
     * Performs actions after the element has been deleted.
     *
     * @param ElementInterface $element The element that was just deleted
     */
    public function afterElementDelete(ElementInterface $element);

    /**
     * Performs actions before an element is restored.
     *
     * @param ElementInterface $element The element that is about to be restored
     * @return bool Whether the element should be restored
     * @since 3.1.0
     */
    public function beforeElementRestore(ElementInterface $element): bool;

    /**
     * Performs actions after the element has been restored.
     *
     * @param ElementInterface $element The element that was just restored
     * @since 3.1.0
     */
    public function afterElementRestore(ElementInterface $element);
}
