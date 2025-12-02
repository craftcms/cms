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
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Html as HtmlHelper;
use function CraftCms\Cms\t;

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
            Cms::config()->showFirstAndLastNameFields &&
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
        $isAdmin = Craft::$app->getUser()->getIsAdmin();

        return HtmlHelper::beginTag('div', ['class' => ['flex', 'flex-nowrap', 'fullwidth']]) .
            Cp::textFieldHtml([
                'id' => 'firstName',
                'status' => $status,
                'fieldClass' => 'flex-grow',
                'label' => t('First Name'),
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
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'firstName']) : null,
                ]),
            ]) .
            Cp::textFieldHtml([
                'id' => 'lastName',
                'status' => $status,
                'fieldClass' => 'flex-grow',
                'label' => t('Last Name'),
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
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'lastName']) : null,
                ]),
            ]) .
            HtmlHelper::endTag('div');
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        if (Cms::config()->showFirstAndLastNameFields) {
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
        return t('Full Name');
    }
}
