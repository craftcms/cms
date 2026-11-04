<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Data\FieldChangeComparison;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\FieldLayout\FieldPreviews;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Enums\ControlMode;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/**
 * Compares a field layout element on a draft, revision, or nested element with what it was based on, for the
 * “Compare changes” field action.
 *
 * - A draft, or an element nested in one, is compared with the current element.
 * - A revision, or an element nested in one, is compared with the revision before it.
 * - An outdated field is compared the other way around: the current element before it last changed the field,
 *   with the current element. That’s the change that was merged into the draft.
 *
 * The field is shown with {@see FieldPreviews}, as the AI assistant shows its proposed changes.
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
    public function compare(ElementInterface $element, BaseField $layoutElement, AttributeStatus $status): ?FieldChangeComparison
    {
        if ($status === AttributeStatus::Outdated && ! $element->getIsRevision()) {
            $current = $element->getCanonical();
            $before = $this->revisionBeforeChange($current, $layoutElement);

            return $this->comparison(
                $element,
                $layoutElement,
                from: $before,
                fromLabel: $before ? $this->label($before) : t('Earlier version'),
                to: $current,
                toLabel: t('Current'),
                hasBase: $before !== null,
            );
        }

        if ($element->getIsRevision()) {
            // An unchanged nested element can be shared by its owner’s revisions, so its base can be itself.
            [$base, $previous] = $this->previousRevision($element);

            return $this->comparison(
                $element,
                $layoutElement,
                from: $base,
                fromLabel: $previous ? $this->label($previous) : t('No earlier revision'),
                to: $element,
                toLabel: $this->label($element),
                hasBase: $previous !== null,
            );
        }

        return $this->comparison(
            $element,
            $layoutElement,
            from: $element->getCanonical(),
            fromLabel: t('Current'),
            to: $element,
            toLabel: $this->label($element),
        );
    }

    /**
     * Compiles the field on both elements, read-only and with the compared element’s field layout, as the AI
     * assistant previews its proposed changes: text as a diff, and anything else as the field’s own controls.
     */
    private function comparison(
        ElementInterface $element,
        BaseField $layoutElement,
        ?ElementInterface $from,
        string $fromLabel,
        ElementInterface $to,
        string $toLabel,
        bool $hasBase = true,
    ): ?FieldChangeComparison {
        // Unique to this comparison, so its fields don’t share IDs with the editor’s.
        $namespace = 'fieldChanges'.Str::random(8);
        $toPayload = $this->previews->compile($element, $to, "{$namespace}To", ControlMode::ReadOnly);
        $toNode = $toPayload ? $this->previews->findNode($toPayload->nodes, (string) $layoutElement->uid) : null;

        if (! $toPayload || ! $toNode) {
            return null;
        }

        $fromPayload = $from ? $this->previews->compile($element, $from, "{$namespace}From", ControlMode::ReadOnly) : null;
        $fromNode = $fromPayload ? $this->previews->findNode($fromPayload->nodes, (string) $layoutElement->uid) : null;
        $toValue = data_get($toPayload->values, $toNode->control->path ?? []);
        $fromValue = $fromPayload && $fromNode ? data_get($fromPayload->values, $fromNode->control->path ?? []) : null;
        $diff = $fromNode ? FieldPreviews::diff($fromValue, $toValue, $to, $layoutElement) : null;

        return new FieldChangeComparison(
            fromLabel: $fromLabel,
            toLabel: $toLabel,
            hasBase: $hasBase,
            // Fields that load their own values, such as Matrix fields shown as cards, have none in the form to compare.
            unchanged: $hasBase && $fromNode !== null && ($from->id === $to->id || ($toValue !== null && $fromValue === $toValue)),
            diff: $diff,
            preview: $diff === null ? [
                'current' => $fromPayload && $fromNode ? $this->previews->payloadFor($fromPayload, $fromNode) : null,
                'proposed' => $this->previews->payloadFor($toPayload, $toNode),
            ] : null,
        );
    }

    /**
     * Returns what an element is called in a comparison: “Current”, a revision, or a draft.
     */
    private function label(ElementInterface $element): string
    {
        $root = $element->getRootOwner();

        return match (true) {
            $root->getIsRevision() => $root->getRevisionLabel(),
            $root->getIsDraft() && $root->isProvisionalDraft => t('Your changes'),
            $root->getIsDraft() => $root->draftName ?: t('Draft'),
            default => t('Current'),
        };
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

    /**
     * Returns the current element’s last revision from before it last changed a field.
     */
    private function revisionBeforeChange(ElementInterface $current, BaseField $layoutElement): ?ElementInterface
    {
        $query = $layoutElement instanceof CustomField
            ? DB::table(Table::CHANGEDFIELDS)
                ->where('fieldId', $layoutElement->getField()->id)
                ->where('layoutElementUid', $layoutElement->uid)
            : DB::table(Table::CHANGEDATTRIBUTES)
                ->where('attribute', $layoutElement->attribute());

        $changedAt = $query
            ->where('elementId', $current->id)
            ->where('siteId', $current->siteId)
            ->value('dateUpdated');

        if ($changedAt === null) {
            return null;
        }

        return $current::find()
            ->revisionOf($current->id)
            ->siteId($current->siteId)
            ->status(null)
            ->where('elements.dateCreated', '<', $changedAt)
            ->orderByDesc('revisions.num')
            ->one();
    }
}
