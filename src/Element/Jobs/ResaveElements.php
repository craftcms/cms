<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Operations\ResaveMutation;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Queue\BatchedElementJob;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\I18N;
use Override;
use Throwable;

class ResaveElements extends BatchedElementJob
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
     * @param  string[]  $withFields
     */
    public function __construct(
        protected string $elementType,
        protected array $criteria = [],
        public bool $updateSearchIndex = false,
        public array $withFields = [],
        public ?string $set = null,
        public ?string $to = null,
        public bool $toDefault = false,
        public bool $ifEmpty = false,
        public bool $ifInvalid = false,
        public bool $touch = false,
        int $batchSize = 100,
        protected ?string $description = null,
    ) {
        parent::__construct();

        $this->batchSize = $batchSize;
    }

    protected function processElement(ElementInterface $element): void
    {
        ResaveMutation::apply(
            $element,
            $this->set,
            $this->to,
            $this->toDefault,
            $this->toDefault && ! $this->set
                ? array_filter(array_map(Fields::getFieldByHandle(...), $this->withFields))
                : [],
            $this->ifEmpty,
            $this->ifInvalid,
        );

        $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
        $element->resaving = true;

        try {
            Elements::saveElement(
                element: $element,
                updateSearchIndex: $this->updateSearchIndex,
                forceTouch: $this->touch,
                saveContent: true,
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Resaving {type}', [
            'type' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }
}
