<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use craft\app\elements\db\ElementQueryInterface;
use craft\app\records\FieldGroup;

/**
 * FieldInterface defines the common interface to be implemented by field classes.
 *
 * A class implementing this interface should also use [[FieldTrait]].
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
	 * Sets the element that the field is currently associated with.
	 *
	 * @param ElementInterface|Element $element
	 */
	public function setElement(ElementInterface $element);

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
	 * [[\craft\app\services\Templates::render()]]. For example, the following code would render a template loacated at
	 * craft/plugins/myplugin/templates/_fieldinput.html, passing the $name and $value variables to it:
	 *
	 * ```php
	 * return Craft::$app->templates->render('myplugin/_fieldinput', [
	 *     'name'  => $name,
	 *     'value' => $value
	 * ]);
	 * ```
	 *
	 * If you need to tie any JavaScript code to your input, it’s important to know that any `name=` and `id=`
	 * attributes within the returned HTML will probably get [[\craft\app\services\Templates::namespaceInputs() namespaced]],
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
	 * Thankfully, [[\craft\app\services\Templates]] provides a couple handy methods that can help you deal with this:
	 *
	 * - [[\craft\app\services\Templates::namespaceInputId()]] will give you the namespaced version of a given ID.
	 * - [[\craft\app\services\Templates::namespaceInputName()]] will give you the namespaced version of a given input name.
	 * - [[\craft\app\services\Templates::formatInputId()]] will format an input name to look more like an ID attribute value.
	 *
	 * So here’s what a getInputHtml() method that includes field-targeting JavaScript code might look like:
	 *
	 * ```php
	 * public function getInputHtml($name, $value)
	 * {
	 *     // Come up with an ID value based on $name
	 *     $id = Craft::$app->templates->formatInputId($name);
	 *
	 *     // Figure out what that ID is going to be namespaced into
	 *     $namespacedId = Craft::$app->templates->namespaceInputId($id);
	 *
	 *     // Render and return the input template
	 *     return Craft::$app->templates->render('myplugin/_fieldinput', [
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
	 * [[\craft\app\services\Templates::includeJs()]].
	 *
	 * @param string $name  The name that the field’s HTML inputs should have.
	 * @param mixed  $value The field’s value. This will either be the [[prepValue() prepped value]], or the raw
	 *                      POST value in the event of a validation error, or if the user is editing an entry
	 *                      draft/version.
	 *
	 * @return string The input HTML.
	 */
	public function getInputHtml($name, $value);

	/**
	 * Returns the input value as it should be stored in the database.
	 *
	 * This method is called from [[ElementInterface::setContentFromPost()]], and is the only chance your plugin has
	 * to modify the POST data before it is saved to the craft_content table (assuming [[defineContentAttribute()]]
	 * doesn’t return `false` and the field actually has a column in the craft_content table).
	 *
	 * @param mixed $value The value that was in the POST data for the field.
	 *
	 * @return mixed The value that should be stored in the database.
	 */
	public function prepValueFromPost($value);

	/**
	 * Validates the field’s value.
	 *
	 * The $value passed into this method will be based on the value that [[prepValueFromPost()]] returned. It may
	 * have gone through additional modification when it was set on the [[ContentModel]] as well, depending on
	 * the attribute type [[defineContentAttribute()]] returns.
	 *
	 * Some validation may already occur for this field without any help from this method. For example, if the field
	 * is required by the field layout, but doesn’t have any value, the [[ContentModel]] will take care of that.
	 * Also, if [[defineContentAttribute()]] defines any validation rules (e.g. `min` or `max` for Number
	 * attributes), those will also be applied automatically. So this method should only be used for _custom_
	 * validation rules that aren’t already provided for free.
	 *
	 * @param mixed $value The field’s value.
	 *
	 * @return true|string|array `true` if everything checks out; otherwise a string for a single validation error, or
	 *                           an array of strings if there are multiple validation errors.
	 */
	public function validateValue($value);

	/**
	 * Returns the search keywords that should be associated with this field.
	 *
	 * The keywords can be separated by commas and/or whitespace; it doesn’t really matter. [[\craft\app\services\Search]]
	 * will be able to find the individual keywords in whatever string is returned, and normalize them for you.
	 *
	 * @param mixed $value The field’s value.
	 *
	 * @return string A string of search keywords.
	 */
	public function getSearchKeywords($value);

	/**
	 * Performs any actions before a field is saved.
	 *
	 * @return null
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
	 * @return null
	 */
	public function beforeDelete();

	/**
	 * Performs any actions after a field is deleted.
	 *
	 * @return null
	 */
	public function afterDelete();

	/**
	 * Performs any additional actions after the element has been saved.
	 *
	 * If your field type is storing data in its own table, this is the best place to do it. That’s because by the time
	 * this method has been called, you can be sure that the element will have an ID, even if it’s getting saved for
	 * the first time.
	 *
	 * @return null
	 */
	public function afterElementSave();

	/**
	 * Prepares the field’s value for use.
	 *
	 * This method is called when the field’s value is first acessed from the element. For example, the first time
	 * `entry.myFieldHandle` is called from a template, or right before [[getFieldHtml()]] is called. Whatever
	 * this method returns is what `entry.myFieldHandle` will likewise return, and what getFieldHandle()’s $value
	 * argument will be set to.
	 *
	 * @param mixed $value The field’s stored value.
	 *
	 * @return mixed The prepped value.
	 */
	public function prepValue($value);

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
