<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\validators\DateTimeValidator;

/**
 * Class Et model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Et extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string|null License key
     */
    public $licenseKey;

    /**
     * @var string|null License key status
     */
    public $licenseKeyStatus;

    /**
     * @var string|null Licensed edition
     */
    public $licensedEdition;

    /**
     * @var string|null Licensed domain
     */
    public $licensedDomain;

    /**
     * @var bool Edition testable domain
     */
    public $editionTestableDomain = false;

    /**
     * @var array|null The installed plugin license keys
     */
    public $pluginLicenseKeys;

    /**
     * @var array|null The plugins' license key statuses. Set by the server response.
     */
    public $pluginLicenseKeyStatuses;

    /**
     * @var array|string|Model|null Data
     */
    public $data;

    /**
     * @var string Request URL
     */
    public $requestUrl = '';

    /**
     * @var string Request ip
     */
    public $requestIp = '1.1.1.1';

    /**
     * @var \DateTime|null Request time
     */
    public $requestTime;

    /**
     * @var string|null Request port
     */
    public $requestPort;

    /**
     * @var string|null Local version
     */
    public $localVersion;

    /**
     * @var string|null Local edition
     */
    public $localEdition;

    /**
     * @var string|null User email
     */
    public $userEmail;

    /**
     * @var bool Show beta updates
     */
    public $showBeta = false;

    /**
     * @var array|null Response errors
     */
    public $responseErrors;

    /**
     * @var array|null Server info
     */
    public $serverInfo;

    /**
     * @var string The context of the request. Either 'craft' or a plugin's package name.
     */
    public $handle = 'craft';

    // Public Methods
    // =========================================================================

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['requestTime'])) {
            $date = DateTimeHelper::currentUTCDateTime();
            $config['requestTime'] = Db::prepareDateForDb($date);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['licensedEdition'], 'in', 'range' => [0, 1, 2]],
            [['requestTime'], DateTimeValidator::class],
            [['localVersion', 'localEdition', 'handle'], 'required'],
            [['userEmail'], 'email'],
        ];
    }

    /**
     */
    public function decode()
    {
        echo Json::decode($this);
    }
}
