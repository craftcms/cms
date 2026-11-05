<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Data\FieldChangeComparison;
use CraftCms\Cms\Element\Data\FieldComparisonRow;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\FieldPreviews;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Controls\NestedElements;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NestedUiPayload;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Uri;

use function CraftCms\Cms\t;

/**
 * Compares the fields of two versions of an element: one field, for the “Compare changes” field action, or all of
 * them, for the Compare screen.
 *
 * - A draft, or an element nested in one, is compared with the current element.
 * - A revision, or an element nested in one, is compared with the revision before it.
 *
 * Fields are shown with {@see FieldPreviews}, as the AI assistant shows its proposed changes.
 *
 * @internal
 *
 * @since 6.0.0
 */
#[Singleton]
readonly class FieldChangeComparisons
{
    public function __construct(
        private FieldPreviews $previews,
    ) {}

    /**
     * Returns whether an element’s field values can be compared with the ones it was based on.
     */
    public static function isComparable(ElementInterface $element): bool
    {
        return $element->id !== null
            && ! $element->getIsCanonical()
            && ! $element->getIsUnpublishedDraft();
    }

    /**
     * Returns `null` when the field isn’t in the element’s form.
     */
    public function compare(ElementInterface $element, BaseField $layoutElement): ?FieldChangeComparison
    {
        if ($element->getIsRevision()) {
            // An unchanged nested element can be shared by its owner’s revisions, so its base can be itself.
            [$base, $previous] = $this->previousRevision($element);

            return $this->comparison(
                $layoutElement,
                from: $base,
                fromLabel: $previous ? $this->label($previous) : t('No earlier revision'),
                to: $element,
                toLabel: $this->label($element),
                hasBase: $previous !== null,
            );
        }

        return $this->comparison(
            $layoutElement,
            from: $element->getCanonical(),
            fromLabel: t('Current'),
            to: $element,
            toLabel: $this->label($element),
        );
    }

    /**
     * Returns the Compare screen’s URL for a version of an entry in a section, comparing it with what it was based
     * on, or null when there’s nothing to compare: an unpublished draft, or a current entry without revisions. A
     * nested element’s is its root owner’s.
     */
    public function compareUrl(ElementInterface $element): ?string
    {
        $element = $element->getRootOwner();
        $canonical = $element->getCanonical(true);
        $isDraft = $element->getIsDraft() && ! $element->isProvisionalDraft;

        if (
            ! $canonical instanceof Entry ||
            ! $canonical->id ||
            ! $canonical->sectionId ||
            $element->getIsUnpublishedDraft() ||
            (! $isDraft && ! $element->getIsRevision() && ! $canonical->hasRevisions())
        ) {
            return null;
        }

        $url = Uri::of($canonical->getCpEditUrl());

        return $url->withPath("{$url->path()}/compare")->withQuery(array_filter([
            'draftId' => $isDraft ? $element->draftId : null,
            'revisionId' => $element->revisionId,
        ]))->value();
    }

    /**
     * Returns what a version of an element is compared with by default: the current element for a draft, and the
     * revision before for a revision, or for the current element, the revision before its current one.
     */
    public function baseOf(ElementInterface $element): ?ElementInterface
    {
        if ($element->getIsDraft()) {
            return $element->getCanonical();
        }

        $revision = $element->getIsRevision() ? $element : $element->getCurrentRevision();

        return $revision ? $this->previousRevision($revision)[1] : null;
    }

    /**
     * Compares every field in two versions of an element, which can be in different sites, as two read-only forms
     * whose fields are in the same order: the newer version’s, followed by fields only the older version has. Each
     * changed field gets the “modified” status on both sides, and nothing else has a status.
     *
     * @return array{from: UiPayload, to: UiPayload, rows: list<FieldComparisonRow>}
     */
    public function compareElements(ElementInterface $from, ElementInterface $to): array
    {
        $namespace = 'compare'.Str::random(8);
        $fromPayload = $this->previews->compile($from, $from, "{$namespace}From", ControlMode::ReadOnly);
        $toPayload = $this->previews->compile($to, $to, "{$namespace}To", ControlMode::ReadOnly);
        $fromNodes = $fromPayload ? $this->previews->fieldNodes($fromPayload->nodes) : [];
        $toNodes = $toPayload ? $this->previews->fieldNodes($toPayload->nodes) : [];
        $pairs = [];

        foreach ($toNodes as $toNode) {
            $layoutElement = $to->getFieldLayout()?->getElementByUid($this->nodeUid($toNode));

            if ($layoutElement instanceof BaseField) {
                $match = $this->previews->matchingLayoutElement($from, $layoutElement);
                $fromNode = $match ? array_find($fromNodes, fn (NodePayload $node): bool => $this->nodeUid($node) === (string) $match->uid) : null;
                $pairs[] = [$layoutElement, $fromNode, $toNode];
            }
        }

        $matchedFromNodes = array_filter(array_column($pairs, 1));

        foreach ($fromNodes as $fromNode) {
            $layoutElement = $from->getFieldLayout()?->getElementByUid($this->nodeUid($fromNode));

            if ($layoutElement instanceof BaseField && ! in_array($fromNode, $matchedFromNodes, true)) {
                $pairs[] = [$layoutElement, $fromNode, null];
            }
        }

        $rows = [];
        $paneNodes = ['from' => [], 'to' => []];

        foreach ($pairs as [$layoutElement, $fromNode, $toNode]) {
            $changed = $this->changed($layoutElement, $fromPayload, $fromNode, $from, $toPayload, $toNode, $to);
            $rows[] = new FieldComparisonRow(
                fromUid: $fromNode ? $this->nodeUid($fromNode) : null,
                toUid: $toNode ? $this->nodeUid($toNode) : null,
                changed: $changed,
            );

            if ($fromNode) {
                $paneNodes['from'][] = $this->paneNode($fromNode, $changed);
            }

            if ($toNode) {
                $paneNodes['to'][] = $this->paneNode($toNode, $changed);
            }
        }

        return [
            'from' => $this->pane($fromPayload, $paneNodes['from']),
            'to' => $this->pane($toPayload, $paneNodes['to']),
            'rows' => $rows,
        ];
    }

    /**
     * Returns what a version of an element is called in a comparison: “Current”, a revision, or a draft.
     */
    public function label(ElementInterface $element): string
    {
        $root = $element->getRootOwner();

        return match (true) {
            $root->getIsRevision() => $root->getRevisionLabel(),
            $root->getIsDraft() && $root->isProvisionalDraft => t('Your changes'),
            $root->getIsDraft() => $root->draftName ?: t('Draft'),
            default => t('Current'),
        };
    }

    private function nodeUid(NodePayload $node): string
    {
        return (string) ($node->props['layoutUid'] ?? $node->uid);
    }

    /**
     * Returns whether a field changed between two versions, or `null` when that can’t be told from their form values.
     * Translations are meant to differ, so between sites only a value missing on one side counts as a change, and a
     * field that isn’t translated, which both sites of a version share, hasn’t changed.
     */
    private function changed(
        BaseField $layoutElement,
        ?UiPayload $fromPayload,
        ?NodePayload $fromNode,
        ElementInterface $from,
        ?UiPayload $toPayload,
        ?NodePayload $toNode,
        ElementInterface $to,
    ): ?bool {
        if ($fromNode === null || $toNode === null) {
            return true;
        }

        $otherSite = $from->siteId !== $to->siteId;

        if ($otherSite && $from->id === $to->id && ! ($toNode->props['translatable'] ?? false)) {
            return false;
        }

        [$unchanged] = $this->compareNodes($layoutElement, [$fromPayload, $fromNode], $from, [$toPayload, $toNode], $to);

        return match (true) {
            $unchanged === null => null,
            $otherSite => $this->isEmpty($fromPayload, $fromNode, $from, $layoutElement) !== $this->isEmpty($toPayload, $toNode, $to, $layoutElement),
            default => ! $unchanged,
        };
    }

    /**
     * Returns a field’s node as one side of the Compare screen shows it: full width, so it can line up with the other
     * side’s, with the comparison’s status rather than the version’s own, and with nothing to act on.
     */
    private function paneNode(NodePayload $node, ?bool $changed): NodePayload
    {
        $node = $this->previews->withoutActionMenus($node);

        return new NodePayload(
            type: $node->type,
            component: $node->component,
            props: [
                ...$node->props,
                'width' => null,
                'status' => $changed ? AttributeStatus::Modified->value : null,
                'statusLabel' => $changed ? t('Changed') : null,
            ],
            uid: $node->uid,
            control: $node->control,
            children: $node->children,
        );
    }

    /**
     * Returns a version’s form with just the given field nodes, in the given order, within the tabs they’re in. Sidebar
     * tabs are kept with the rest, which a form without a sidebar shows as ordinary tabs.
     *
     * @param  list<NodePayload>  $nodes
     */
    private function pane(?UiPayload $payload, array $nodes): UiPayload
    {
        $tabsByField = [];

        foreach ($payload->nodes ?? [] as $node) {
            if ($node->component === 'craft:tab') {
                foreach ($this->previews->fieldNodes($node->children ?? []) as $field) {
                    $tabsByField[$this->nodeUid($field)] = $node;
                }
            }
        }

        /** @var array<string, array{0: NodePayload|null, 1: list<NodePayload>}> $groups */
        $groups = [];

        foreach ($nodes as $node) {
            $tab = $tabsByField[$this->nodeUid($node)] ?? null;
            $key = $tab ? "tab:$tab->uid" : 'field:'.$this->nodeUid($node);
            $groups[$key] ??= [$tab, []];
            $groups[$key][1][] = $node;
        }

        $paneNodes = [];

        foreach ($groups as [$tab, $fields]) {
            $paneNodes = [...$paneNodes, ...($tab ? [new NodePayload(
                type: $tab->type,
                component: $tab->component,
                props: $tab->props,
                uid: $tab->uid,
                children: $fields,
            )] : $fields)];
        }

        return new UiPayload(
            scope: $payload->scope ?? [],
            refreshable: false,
            nodes: $paneNodes,
            values: $payload->values ?? [],
            errors: [],
            globalErrors: [],
        );
    }

    private function isEmpty(?UiPayload $payload, ?NodePayload $node, ElementInterface $element, BaseField $layoutElement): bool
    {
        if ($node && $this->loadsOwnValues($node) && ($nested = $this->nestedElements($element, $layoutElement)) !== null) {
            return $nested === [];
        }

        $value = $payload && $node ? data_get($payload->values, $node->control->path ?? []) : null;

        return $value === null || $value === '' || $value === [];
    }

    /**
     * Compiles the field on both elements, read-only and each with its own field layout, as the AI assistant previews
     * its proposed changes: text as a diff, and anything else as the field’s own controls.
     */
    private function comparison(
        BaseField $layoutElement,
        ?ElementInterface $from,
        string $fromLabel,
        ElementInterface $to,
        string $toLabel,
        bool $hasBase = true,
    ): ?FieldChangeComparison {
        // Unique to this comparison, so its fields don’t share IDs with the editor’s.
        $namespace = 'fieldChanges'.Str::random(8);
        $toField = $this->compileField($to, $layoutElement, "{$namespace}To");

        if ($toField === null) {
            return null;
        }

        $fromField = $from ? $this->compileField($from, $layoutElement, "{$namespace}From") : null;
        [$unchanged, $diff] = $this->compareNodes($layoutElement, $fromField, $from, $toField, $to);

        return new FieldChangeComparison(
            fromLabel: $fromLabel,
            toLabel: $toLabel,
            hasBase: $hasBase,
            unchanged: $hasBase && $unchanged === true,
            diff: $diff,
            preview: $diff === null ? [
                'current' => $fromField ? $this->previews->payloadFor(...$fromField) : null,
                'proposed' => $this->previews->payloadFor(...$toField),
            ] : null,
        );
    }

    /**
     * Compares a field’s compiled nodes on two elements. The field is unchanged when both have the same value, or when
     * both are the same element in the same site, and it’s `null` when that can’t be told from their form values.
     *
     * @param  array{UiPayload, NodePayload}|null  $fromField
     * @param  array{UiPayload, NodePayload}|null  $toField
     * @return array{?bool, array{current: string, proposed: string}|null}
     */
    private function compareNodes(
        BaseField $layoutElement,
        ?array $fromField,
        ?ElementInterface $from,
        ?array $toField,
        ElementInterface $to,
    ): array {
        [$fromPayload, $fromNode] = $fromField ?? [null, null];
        [$toPayload, $toNode] = $toField ?? [null, null];
        $fromValue = $fromNode ? data_get($fromPayload->values, $fromNode->control->path ?? []) : null;
        $toValue = $toNode ? data_get($toPayload->values, $toNode->control->path ?? []) : null;
        $diff = $fromNode && $toNode ? FieldPreviews::diff($fromValue, $toValue, [$from, $to], $layoutElement) : null;

        $unchanged = match (true) {
            $fromNode === null || $toNode === null => false,
            $from->id === $to->id && $from->siteId === $to->siteId => true,
            $this->loadsOwnValues($toNode) || $this->loadsOwnValues($fromNode) => $from ? $this->sameNestedElements($layoutElement, $from, $to) : null,
            default => $this->sameValue($fromValue, $toValue),
        };

        return [$unchanged, $diff];
    }

    /**
     * Returns whether two versions’ control values are the same as the browser gets them. A value can hold objects,
     * such as a table’s date and link cells, which are separate instances on each version even when they’re equal.
     */
    private function sameValue(mixed $fromValue, mixed $toValue): bool
    {
        return $fromValue === $toValue || Json::encode($fromValue) === Json::encode($toValue);
    }

    /**
     * Returns whether a field holds the same nested elements on two versions, in the same order and with the same
     * content, or `null` when it doesn’t hold nested elements of its own, such as a Matrix field in a Content Block.
     * A draft’s or revision’s copy of a nested element is the same element as the one it was copied from.
     */
    private function sameNestedElements(BaseField $layoutElement, ElementInterface $from, ElementInterface $to): ?bool
    {
        $fromNested = $this->nestedElements($from, $layoutElement);
        $toNested = $this->nestedElements($to, $layoutElement);

        return $fromNested === null || $toNested === null ? null : $fromNested === $toNested;
    }

    /**
     * Returns what identifies a field’s nested elements and their content, in order, or `null` if the field doesn’t
     * hold nested elements of its own.
     *
     * @return list<array{int|null, string|null, bool, array<string, mixed>}>|null
     */
    private function nestedElements(ElementInterface $element, BaseField $layoutElement): ?array
    {
        $field = $layoutElement instanceof CustomField ? $layoutElement->getField() : null;
        $value = $field instanceof ElementContainerFieldInterface ? $element->getFieldValue($field->handle) : null;

        if (! $value instanceof ElementQueryInterface) {
            return null;
        }

        return array_map(fn (ElementInterface $nested): array => [
            $nested->getCanonicalId(),
            $nested->title,
            (bool) $nested->enabled,
            $nested->getSerializedFieldValues(),
        ], (clone $value)->status(null)->all());
    }

    /**
     * Returns whether a field’s controls include ones that load their own values, such as Matrix fields shown as
     * cards, including ones nested in other fields’ entries. They have no values in the form, so the same form values
     * don’t mean the same content.
     */
    private function loadsOwnValues(NodePayload $node): bool
    {
        if ($node->control?->type === NestedElements::class) {
            return true;
        }

        $nested = [
            ...($node->children ?? []),
            ...array_merge(...array_map(fn (NestedUiPayload $ui): array => $ui->nodes, $node->control->uis ?? [])),
        ];

        return array_any($nested, $this->loadsOwnValues(...));
    }

    /**
     * Compiles an element’s form with its own field layout, and finds the field in it.
     *
     * @return array{UiPayload, NodePayload}|null
     */
    private function compileField(ElementInterface $element, BaseField $layoutElement, string $namespace): ?array
    {
        $match = $this->previews->matchingLayoutElement($element, $layoutElement);

        if ($match === null) {
            return null;
        }

        $payload = $this->previews->compile($element, $element, $namespace, ControlMode::ReadOnly);
        $node = $payload ? $this->previews->findNode($payload->nodes, (string) $match->uid) : null;

        return $payload && $node ? [$payload, $node] : null;
    }

    /**
     * Returns the revision before a revision, and the revision it belongs to. For an element nested in a revision,
     * that’s the same element in the owner’s previous revision, which may not have it.
     *
     * @return array{?ElementInterface, ?ElementInterface}
     */
    private function previousRevision(ElementInterface $revision): array
    {
        $owner = $revision instanceof NestedElementInterface ? $revision->getOwner() : null;

        if ($owner?->getIsRevision()) {
            [$previousOwner, $previous] = $this->previousRevision($owner);

            if ($previousOwner === null) {
                return [null, $previous];
            }

            $query = $revision::find();

            if (! $query instanceof NestedElementQueryInterface) {
                return [null, $previous];
            }

            $canonicalId = $revision->getCanonicalId();
            $base = $query
                ->ownerId($previousOwner->id)
                ->fieldId($revision->getField()?->id)
                ->siteId($revision->siteId)
                ->revisions(null)
                ->status(null)
                ->where(fn (Builder $query) => $query
                    ->where('elements.id', $canonicalId)
                    ->orWhere('elements.canonicalId', $canonicalId))
                ->one();

            return [$base, $previous];
        }

        $previous = $revision::find()
            ->revisionOf($revision->getCanonicalId())
            ->siteId($revision->siteId)
            ->status(null)
            ->where('revisions.num', '<', $revision->revisionNum)
            ->orderByDesc('revisions.num')
            ->one();

        return [$previous, $previous];
    }
}
