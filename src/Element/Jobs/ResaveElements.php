<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use CraftCms\Cms\Element\Commands\Resave\ResaveCommand;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\Contracts\DefaultableFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
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
        if ($this->toDefault) {
            if ($this->set) {
                /** @var ElementInterface $element */
                $fields = [$element->getFieldLayout()?->getFieldByHandle($this->set)];
            } else {
                $fields = array_map(
                    function (string $handle) use ($element) {
                        $field = Fields::getFieldByHandle($handle);
                        if (! $field) {
                            return null;
                        }

                        return $element->getFieldLayout()?->getFieldByUid($field->uid);
                    },
                    $this->withFields,
                );
            }

            $fields = array_filter(
                $fields,
                fn (?FieldInterface $field) => $field instanceof DefaultableFieldInterface
            );

            foreach ($fields as $field) {
                $set = true;
                if ($this->ifEmpty) {
                    if (! ElementHelper::isAttributeEmpty($element, $field->handle)) {
                        $set = false;
                    }
                } elseif ($this->ifInvalid) {
                    $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
                    if ($element->validate("field:$field->handle")) {
                        $set = false;
                    }
                }

                if ($set) {
                    /** @var ElementInterface $element */
                    /** @var DefaultableFieldInterface $field */
                    $defaultValue = $field->getDefaultValue();
                    if ($defaultValue !== null) {
                        $element->setFieldValue($field->handle, $defaultValue);
                    }
                }
            }
        } elseif (isset($this->set)) {
            $set = true;

            if ($this->ifEmpty) {
                if (! ElementHelper::isAttributeEmpty($element, $this->set)) {
                    $set = false;
                }
            } elseif ($this->ifInvalid) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);

                if ($element->validate($this->set) && $element->validate("field:$this->set")) {
                    $set = false;
                }
            }

            if ($set) {
                $to = ResaveCommand::normalizeTo($this->to);
                $element->{$this->set} = $to($element);
            }
        }

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
