<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\FieldLayout\LayoutElements\addresses;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Auth;
use yii\base\InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Class LatLongField.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class LatLongField extends BaseNativeField
{
    /**
     * {@inheritdoc}
     */
    public string $attribute = 'latLong';

    /**
     * {@inheritdoc}
     */
    public bool $requirable = true;

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    #[\Override]
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
     * {@inheritdoc}
     */
    #[\Override]
    public function hasCustomWidth(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function previewable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function previewHtml(ElementInterface $element): string
    {
        /** @var Address $element */
        return sprintf('%s, %s', $element->longitude ?? '0', $element->latitude ?? '0');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function showLabel(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        // we need it for the card view designer
        return t('Latitude/Longitude');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function selectorLabel(): ?string
    {
        return t('Latitude/Longitude');
    }

    /**
     * {@inheritdoc}
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $element instanceof Address) {
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        $isAdmin = Auth::user()?->isAdmin();

        return
            Html::beginTag('div', ['class' => 'flex-fields']).
            Cp::textFieldHtml([
                'fieldClass' => 'width-50',
                'label' => t('Latitude'),
                'id' => 'latitude',
                'name' => 'latitude',
                'value' => $element->latitude,
                'required' => $this->required,
                'data' => [
                    'error-key' => 'latitude',
                ],
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'latitude']) : null,
                ]),
            ]).
            Cp::textFieldHtml([
                'fieldClass' => 'width-50',
                'label' => t('Longitude'),
                'id' => 'longitude',
                'name' => 'longitude',
                'value' => $element->longitude,
                'required' => $this->required,
                'data' => [
                    'error-key' => 'longitude',
                ],
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'longitude']) : null,
                ]),
            ]).
            Html::endTag('div');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function fieldErrors(?ElementInterface $element = null): array
    {
        if (! $element) {
            return [];
        }

        return array_merge($element->errors()->get('latitude'), $element->errors()->get('longitude'));
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if ($element) {
            return $this->previewHtml($element);
        }

        return '61.108, -149.779';
    }
}
