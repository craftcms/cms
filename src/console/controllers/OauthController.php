<?php

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use yii\console\ExitCode;

/** OAuth signing key management. @since 5.12.0 */
class OauthController extends Controller
{
    public bool $force = false;

    public function options($actionID): array
    {
        return [...parent::options($actionID), 'force'];
    }

    /** Generate shared OAuth signing keys. */
    public function actionGenerateKeys(): int
    {
        Craft::$app->getOAuth()->generateKeys($this->force);
        $this->stdout("OAuth signing keys generated.\n");
        return ExitCode::OK;
    }
}
