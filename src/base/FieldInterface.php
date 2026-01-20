<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\db\ElementQueryInterface;
use craft\enums\AttributeStatus;
use craft\models\GqlSchema;
use GraphQL\Type\Definition\Type;
use yii\base\Component as YiiComponent;
use yii\db\ExpressionInterface;
use yii\validators\Validator;

/**
 * FieldInterface defines the common interface to be implemented by field classes.
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[FieldTrait]].
 *
 * @mixin FieldTrait
 * @mixin YiiComponent
 * @mixin Model
 * @mixin SavableComponentTrait
 * @phpstan-require-extends Field
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface FieldInterface extends SavableComponentInterface, Chippable, Grippable, CpEditable
{
    /**
     * Returns the field type’s SVG icon.
     *
     * The returned icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`),
     * the path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `src/icons/solid/`.
     *
     * @return string
     * @since 5.0.0
     */
    public static function icon(): string;

    /**
     * Returns whether the field can be included multiple times within a field layout.
     *
     * @return bool
     * @since 5.0.0
     */
    public static function isMultiInstance(): bool;

    /**
     * Returns whether the field can be marked as required.
     *
     * @return bool
     * @since 4.0.0
     */
    public static function isRequirable(): bool;

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
     * public static function valueType(): string
     * {
     *      return 'int|string';
     * }
     * ```
     *
     * @return string
     * @since 5.0.0
     */
    public static function phpType(): string;

    /**
     * Returns the DB data type(s) that fields of this type will store within the `elements_sites.content` column.
     *
     * ```php
     * return \yii\db\Schema::TYPE_STRING;
     * ```
     *
     * Specifying the DB type isn’t strictly necessary, but it enables individual field values to be targeted
     * by functional indexes.
     *
     * If field values will consist of an associative array, each of the array keys can be specified here,
     * so the nested values can receive their own functional indexes.
     *
     * ```php
     * return [
     *     'date' => \yii\db\Schema::TYPE_DATETIME,
     *     'tz' => \yii\db\Schema::TYPE_STRING,
     * ];
     * ```
     *
     * If `null` is returned, the field’s values won’t be stored in the `elements_sites.content` column at all.
     * In that case, the field will be solely responsible for storing and retrieving its own values from
     * [[normalizeValue()]] and [[afterElementSave()]]/[[afterElementPropagate()]].
     *
     * @return string|string[]|null The column type(s).
     * @since 5.0.0
     */
    public static function dbType(): array|string|null;

    /**
     * Returns a query builder-compatible condition for the given field instances, for a user-provided param value.
     *
     * If `false` is returned, an always-false condition will be used.
     *
     * @param static[] $instances The field instances to search
     * @param mixed $value The user-supplied param value
     * @param array $params Additional parameters that should be bound to the query via [[\yii\db\Query::addParams()]]
     * @return array|string|ExpressionInterface|false|null
     * @since 5.0.0
     */
    public static function queryCondition(
        array $instances,
        mixed $value,
        array &$params,
    ): array|string|ExpressionInterface|false|null;

    /**
     * Returns the orientation the field should use (`ltr` or `rtl`).
     *
     * @param ElementInterface|null $element The element being edited
     * @return string
     * @since 3.7.5
     */
    public function getOrientation(?ElementInterface $element): string;

    /**
     * Returns whether the field should be shown as translatable in the UI.
     *
     * Note this method has no effect on whether the field’s value will get copied over to other
     * sites when the element is actually getting saved. That is determined by [[getTranslationKey()]].
     *
     * @param ElementInterface|null $element The element being edited
     * @return bool
     */
    public function getIsTranslatable(?ElementInterface $element): bool;

    /**
     * Returns the description of this field’s translation support.
     *
     * @param ElementInterface|null $element The element being edited
     * @return string|null
     * @since 3.4.0
     */
    public function getTranslationDescription(?ElementInterface $element): ?string;

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
     * Returns whether the field should show a status indicator when modified.
     *
     * @return bool
     * @since 5.8.0
     */
    public function showStatus(): bool;

    /**
     * Returns the status of the field for a given element.
     *
     * If the field has a known status, an array should be returned with two elements:
     *
     * - A [[\craft\enums\AttributeStatus]] case
     * - The status label
     *
     * For example:
     *
     * ```php
     * return [AttributeStatus::Modified, 'The field has been modified.');
     * ```
     *
     * @param ElementInterface $element
     * @return array{0:AttributeStatus|value-of<AttributeStatus>,1:string}|null
     * @since 3.7.0
     */
    public function getStatus(ElementInterface $element): ?array;

    /**
     * Returns the input’s ID, which the `<label>`’s `for` attribute should reference.
     *
     * @return string
     * @since 3.7.32
     */
    public function getInputId(): string;

    /**
     * Returns the input’s label ID.
     *
     * @return string
     * @since 4.1.0
     */
    public function getLabelId(): string;

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
     *   var textarea = document.getElementById('foo');
     * </script>
     * ```
     *
     * …then it might actually look like this before getting output to the browser:
     *
     * ```html
     * <textarea id="namespace-foo" name="namespace[foo]"></textarea>
     * <script type="text/javascript">
     *   var textarea = document.getElementById('foo');
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
     * public function getInputHtml($value, $element): string
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
     *   var textarea = document.getElementById('{{ namespacedId }}');
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
    public function getInputHtml(mixed $value, ?ElementInterface $element): string;

    /**
     * Returns a read-only version of the field’s input HTML.
     *
     * This method is called to output field values when viewing element revisions.
     *
     * @param mixed $value The field’s value
     * @param ElementInterface $element The element the field is associated with
     * @return string The static version of the field’s input HTML
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string;

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
    public function isValueEmpty(mixed $value, ElementInterface $element): bool;

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
    public function getSearchKeywords(mixed $value, ElementInterface $element): string;

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
     * - If the field is being cleared out (e.g. via the `resave/entries` command with `--to :empty:`),
     *   the value will be an empty string (`''`).
     *
     * There are cases where a pre-normalized value could be passed in as well, so be sure to account for that.
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return mixed The prepared field value
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed;

    /**
     * Normalizes a posted field value for use.
     *
     * This should call [[normalizeValue()]] by default, unless there are any special considerations that
     * need to be made for posted values.
     *
     * @param mixed $value The serialized value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return mixed The prepared field value
     * @since 4.5.0
     */
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed;

    /**
     * Serializes the field’s value into a transportable format (either a scalar value or array of scalar values).
     *
     * Whatever this returns should be something [[normalizeValue()]] can handle.
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return mixed The serialized field value
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed;

    /**
     * Serializes the field’s value into a transportable format (either a scalar value or array of scalar values),
     * for database storage.
     *
     * Whatever this returns should be something [[normalizeValue()]] can handle.
     *
     * @param mixed $value
     * @param ElementInterface $element
     * @return mixed
     * @since 5.7.0
     */
    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed;

    /**
     * Copies the field’s value from one element to another.
     *
     * @param ElementInterface $from
     * @param ElementInterface $to
     * @since 3.7.0
     */
    public function copyValue(ElementInterface $from, ElementInterface $to): void;

    /**
     * Returns the element condition rule class that should be used for this field.
     *
     * The rule class must be an instance of [[\craft\fields\conditions\FieldConditionRuleInterface]].
     *
     * @return string|array|null
     * @phpstan-return string|array{class:string}|null
     */
    public function getElementConditionRuleType(): array|string|null;

    /**
     * Returns a SQL expression which extracts the field’s value from the `elements_sites.content` column.
     *
     * > [!NOTE]
     * > This method expects the resulting SQL to be used within a query where the `elements_sites`
     * > table has been explicitly aliased to `elements_sites`, in case the actual table name has a prefix.
     *
     * @param string|null $key The data key to fetch, if this field stores multiple values
     * @return string|null
     * @since 5.0.0
     */
    public function getValueSql(?string $key = null): ?string;

    /**
     * Modifies an element index query.
     *
     * This method will be called whenever an element index is being loaded,
     * which contains a column for this field.
     *
     * @param ElementQueryInterface $query The element query
     * @since 3.0.9
     */
    public function modifyElementIndexQuery(ElementQueryInterface $query): void;

    /**
     * Sets whether the field is fresh.
     *
     * @param bool|null $isFresh Whether the field is fresh.
     */
    public function setIsFresh(?bool $isFresh = null): void;

    /**
     * Copies the field value from one site to another.
     *
     * @param ElementInterface $from
     * @param ElementInterface $to
     * @since 5.9.0
     */
    public function propagateValue(ElementInterface $from, ElementInterface $to): void;

    /**
     * Returns whether the field should be included in the given GraphQL schema.
     *
     * @param GqlSchema $schema
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
    public function getContentGqlType(): Type|array;

    /**
     * Returns the GraphQL type to be used as an argument in mutations for this field type.
     *
     * @return Type|array
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array;

    /**
     * Returns the GraphQL type to be used as an argument in queries for this field type.
     *
     * @return Type|array
     * @since 3.5.0
     */
    public function getContentGqlQueryArgumentType(): Type|array;

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
    public function afterElementSave(ElementInterface $element, bool $isNew): void;

    /**
     * Performs actions after the element has been fully saved and propagated to other sites.
     *
     * @param ElementInterface $element The element that was just saved and propagated
     * @param bool $isNew Whether the element is brand new
     * @since 3.2.0
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void;

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
    public function afterElementDelete(ElementInterface $element): void;

    /**
     * Performs actions before an element is deleted for a site.
     *
     * @param ElementInterface $element The element that is about to be deleted
     * @return bool Whether the element should be deleted for a site
     * @since 4.7.0
     */
    public function beforeElementDeleteForSite(ElementInterface $element): bool;

    /**
     * Performs actions after the element has been deleted.
     *
     * @param ElementInterface $element The element that was just deleted for a site
     * @since 4.7.0
     */
    public function afterElementDeleteForSite(ElementInterface $element): void;

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
    public function afterElementRestore(ElementInterface $element): void;
}
