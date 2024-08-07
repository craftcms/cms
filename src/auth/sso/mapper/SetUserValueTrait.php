<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso\mapper;

use Craft;
use craft\base\FieldInterface;
use craft\elements\User;
use craft\helpers\Json;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 */
trait SetUserValueTrait
{
    /**
     * @var string
     */
    public string $craftProperty;

    /**
     * Set a value on a user; determine if we're setting a field value or property
     *
     * @param User $user
     * @param mixed $value
     * @return void
     */
    protected function setValue(User $user, mixed $value): void
    {
        $field = $this->getFieldLayoutField($user, $this->craftProperty);
        if ($field) {
            Craft::info(
                sprintf(
                    "Attribute mapper is setting a user value '%s' via field '%s'",
                    Json::encode($value),
                    $field->handle,
                ),
                "auth"
            );

            $user->setFieldValue($this->craftProperty, $value);
        } elseif ($user->canSetProperty($this->craftProperty)) {
            Craft::info(
                sprintf(
                    "Attribute mapper is setting a user value '%s' via property '%s'",
                    Json::encode($value),
                    $this->craftProperty,
                ),
                "auth"
            );

            $user->{$this->craftProperty} = $value;
        }
    }

    /**
     * @param User $user
     * @param string $fieldHandle
     * @return FieldInterface|null
     */
    private function getFieldLayoutField(User $user, string $fieldHandle): ?FieldInterface
    {
        $fieldLayout = $user->getFieldLayout();

        if (is_null($fieldLayout)) {
            Craft::warning(
                sprintf(
                    "User field layout was not found; therefore we will not set field '%s' value",
                    $fieldHandle
                ),
                'auth'
            );

            return null;
        }

        $field = $fieldLayout->getFieldByHandle($fieldHandle);

        $field ? Craft::info(
            sprintf(
                "User field '%s' was found",
                $fieldHandle
            ),
            'Auth'
        ) : Craft::warning(
            sprintf(
                "User field '%s' was not found",
                $fieldHandle
            ),
            'auth'
        );

        return $field;
    }
}
