<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Composer\Semver\Semver;
use Craft;

/**
 * Update helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.15
 */
class Update
{
    /**
     * Compares the given PHP version constraint with the environment, and returns any issues with it.
     *
     * @param string $constraint The PHP version constraint
     * @param string|null $error The error message
     * @param bool $withLink Whether the error message should include a “Learn more” link
     * @return bool Whether the environment passes the PHP constraint
     */
    public static function checkPhpConstraint(string $constraint, ?string &$error = null, bool $withLink = false): bool
    {
        $installedVersion = App::phpVersion();
        if (!Semver::satisfies($installedVersion, $constraint)) {
            $error = Craft::t('app', 'This update requires PHP {v1}, but your environment is currently running PHP {v2}.', [
                'v1' => $constraint,
                'v2' => $installedVersion,
            ]);
            return false;
        }

        $composerVersion = Craft::$app->getComposer()->getConfig()['config']['platform']['php'] ?? null;
        if ($composerVersion && !Semver::satisfies($composerVersion, $constraint)) {
            $error = Craft::t('app', 'This update requires PHP {v1}, but your composer.json file is currently set to PHP {v2}.', [
                'v1' => $constraint,
                'v2' => $composerVersion,
            ]);
            if ($withLink) {
                $error .= ' ' . Html::a(Craft::t('app', 'Learn more'), 'https://craftcms.com/knowledge-base/resolving-php-requirement-conflicts', [
                        'class' => 'go',
                    ]);
            }
            return false;
        }

        return true;
    }
}
