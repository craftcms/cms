<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * Class FullNameField.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FullNameField extends TextField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'fullName';

    /**
     * @inheritdoc
     */
    public bool $requirable = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        unset(
            $config['mandatory'],
            $config['translatable'],
            $config['maxlength'],
            $config['autofocus']
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
            $fields['translatable'],
            $fields['maxlength'],
            $fields['autofocus']
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function previewable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Full Name');
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        /** @var User $element */
        $parsedName = $element->getParsedName();
        $showFullName = true;
        if (
            !$element->hasErrors('fullName') &&
            ($parsedName['firstName'] != $element->firstName || $parsedName['lastName'] != $element->lastName)
        ) {
            $showFullName = false;
        }

        $isCurrentUser = $element == Craft::$app->getUser()->getIdentity();

        $fullNameHtml = $showFullName ?
            (Html::beginTag('div', ['id' => 'fullNameField', 'class' => 'name-wrapper']) .
                Cp::textFieldHtml([
                    'label' => Craft::t('app', 'Full Name'),
                    'id' => 'fullName',
                    'name' => 'fullName',
                    'value' => $element->fullName,
                    'autocomplete' => $isCurrentUser ? 'name' : false,
                    'errors' => $element->getErrors('fullName'),
                    'inputAttributes' => [
                        'data' => ['lpignore' => 'true'],
                    ],
                    'disabled' => $static,
                ]) .
                Cp::lightswitchFieldHtml([
                    'label' => Craft::t('app', 'Edit Name'),
                    'id' => 'editName',
                    'name' => 'editName',
                    'on' => false,
                    'value' => 1,
                    'toggle' => 'editNameFields',
                    'reverseToggle' => '[id$="fullNameField"], [id$="fullName-label"]',
                ]) .
                Html::endTag('div')) : '';

        return
            $fullNameHtml .
            Html::beginTag('div', ['id' => 'editNameFields', 'class' => 'name-wrapper' . ($showFullName ? ' hidden' : '')]) .
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'First Name'),
                'id' => 'firstName',
                'name' => 'firstName',
                'value' => $element->firstName,
                'autocomplete' => $isCurrentUser ? 'given-name' : false,
                'errors' => $element->getErrors('firstName'),
                'inputAttributes' => [
                    'data' => ['lpignore' => 'true'],
                ],
                'disabled' => $static,
            ]) .
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'Last Name'),
                'id' => 'lastName',
                'name' => 'lastName',
                'value' => $element->lastName,
                'autocomplete' => $isCurrentUser ? 'family-name' : false,
                'errors' => $element->getErrors('lastName'),
                'inputAttributes' => [
                    'data' => ['lpignore' => 'true'],
                ],
                'disabled' => $static,
            ]) .
            Html::endTag('div');
    }
}
