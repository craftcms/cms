<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\methods;

use craft\base\ComponentInterface;
use craft\elements\User;

/**
 * AuthMethodInterface defines the common interface to be implemented by
 * authentication methods used for 2-step verification.
 *
 * A base implementation is provided by [[BaseAuthMethod]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface AuthMethodInterface extends ComponentInterface
{
    /**
     * Returns the description of this authentication method.
     *
     * @return string
     */
    public static function description(): string;

    /**
     * Sets the user that is being verified.
     *
     * This will be called once during initialization.
     *
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * Returns whether the authentication method is active for the user.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Returns the HTML for the authentication method’s setup slideout.
     *
     * Once the method is enabled for the user, call the slideout’s `showSuccess()` method to display a success message,
     * and call `Craft.authMethodSetup.refresh()` to refresh the method’s info and actions in the main window.
     *
     * ```php
     * Craft::$app->view->registerJsWithVars(fn($containerId, $class) => <<<JS
     *   // ...
     *   Craft.Slideout.instances[$containerId].showSuccess();
     *   Craft.authMethodSetup.refresh();
     * JS, [
     *     $containerId,
     *     static::class
     * ]);
     * ```
     *
     * @param string $containerId The ID of the setup slideout’s container element
     * @return string
     */
    public function getSetupHtml(string $containerId): string;

    /**
     * Returns the HTML for the authentication method’s authentication form.
     *
     * Before returning the HTML, ensure an asset bundle is registered which defines a JavaScript class for
     * handling your form. The class should be registered via `Craft.registerAuthFormHandler()`.
     *
     * ```js
     * Acme.VoiceAuthForm = Garnish.Base.extend({
     *   init(form, onSuccess, showError) {
     *     this.addListener(form, 'submit', (ev) => {
     *       ev.preventDefault();
     *       const data = {
     *         voiceSignature: '...',
     *       };
     *       Craft.sendActionRequest('acme/auth/verify-voice', {data})
     *         .then(() => {
     *           onSuccess();
     *         })
     *         .catch(({response}) => {
     *           showError(response.data.message);
     *         });
     *     });
     *   },
     * }, {
     *   METHOD: 'acme\\auth\\VoiceAuth',
     * });
     *
     * Craft.registerAuthFormHandler(Acme.VoiceAuthForm.METHOD, Acme.VoiceAuthForm);
     * ```
     *
     * The class should send a request to a controller action, which collects the form data and passes it to
     * [[\craft\services\Auth::verify()]]. That in turn will call your [[verify()]] method, passing it
     * the same arguments.
     *
     * If your [[verify()]] method returns `true`, [[\craft\services\Auth::verify()]] will log the user in
     * before returning the result.
     *
     * ```php
     * use Craft;
     * use yii\web\Response;
     *
     * protected array|bool|int $allowAnonymous = [
     *     'verify-voice' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
     * ];
     *
     * public function actionVerifyVoice(): Response
     * {
     *     $this->requirePostRequest();
     *     $this->requireAcceptsJson();
     *
     *     $voiceSignature = $this->request->getRequiredBodyParam('voiceSignature');
     *     $success = Craft::$app->auth->verify(VoiceAuth::class, $voiceSignature);
     *
     *     if (!$success) {
     *         return $this->asFailure('Voice verification failed.');
     *     }
     *
     *     return $this->asSuccess('Voice verification successful.');
     * }
     * ```
     *
     * @return string
     */
    public function getAuthFormHtml(): string;

    /**
     * Returns action menu items for the authentication method, when active.
     *
     * See [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties.
     *
     * @return array
     */
    public function getActionMenuItems(): array;

    /**
     * Authenticates the user.
     *
     * This will be called from [[\craft\services\Auth::verify()]], which can be passed any number of arguments
     * which will be forwarded onto this method. (See [[getAuthFormHtml()]] for a full walkthrough of how it works.)
     *
     * @param mixed $args,... Any arguments passed to [[\craft\services\Auth::verify()]]
     * @return bool Whether the user should be authenticated.
     */
    public function verify(mixed ...$args): bool;

    /**
     * Removes the authentication method for the current user.
     */
    public function remove(): void;
}
