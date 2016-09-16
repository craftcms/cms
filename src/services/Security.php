<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\helpers\Io;
use craft\app\helpers\StringHelper;
use yii\base\Exception;
use yii\base\InvalidParamException;

/**
 * Class Security service.
 *
 * An instance of the Security service is globally accessible in Craft via [[Application::security `Craft::$app->getSecurity()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Security extends \yii\base\Security
{
    // Properties
    // =========================================================================

    /**
     * @var mixed
     */
    private $_blowFishHashCost;

    // Public Methods
    // =========================================================================

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->_blowFishHashCost = Craft::$app->getConfig()->get('blowfishHashCost');
    }

    /**
     * @return integer
     */
    public function getMinimumPasswordLength()
    {
        return 6;
    }

    /**
     * Hashes a given password with the bcrypt blowfish encryption algorithm.
     *
     * @param string  $password     The string to hash
     * @param boolean $validateHash If you want to validate the just generated hash. Will throw an exception if
     *                              validation fails.
     *
     * @return string The hash.
     */
    public function hashPassword($password, $validateHash = false)
    {
        $hash = $this->generatePasswordHash($password, $this->_blowFishHashCost);

        if ($validateHash) {
            if (!$this->validatePassword($password, $hash)) {
                throw new InvalidParamException('Could not hash the given string.');
            }
        }

        return $hash;
    }

    /**
     * Returns a validtion key unique to this Craft installation. Craft will initially check the 'validationKey'
     * config setting and return that if one has been explicitly set. If not, Craft will generate a cryptographically
     * secure, random key and save it in `craft\storage\validation.key` and serve that on future requests.
     *
     * Note that if this key ever changes, any data that was encrypted with it will not be accessible.
     *
     * @return mixed|string The validation key.
     * @throws Exception if the validation key could not be written
     */
    public function getValidationKey()
    {
        if ($key = Craft::$app->getConfig()->get('validationKey')) {
            return $key;
        }

        $validationKeyPath = Craft::$app->getPath()->getRuntimePath().'/validation.key';

        if (Io::fileExists($validationKeyPath)) {
            return StringHelper::trim(Io::getFileContents($validationKeyPath));
        }

        if (!Io::isWritable($validationKeyPath)) {
            throw new Exception("Tried to write the validation key to {$validationKeyPath}, but could not.");
        }

        $key = $this->generateRandomString();

        if (Io::writeToFile($validationKeyPath, $key)) {
            return $key;
        }

        throw new Exception("Tried to write the validation key to {$validationKeyPath}, but could not.");
    }

    /**
     * @inheritdoc
     */
    public function hashData($data, $key = null, $rawHash = false)
    {
        if ($key === null) {
            $key = $this->getValidationKey();
        }

        return parent::hashData($data, $key, $rawHash);
    }

    /**
     * @inheritdoc
     */
    public function validateData($data, $key = null, $rawHash = false)
    {
        if ($key === null) {
            $key = $this->getValidationKey();
        }

        return parent::validateData($data, $key, $rawHash);
    }
}
