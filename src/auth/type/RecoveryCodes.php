<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\type;

use Craft;
use craft\auth\Configurable2faType;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\records\RecoveryCodes as RecoveryCodesRecord;
use craft\web\View;
use PragmaRX\Recovery\Recovery;

class RecoveryCodes extends Configurable2faType
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Recovery Codes');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return Craft::t('app', 'Authenticate with a recovery code');
    }

    /**
     * @inheritdoc
     */
    public function isSetupForUser(User $user): bool
    {
        return self::_getRecoveryCodesFromDb($user->id) !== null;
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return [
            'recoveryCode' => Craft::t('app', 'Recovery code'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(string $html = '', array $options = []): string
    {
        $user = Craft::$app->getAuth()->getUserFor2fa();

        if ($user === null) {
            return '';
        }

        $data = [
            'user' => $user,
            'fields' => $this->getNamespacedFields(),
            'currentMethod' => self::class,
        ];

        // if recovery codes are stored in the DB - show the verification code form only
        // (it means they've finished the setup and haven't used up all their codes)
        if (self::_getRecoveryCodesFromDb($user->id)) {
            $view = Craft::$app->getView();
            $view->templateMode = View::TEMPLATE_MODE_CP;
            $formHtml = Craft::$app->getView()->renderTemplate(
                '_components/auth/recoverycodes/verification.twig',
                $data
            );
        } else {
            // otherwise show the message
            $formHtml = '<p>' . Craft::t('app', 'Sorry, you haven’t set up your recovery codes.') . '</p>';
        }

        return parent::getInputHtml($formHtml, $options);
    }

    /**
     * @inheritdoc
     */
    public function getSetupFormHtml(string $html = '', bool $withInto = false, ?User $user = null): string
    {
        if ($user === null) {
            $user = Craft::$app->getAuth()->getUserFor2fa();
        }

        if ($user === null) {
            return '';
        }

        // show setup instructions
        $data = [
            'user' => $user,
            'fields' => $this->getNamespacedFields(),
            'withIntro' => $withInto,
            'currentMethod' => self::class,
            'recoveryCodes' => self::_getRecoveryCodesFromDb($user->id),
        ];

        if ($withInto) {
            $data['typeName'] = self::displayName();
            $data['typeDescription'] = self::getDescription();
        }

        $html = Craft::$app->getView()->renderTemplate(
            '_components/auth/recoverycodes/setup.twig',
            $data,
            View::TEMPLATE_MODE_CP
        );

        return parent::getSetupFormHtml($html, $withInto, $user);
    }

    /**
     * @inheritdoc
     */
    public function removeSetup(): bool
    {
        $userId = Craft::$app->getUser()->getId();

        if ($userId === null) {
            return false;
        }

        RecoveryCodesRecord::deleteAll(['userId' => $userId]);

        return true;
    }

    /**
     * Verify provided Recovery Code
     *
     * @param array $data
     * @return bool
     */
    public function verify(array $data): bool
    {
        $user = Craft::$app->getAuth()->getUserFor2fa();

        if ($user === null) {
            return false;
        }

        // check if secret is stored, if not, we need to store it
        $recoveryCodes = self::_getRecoveryCodesFromDb($user->id);

        if ($recoveryCodes === null) {
            return false;
        }

        $code = $data['recoveryCode'];
        if (empty($code)) {
            return false;
        }

        // verify the code:
        $verified = !empty(ArrayHelper::whereMultiple($recoveryCodes, ['value' => $code, 'dateLastUsed' => null]));

        if ($verified) {
            $this->_markCodeAsUsed($user->id, $code);
        }

        return $verified;
    }


    // RecoveryCodes-specific methods
    // -------------------------------------------------------------------------

    /**
     * Generate recovery codes. If they already exist, remove previous ones and replace with those.
     *
     * @param User $user
     * @return array
     */
    public function generateRecoveryCodes(User $user): array
    {
        $recovery = new Recovery();
        $recovery
            ->mixedcase()
            ->setBlockSeparator('-')
            ->setCount(8)
            ->setBlocks(2)
            ->setChars(6);

        $codes = $recovery->toArray();
        $recoveryCodes = [];

        if (!empty($codes)) {
            foreach ($codes as $key => $code) {
                $recoveryCodes[$key]['value'] = $code;
                $recoveryCodes[$key]['dateLastUsed'] = null;
            }

            $this->_storeRecoveryCodesInDb($user->id, $recoveryCodes);
        }

        return $recoveryCodes;
    }

    /**
     * Get user's recovery codes as a string
     *
     * @param int $userId
     * @return string|null
     */
    public function getRecoveryCodesForDownload(int $userId): ?string
    {
        $recoveryCodes = self::_getRecoveryCodesFromDb($userId);
        $codes = null;
        if ($recoveryCodes !== null) {
            foreach ($recoveryCodes as $item) {
                $codes .= $item['value'] . PHP_EOL;
            }
        }

        return $codes;
    }


    /**
     * Return user's recovery codes from the database.
     * If all codes in the DB have been used - return null.
     *
     * @param int $userId
     * @return array|null
     */
    private static function _getRecoveryCodesFromDb(int $userId): ?array
    {
        $record = RecoveryCodesRecord::find()
            ->select(['recoveryCodes'])
            ->where(['userId' => $userId])
            ->one();

        if (!$record) {
            return null;
        }

        $allUsed = true;
        $codes = Json::decode($record['recoveryCodes']);
        foreach ($codes as $key => $code) {
            if ($code['dateLastUsed'] === null) {
                $allUsed = false;
            } else {
                $codes[$key]['dateLastUsed'] = \DateTime::__set_state($code['dateLastUsed']);
            }
        }
        return $allUsed ? null : $codes;
    }

    /**
     * Store obtained recovery codes in the DB against userId
     *
     * @param int $userId
     * @param array $codes
     * @return void
     */
    private function _storeRecoveryCodesInDb(int $userId, array $codes): void
    {
        $record = RecoveryCodesRecord::find()
            ->where(['userId' => $userId])
            ->one();

        if (!$record) {
            $record = new RecoveryCodesRecord();
            $record->userId = $userId;
        }

        /** @var RecoveryCodesRecord $record */
        $record->recoveryCodes = Json::encode($codes);
        $record->save();
    }

    /**
     * Mark recovery code as used
     *
     * @param int $userId
     * @param string $code
     * @return void
     */
    private function _markCodeAsUsed(int $userId, string $code): void
    {
        $recoveryCodes = self::_getRecoveryCodesFromDb($userId);

        if ($recoveryCodes !== null) {
            foreach ($recoveryCodes as $key => $recoveryCode) {
                if ($recoveryCode['value'] === $code) {
                    $recoveryCodes[$key]['dateLastUsed'] = new \DateTime();
                }
            }

            $this->_storeRecoveryCodesInDb($userId, $recoveryCodes);
        }
    }
}
