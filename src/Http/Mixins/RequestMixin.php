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
    public function isMobileBrowser(): Closure
    {
        return function (bool $detectTablets = false): bool {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;
            $userAgent = $request->userAgent();

            if ($userAgent === null) {
                return false;
            }

            return preg_match(
                '/(android|bb\\d+|meego).+mobile|avantgo|bada\\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\\.(browser|link)|vodafone|wap|windows ce|xda|xiino'
                .($detectTablets ? '|android|ipad|playbook|silk' : '').'/i',
                $userAgent,
            ) || preg_match(
                '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\\-(n|u)|c55\\/|capi|ccwa|cdm\\-|cell|chtm|cldc|cmd\\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\\-s|devi|dica|dmob|do(c|p)o|ds(12|\\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\\-|_)|g1 u|g560|gene|gf\\-5|g\\-mo|go(\\.w|od)|gr(ad|un)|haie|hcit|hd\\-(m|p|t)|hei\\-|hi(pt|ta)|hp( i|ip)|hs\\-c|ht(c(\\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\\-(20|go|ma)|i230|iac( |\\-|\\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\\/)|klon|kpt |kwc\\-|kyo(c|k)|le(no|xi)|lg( g|\\/(k|l|u)|50|54|\\-[a-w])|libw|lynx|m1\\-w|m3ga|m50\\/|ma(te|ui|xo)|mc(01|21|ca)|m\\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\\-2|po(ck|rt|se)|prox|psio|pt\\-g|qa\\-a|qc(07|12|21|32|60|\\-[2-7]|i\\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\\-|oo|p\\-)|sdk\\/|se(c(\\-|0|1)|47|mc|nd|ri)|sgh\\-|shar|sie(\\-|m)|sk\\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\\-|v\\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\\-|tdg\\-|tel(i|m)|tim\\-|t\\-mo|to(pl|sh)|ts(70|m\\-|m3|m5)|tx\\-9|up(\\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\\-|your|zeto|zte\\-/i',
                mb_substr($userAgent, 0, 4),
            ) === 1;
        };
    }

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

    public function siteToken(): Closure
    {
        return function (): ?string {
            /**
             * @var Request $request
             *
             * @phpstan-ignore-next-line
             */
            $request = $this;

            return $request->query(Cms::config()->siteToken, $request->header('X-Craft-Site-Token'));
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
