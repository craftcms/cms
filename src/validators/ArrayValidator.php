<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Countable;
use Craft;
use yii\validators\Validator;

/**
 * Class ArrayValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ArrayValidator extends Validator
{
    /**
     * @var int|array|null specifies the count limit of the value to be validated.
     *
     * This can be specified in one of the following forms:
     * - an int: the exact count that the value should be of;
     * - an array of one element: the minimum count that the value should be of. For example, `[8]`.
     *   This will overwrite [[min]].
     * - an array of two elements: the minimum and maximum counts that the value should be of.
     *   For example, `[8, 128]`. This will overwrite both [[min]] and [[max]].
     * @see tooFew for the customized message for a too short array.
     * @see tooMany for the customized message for a too long array.
     * @see notEqual for the customized message for an array that does not match desired count.
     */
    public int|array|null $count = null;

    /**
     * @var int|null maximum count. If not set, it means no maximum count limit.
     * @see tooMany for the customized message for a too long array.
     */
    public ?int $max = null;

    /**
     * @var int|null minimum count. If not set, it means no minimum count limit.
     * @see tooFew for the customized message for a too short array.
     */
    public ?int $min = null;

    /**
     * @var string|null user-defined error message used when the count of the value is smaller than [[min]].
     */
    public ?string $tooFew = null;

    /**
     * @var string|null user-defined error message used when the count of the value is greater than [[max]].
     */
    public ?string $tooMany = null;

    /**
     * @var string|null user-defined error message used when the count of the value is not equal to [[count]].
     */
    public ?string $notEqual = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (is_array($this->count)) {
            if (isset($this->count[0])) {
                $this->min = $this->count[0];
            }

            if (isset($this->count[1])) {
                $this->max = $this->count[1];
            }

            $this->count = null;
        }

        if (!isset($this->message)) {
            $this->message = Craft::t('app', '{attribute} must be an array.');
        }

        if (isset($this->min) && !isset($this->tooFew)) {
            $this->tooFew = Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{item} other{items}}.');
        }

        if (isset($this->max) && !isset($this->tooMany)) {
            $this->tooMany = Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{item} other{items}}.');
        }

        if (isset($this->count) && !isset($this->notEqual)) {
            $this->notEqual = Craft::t('app', '{attribute} should contain {count, number} {count, plural, one{item} other{items}}.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value): ?array
    {
        if (!$value instanceof Countable && !is_array($value)) {
            return [$this->message, []];
        }

        $count = count($value);

        if (isset($this->min) && $count < $this->min) {
            return [$this->tooFew, ['min' => $this->min]];
        }
        if (isset($this->max) && $count > $this->max) {
            return [$this->tooMany, ['max' => $this->max]];
        }
        if (isset($this->count) && $count !== $this->count) {
            return [$this->notEqual, ['count' => $this->count]];
        }

        return null;
    }
}
