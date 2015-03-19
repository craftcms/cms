<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * SavableComponentInterface defines the common interface to be implemented by savable Craft component classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface SavableComponentInterface extends ComponentInterface
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns whether the component is new (unsaved).
	 *
	 * @return boolean Whether the component is new
	 */
	public function isNew();

	/**
	 * Validates the component.
	 *
	 * @param array $attributeNames List of attribute names that should be validated.
	 * If this parameter is empty, it means any attribute listed in the applicable
	 * validation rules should be validated.
	 * @param boolean $clearErrors Whether existing errors should be cleared before performing validation
	 * @return boolean Whether the validation is successful without any error.
	 */
	public function validate($attributeNames = null, $clearErrors = true);

	/**
	 * Returns an array of the component’s settings.
	 *
	 * @return array The component’s settings.
	 */
	public function getSettings();

	/**
	 * Returns the component’s settings HTML.
	 *
	 * An extremely simple implementation would be to directly return some HTML:
	 *
	 * ```php
	 * return '<textarea name="foo">'.$this->getSettings()->foo.'</textarea>';
	 * ```
	 *
	 * For more complex settings, you might prefer to create a template, and render it via
	 * [[\craft\app\services\Templates::render()]]. For example, the following code would render a template loacated at
	 * craft/plugins/myplugin/templates/_settings.html, passing the settings to it:
	 *
	 * ```php
	 * return Craft::$app->templates->render('myplugin/_settings', [
	 *     'settings' => $this->getSettings()
	 * ]);
	 * ```
	 *
	 * If you need to tie any JavaScript code to your settings, it’s important to know that any `name=` and `id=`
	 * attributes within the returned HTML will probably get [[\craft\app\services\Templates::namespaceInputs() namespaced]],
	 * however your JavaScript code will be left untouched.
	 *
	 * For example, if getSettingsHtml() returns the following HTML:
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
	 * Thankfully, [[\craft\app\services\Templates]] service provides a couple handy methods that can help you deal
	 * with this:
	 *
	 * - [[\craft\app\services\Templates::namespaceInputId()]] will give you the namespaced version of a given ID.
	 * - [[\craft\app\services\Templates::namespaceInputName()]] will give you the namespaced version of a given input name.
	 * - [[\craft\app\services\Templates::formatInputId()]] will format an input name to look more like an ID attribute value.
	 *
	 * So here’s what a getSettingsHtml() method that includes field-targeting JavaScript code might look like:
	 *
	 * ```php
	 * public function getSettingsHtml()
	 * {
	 *     // Come up with an ID value for 'foo'
	 *     $id = Craft::$app->templates->formatInputId('foo');
	 *
	 *     // Figure out what that ID is going to be namespaced into
	 *     $namespacedId = Craft::$app->templates->namespaceInputId($id);
	 *
	 *     // Render and return the input template
	 *     return Craft::$app->templates->render('myplugin/_fieldinput', [
	 *         'id'           => $id,
	 *         'namespacedId' => $namespacedId,
	 *         'settings'     => $this->getSettings()
	 *     ]);
	 * }
	 * ```
	 *
	 * And the _settings.html template might look like this:
	 *
	 * ```twig
	 * <textarea id="{{ id }}" name="foo">{{ settings.foo }}</textarea>
	 *
	 * <script type="text/javascript">
	 *     var textarea = document.getElementById('{{ namespacedId }}');
	 * </script>
	 * ```
	 *
	 * The same principles also apply if you’re including your JavaScript code with
	 * [[\craft\app\services\Templates::includeJs()]].
	 *
	 * @return string|null
	 */
	public function getSettingsHtml();
}
