<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\TemplateMode;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function CraftCms\Cms\template;

trait UpdatesFieldLayout
{
    /**
     * @return array<string, mixed>
     */
    protected function fieldLayoutData(ElementInterface $element): array
    {
        $namespace = request()->header('X-Craft-Namespace');
        $rootScope = $this->requestedFormScope(
            'X-Craft-Form-Root-Scope',
            $namespace === null || $namespace === ''
                ? []
                : explode('[', str_replace([']', '.'], ['', '['], $namespace)),
        );
        $requestedScope = $this->requestedFormScope('X-Craft-Form-Scope', $rootScope);
        $rootPayload = app(FieldLayoutCompiler::class)->compile(
            $element->getFieldLayout(),
            $element,
            new FormContext(
                namespace: $rootScope,
                errors: $element->errors()->getMessages(),
                mode: ControlMode::Editable,
                refreshable: true,
            ),
        );
        try {
            $payload = $rootPayload->forScope($requestedScope);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }
        $renderer = app(FormHtmlRenderer::class);
        $tabs = $renderer->tabMenu($rootPayload);

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

        if (! is_array($scope) || ! array_is_list($scope) || ! array_all($scope, is_string(...))) {
            throw new BadRequestHttpException("Invalid {$header} header.");
        }

        return $scope;
    }
}
