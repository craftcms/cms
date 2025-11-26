<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\methods;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\assets\recoverycodes\RecoveryCodesAsset;
use CraftCms\Cms\Auth\Models\RecoveryCodes as RecoveryCodesModel;
use DateTime;
use PragmaRX\Recovery\Recovery;
use yii\base\InvalidArgumentException;
use function CraftCms\Cms\t;

/**
 * Recovery codes authentication method.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class RecoveryCodes extends BaseAuthMethod
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return t('Recovery Codes');
    }

    /**
     * @inheritdoc
     */
    public static function description(): string
    {
        return t('Generate recovery codes that can be used as a backup.');
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        return RecoveryCodesModel::where('userId', $this->user->id)->exists();
    }

    /**
     * @inheritdoc
     */
    public function getSetupHtml(string $containerId): string
    {
        $view = Craft::$app->getView();

        $view->registerJsWithVars(fn($containerId) => <<<JS
new Craft.RecoveryCodesSetup($containerId)
JS, [$containerId]);

        return $view->renderTemplate('_components/auth/methods/RecoveryCodes/setup.twig');
    }

    /**
     * @inheritdoc
     */
    public function getAuthFormHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(RecoveryCodesAsset::class);
        return $view->renderTemplate('_components/auth/methods/RecoveryCodes/form.twig');
    }

    /**
     * @inheritdoc
     */
    public function getActionMenuItems(): array
    {
        return [
            [
                'label' => t('Download codes'),
                'icon' => 'download',
                'action' => 'auth/download-recovery-codes',
                'requireElevatedSession' => true,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function verify(mixed ...$args): bool
    {
        [$code] = $args;

        if (!is_string($code)) {
            throw new InvalidArgumentException(sprintf('%s must be passed a string.', __METHOD__));
        }

        try {
            $this->formatCode($code);
        } catch (InvalidArgumentException) {
            return false;
        }

        // check if secret is stored, if not, we need to store it
        [$codes] = $this->getRecoveryCodes();
        $codeExists = in_array($code, $codes, true);

        if (!$codeExists) {
            return false;
        }

        $this->markCodeAsUsed($code);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function remove(): void
    {
        RecoveryCodesModel::where('userId', $this->user->id)->delete();
    }

    /**
     * Generate recovery codes. If they already exist, remove previous ones and replace with those.
     *
     * @return string[]
     */
    public function generateRecoveryCodes(): array
    {
        $codes = (new Recovery())
            ->mixedcase()
            ->setBlockSeparator('-')
            ->setCount(10)
            ->setBlocks(2)
            ->setChars(6)
            ->toArray();

        $this->storeRecoveryCodes($codes);
        return $codes;
    }

    /**
     * Returns the user’s recovery codes.
     *
     * @return array{0:array<string|false>,1:DateTime|null}
     */
    public function getRecoveryCodes(): array
    {
        $model = RecoveryCodesModel::where('userId', $this->user->id)->first();

        if (!$model) {
            return [[], null];
        }

        return [
            $model->recoveryCodes ?? false,
            DateTimeHelper::toDateTime($model->dateCreated),
        ];
    }

    private function formatCode(string &$code): void
    {
        $code = preg_replace('/[^a-z0-9]/i', '', $code) ?? '';
        if (strlen($code) !== 12) {
            throw new InvalidArgumentException("Invalid recovery code: $code");
        }
        $code = sprintf('%s-%s', substr($code, 0, 6), substr($code, 6, 6));
    }

    private function storeRecoveryCodes(array $codes): void
    {
        $model = RecoveryCodesModel::firstOrNew([
            'userId' => $this->user->id,
        ]);

        $model->recoveryCodes = $codes;
        $model->save();
    }

    /**
     * Mark recovery code as used
     *
     * @param string $code
     */
    private function markCodeAsUsed(string $code): void
    {
        $this->formatCode($code);
        [$codes] = $this->getRecoveryCodes();

        if (!empty($codes)) {
            $codes = array_map(fn(string|false $c) => $c === $code ? false : $c, $codes);
            $this->storeRecoveryCodes($codes);
        }
    }
}
