<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;
use craft\helpers\StringHelper;

/**
 * Debugger panel that collects and displays request data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.1
 */
class RequestPanel extends \yii\debug\panels\RequestPanel
{
    /**
     * @inheritdoc
     */
    public function save(): array
    {
        $data = parent::save();
        $data = Craft::$app->getSecurity()->redactIfSensitive('', $data);
        if (isset($data['actionParams'])) {
            $this->serializeObjects($data['actionParams']);
        }
        return $data;
    }

    private function serializeObjects(array &$arr, int $indent = 1): void
    {
        foreach ($arr as &$value) {
            if (is_object($value)) {
                $dump = trim(Craft::dump($value, 10, false, true));
                $value = ltrim(StringHelper::indent($dump, str_repeat('    ', $indent)));
            } elseif (is_array($value)) {
                $this->serializeObjects($value, $indent + 1);
            }
        }
    }
}
