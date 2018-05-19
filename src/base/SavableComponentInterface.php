<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * SavableComponentInterface defines the common interface to be implemented by savable Craft component classes.
 * A class implementing this interface should also use [[SavableComponentTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface SavableComponentInterface extends ComponentInterface
{
    // Static
    // =========================================================================

    /**
     * Returns whether the component should be selectable in component Type selects.
     *
     * @return bool whether the component should be selectable in component Type selects.
     */
    public static function isSelectable(): bool;

    // Public Methods
    // =========================================================================

    /**
     * Returns whether the component is new (unsaved).
     *
     * @return bool Whether the component is new
     */
    public function getIsNew(): bool;

    /**
     * Validates the component.
     *
     * @param string[]|null $attributeNames List of attribute names that should
     * be validated. If this parameter is empty, it means any attribute listed
     * in the applicable validation rules should be validated.
     * @param bool $clearErrors Whether existing errors should be cleared before
     * performing validation
     * @return bool Whether the validation is successful without any error.
     */
    public function validate($attributeNames = null, $clearErrors = true);

    /**
     * Returns the list of settings attribute names.
     * By default, this method returns all public non-static properties that were defined on the called class.
     * You may override this method to change the default behavior.
     *
     * @return array The list of settings attribute names
     * @see getSettings()
     */
    public function settingsAttributes(): array;

    /**
     * Returns an array of the component’s settings.
     *
     * @return array The component’s settings.
     */
    public function getSettings(): array;

    /**
     * Returns the component’s settings HTML.
     * An extremely simple implementation would be to directly return some HTML:
     *
     * ```php
     * return '<textarea name="foo">'.$this->foo.'</textarea>';
     * ```
     *
     * For more complex settings, you might prefer to create a template, and render it via
     * [[\craft\web\View::renderTemplate()]]. For example, the following code would render a template located at
     * `src/templates/_settings.html`, passing the settings to it:
     *
     * ```php
     * return Craft::$app->view->renderTemplate('plugin-handle/_widget-settings', [
     *     'widget' => $this
     * ]);
     * ```
     *
     * If you need to tie any JavaScript code to your settings, it’s important to know that any `name=` and `id=`
     * attributes within the returned HTML will probably get [[\craft\web\View::namespaceInputs()|namespaced]],
     * however your JavaScript code will be left untouched.
     * For example, if getSettingsHtml() returns the following HTML:
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
     * As you can see, that JavaScript code will not be able to find the textarea, because the textarea’s `id=`
     * attribute was changed from `foo` to `namespace-foo`.
     * Before you start adding `namespace-` to the beginning of your element ID selectors, keep in mind that the actual
     * namespace is going to change depending on the context. Often they are randomly generated. So it’s not quite
     * that simple.
     *
     * Thankfully, [[\craft\web\View]] service provides a couple handy methods that can help you deal
     * with this:
     *
     * - [[\craft\web\View::namespaceInputId()]] will give you the namespaced version of a given ID.
     * - [[\craft\web\View::namespaceInputName()]] will give you the namespaced version of a given input name.
     * - [[\craft\web\View::formatInputId()]] will format an input name to look more like an ID attribute value.
     *
     * So here’s what a getSettingsHtml() method that includes field-targeting JavaScript code might look like:
     *
     * ```php
     * public function getSettingsHtml()
     * {
     *     // Come up with an ID value for 'foo'
     *     $id = Craft::$app->getView()->formatInputId('foo');
     *     // Figure out what that ID is going to be namespaced into
     *     $namespacedId = Craft::$app->view->namespaceInputId($id);
     *     // Render and return the input template
     *     return Craft::$app->view->renderTemplate('plugin-handle/_widget-settings', [
     *         'id'           => $id,
     *         'namespacedId' => $namespacedId,
     *         'widget'       => $this
     *     ]);
     * }
     * ```
     *
     * And the _widget-settings.twig template might look like this:
     *
     * ```twig
     * <textarea id="{{ id }}" name="foo">{{ widget.foo }}</textarea>
     * <script type="text/javascript">
     *     var textarea = document.getElementById('{{ namespacedId }}');
     * </script>
     * ```
     *
     * The same principles also apply if you’re including your JavaScript code with
     * [[\craft\web\View::registerJs()]].
     *
     * @return string|null
     */
    public function getSettingsHtml();

    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before a component is saved.
     *
     * @param bool $isNew Whether the component is brand new
     * @return bool Whether the component should be saved
     */
    public function beforeSave(bool $isNew): bool;

    /**
     * Performs actions after a component is saved.
     *
     * @param bool $isNew Whether the component is brand new
     */
    public function afterSave(bool $isNew);

    /**
     * Performs actions before a component is deleted.
     *
     * @return bool Whether the component should be deleted
     */
    public function beforeDelete(): bool;

    /**
     * Performs actions after a component is deleted.
     */
    public function afterDelete();
}
