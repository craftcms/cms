<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\assets;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Asset;
use craft\fieldlayoutelements\TextareaField;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use yii\base\InvalidArgumentException;

/**
 * AltField represents an Alternative Text field that can be included within a volume’s field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AltField extends TextareaField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'alt';

    /**
     * @inheritdoc
     */
    public bool $requirable = true;

    /**
     * @var string
     */
    public string $translationMethod = Field::TRANSLATION_METHOD_NONE;

    /**
     * @var null|string
     */
    public ?string $translationKeyFormat = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        unset(
            $config['attribute'],
            $config['autofocus'],
            $config['mandatory'],
            $config['maxlength'],
            $config['requirable'],
            $config['translatable'],
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
            $fields['autofocus'],
            $fields['mandatory'],
            $fields['maxlength'],
            $fields['translatable'],
        );
        $fields['translationMethod'] = 'translationMethod';
        $fields['translationKeyFormat'] = 'translationKeyFormat';
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Alternative Text');
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        $translationMethod = '';

        if (Craft::$app->getIsMultiSite()) {
            $supportedTranslationMethods = Field::supportedTranslationMethods() ?: [Field::TRANSLATION_METHOD_NONE];
            if (!empty($supportedTranslationMethods)) {
                $options = [];
                foreach ($supportedTranslationMethods as $supportedTranslationMethod) {
                    $option = ['value' => $supportedTranslationMethod];
                    switch ($supportedTranslationMethod) {
                        case 'none':
                            $option['label'] = Craft::t('app', 'Not translatable');
                            break;
                        case 'site':
                            $option['label'] = Craft::t('app', 'Translate for each site');
                            break;
                        case 'siteGroup':
                            $option['label'] = Craft::t('app', 'Translate for each site group');
                            break;
                        case 'language':
                            $option['label'] = Craft::t('app', 'Translate for each language');
                            break;
                        case 'custom':
                            $option['label'] = Craft::t('app', 'Custom…');
                            break;

                    }
                    $options[] = $option;
                }

                $translationMethod = Cp::selectFieldHtml([
                    'field' => $this,
                    'instructions' => Craft::t('app', 'How should this field’s values be translated?'),
                    'label' => Craft::t('app', 'Translation Method'),
                    'id' => 'translation-method',
                    'name' => 'translationMethod',
                    'options' => $options,
                    'value' => $this->translationMethod,
                    'toggle' => true,
                    'targetPrefix' => 'translation-method-',
                ]);

                if (in_array('custom', $supportedTranslationMethods, true)) {
                    $translationMethod .= Html::beginTag('div', [
                        'id' => 'translation-method-custom',
                        'class' => $this->translationMethod != 'custom' ? 'hidden' : null,
                    ]);

                    $translationMethod .= Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Translation Key Format'),
                        'instructions' => Craft::t('app', 'Template that defines the field’s custom “translation key” format. Field values will be copied to all sites that produce the same key. For example, to make the field translatable based on the first two characters of the site handle, you could enter `{site.handle[:2]}`.'),
                        'id' => 'translation-key-format',
                        'class' => 'code',
                        'name' => 'translationKeyFormat',
                        'value' => $this->translationKeyFormat ?? '',
                        'errors' => $this->getErrors('translationKeyFormat')
                    ]);

                    $translationMethod .= Html::endTag('div');
                }
            }
        }

        return parent::settingsHtml() . $translationMethod;
    }

    /**
     * @inheritdoc
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (!$element instanceof Asset) {
            throw new InvalidArgumentException('AltField can only be used in asset field layouts.');
        }

        return $this->translationMethod !== Field::TRANSLATION_METHOD_NONE;
    }

    /**
     * @inheritdoc
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        return ElementHelper::translationDescription($this->translationMethod);
    }
}
