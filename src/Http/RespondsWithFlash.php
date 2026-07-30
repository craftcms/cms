<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

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

        $message = Flash::error($message);

        request()->flash();

        // Attributes with no messages must not reach the session error bag:
        // Inertia's middleware resolves each entry's first message and 500s
        // on an empty one.
        $errors = array_filter($data['errors'] ?? []);

        return back()
            ->with('error', $message)
            ->with($data)->withErrors($errors);
    }

    /** @param array<string, mixed> $data */
    public function asJsonFailure(?string $message = null, array $data = []): JsonResponse
    {
        return new JsonResponse($data + array_filter([
            'message' => $message,
        ]), 400);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $notificationSettings
     */
    public function asSuccess(?string $message = null, array $data = [], ?string $redirect = null, array $notificationSettings = []): Response
    {
        $redirect ??= $this->getPostedRedirectUrl();

        $message = Flash::success($message, $notificationSettings);

        // Set the Inertia shared flash (HandleInertiaRequests reads it from the
        // session `success` key) on every branch. The JSON branch needs it too:
        // a client-driven navigation (e.g. runAction performing an Inertia visit
        // after a DELETE) renders the flash on the *next* request, so the message
        // must be in the session regardless of the response type.
        if ($message !== null) {
            session()->flash('success', $message);
        }

        if (request()->expectsJson()) {
            return $this->asJsonSuccess($message, $data, $redirect);
        }

        if ($redirect) {
            return redirect($redirect)->with($data);
        }

        return back()->with($data);
    }

    /** @param array<string, mixed> $data */
    public function asJsonSuccess(?string $message = null, array $data = [], ?string $redirect = null): JsonResponse
    {
        return new JsonResponse($data + array_filter([
            'message' => $message,
            'redirect' => $redirect,
        ]), 200);
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
