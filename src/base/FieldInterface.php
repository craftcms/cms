<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use craft\app\elements\db\ElementQueryInterface;
use craft\app\records\FieldGroup;

/**
 * FieldInterface defines the common interface to be implemented by field classes.
 *
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[FieldTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface FieldInterface extends SavableComponentInterface
{
	// Static
	// =========================================================================

	/**
	 * Returns whether this field has a column in the content table.
	 *
	 * @return boolean
	 */
	public static function hasContentColumn();

	// Public Methods
	// =========================================================================

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
	public function getContentColumnType();

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
	 * [[\craft\app\web\View::renderTemplate()]]. For example, the following code would render a template located at
	 * craft/plugins/myplugin/templates/_fieldinput.html, passing the $name and $value variables to it:
	 *
	 * ```php
	 * return Craft::$app->getView()->renderTemplate('myplugin/_fieldinput', [
	 *     'name'  => $name,
	 *     'value' => $value
	 * ]);
	 * ```
	 *
	 * If you need to tie any JavaScript code to your input, it’s important to know that any `name=` and `id=`
	 * attributes within the returned HTML will probably get [[\craft\app\web\View::namespaceInputs() namespaced]],
	 * however your JavaScript code will be left untouched.
	 *
	 * For example, if getInputHtml() returns the following HTML:
	 *
	 * ```html
	 * <textarea id="foo" name="foo"></textarea>
	 *
	 * <script type="text/javascript">
	 *     var textarea = document.getElementById('foo');
	 * </script>
	 * ```
	 *
	 * …then it might actually look like this before getting output to the browser:
	 *
	 * ```html
	 * <textarea id="namespace-foo" name="namespace[foo]"></textarea>
	 *
	 * <script type="text/javascript">
	 *     var textarea = document.getElementById('foo');
	 * </script>
	 * ```
	 *
	 * As you can see, that JavaScript code will not be able to find the textarea, because the textarea’s `id=`
	 * attribute was changed from `foo` to `namespace-foo`.
	 *
	 * Before you start adding `namespace-` to the beginning of your element ID selectors, keep in mind that the actual
	 * namespace is going to change depending on the context. Often they are randomly generated. So it’s not quite
	 * that simple.
	 *
	 * Thankfully, [[\craft\app\web\View]] provides a couple handy methods that can help you deal with this:
	 *
	 * - [[\craft\app\web\View::namespaceInputId()]] will give you the namespaced version of a given ID.
	 * - [[\craft\app\web\View::namespaceInputName()]] will give you the namespaced version of a given input name.
	 * - [[\craft\app\web\View::formatInputId()]] will format an input name to look more like an ID attribute value.
	 *
	 * So here’s what a getInputHtml() method that includes field-targeting JavaScript code might look like:
	 *
	 * ```php
	 * public function getInputHtml($value, $element)
	 * {
	 *     // Come up with an ID value based on $name
	 *     $id = Craft::$app->getView()->formatInputId($name);
	 *
	 *     // Figure out what that ID is going to be namespaced into
	 *     $namespacedId = Craft::$app->getView()->namespaceInputId($id);
	 *
	 *     // Render and return the input template
	 *     return Craft::$app->getView()->renderTemplate('myplugin/_fieldinput', [
	 *         'name'         => $name,
	 *         'id'           => $id,
	 *         'namespacedId' => $namespacedId,
	 *         'value'        => $value
	 *     ]);
	 * }
	 * ```
	 *
	 * And the _fieldinput.html template might look like this:
	 *
	 * ```twig
	 * <textarea id="{{ id }}" name="{{ name }}">{{ value }}</textarea>
	 *
	 * <script type="text/javascript">
	 *     var textarea = document.getElementById('{{ namespacedId }}');
	 * </script>
	 * ```
	 *
	 * The same principles also apply if you’re including your JavaScript code with
	 * [[\craft\app\web\View::registerJs()]].
	 *
	 * @param mixed                         $value   The field’s value. This will either be the [[prepareValue() prepared value]],
	 *                                               raw POST data (i.e. if there was a validation error), or null
	 * @param ElementInterface|Element|null $element The element the field is associated with, if there is one
	 *
	 * @return string The input HTML.
	 */
	public function getInputHtml($value, $element);

	/**
	 * Returns static HTML for the field's value.
	 *
	 * @param mixed                    $value   The field’s value
	 * @param ElementInterface|Element $element The element the field is associated with, if there is one
	 * @return string
	 */
	public function getStaticHtml($value, $element);

	/**
	 * Validates the field’s value.
	 *
	 * @param mixed                    $value   The field’s value
	 * @param ElementInterface|Element $element The element the field is associated with, if there is one
	 *
	 * @return true|string|array `true` if everything checks out; otherwise a string for a single validation error, or
	 *                           an array of strings if there are multiple validation errors.
	 */
	public function validateValue($value, $element);

	/**
	 * Returns the search keywords that should be associated with this field.
	 *
	 * The keywords can be separated by commas and/or whitespace; it doesn’t really matter. [[\craft\app\services\Search]]
	 * will be able to find the individual keywords in whatever string is returned, and normalize them for you.
	 *
	 * @param mixed                    $value   The field’s value
	 * @param ElementInterface|Element $element The element the field is associated with, if there is one
	 *
	 * @return string A string of search keywords.
	 */
	public function getSearchKeywords($value, $element);

	/**
	 * Performs any actions before a field is saved.
	 *
	 * @return boolean Whether the field should be saved
	 */
	public function beforeSave();

	/**
	 * Performs any actions after a field is saved.
	 *
	 * @return null
	 */
	public function afterSave();

	/**
	 * Performs any actions before a field is deleted.
	 *
	 * @return boolean Whether the field should be deleted
	 */
	public function beforeDelete();

	/**
	 * Performs any actions after a field is deleted.
	 *
	 * @return null
	 */
	public function afterDelete();

	/**
	 * Performs any actions before an element is saved.
	 *
	 * @param ElementInterface|Element $element The element that is about to be saved
	 */
	public function beforeElementSave(ElementInterface $element);

	/**
	 * Performs any actions after the element has been saved.
	 *
	 * @param ElementInterface|Element $element The element that was just saved
	 */
	public function afterElementSave(ElementInterface $element);

	/**
	 * Prepares the field’s value for use.
	 *
	 * This method is called when the field’s value is first accessed from the element. For example, the first time
	 * `entry.myFieldHandle` is called from a template, or right before [[getInputHtml()]] is called. Whatever
	 * this method returns is what `entry.myFieldHandle` will likewise return, and what [[getInputHtml()]]’s $value
	 * argument will be set to.
	 *
	 * @param mixed                         $value   The raw field value
	 * @param ElementInterface|Element|null $element The element the field is associated with, if there is one
	 *
	 * @return mixed The prepared field value
	 */
	public function prepareValue($value, $element);

	/**
	 * Modifies an element query.
	 *
	 * This method will be called whenever elements are being searched for that may have this field assigned to them.
	 *
	 * If the method returns `false`, the query will be stopped before it ever gets a chance to execute.
	 *
	 * @param ElementQueryInterface $query The element query
	 * @param mixed                 $value The value that was set on this field’s corresponding [[ElementCriteriaModel]] param,
	 *                              if any.
	 *
	 * @return null|false `false` in the event that the method is sure that no elements are going to be found.
	 */
	public function modifyElementsQuery(ElementQueryInterface $query, $value);

	/**
	 * Sets whether the field is fresh.
	 *
	 * @param boolean $isFresh Whether the field is fresh.
	 */
	public function setIsFresh($isFresh);

	/**
	 * Returns the field’s group.
	 *
	 * @return FieldGroup
	 */
	public function getGroup();
}
