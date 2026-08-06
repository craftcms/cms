<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\Nodes\Tab;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\TemplateMode;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function CraftCms\Cms\template;

trait UpdatesFieldLayout
{
    /**
     * @param  array<string, mixed>  $formConfig
     * @return array<string, mixed>
     */
    protected function fieldLayoutData(ElementInterface $element, array $formConfig = []): array
    {
        if ($element instanceof Entry) {
            return $this->entryFieldLayoutData($element, $formConfig);
        }

        $namespace = request()->header('X-Craft-Namespace');
        $fieldLayout = $element->getFieldLayout();
        $form = $fieldLayout->createForm($element, false, $formConfig + [
            'namespace' => $namespace,
            'registerDeltas' => false,
            'visibleElements' => request()->input('visibleLayoutElements'),
            'staticElements' => request()->input('staticLayoutElements'),
        ]);
        $missingElements = [];

        foreach ($form->tabs as $tab) {
            if (! $tab->getUid()) {
                continue;
            }

            $elementInfo = [];

            foreach ($tab->elements as $formElement) {
                if ($formElement->isConditional) {
                    $elementInfo[] = [
                        'uid' => $formElement->layoutElement->uid,
                        'html' => $formElement->html,
                        'static' => $formElement->isStatic,
                    ];
                }
            }

            $missingElements[] = [
                'uid' => $tab->getUid(),
                'id' => $tab->getId(),
                'elements' => $elementInfo,
            ];
        }

        $tabs = $form->getTabMenu();
        if (count($tabs) > 1) {
            $selectedTab = request()->input('selectedTab');
            $selectedTab = isset($tabs[$selectedTab]) ? $selectedTab : null;
            $tabHtml = InputNamespace::namespaceInputs(fn () => template('_includes/tabs', [
                'tabs' => $tabs,
                'selectedTab' => $selectedTab,
            ], templateMode: TemplateMode::Cp), $namespace);
        } else {
            $tabHtml = null;
        }

        return [
            'tabs' => $tabHtml,
            'missingElements' => $missingElements,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ];
    }

    /**
     * @param  array{registerDeltas?: bool}  $formConfig
     * @return array<string, mixed>
     */
    private function entryFieldLayoutData(Entry $entry, array $formConfig): array
    {
        $namespace = request()->header('X-Craft-Namespace');
        $rootScope = $this->requestedFormScope(
            'X-Craft-Form-Root-Scope',
            $namespace === null || $namespace === ''
                ? []
                : explode('[', str_replace([']', '.'], ['', '['], $namespace)),
        );
        $requestedScope = $this->requestedFormScope('X-Craft-Form-Scope', $rootScope);
        $compile = fn (): FormPayload => app(FieldLayoutCompiler::class)->compile(
            $entry->getFieldLayout(),
            $entry,
            new FormContext(
                namespace: $rootScope,
                errors: $entry->errors()->getMessages(),
                mode: ControlMode::Editable,
                refreshable: true,
            ),
        );
        $rootPayload = array_key_exists('registerDeltas', $formConfig)
            ? DeltaRegistry::withActive($formConfig['registerDeltas'], $compile)
            : $compile();
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
            'missingElements' => $this->entryLayoutElements($rootPayload, $renderer),
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

    /** @return list<array{uid: string, id: string, elements: list<array{uid: string, html: bool|string, static: bool}>}> */
    private function entryLayoutElements(FormPayload $payload, FormHtmlRenderer $renderer): array
    {
        $previousVisible = request()->input('visibleLayoutElements', []);
        $previousStatic = request()->input('staticLayoutElements', []);
        $tabs = [];

        foreach ($payload->nodes as $tab) {
            if ($tab->type !== Tab::class) {
                continue;
            }
            if ($tab->uid === null) {
                continue;
            }
            $elements = [];
            $visible = [];

            foreach ($tab->children ?? [] as $node) {
                $uid = $renderer->layoutElementUid($node);

                if ($uid === null) {
                    continue;
                }

                $visible[] = $uid;
                $static = $renderer->nodeIsStatic($node);
                $unchanged = in_array($uid, $previousVisible[$tab->uid] ?? [], true)
                    && $static === in_array($uid, $previousStatic[$tab->uid] ?? [], true)
                    && ! $renderer->nodeHasErrors($node, $payload);
                $elements[] = [
                    'uid' => $uid,
                    'html' => $unchanged ? true : $renderer->renderNodes([$node], $payload),
                    'static' => $static,
                ];
            }

            foreach (array_diff($previousVisible[$tab->uid] ?? [], $visible) as $uid) {
                $elements[] = ['uid' => $uid, 'html' => false, 'static' => false];
            }

            $tabs[] = [
                'uid' => $tab->uid,
                'id' => $renderer->tabBaseId($tab),
                'elements' => $elements,
            ];
        }

        return $tabs;
    }
}
