<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\Html as HtmlHelper;

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
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (
            $element &&
            Craft::$app->getConfig()->getGeneral()->showFirstAndLastNameFields &&
            count(array_intersect($element->safeAttributes(), ['firstName', 'lastName'])) === 2
        ) {
            return $this->firstAndLastNameFields($element, $static);
        }

        return parent::formHtml($element, $static);
    }

    private function firstAndLastNameFields(?ElementInterface $element, bool $static): string
    {
        $statusClass = $this->statusClass($element);
        $status = $statusClass ? [$statusClass, $this->statusLabel($element, $static) ?? ucfirst($statusClass)] : null;
        $required = !$static && $this->required;

        return HtmlHelper::beginTag('div', ['class' => ['flex', 'flex-nowrap', 'fullwidth']]) .
            Cp::textFieldHtml([
                'id' => 'firstName',
                'status' => $status,
                'fieldClass' => 'flex-grow',
                'label' => Craft::t('app', 'First Name'),
                'attribute' => 'firstName',
                'showAttribute' => $this->showAttribute(),
                'required' => $required,
                'autocomplete' => false,
                'name' => 'firstName',
                'value' => $element->firstName ?? null,
                'errors' => !$static ? $element->getErrors('firstName') : [],
                'disabled' => $static,
                'data' => [
                    'error-key' => 'firstName',
                ],
            ]) .
            Cp::textFieldHtml([
                'id' => 'lastName',
                'status' => $status,
                'fieldClass' => 'flex-grow',
                'label' => Craft::t('app', 'Last Name'),
                'attribute' => 'lastName',
                'showAttribute' => $this->showAttribute(),
                'required' => $required,
                'autocomplete' => false,
                'name' => 'lastName',
                'value' => $element->lastName ?? null,
                'errors' => !$static ? $element->getErrors('lastName') : [],
                'disabled' => $static,
                'data' => [
                    'error-key' => 'lastName',
                ],
            ]) .
            HtmlHelper::endTag('div');
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        if (Craft::$app->getConfig()->getGeneral()->showFirstAndLastNameFields) {
            // can't know for sure if the element will support firstName and lastName, but probably?
            return null;
        }

        return parent::settingsHtml();
    }

    /**
     * @inheritdoc
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Full Name');
    }
}
