<?php
namespace Craft;

/**
 * Interface IFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
interface IFieldType extends ISavableComponentType
{
	// Public Methods
	// =========================================================================

	/**
	 * Sets the element that the field type is associated with.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return null
	 */
	public function setElement(BaseElementModel $element);

	/**
	 * Returns the field’s content attribute config.
	 *
	 * The attribute config returned by this method is used to define two things:
	 *
	 * - This field’s attribute in {@link ContentModel::defineAttributes()}.
	 * - This field’s column in the craft_content table.
	 *
	 * The method can return a string (e.g. `AttributeType::Number`) or an array with additional settings (e.g.
	 * `array(AttributeType::Number, 'min' => 0, 'max' => 100, 'decimals' => 2)`) if the attribute type’s default
	 * settings defined by {@link ModelHelper::$attributeTypeDefaults} aren’t good enough.
	 *
	 * If you return `AttributeType::Mixed`, your field type can work with array data, and it will automatically be
	 * JSON-encoded when getting saved to the database, and automatically JSON-decoded when getting fetched from the
	 * database. All your field type will ever see is the actual array.
	 *
	 * If the field type is storing its data in its own table and doesn’t need a column in the craft_content table,
	 * this method should return `false`. You can then save your data manually from {@link onAfterElementSave}.
	 *
	 * @return mixed The field’s content attribute config, or `false` if it’s storing data in its own table.
	 */
	public function defineContentAttribute();

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
	 * {@link TemplatesService::render()}. For example, the following code would render a template loacated at
	 * craft/plugins/myplugin/templates/_fieldinput.html, passing the $name and $value variables to it:
	 *
	 * ```php
	 * return craft()->templates->render('myplugin/_fieldinput', array(
	 *     'name'  => $name,
	 *     'value' => $value
	 * ));
	 * ```
	 *
	 * If you need to tie any JavaScript code to your input, it’s important to know that any `name=` and `id=`
	 * attributes within the returned HTML will probably get {@link TemplatesService::namespaceInputs() namespaced},
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
	 * Thankfully, {@link TemplatesService} provides a couple handy methods that can help you deal with this:
	 *
	 * - {@link TemplatesService::namespaceInputId()} will give you the namespaced version of a given ID.
	 * - {@link TemplatesService::namespaceInputName()} will give you the namespaced version of a given input name.
	 * - {@link TemplatesService::formatInputId()} will format an input name to look more like an ID attribute value.
	 *
	 * So here’s what a getInputHtml() method that includes field-targeting JavaScript code might look like:
	 *
	 * ```php
	 * public function getInputHtml($name, $value)
	 * {
	 *     // Come up with an ID value based on $name
	 *     $id = craft()->templates->formatInputId($name);
	 *
	 *     // Figure out what that ID is going to be namespaced into
	 *     $namespacedId = craft()->templates->namespaceInputId($id);
	 *
	 *     // Render and return the input template
	 *     return craft()->templates->render('myplugin/_fieldinput', array(
	 *         'name'         => $name,
	 *         'id'           => $id,
	 *         'namespacedId' => $namespacedId,
	 *         'value'        => $value
	 *     ));
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
	 * {@link TemplatesService::includeJs()}.
	 *
	 * @param string $name  The name that the field’s HTML inputs should have.
	 * @param mixed  $value The field’s value. This will either be the {@link prepValue() prepped value}, or the raw
	 *                      POST value in the event of a validation error, or if the user is editing an entry
	 *                      draft/version.
	 *
	 * @return string The input HTML.
	 */
	public function getInputHtml($name, $value);

	/**
	 * Returns the input value as it should be stored in the database.
	 *
	 * This method is called from {@link BaseElementModel::setContentFromPost()}, and is the only chance your plugin has
	 * to modify the POST data before it is saved to the craft_content table (assuming {@link defineContentAttribute()}
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
	 * The $value passed into this method will be based on the value that {@link prepValueFromPost()} returned. It may
	 * have gone through additional modification when it was set on the {@link ContentModel} as well, depending on
	 * the attribute type {@link defineContentAttribute()} returns.
	 *
	 * Some validation may already occur for this field without any help from this method. For example, if the field
	 * is required by the field layout, but doesn’t have any value, the {@link ContentModel} will take care of that.
	 * Also, if {@link defineContentAttribute()} defines any validation rules (e.g. `min` or `max` for Number
	 * attributes), those will also be applied automatically. So this method should only be used for _custom_
	 * validation rules that aren’t already provided for free.
	 *
	 * @param mixed $value The field’s value.
	 *
	 * @return true|string|array `true` if everything checks out; otherwise a string for a single validation error, or
	 *                           an array of strings if there are multiple validation errors.
	 */
	public function validate($value);

	/**
	 * Returns the search keywords that should be associated with this field.
	 *
	 * The keywords can be separated by commas and/or whitespace; it doesn’t really matter. {@link SearchService} will
	 * be able to find the individual keywords in whatever string is returned, and normalize them for you.
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
	public function onBeforeSave();

	/**
	 * Performs any actions after a field is saved.
	 *
	 * @return null
	 */
	public function onAfterSave();

	/**
	 * Performs any actions before a field is deleted.
	 *
	 * @return null
	 */
	public function onBeforeDelete();

	/**
	 * Performs any actions after a field is deleted.
	 *
	 * @return null
	 */
	public function onAfterDelete();

	/**
	 * Performs any additional actions after the element has been saved.
	 *
	 * If your field type is storing data in its own table, this is the best place to do it. That’s because by the time
	 * this method has been called, you can be sure that the element will have an ID, even if it’s getting saved for
	 * the first time.
	 *
	 * @return null
	 */
	public function onAfterElementSave();

	/**
	 * Prepares the field’s value for use.
	 *
	 * This method is called when the field’s value is first acessed from the element. For example, the first time
	 * `entry.myFieldHandle` is called from a template, or right before {@link getFieldHtml()} is called. Whatever
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
	 * @param DbCommand $query The database query currently being built to find the elements.
	 * @param mixed     $value The value that was set on this field’s corresponding {@link ElementCriteriaModel} param,
	 *                         if any.
	 *
	 * @return null|false `false` in the event that the method is sure that no elements are going to be found.
	 */
	public function modifyElementsQuery(DbCommand $query, $value);
}
