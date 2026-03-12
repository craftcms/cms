<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Mixins;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;

class RequestMixin
{
    public function isCpRequest(): Closure
    {
        return function (): bool {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;

            return $request->is(
                Cms::config()->cpTrigger,
                Cms::config()->cpTrigger.'/*',
            );
        };
    }

    public function getToken(): Closure
    {
        return function (): ?string {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;

            return $request->input(Cms::config()->tokenParam, $request->header(HandleTokenRequest::TOKEN_HEADER));
        };
    }

    public function isSiteRequest(): Closure
    {
        return function (): bool {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;

            return ! $request->isCpRequest();
        };
    }

    public function isActionRequest(): Closure
    {
        return function (): bool {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;

            return $request->actionSegments() !== [];
        };
    }

    public function isPreview(): Closure
    {
        return function (): bool {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;
            $previewParamValue = $request->input('x-craft-preview') ?? $request->input('x-craft-live-preview') ?? $request->header('X-Craft-Preview-Token');

            if ($previewParamValue === null || $previewParamValue === '') {
                return false;
            }

            try {
                Crypt::decrypt($previewParamValue);
            } catch (DecryptException) {
                return false;
            }

            return Context::hasHidden(HandleTokenRequest::TOKEN_KEY);
        };
    }

    public function actionSegments(): Closure
    {
        return function (): array {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;
            $actionTrigger = Cms::config()->actionTrigger;
            $segmentIndex = $request->isCpRequest() ? 2 : 1;

            if ($request->segment($segmentIndex) === $actionTrigger && count($request->segments()) > $segmentIndex) {
                return array_slice($request->segments(), $segmentIndex);
            }

            $actionParam = $request->get('action');

            if ($actionParam !== null) {
                if (! is_string($actionParam)) {
                    abort(400, 'Invalid action param');
                }

                return array_values(array_filter(explode('/', $actionParam)));
            }

            return [];
        };
    }

    public function actionSegmentsToRoute(): Closure
    {
        return function (?array $actionSegments = null): string {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;

            $actionSegments ??= $request->actionSegments();

            return implode('/', array_filter([
                '',
                $request->isCpRequest() ? Cms::config()->cpTrigger : null,
                Cms::config()->actionTrigger,
                ...$actionSegments,
            ], fn ($value) => $value !== null));
        };
    }

    public function pageNumber(): Closure
    {
        return function (): int {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;
            $pageTrigger = $request->isCpRequest() ? 'p' : Cms::config()->getPageTrigger();

            if (str_starts_with($pageTrigger, '?')) {
                return max(1, (int) $request->query(trim($pageTrigger, '?='), '1'));
            }

            $path = trim($request->decodedPath(), '/');

            if ($path === '') {
                return 1;
            }

            $pageTriggerPattern = preg_quote($pageTrigger, '/');

            if (preg_match("/^(?:(.*)\\/)?{$pageTriggerPattern}(\\d+)$/", $path, $matches)) {
                return max(1, (int) $matches[2]);
            }

            return 1;
        };
    }

    public function duplicateWithUri(): Closure
    {
        return function (string $newUri, ?array $query = null, array $server = []): Request {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;

            return $request->duplicate(
                query: $query ?? $request->query->all(),
                server: array_merge($request->server->all(), $server, [
                    'REQUEST_URI' => $newUri,
                ]),
            );
        };
    }

    public function getSigned(): Closure
    {
        return function (string $key, mixed $default = null): mixed {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;
            $value = $request->get($key);

            if ($value === null) {
                return $default;
            }

            try {
                return Crypt::decrypt($value);
            } catch (DecryptException) {
                abort(400, 'Request contained an invalid body param');
            }
        };
    }
}
