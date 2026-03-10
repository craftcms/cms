<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\users;

use Craft;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\fieldlayoutelements\BaseNativeField;
use craft\web\assets\userphoto\UserPhotoAsset;
use yii\base\InvalidArgumentException;

/**
 * PhotoField represents a Photo field that can be included in the user field layout.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class PhotoField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public string $attribute = 'photo';

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        unset(
            $config['mandatory'],
            $config['attribute'],
            $config['translatable'],
            $config['required'],
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
            $fields['attribute'],
            $fields['translatable'],
            $fields['required'],
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Photo');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && !$element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        if (!$element?->id) {
            return null;
        }

        $volumeUid = Craft::$app->getProjectConfig()->get('users.photoVolumeUid');
        if (!$volumeUid) {
            return null;
        }

        $volume = Craft::$app->getVolumes()->getVolumeByUid($volumeUid);
        if (!$volume) {
            return null;
        }

        $view = Craft::$app->getView();
        $view->registerAssetBundle(UserPhotoAsset::class);
        $inputId = sprintf('user-photo-%s', mt_rand());

        $view->registerJsWithVars(fn($userId, $inputId, $isCurrentUser) => <<<JS
new Craft.UserPhotoInput($userId, '#' + $inputId, {
  isCurrentUser: $isCurrentUser,
});
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
     * @inheritdoc
     */
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (Craft::$app->getUser()->getIsAdmin()) {
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }
}
