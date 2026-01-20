<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\users;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use yii\base\InvalidArgumentException;
use function CraftCms\Cms\t;

/**
 * AffiliatedSiteField represents the Affiliated Site field that can be included in the user field layout.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
class AffiliatedSiteField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'affiliatedSiteId';

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
            $config['warning'],
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
            $fields['warning'],
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Affiliated Site');
    }

    /**
     * @inheritdoc
     */
    protected function instructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Determines which site the user will receive emails from, when sent via the control panel.');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && !$element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        if (!Sites::isMultiSite()) {
            return null;
        }

        return Cp::selectHtml([
            'name' => 'affiliatedSiteId',
            'id' => 'affiliated-site',
            'options' => [
                ['label' => t('None'), 'value' => ''],
                ...Sites::getAllSites()->map(fn(Site $site) => [
                    'label' => $site->getUiLabel(),
                    'value' => $site->id,
                ])->all(),
            ],
            'value' => $element?->affiliatedSiteId,
        ]);
    }

    /**
     * @inheritdoc
     */
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
