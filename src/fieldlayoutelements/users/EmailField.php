<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\users;

use Craft;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\fieldlayoutelements\TextField;
use yii\base\InvalidArgumentException;

/**
 * EmailField represents an Email field that can be included in the user field layout.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class EmailField extends TextField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public string $attribute = 'email';

    /**
     * @inheritdoc
     */
    public ?int $maxlength = 255;

    /**
     * @inheritdoc
     */
    public bool $autofocus = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        unset(
            $config['mandatory'],
            $config['attribute'],
            $config['translatable'],
            $config['maxlength'],
            $config['required'],
            $config['autofocus'],
            $config['warning'],
        );

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset(
            $fields['mandatory'],
            $fields['attribute'],
            $fields['translatable'],
            $fields['maxlength'],
            $fields['required'],
            $fields['autofocus'],
            $fields['warning'],
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Email');
    }

    /**
     * @inheritdoc
     */
    protected function warning(?ElementInterface $element = null, bool $static = false): ?string
    {
        /** @var User $element */
        if (
            Craft::$app->edition->value >= CmsEdition::Pro->value &&
            Craft::$app->getProjectConfig()->get('users.requireEmailVerification') &&
            !$element->getIsDraft() &&
            !Craft::$app->getUser()->checkPermission('administrateUsers')
        ) {
            return Craft::t('app', 'New email addresses must be verified before taking effect.');
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element) {
            if (!$element instanceof User) {
                throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
            }

            if (
                !$element->getIsCurrent() &&
                !$element->getIsDraft() &&
                !Craft::$app->getUser()->checkPermission('administrateUsers')
            ) {
                return null;
            }

            Craft::$app->getView()->registerJsWithVars(fn($id) => <<<JS
new Craft.ElevatedSessionForm($('#' + $id).closest('form'), ['#' + $id]);
JS, [
                Craft::$app->getView()->namespaceInputId($this->attribute),
            ]);
        }

        return parent::inputHtml($element, $static);
    }

    /**
     * @inheritdoc
     */
    protected function inputAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        if (!$element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        return [
            'autocomplete' => $element->getIsCurrent() ? 'email' : 'off',
            'data' => ['lpignore' => 'true'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function errors(?ElementInterface $element = null): array
    {
        if (!$element) {
            return [];
        }

        return array_merge($element->getErrors('email'), $element->getErrors('unverifiedEmail'));
    }
}
