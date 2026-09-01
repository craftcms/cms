<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Users;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\UserPhotoAsset;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class PhotoField extends BaseNativeField
{
    #[Override]
    public bool $mandatory = true;

    #[Override]
    public string $attribute = 'photo';

    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        parent::__construct(Arr::except($config, [
            'mandatory',
            'attribute',
            'translatable',
            'required',
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
        ]);
    }

    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Photo');
    }

    #[Override]
    protected function formControl(FieldLayoutElementContext $context): ?Control
    {
        if ($context->element && ! $context->element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        if (! $context->element?->id || ! ProjectConfig::get('users.photoVolumeUid')) {
            return null;
        }

        return ElementSelect::make($this->attribute())
            ->elementType(Asset::class)
            ->limit(1)
            ->value($context->element->photoId ? [$context->element->photoId] : []);
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ! $element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        if (! $element?->id) {
            return null;
        }

        if (! $volumeUid = ProjectConfig::get('users.photoVolumeUid')) {
            return null;
        }

        $volume = Volumes::getVolumeByUid($volumeUid);
        if (! $volume) {
            return null;
        }

        app(InternalAssetRegistry::class)->register(UserPhotoAsset::class);
        $inputId = sprintf('user-photo-%s', mt_rand());

        HtmlStack::jsWithVars(fn ($userId, $inputId, $isCurrentUser) => <<<JS
new Craft.UserPhotoInput($userId, '#' + $inputId, {
  isCurrentUser: $isCurrentUser,
})
JS, [
            $element->id,
            InputNamespace::namespaceId($inputId),
            $element->getIsCurrent(),
        ]);

        return template('users/_photo', [
            'id' => $inputId,
            'user' => $element,
        ]);
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (currentUser()?->isAdmin()) {
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }
}
