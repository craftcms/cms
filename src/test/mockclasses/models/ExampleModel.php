<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\models;

use craft\base\Model;
use DateTime;

/**
 * Class ExampleModel.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ExampleModel extends Model
{
    /**
     * @var mixed
     */
    public mixed $exampleParam = null;

    /**
     * @var DateTime|string|null
     */
    public DateTime|string|null $exampleDateParam = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $dateUpdated = null;

    /**
     * @var string|null
     */
    public ?string $nullableStringParam = null;

    /**
     * @var string
     */
    public string $stringParam;

    /**
     * @var int|null
     */
    public ?int $nullableIntParam = null;

    /**
     * @var int
     */
    public int $intParam;

    /**
     * @var float|null
     */
    public ?float $nullableFloatParam = null;

    /**
     * @var float
     */
    public float $floatParam;

    /**
     * @var inT|float|null
     */
    public int|float|null $nullableNumericParam = null;

    /**
     * @var int|float
     */
    public int|float $numericParam;

    /**
     * @var bool|null
     */
    public ?bool $nullableBoolParam = null;

    /**
     * @var bool
     */
    public bool $boolParam;

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attr = parent::datetimeAttributes();
        $attr[] = 'exampleDateParam';

        return $attr;
    }

    public function fields(): array
    {
        $fields = parent::fields();
        $resolveNotNullableProperty = fn(self $model, string $field) => $this->$field ?? null;
        $fields['stringParam'] = $resolveNotNullableProperty;
        $fields['intParam'] = $resolveNotNullableProperty;
        $fields['floatParam'] = $resolveNotNullableProperty;
        $fields['numericParam'] = $resolveNotNullableProperty;
        $fields['boolParam'] = $resolveNotNullableProperty;
        return $fields;
    }
}
