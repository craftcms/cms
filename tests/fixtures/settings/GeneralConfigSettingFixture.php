<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures\settings;

use Craft;
use yii\test\Fixture;

/**
 * Class GeneralConfigSettingFixture.
 *
 * Used to set and unset specific config values.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.0
 */
class GeneralConfigSettingFixture extends Fixture
{
    /**
     * @var string The config setting that should be updated for the duration of the test.
     */
    public string $setting;

    /**
     * @var mixed The value for the setting that will be used.
     */
    public mixed $value;

    /**
     * @return mixed Original value of the setting, which will be restored at the end of the test.
     */
    public mixed $originalValue;

    public function load()
    {
        parent::load();

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        // Save the current value:
        $this->originalValue = $generalConfig->{$this->setting};

        // Set the new value:
        $generalConfig->{$this->setting} = $this->value;
    }

    public function unload()
    {
        parent::unload();

        // If this is in the cleanup (pre-load unload) phase, we won’t have anything to clean up:
        if (!isset($this->originalValue)) {
            return;
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        // Restore the original value:
        $generalConfig->{$this->setting} = $this->originalValue;
    }
}
