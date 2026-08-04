<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Batchable;
use craft\base\DefaultableFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\console\controllers\ResaveController;
use craft\db\QueryBatcher;
use craft\helpers\ElementHelper;
use craft\i18n\Translation;
use craft\queue\BaseBatchedElementJob;
use Throwable;

/**
 * ResaveElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ResaveElements extends BaseBatchedElementJob
{
    /**
     * @var class-string<ElementInterface> The element type that should be resaved
     */
    public string $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be resaved
     */
    public ?array $criteria = null;

    /**
     * @var bool Whether to update the search indexes for the resaved elements.
     * @since 3.4.2
     */
    public bool $updateSearchIndex = false;

    /**
     * @var string[] Only resave elements that have custom fields with these global field handles.
     * @since 5.10.0
     */
    public array $withFields = [];

    /**
     * @var string|null An attribute name that should be set for each of the elements. The value will be determined by [[to]].
     * @since 4.2.6
     */
    public ?string $set = null;

    /**
     * @var string|null The value that should be set on the [[set]] attribute.
     * @since 4.2.6
     */
    public ?string $to = null;

    /**
     * @var bool Sets the specified fields to their default values.
     * @since 5.10.0
     */
    public bool $toDefault = false;

    /**
     * @var bool Whether the [[set]] attribute should only be set if it doesn’t have a value.
     * @since 4.2.6
     */
    public bool $ifEmpty = false;

    /**
     * @var bool Whether the [[set]] attribute should only be set if the current value doesn’t validate.
     * @since 5.1.0
     */
    public bool $ifInvalid = false;

    /**
     * @var bool Whether to update the `dateUpdated` timestamp for the elements.
     * @since 4.2.6
     */
    public bool $touch = false;

    /**
     * @inheritdoc
     */
    protected function loadData(): Batchable
    {
        $query = $this->elementType::find()
            ->orderBy(['elements.id' => SORT_ASC]);

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        return new QueryBatcher($query);
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
        if ($this->toDefault) {
            if ($this->set) {
                /** @var ElementInterface $item */
                $fields = [$item->getFieldLayout()?->getFieldByHandle($this->set)];
            } else {
                $fieldsService = Craft::$app->getFields();
                $fields = array_map(
                    function(string $handle) use ($fieldsService, $item) {
                        $field = $fieldsService->getFieldByHandle($handle);
                        if (!$field) {
                            return null;
                        }
                        /** @var ElementInterface $item */
                        return $item->getFieldLayout()?->getFieldByUid($field->uid);
                    },
                    $this->withFields,
                );
            }

            $fields = array_filter($fields, fn(?FieldInterface $field) => $field instanceof DefaultableFieldInterface);

            foreach ($fields as $field) {
                $set = true;
                if ($this->ifEmpty) {
                    /** @var ElementInterface $item */
                    if (!ElementHelper::isAttributeEmpty($item, $field->handle)) {
                        $set = false;
                    }
                } elseif ($this->ifInvalid) {
                    /** @var ElementInterface $item */
                    $item->setScenario(Element::SCENARIO_LIVE);
                    if ($item->validate("field:$field->handle")) {
                        $set = false;
                    }
                }

                if ($set) {
                    /** @var ElementInterface $item */
                    /** @var DefaultableFieldInterface $field */
                    $defaultValue = $field->getDefaultValue();
                    if ($defaultValue !== null) {
                        $item->setFieldValue($field->handle, $defaultValue);
                    }
                }
            }
        } elseif (isset($this->set)) {
            $set = true;
            if ($this->ifEmpty) {
                if (!ElementHelper::isAttributeEmpty($item, $this->set)) {
                    $set = false;
                }
            } elseif ($this->ifInvalid) {
                $item->setScenario(Element::SCENARIO_LIVE);
                if ($item->validate($this->set) && $item->validate("field:$this->set")) {
                    $set = false;
                }
            }

            if ($set) {
                $to = ResaveController::normalizeTo($this->to);
                $item->{$this->set} = $to($item);
            }
        }

        $item->setScenario(Element::SCENARIO_ESSENTIALS);
        $item->resaving = true;

        try {
            Craft::$app->getElements()->saveElement($item,
                updateSearchIndex: $this->updateSearchIndex,
                forceTouch: $this->touch,
                saveContent: true,
            );
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Resaving {type}', [
            'type' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }
}
