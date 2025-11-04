<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use Craft;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Component\Contracts\Identifiable;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Flash;
use Symfony\Component\HttpFoundation\Response;

trait RespondsWithFlash
{
    public function asFailure(?string $message = null, array $data = []): Response
    {
        if (request()->expectsJson()) {
            return response()->json($data + array_filter([
                'message' => $message,
            ]), 400);
        }

        Flash::fail($message);

        return redirect()->back()->with($data);
    }

    public function asSuccess(?string $message = null, array $data = [], ?string $redirect = null): Response
    {
        if (request()->expectsJson()) {
            return response()->json($data + array_filter([
                'message' => $message,
            ]), 200);
        }

        Flash::success($message);

        $redirect ??= $this->getPostedRedirectUrl();

        if ($redirect) {
            return redirect($redirect)->with($data);
        }

        return redirect()->back()->with($data);
    }

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
            'errors' => method_exists($model, 'getErrors')
                ? $model->getErrors()
                : null,
        ]);

        return $this->asFailure($message, $data);
    }

    public function asModelSuccess(
        object $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
        ?string $redirect = null,
    ): Response {
        $modelName ??= 'model';
        $data += [
            'modelName' => $modelName,
            'modelClass' => $model::class,
            $modelName => Arr::toArray($model),
        ];

        if ($model instanceof Identifiable) {
            $data['modelId'] = $model->getId();
        }

        $redirect ??= $this->getPostedRedirectUrl($model);

        return $this->asSuccess($message, $data, $redirect);
    }

    public function redirectToPostedUrl(?object $object = null, ?string $redirect = null): Response
    {
        return redirect($this->getPostedRedirectUrl($object) ?? $redirect);
    }

    protected function getPostedRedirectUrl(?object $object = null): ?string
    {
        $url = request('redirect');

        if (! $url) {
            return null;
        }

        $url = Craft::$app->getSecurity()->validateData($url);

        if ($url === false) {
            abort(400, 'Request contained an invalid body param');
        }

        if ($object) {
            $url = Craft::$app->getView()->renderObjectTemplate($url, $object);
        }

        if (request()->isCpRequest()) {
            return UrlHelper::cpUrl($url);
        }

        return $url;
    }
}
