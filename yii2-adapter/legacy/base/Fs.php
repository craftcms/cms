<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\fs\bridge\LegacyFsFlysystemAdapter;
use craft\validators\HandleValidator;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Yii2Adapter\Form\Concerns\LegacySettingsForm;
use CraftCms\Yii2Adapter\Form\Contracts\LegacySettingsComponent;
use Override;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

/**
 * Field is the base class for classes representing filesystems in terms of objects.
 *
 * @property-read null|string $rootUrl
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 * @deprecated 6.0.0
 */
abstract class Fs extends SavableComponent implements FsInterface, LegacySettingsComponent
{
    use FsTrait;
    use LegacySettingsForm {
        settingsForm as private legacySettingsForm;
    }

    public const CONFIG_MIMETYPE = 'mimetype';

    public const CONFIG_VISIBILITY = 'visibility';

    public const VISIBILITY_DEFAULT = 'default';

    public const VISIBILITY_HIDDEN = 'hidden';

    public const VISIBILITY_PUBLIC = 'public';

    #[Override]
    public function settingsForm(FormContext $context = new FormContext()): ?Form
    {
        $form = Form::make();

        if ($this->getShowHasUrlSetting()) {
            $form->add(Field::make(t('Files in this filesystem have public URLs'))
                ->control(Lightswitch::make('hasUrls')->value($this->hasUrls)));
        }

        if ($this->hasUrls && $this->getShowUrlSetting()) {
            $form->add(Field::make(t('Base URL'))
                ->instructions(t('The base URL to the files in this filesystem.'))
                ->required()
                ->control(Text::make('url')
                    ->value($this->url)
                    ->textExpanderTriggers(SelectOptions::getEnvTextExpanderTriggers(true, fn($value): bool => Str::isUrl($value)))
                    ->placeholder('//example.com/path/to/folder'))
                ->tip(t('Type `$` to choose an environment variable, or `@` to choose an alias.')));
        }

        $legacyForm = $this->legacySettingsForm($context);

        if ($legacyForm !== null) {
            $form->add(...$legacyForm->nodes());
        }

        return $form->nodes() === [] ? null : $form;
    }

    public function getShowHasUrlSetting(): bool
    {
        return static::$showHasUrlSetting;
    }

    public function getShowUrlSetting(): bool
    {
        return static::$showUrlSetting;
    }

    #[Override]
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();

        if ($this->getShowHasUrlSetting()) {
            $attributes[] = 'hasUrls';
        }

        if ($this->getShowUrlSetting()) {
            $attributes[] = 'url';
        }

        return $attributes;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'url' => t('Base URL'),
        ]);
    }

    #[Override]
    public function getRootUrl(): ?string
    {
        if (!$this->hasUrls) {
            return null;
        }

        $url = Env::parse($this->url);
        if (is_string($url)) {
            $url = rtrim($url, '/');
        }

        return $url ? "$url/" : null;
    }

    public function getSettingsHtml(): ?string
    {
        return null;
    }

    public function getReadOnlySettingsHtml(): ?string
    {
        return Html::disableInputs(fn() => $this->getSettingsHtml());
    }

    public function getDiskConfig(): array
    {
        if (!is_string($this->handle) || $this->handle === '') {
            throw new InvalidConfigException('Filesystem handle is missing.');
        }

        $config = [
            'driver' => LegacyFsFlysystemAdapter::DISK_DRIVER,
            'fsHandle' => $this->handle,
        ];

        $rootUrl = $this->getRootUrl();
        if (is_string($rootUrl) && $rootUrl !== '') {
            $config['url'] = rtrim($rootUrl, '/');
        }

        return $config;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [
            'url',
            'required',
            'when' => fn(self $fs) => $fs->hasUrls && $this->getShowUrlSetting(),
        ];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'dateCreated',
                'dateUpdated',
                'edit',
                'id',
                'new',
                'title',
                'uid',
            ],
        ];

        return $rules;
    }
}
