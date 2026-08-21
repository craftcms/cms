<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\ElementResponse;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class CopyElementValuesController
{
    use EnforcesPermissions;

    public function __construct(
        private ElementRequest $request,
        private Sites $sites,
    ) {}

    public function __invoke(): Response
    {
        $element = $this->request->element(checkForProvisionalDraft: true);

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        $this->request->validate([
            'fromSiteId' => ['required', 'integer'],
            'layoutElementUid' => ['required', 'uuid'],
            'namespace' => ['nullable', 'string'],
        ]);

        $copyFromSiteId = $this->request->integer('fromSiteId');

        if (! $site = $this->sites->getSiteById($copyFromSiteId)) {
            abort(400, "Invalid site ID: $copyFromSiteId");
        }

        $this->requirePermission("editSite:$site->uid");

        $layoutElementUid = $this->request->input('layoutElementUid');
        $namespace = $this->request->input('namespace');

        $fromElement = $element::find()
            ->id($element->id)
            ->structureId($element->structureId)
            ->siteId($copyFromSiteId)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->one();

        if (! $fromElement) {
            throw new UnsupportedSiteException($element, $copyFromSiteId, 'Attempting to copy element content from an unsupported site.');
        }

        $layoutElement = $element->getFieldLayout()->getElementByUid($layoutElementUid);
        if (! $layoutElement instanceof BaseField || ! $layoutElement->isCrossSiteCopyable($element)) {
            abort(400, "Invalid layout element UUID: $layoutElementUid");
        }

        if ($layoutElement instanceof CustomField) {
            /** @var FieldInterface&CrossSiteCopyableFieldInterface $field */
            $field = $layoutElement->getField();
            $field->copyCrossSiteValue($fromElement, $element);
        } else {
            $attribute = $layoutElement->attribute();
            $element->$attribute = $fromElement->$attribute;
        }

        $payload = app(FieldLayoutCompiler::class)->compile(
            $element->getFieldLayout(),
            $element,
            new FormContext(namespace: $namespace ?? []),
        );
        $renderer = app(FormHtmlRenderer::class);
        $node = null;

        foreach ($payload->nodes as $tab) {
            $node = array_find(
                $tab->children ?? [],
                fn ($node): bool => ($node->props['layoutUid'] ?? $node->uid) === $layoutElementUid,
            );

            if ($node !== null) {
                break;
            }
        }

        $html = $node === null ? null : $renderer->renderNodes([$node], $payload);

        return new ElementResponse()->success($element, t('Field value copied.'), [
            'fieldHtml' => $html,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }
}
