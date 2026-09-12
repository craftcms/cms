<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Users;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Concerns\ImportableFieldLayoutElement;
use CraftCms\Cms\FieldLayout\Contracts\ImportableFieldLayoutElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

class AffiliatedSiteField extends BaseNativeField implements ImportableFieldLayoutElementInterface
{
    use ImportableFieldLayoutElement;

    #[Override]
    public string $attribute = 'affiliatedSiteId';

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

    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Affiliated Site');
    }

    #[Override]
    protected function instructionsText(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Determines which site the user will receive emails from, when sent via the control panel.');
    }

    #[Override]
    protected function formControl(FieldLayoutElementContext $context): ?Control
    {
        if ($context->element && ! $context->element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        if (! Sites::isMultiSite()) {
            return null;
        }

        return Choice::make($this->attribute())
            ->options([
                ['label' => t('None'), 'value' => ''],
                ...Sites::getAllSites()->map(fn (Site $site) => [
                    'label' => $site->getUiLabel(),
                    'value' => $site->id,
                ])->all(),
            ])
            ->value($context->element?->affiliatedSiteId);
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ! $element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        if (! Sites::isMultiSite()) {
            return null;
        }

        return FormFields::selectHtml([
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

    /** @return list<array<string, mixed>> */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (currentUser()?->isAdmin()) {
            $items[] = $this->copyAttributeAction([
                'attribute' => 'affiliatedSite',
            ]);
        }

        return $items;
    }
}
