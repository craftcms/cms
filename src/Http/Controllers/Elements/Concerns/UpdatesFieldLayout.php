<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\template;

trait UpdatesFieldLayout
{
    protected function fieldLayoutData(ElementInterface $element, array $formConfig = []): array
    {
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
}
