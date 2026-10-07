<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function CraftCms\Cms\template;

trait UpdatesFieldLayout
{
    /**
     * Compiles the element's field layout at the scope the request asked for.
     *
     * Split out so a caller that needs the layout for something else as well —
     * autosave rebuilds the whole edit screen payload around it — can compile
     * it once and hand the same payload to {@see fieldLayoutData()}.
     */
    protected function compileFieldLayout(ElementInterface $element): UiPayload
    {
        return app(FieldLayoutCompiler::class)->compile(
            $element->getFieldLayout(),
            $element,
            new UiContext(
                namespace: $this->fieldLayoutRootScope(),
                errors: $element->errors()->getMessages(),
                mode: Gate::check('save', $element) ? ControlMode::Editable : ControlMode::ReadOnly,
                refreshable: true,
            ),
        );
    }

    /**
     * @param  UiPayload|null  $rootPayload  An already-compiled layout to reuse; compiled here when omitted.
     * @return array<string, mixed>
     */
    protected function fieldLayoutData(ElementInterface $element, ?UiPayload $rootPayload = null): array
    {
        $requestedScope = $this->requestedFormScope('X-Craft-Ui-Scope', $this->fieldLayoutRootScope());
        $rootPayload ??= $this->compileFieldLayout($element);

        try {
            $payload = $rootPayload->forScope($requestedScope);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }
        $tabs = request()->hasHeader('X-Craft-Ui-Root-Scope')
            ? []
            : app(UiHtmlRenderer::class)->tabMenu($rootPayload);

        if (count($tabs) > 1) {
            $selectedTab = request()->input('selectedTab');
            $selectedTab = isset($tabs[$selectedTab]) ? $selectedTab : null;
            $tabHtml = template('_includes/tabs', [
                'tabs' => $tabs,
                'selectedTab' => $selectedTab,
            ], templateMode: TemplateMode::Cp);
        } else {
            $tabHtml = null;
        }

        return [
            'form' => $payload,
            'tabs' => $tabHtml,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ];
    }

    /**
     * The scope the whole layout is compiled at — the namespace a slideout or
     * the legacy editor posts under, and the page root otherwise.
     *
     * @return list<string>
     */
    private function fieldLayoutRootScope(): array
    {
        $namespace = request()->header('X-Craft-Namespace');

        return $this->requestedFormScope(
            'X-Craft-Ui-Root-Scope',
            $namespace === null || $namespace === ''
                ? []
                : explode('[', str_replace([']', '.'], ['', '['], $namespace)),
        );
    }

    /**
     * @param  list<string>  $fallback
     * @return list<string>
     */
    private function requestedFormScope(string $header, array $fallback): array
    {
        $value = request()->header($header);

        if ($value === null) {
            return $fallback;
        }

        try {
            $scope = Json::decode($value);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException("Invalid {$header} header.", $exception);
        }

        if (! is_array($scope) || ! array_is_list($scope) || ! array_all($scope, fn ($segment) => is_string($segment))) {
            throw new BadRequestHttpException("Invalid {$header} header.");
        }

        return $scope;
    }
}
