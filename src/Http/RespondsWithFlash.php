<?php

namespace CraftCms\Cms\Http;

use craft\base\Identifiable;
use CraftCms\Cms\Component\Contracts\ValidatableComponentInterface;
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

        return redirect()->back()->withErrors($data);
    }

    public function asSuccess(?string $message = null, array $data = []): Response
    {
        if (request()->expectsJson()) {
            return response()->json($data + array_filter([
                'message' => $message,
            ]), 200);
        }

        Flash::success($message);

        return redirect()->back()->with($data);
    }

    public function asModelFailure(
        ValidatableComponentInterface $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
    ): Response {
        $modelName ??= 'model';
        $data += [
            'modelName' => $modelName,
            'modelClass' => get_class($model),
            $modelName => Arr::toArray($model),
            'errors' => $model->getErrors(),
        ];

        return $this->asFailure($message, $data);
    }

    public function asModelSuccess(
        ValidatableComponentInterface $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
    ): Response {
        $modelName ??= 'model';
        $data += [
            'modelName' => $modelName,
            'modelClass' => get_class($model),
            $modelName => Arr::toArray($model),
        ];

        if ($model instanceof Identifiable) {
            $data['modelId'] = $model->getId();
        }

        return $this->asSuccess($message, $data);
    }
}
