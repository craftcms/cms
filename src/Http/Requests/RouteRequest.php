<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Validation\Rules\UriFormatRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

use function CraftCms\Cms\t;

class RouteRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'uriParts' => ['present', 'array'],
            'template' => ['required', 'string'],
            'siteUid' => ['nullable', 'uuid'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('uriParts') || ! is_array($this->input('uriParts'))) {
                    return;
                }

                try {
                    $uriParts = $this->normalizeUriParts($this->input('uriParts'));
                } catch (ValidationException) {
                    $validator->errors()->add('uriParts', t('Invalid route URI.'));

                    return;
                }

                $uriValidator = ValidatorFacade::make(
                    ['uriParts' => new Route(uriParts: $uriParts, template: '')->getUri()],
                    ['uriParts' => [new UriFormatRule]],
                );

                foreach ($uriValidator->errors()->get('uriParts') as $message) {
                    $validator->errors()->add('uriParts', $message);
                }
            },
        ];
    }

    public function toRoute(?string $routeUid = null): Route
    {
        $data = $this->validated();

        return new Route(
            uriParts: $this->normalizeUriParts($data['uriParts']),
            template: $data['template'],
            siteUid: $data['siteUid'] ?? null,
            uid: $routeUid,
        );
    }

    /**
     * @param  list<string|array{string, string}>  $uriParts
     * @return list<string|array{string, string}>
     */
    private function normalizeUriParts(array $uriParts): array
    {
        return collect($uriParts)
            ->map(function (mixed $part): string|array {
                if (is_string($part)) {
                    return $part;
                }

                if (
                    is_array($part) &&
                    isset($part[0], $part[1]) &&
                    is_string($part[0]) &&
                    is_string($part[1])
                ) {
                    return [$part[0], $part[1]];
                }

                throw ValidationException::withMessages([
                    'uriParts' => t('Invalid route URI.'),
                ]);
            })
            ->all();
    }
}
