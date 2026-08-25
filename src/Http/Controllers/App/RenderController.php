<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\App;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Field\Data\MarkdownData;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\Markdown\Markdown as MarkdownService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

readonly class RenderController
{
    public function __construct(
        private Drafts $drafts,
        private ElementHtml $elementHtml,
        private MenuHtml $menuHtml,
        private Volumes $volumes,
    ) {}

    public function elements(Request $request): JsonResponse
    {
        $criteria = $request->validate([
            'elements' => ['required', 'array'],
            'elements.*.type' => ['required', 'string', new ElementTypeRule],
            'elements.*.id' => ['required'],
            'elements.*.siteId' => ['required'],
            'elements.*.instances' => ['required', 'array'],
            'elements.*.fieldId' => ['nullable'],
            'elements.*.ownerId' => ['nullable'],
            'elements.*.revisionId' => ['nullable'],
        ])['elements'];

        $elementHtml = [];

        foreach ($criteria as $criterion) {
            /** @var class-string<ElementInterface> $elementType */
            $elementType = $criterion['type'];
            $id = $criterion['id'];
            $fieldId = $criterion['fieldId'] ?? null;
            $ownerId = $criterion['ownerId'] ?? null;
            $siteId = $criterion['siteId'];
            $instances = $criterion['instances'];

            if (! $id || (! is_numeric($id) && ! (is_array($id) && Arr::isNumeric($id)))) {
                abort(400, 'Invalid element ID');
            }

            $query = $elementType::find()
                ->id($id)
                ->fixedOrder()
                ->drafts(null)
                ->revisions(null)
                ->siteId($siteId)
                ->status(null);

            if ($query instanceof NestedElementQueryInterface) {
                $query
                    ->fieldId($fieldId)
                    ->ownerId($ownerId);
            }

            // if we have revisionId, then we might need to look through soft-deleted elements too
            if (isset($criterion['revisionId'])) {
                $query->trashed(null);
            }

            $elements = $query->all();

            // See if there are any provisional changes we should show
            $this->drafts->loadProvisionalChanges($elements);

            foreach ($elements as $element) {
                foreach ($instances as $key => $instance) {
                    $id = $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id;
                    /** @var 'chip'|'card' $ui */
                    $ui = $instance['ui'] ?? 'chip';

                    // Which actions belong in the rendered menus is the
                    // server's decision: a nested element rendered for its
                    // owner (the criterion's `ownerId`) gets its nested
                    // Duplicate/Delete actions; client-supplied configs
                    // can't request them.
                    $instance['showNestedActions'] = (
                        $ownerId !== null &&
                        $element instanceof NestedElementInterface &&
                        (int) $element->getOwnerId() === (int) $ownerId
                    );

                    $elementHtml[$id][$key] = match ($ui) {
                        'chip' => $this->elementHtml->elementChipHtml($element, $instance),
                        'card' => $this->elementHtml->elementCardHtml($element, $instance),
                    };
                }
            }
        }

        return new JsonResponse([
            'elements' => $elementHtml,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    public function components(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'components' => ['required', 'array'],
            'components.*.type' => ['required', 'string'],
            'components.*.id' => ['required'],
            'components.*.instances' => ['required', 'array'],
            'withMenuItems' => ['nullable', 'boolean'],
            'menuId' => ['nullable'],
        ]);

        $components = $validated['components'];
        $withMenuItems = $validated['withMenuItems'] ?? false;
        $menuId = $validated['menuId'] ?? null;

        $componentHtml = [];
        $menuItemHtml = [];

        foreach ($components as $componentInfo) {
            /** @var class-string<Chippable> $componentType */
            $componentType = $componentInfo['type'];
            $id = $componentInfo['id'];

            $component = null;
            if (is_callable([$componentType, 'get'])) {
                $component = $componentType::get($id);
            } elseif (is_a($componentType, Volume::class, true)) {
                $component = $this->volumes->getVolumeById((int) $id);
            }

            if ($component) {
                foreach ($componentInfo['instances'] as $config) {
                    if (! empty($config['overrides'])) {
                        $component = clone $component;
                        Typecast::configure($component, $config['overrides']);
                    }
                    $componentHtml[$componentType][$id][] = $this->elementHtml->chipHtml($component, $config);
                }

                if ($withMenuItems) {
                    $menuItemHtml[$componentType][$id] = $this->menuHtml->menuItem([
                        'label' => $component->getUiLabel(),
                        'icon' => $component instanceof Iconic ? $component->getIcon() : null,
                        'attributes' => [
                            'data' => [
                                'type' => $component::class,
                                'id' => $component->getId(),
                            ],
                        ],
                    ], $menuId);
                }
            }
        }

        $data = [
            'components' => $componentHtml,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ];

        if ($withMenuItems) {
            $data['menuItems'] = $menuItemHtml;
        }

        return new JsonResponse($data);
    }

    public function markdown(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'markdown' => ['nullable', 'string'],
            'flavor' => ['required', 'string', Rule::in(Arr::pluck(MarkdownField::flavorOptions(), 'value'))],
            'encode' => ['boolean'],
            'inlineOnly' => ['boolean'],
            'sanitizeHtml' => ['boolean'],
            'htmlSanitizer' => ['nullable', 'string', Rule::in(HtmlSanitizers::names())],
        ]);

        $encode = $request->boolean('encode');

        $html = new MarkdownData(
            $validated['markdown'] ?? '',
            $encode ? MarkdownService::FLAVOR_PRE_ENCODED : $validated['flavor'],
            encode: $encode,
            inlineOnly: $request->boolean('inlineOnly'),
            sanitizeHtml: $request->boolean('sanitizeHtml'),
            htmlSanitizer: $validated['htmlSanitizer'] ?? null,
        )->getHtml();

        return new JsonResponse([
            'html' => $html,
        ]);
    }
}
