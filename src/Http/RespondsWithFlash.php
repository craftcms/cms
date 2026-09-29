<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Component\Contracts\Identifiable;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\renderObjectTemplate;

trait RespondsWithFlash
{
    /** @param array<string, mixed> $data */
    public function asFailure(?string $message = null, array $data = []): Response
    {
        if (request()->expectsJson()) {
            return $this->asJsonFailure($message, $data);
        }

        request()->flash();

        // Attributes with no messages must not reach the session error bag:
        // Inertia's middleware resolves each entry's first message and 500s
        // on an empty one.
        $errors = array_filter($data['errors'] ?? []);

        $response = back()->with($data);

        if ($errors !== []) {
            $response->withErrors($errors);
        }

        // After `$data`, so an `error` key in it can't replace the message.
        Flash::error($message);

        return $response;
    }

    /** @param array<string, mixed> $data */
    public function asJsonFailure(?string $message = null, array $data = []): JsonResponse
    {
        return new JsonResponse($data + self::messageData('error', $message), 400);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $notificationSettings
     */
    public function asSuccess(?string $message = null, array $data = [], ?string $redirect = null, array $notificationSettings = []): Response
    {
        $redirect ??= $this->getPostedRedirectUrl();

        // A JSON response carries its message in the body for the client to
        // show, rather than in the session, where the next unrelated page
        // load would show it a second time.
        if (request()->expectsJson()) {
            $message = request()->getSigned('successMessage', $message);

            return $this->asJsonSuccess($message, $data, $redirect, $notificationSettings);
        }

        Flash::success($message, $notificationSettings);

        if ($redirect) {
            return redirect($redirect)->with($data);
        }

        return back()->with($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $notificationSettings
     */
    public function asJsonSuccess(?string $message = null, array $data = [], ?string $redirect = null, array $notificationSettings = []): JsonResponse
    {
        return new JsonResponse($data + self::messageData('success', $message, $notificationSettings) + array_filter([
            'redirect' => $redirect,
        ]), 200);
    }

    /**
     * The `message` key JSON clients have always read, plus on control panel
     * requests `messages` (the full message, in a list like the Inertia prop)
     * and the `notificationSettings` that
     * `Craft.cp.displaySuccess(data.message, data.notificationSettings)`
     * passes along. The message's `id` rides in the settings, so a client
     * showing it either way shows it once.
     *
     * @param  'success'|'error'  $type
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private static function messageData(string $type, ?string $message, array $settings = []): array
    {
        if ($message === null) {
            return [];
        }

        if (! request()->isCpRequest()) {
            return ['message' => $message];
        }

        $flashed = Flash::make($type, $message, $settings);

        return [
            'message' => $message,
            'messages' => [$flashed],
            'notificationSettings' => $flashed['settings'] + ['id' => $flashed['id']],
        ];
    }

    /** @param array<string, mixed> $data */
    public function asModelFailure(
        object $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
    ): Response {
        $modelName ??= 'model';
        $data += array_filter([
            'modelName' => $modelName,
            'modelClass' => $model::class,
            $modelName => Arr::toArray($model),
            'errors' => $model instanceof Validatable
                ? $model->errors()->getMessages()
                : null,
        ]);

        return $this->asFailure($message, $data);
    }

    /** @param array<string, mixed> $data */
    public function asModelSuccess(
        object $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
        ?string $redirect = null,
    ): Response {
        $modelName ??= 'model';
        $modelData = Arr::toArray($model);

        if (! request()->isCpRequest() && ! currentUser()?->can('accessCp')) {
            unset($modelData['cpEditUrl']);
        }

        $data += [
            'modelName' => $modelName,
            'modelClass' => $model::class,
            $modelName => $modelData,
        ];

        if ($model instanceof Identifiable) {
            $data['modelId'] = $model->getId();
        }

        $redirect ??= $this->getPostedRedirectUrl($model);

        if ($redirect === null && ! request()->expectsJson() && request()->isCpRequest() && $model instanceof CpEditable) {
            $redirect = $model->getCpEditUrl();
        }

        return $this->asSuccess($message, $data, $redirect);
    }

    public function redirectToPostedUrl(?object $object = null, ?string $redirect = null): RedirectResponse
    {
        return redirect()->to($this->getPostedRedirectUrl($object) ?? $redirect);
    }

    protected function getPostedRedirectUrl(?object $object = null): ?string
    {
        $url = request('redirect');

        if (! $url) {
            return null;
        }

        try {
            $url = Crypt::decrypt($url);
        } catch (DecryptException) {
            abort(400, 'Request contained an invalid body param');
        }

        // I'm not sure why, but decrypt ac
        if (! $url) {
            return null;
        }

        if ($object) {
            $url = renderObjectTemplate($url, $object);
        }

        $params = request()->input('redirectParams');

        if ($params) {
            try {
                $params = Json::decode($params);
            } catch (InvalidArgumentException $e) {
                abort(400, $e->getMessage());
            }

            $url = Url::urlWithParams($url, $params);
        }

        if (request()->isCpRequest()) {
            return Url::cpUrl($url);
        }

        return $url;
    }
}
