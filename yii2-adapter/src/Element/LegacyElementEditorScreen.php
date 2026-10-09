<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\ElementEditorContentResolving;
use CraftCms\Cms\Element\Events\ElementEditorPayloadResolving;
use CraftCms\Cms\Element\Events\ElementSidebarHtmlResolving;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use ReflectionMethod;

/**
 * Preserves HTML edit-screen customizations around the native editor forms.
 *
 * @since 6.0.0
 */
class LegacyElementEditorScreen
{
    public function handle(ElementEditorPayloadResolving $event): void
    {
        $element = $event->element;
        $data = $event->data;
        $containerId = $event->containerId;
        $formHtml = Html::tag('div', '', ['data-element-editor-form' => true]);
        $sidebarHtml = Html::tag('div', '', ['data-element-editor-form' => true]);
        $screen = new CpScreenResponse()
            ->editUrl($element->getCpEditUrl())
            ->title($data['title'])
            ->docTitle($data['docTitle'])
            ->crumbs($data['crumbs'])
            ->contentHtml($formHtml)
            ->metaSidebarHtml($sidebarHtml);

        $assets = HtmlStack::capture(function() use ($element, $screen, $containerId, $data): string {
            event($content = new ElementEditorContentResolving($element, (string) $screen->contentHtml, $data['readOnly']));
            $screen->contentHtml($content->html);

            if ($this->hasLegacySidebar($element)) {
                $screen->metaSidebarHtml($element->getSidebarHtml($data['readOnly']));
            } else {
                event($sidebar = new ElementSidebarHtmlResolving($element, $data['readOnly'], (string) $screen->metaSidebarHtml));
                $screen->metaSidebarHtml($sidebar->html);
            }

            $element->prepareEditScreen($screen, $containerId);

            $screen->contentHtml($this->resolveHtml($screen->contentHtml));
            $screen->metaSidebarHtml($this->resolveHtml($screen->metaSidebarHtml));

            return '';
        });

        $event->data = [
            ...$data,
            'editorComponent' => 'craft-legacy:element-editor',
            'title' => $screen->title,
            'docTitle' => $screen->docTitle,
            'crumbs' => is_callable($screen->crumbs) ? ($screen->crumbs)() : $screen->crumbs,
            'editorContentHtml' => $screen->contentHtml === $formHtml ? null : $screen->contentHtml,
            'editorSidebarHtml' => $screen->metaSidebarHtml === $sidebarHtml ? null : $screen->metaSidebarHtml,
            'editorAssets' => $assets,
            'editorContainerId' => $containerId,
        ];
    }

    private function hasLegacySidebar(ElementInterface $element): bool
    {
        if (!$element instanceof Element) {
            return true;
        }

        // HTML-only overrides cannot be replaced by a UI without losing the
        // type's fields. Types that port both meta-field methods use the UI.
        return new ReflectionMethod($element, 'getSidebarHtml')->getDeclaringClass()->getName() !== Element::class
            || new ReflectionMethod($element, 'metaFieldsHtml')->getDeclaringClass()->getName()
                !== new ReflectionMethod($element, 'metaFieldsNodes')->getDeclaringClass()->getName();
    }

    private function resolveHtml(mixed $html): string
    {
        return (string) (is_callable($html) ? $html() : $html);
    }
}
