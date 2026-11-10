<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Data\FieldChangeComparison;
use CraftCms\Cms\Element\Data\FieldComparisonRow;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\FieldPreviews;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Controls\NestedElements;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NestedUiPayload;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Database\Query\Builder;

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
        // An unchanged nested element can be shared by its owner’s revisions, so its base can be itself. The version
        // the base belongs to names the side, even when a nested element wasn’t in it yet.
        [$base, $version] = $this->base($element);

        return $this->comparison(
            $layoutElement,
            from: $base,
            fromLabel: $version ? $this->label($version) : t('No earlier revision'),
            to: $element,
            toLabel: $this->label($element),
            hasBase: $version !== null,
        );
    }

    /**
     * Returns the Compare screen’s URL for a version of an element, comparing it with what it was based on, or null
     * when there’s nothing to compare it with, as with the first revision, or it can’t be compared: an unpublished
     * draft. A nested element’s is its root owner’s.
     *
     * @param  bool|null  $hasBase  Whether the version is known to have something to compare with, which spares looking.
     */
    public function compareUrl(ElementInterface $element, ?bool $hasBase = null): ?string
    {
        $element = $element->getRootOwner();

        if (! ($hasBase ?? $this->baseOf($element) !== null)) {
            return null;
        }

        return $this->compareScreenUrl($element, ['revisionId' => $element->revisionId]);
    }

    /**
     * Returns the Compare screen’s URL for a version of an element, comparing it with the current element in another
     * site, or null when the element can’t be compared: a revision, or an unpublished draft. A nested element’s is its
     * root owner’s.
     */
    public function siteCompareUrl(ElementInterface $element, int $siteId): ?string
    {
        $element = $element->getRootOwner();

        if ($element->getIsRevision()) {
            return null;
        }

        return $this->compareScreenUrl($element, ['fromSiteId' => $siteId]);
    }

    /**
     * Returns the Compare screen’s URL showing a version of an element, or null when it can’t be shown: an unpublished
     * draft. The user’s unsaved edits are the current element’s, which the screen shows in their place.
     *
     * @param  array<string, int|string|null>  $params
     */
    private function compareScreenUrl(ElementInterface $element, array $params): ?string
    {
        $canonical = $element->getCanonical(true);

        if (! $canonical->id || $element->getIsUnpublishedDraft()) {
            return null;
        }

        return Url::urlWithParams(route('craft.cp.elements.compare', ['id' => $canonical->id]), array_filter([
            'draftId' => $element->getIsDraft() && ! $element->isProvisionalDraft ? $element->draftId : null,
            ...$params,
            'siteId' => $element::isLocalized() ? $element->siteId : null,
        ]));
    }

    /**
     * Returns what a version of an element is compared with by default: the current element for a draft or an element
     * nested in one, and the revision before for a revision, or for the current element, the revision before its
     * current one. It’s what the “Compare changes” field action and the Compare screen both compare with.
     */
    public function baseOf(ElementInterface $element): ?ElementInterface
    {
        return $this->base($element)[0];
    }

    /**
     * Returns what a version of an element is compared with by default, as {@see self::baseOf()} does, along with the
     * version of its root element that it belongs to, which names it: the current element, or the revision before.
     * For an element nested in a revision, the base is the same element in the owner’s previous revision, which may
     * not have it, so there can be a version without a base.
     *
     * @return array{?ElementInterface, ?ElementInterface}
     */
    private function base(ElementInterface $element): array
    {
        if ($element->getIsRevision()) {
            return $this->previousRevision($element);
        }

        // A draft, or an element nested in one, which needn't be a draft itself.
        if (! $element->getIsCanonical()) {
            $canonical = $element->getCanonical();

            return [$canonical, $canonical];
        }

        $revision = $element->getCurrentRevision();

        return $revision ? $this->previousRevision($revision) : [null, null];
    }

    /**
     * Compares every field in two versions of an element, which can be in different sites, or in two separate elements,
     * as two read-only forms whose fields are in the same order: the newer version’s, followed by fields only the older
     * version has. Changed fields are badged on the sides {@see self::badgedSides()} picks, and no field has a status.
     *
     * The `counterparts` pair each side's fields with the other side's by the same rules as the rows, by their layout
     * element UIDs, wherever those differ, so a field a condition shows only once the page has loaded finds its row.
     *
     * @return array{from: UiPayload, to: UiPayload, rows: list<FieldComparisonRow>, badged: array{from: bool, to: bool}, counterparts: array{from: array<string, string>, to: array<string, string>}}
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
        $previousFromNode = null;

        // A field only the older version has goes after the one before it there, so it stays in the older version's
        // order, and in its tab.
        foreach ($fromNodes as $fromNode) {
            $layoutElement = $from->getFieldLayout()?->getElementByUid($this->nodeUid($fromNode));

            if (! $layoutElement instanceof BaseField) {
                continue;
            }

            if (! in_array($fromNode, $matchedFromNodes, true)) {
                $previousIndex = $previousFromNode
                    ? array_find_key($pairs, fn (array $pair): bool => $pair[1] === $previousFromNode)
                    : null;
                array_splice($pairs, $previousIndex === null ? 0 : $previousIndex + 1, 0, [[$layoutElement, $fromNode, null]]);
            }

            $previousFromNode = $fromNode;
        }

        $rows = [];
        $paneNodes = ['from' => [], 'to' => []];
        $badged = $this->badgedSides($from, $to);

        foreach ($pairs as [$layoutElement, $fromNode, $toNode]) {
            $changed = $this->changed($layoutElement, $fromPayload, $fromNode, $from, $toPayload, $toNode, $to);
            $rows[] = new FieldComparisonRow(
                fromUid: $fromNode ? $this->nodeUid($fromNode) : null,
                toUid: $toNode ? $this->nodeUid($toNode) : null,
                changed: $changed,
            );

            if ($fromNode) {
                $paneNodes['from'][] = $this->paneNode($fromNode, $badged['from'] && $changed === true);
            }

            if ($toNode) {
                $paneNodes['to'][] = $this->paneNode($toNode, $badged['to'] && $changed === true);
            }
        }

        return [
            'from' => $this->pane($fromPayload, $paneNodes['from']),
            'to' => $this->pane($toPayload, $paneNodes['to']),
            'rows' => $rows,
            'badged' => $badged,
            'counterparts' => ['from' => $this->counterparts($from, $to), 'to' => $this->counterparts($to, $from)],
        ];
    }

    /**
     * Returns the UIDs of the fields in one element's layout, conditional ones included, by which the other element's
     * layout has the same field under another UID.
     *
     * @return array<string, string>
     */
    private function counterparts(ElementInterface $element, ElementInterface $other): array
    {
        $counterparts = [];

        foreach ($element->getFieldLayout()?->getElementsByType(BaseField::class) ?? [] as $layoutElement) {
            $match = $this->previews->matchingLayoutElement($other, $layoutElement);

            if ($match && $match->uid !== $layoutElement->uid) {
                $counterparts[(string) $layoutElement->uid] = (string) $match->uid;
            }
        }

        return $counterparts;
    }

    /**
     * Returns which sides of a comparison badge their changed fields: the side that made the changes, when one did,
     * and otherwise both.
     *
     * - Of two revisions, the newer one.
     * - Of a revision and the current element, the current element.
     * - Of a draft and the current element or a revision, the draft.
     * - Of the user’s unsaved edits and the current element or a revision, the edits.
     * - Of two separate elements, both.
     *
     * @return array{from: bool, to: bool}
     */
    public function badgedSides(ElementInterface $from, ElementInterface $to): array
    {
        if ($from->getCanonicalId() !== $to->getCanonicalId()) {
            return ['from' => true, 'to' => true];
        }

        $kind = fn (ElementInterface $element): string => match (true) {
            $element->getIsRevision() => 'revision',
            $element->isProvisionalDraft => 'edits',
            $element->getIsDraft() => 'draft',
            default => 'current',
        };

        $changer = match ([$kind($from), $kind($to)]) {
            ['revision', 'revision'] => $from->revisionNum <=> $to->revisionNum,
            ['revision', 'current'], ['revision', 'draft'], ['current', 'draft'],
            ['revision', 'edits'], ['current', 'edits'] => -1,
            ['current', 'revision'], ['draft', 'revision'], ['draft', 'current'],
            ['edits', 'revision'], ['edits', 'current'] => 1,
            default => 0,
        };

        return ['from' => $changer >= 0, 'to' => $changer <= 0];
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
     * Between sites, a field that isn’t translated, which both sites of a version share, hasn’t changed.
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

        // Two separate elements' values are compared as they are, in whichever sites.
        $otherSite = $from->siteId !== $to->siteId && $from->getCanonicalId() === $to->getCanonicalId();

        if ($otherSite && $from->id === $to->id && ! ($toNode->props['translatable'] ?? false)) {
            return false;
        }

        [$unchanged] = $this->compareNodes($layoutElement, [$fromPayload, $fromNode], $from, [$toPayload, $toNode], $to);

        return $unchanged === null ? null : ! $unchanged;
    }

    /**
     * Returns a field’s node as one side of the Compare screen shows it: full width, so it can line up with the other
     * side’s, without the version’s own status, badged when it’s the newer version’s and changed, and with nothing to
     * act on.
     */
    private function paneNode(NodePayload $node, bool $badged): NodePayload
    {
        $node = $this->previews->withoutActionMenus($node);

        return new NodePayload(
            type: $node->type,
            component: $node->component,
            props: [
                ...$node->props,
                'width' => null,
                'status' => null,
                'statusLabel' => null,
                'labelBadge' => $badged ? t('Changed') : null,
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
