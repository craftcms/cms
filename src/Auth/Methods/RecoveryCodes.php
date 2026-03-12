<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Methods;

use Carbon\CarbonInterface;
use Craft;
use craft\web\assets\recoverycodes\RecoveryCodesAsset;
use CraftCms\Cms\Auth\Models\RecoveryCodes as RecoveryCodesModel;
use CraftCms\Cms\Support\Facades\HtmlStack;
use InvalidArgumentException;
use Override;
use PragmaRX\Recovery\Recovery;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class RecoveryCodes extends BaseAuthMethod
{
    public static function displayName(): string
    {
        return t('Recovery Codes');
    }

    public static function description(): string
    {
        return t('Generate recovery codes that can be used as a backup.');
    }

    public function isActive(): bool
    {
        return RecoveryCodesModel::where('userId', $this->user->id)->exists();
    }

    public function getSetupHtml(string $containerId): string
    {
        HtmlStack::jsWithVars(fn ($containerId) => <<<JS
new Craft.RecoveryCodesSetup($containerId)
JS, [$containerId]);

        return template('_components/auth/methods/RecoveryCodes/setup');
    }

    public function getAuthFormHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(RecoveryCodesAsset::class);

        return template('_components/auth/methods/RecoveryCodes/form');
    }

    #[Override]
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

    public function verify(mixed ...$args): bool
    {
        [$code] = $args;

        if (! is_string($code)) {
            throw new InvalidArgumentException(sprintf('%s must be passed a string.', __METHOD__));
        }

        try {
            $code = $this->formatCode($code);
        } catch (InvalidArgumentException) {
            return false;
        }

        // check if secret is stored, if not, we need to store it
        [$codes] = $this->getRecoveryCodes();
        $codeExists = in_array($code, $codes, true);

        if (! $codeExists) {
            return false;
        }

        $this->markCodeAsUsed($code);

        return true;
    }

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
        $codes = new Recovery()
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
     * @return array{0:array<string|false>,1:CarbonInterface|null}
     */
    public function getRecoveryCodes(): array
    {
        $model = RecoveryCodesModel::where('userId', $this->user->id)->first();

        if (! $model) {
            return [[], null];
        }

        return [
            $model->recoveryCodes ?? false,
            $model->dateCreated,
        ];
    }

    private function formatCode(string $code): string
    {
        $code = preg_replace('/[^a-z0-9]/i', '', $code) ?? '';

        if (strlen($code) !== 12) {
            throw new InvalidArgumentException("Invalid recovery code: $code");
        }

        return sprintf('%s-%s', substr($code, 0, 6), substr($code, 6, 6));
    }

    private function storeRecoveryCodes(array $codes): void
    {
        $model = RecoveryCodesModel::firstOrNew([
            'userId' => $this->user->id,
        ]);

        $model->recoveryCodes = $codes;
        $model->save();
    }

    private function markCodeAsUsed(string $code): void
    {
        $code = $this->formatCode($code);

        [$codes] = $this->getRecoveryCodes();

        if (! empty($codes)) {
            $codes = array_map(fn (string|false $c) => $c === $code ? false : $c, $codes);
            $this->storeRecoveryCodes($codes);
        }
    }
}
