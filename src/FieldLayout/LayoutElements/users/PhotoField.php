<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\users;

use Craft;
use craft\base\ElementInterface;
use craft\web\assets\userphoto\UserPhotoAsset;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class PhotoField extends BaseNativeField
{
    /**
     * {@inheritdoc}
     */
    public bool $mandatory = true;

    /**
     * {@inheritdoc}
     */
    public string $attribute = 'photo';

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
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Photo');
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

        $volume = Craft::$app->getVolumes()->getVolumeByUid($volumeUid);
        if (! $volume) {
            return null;
        }

        $view = Craft::$app->getView();
        $view->registerAssetBundle(UserPhotoAsset::class);
        $inputId = sprintf('user-photo-%s', mt_rand());

        $view->registerJsWithVars(fn ($userId, $inputId, $isCurrentUser) => <<<JS
new Craft.UserPhotoInput($userId, '#' + $inputId, {
  isCurrentUser: $isCurrentUser,
})
JS, [
            $element->id,
            $view->namespaceInputId($inputId),
            $element->getIsCurrent(),
        ]);

        return $view->renderTemplate('users/_photo.twig', [
            'id' => $inputId,
            'user' => $element,
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
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }
}
