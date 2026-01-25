<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use Craft;
use craft\base\Batchable;
use craft\base\ElementInterface;
use craft\console\controllers\ResaveController;
use craft\db\QueryBatcher;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Queue\BatchedElementJob;
use CraftCms\Cms\Support\Facades\I18N;
use Throwable;

/**
 * Resaves elements matching the given criteria.
 *
 * @since 6.0.0
 */
final class ResaveElements extends BatchedElementJob
{
    /**
     * Creates a new ResaveElements job.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type to resave.
     * @param  array<string, mixed>  $criteria  The element query criteria.
     * @param  bool  $updateSearchIndex  Whether to update search indexes.
     * @param  string|null  $set  An attribute to set on each element.
     * @param  string|null  $to  The value to set on the attribute.
     * @param  bool  $ifEmpty  Only set if the attribute is empty.
     * @param  bool  $ifInvalid  Only set if the current value is invalid.
     * @param  bool  $touch  Whether to update the dateUpdated timestamp.
     */
    public function __construct(
        protected string $elementType,
        protected array $criteria = [],
        public bool $updateSearchIndex = false,
        public ?string $set = null,
        public ?string $to = null,
        public bool $ifEmpty = false,
        public bool $ifInvalid = false,
        public bool $touch = false,
    ) {}

    #[\Override]
    protected function loadData(): Batchable
    {
        $query = $this->elementType::find()
            ->orderBy(['elements.id' => SORT_ASC]);

        if (! empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        return new QueryBatcher($query);
    }

    protected function processElement(ElementInterface $element): void
    {
        if (isset($this->set)) {
            $set = true;

            if ($this->ifEmpty) {
                if (! ElementHelper::isAttributeEmpty($element, $this->set)) {
                    $set = false;
                }
            } elseif ($this->ifInvalid) {
                $element->setScenario(Element::SCENARIO_LIVE);

                if ($element->validate($this->set) && $element->validate("field:$this->set")) {
                    $set = false;
                }
            }

            if ($set) {
                $to = ResaveController::normalizeTo($this->to);
                $element->{$this->set} = $to($element);
            }
        }

        $element->setScenario(Element::SCENARIO_ESSENTIALS);
        $element->resaving = true;

        try {
            Craft::$app->getElements()->saveElement(
                $element,
                updateSearchIndex: $this->updateSearchIndex,
                forceTouch: $this->touch,
                saveContent: true,
            );
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
        }
    }

    #[\Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Resaving {type}', [
            'type' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }
}
