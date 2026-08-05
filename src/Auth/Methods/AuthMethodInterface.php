<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Methods;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\User\Elements\User;

/**
 * AuthMethodInterface defines the common interface to be implemented by
 * authentication methods used for 2-step verification.
 *
 * A base implementation is provided by {@see BaseAuthMethod}.
 */
interface AuthMethodInterface extends ComponentInterface
{
    public static function handle(): string;

    /**
     * Returns the description of this authentication method.
     */
    public static function description(): string;

    /**
     * Sets the user that is being verified.
     *
     * This will be called once during initialization.
     */
    public function setUser(User $user): void;

    /**
     * Returns whether the authentication method is active for the user.
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
     * @param  string  $containerId  The ID of the setup slideout’s container element
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
     * [[\CraftCms\Cms\Auth\AuthMethods::verifyMethod()]]. That in turn will call your [[verify()]] method, passing it
     * the same arguments.
     *
     * If your [[verify()]] method returns `true`, [[\CraftCms\Cms\Auth\AuthMethods::verifyMethod()]] will log the user in
     * before returning the result.
     *
     * ```php
     * use CraftCms\Cms\Auth\AuthMethods;
     * use Illuminate\Http\JsonResponse;
     * use Illuminate\Http\Request;
     *
     * public function verifyVoice(Request $request, AuthMethods $authMethods): JsonResponse
     * {
     *     $data = $request->validate([
     *         'voiceSignature' => ['required', 'string'],
     *     ]);
     *
     *     $success = $authMethods->verifyMethod(VoiceAuth::class, $data['voiceSignature']);
     *
     *     if (! $success) {
     *         return response()->json(['message' => 'Voice verification failed.'], 422);
     *     }
     *
     *     return response()->json(['message' => 'Voice verification successful.']);
     * }
     * ```
     */
    public function getAuthFormHtml(?string $returnUrl = null): string;

    /**
     * Returns action menu items for the authentication method, when active.
     *
     * See [[\CraftCms\Cms\Cp\Html\MenuHtml::disclosureMenu()]] for documentation on supported item properties.
     *
     * @return list<array<string, mixed>>
     */
    public function getActionMenuItems(): array;

    /**
     * Authenticates the user.
     *
     * This will be called from {@see AuthMethods::verifyMethod}, which can be passed any number of arguments
     * which will be forwarded onto this method. (See [[getAuthFormHtml()]] for a full walkthrough of how it works.)
     *
     * @param  mixed  $args,...  Any arguments passed to {@see AuthMethods::verifyMethod}
     * @return bool Whether the user should be authenticated.
     */
    public function verify(mixed ...$args): bool;

    /**
     * Removes the authentication method for the current user.
     */
    public function remove(): void;
}
