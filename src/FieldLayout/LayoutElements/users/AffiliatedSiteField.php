<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\users;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class AffiliatedSiteField extends BaseNativeField
{
    /**
     * {@inheritdoc}
     */
    public string $attribute = 'affiliatedSiteId';

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        parent::__construct(Arr::except($config, [
            'mandatory',
            'attribute',
            'translatable',
            'required',
            'warning',
        ]));
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'mandatory',
            'attribute',
            'translatable',
            'required',
            'warning',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Affiliated Site');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function instructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Determines which site the user will receive emails from, when sent via the control panel.');
    }

    /**
     * {@inheritdoc}
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ! $element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        if (! Sites::isMultiSite()) {
            return null;
        }

        return Cp::selectHtml([
            'name' => 'affiliatedSiteId',
            'id' => 'affiliated-site',
            'options' => [
                ['label' => t('None'), 'value' => ''],
                ...Sites::getAllSites()->map(fn (Site $site) => [
                    'label' => $site->getUiLabel(),
                    'value' => $site->id,
                ])->all(),
            ],
            'value' => $element?->affiliatedSiteId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (Auth::user()?->isAdmin()) {
            $items[] = $this->copyAttributeAction([
                'attribute' => 'affiliatedSite',
            ]);
        }

        return $items;
    }
}
