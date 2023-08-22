<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\addresses;

use CommerceGuys\Addressing\Locale;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository as BaseSubdivisionRepository;
use Craft;

/**
 * Craft's extension of the commerceguys/addressing SubdivisionRepository.
 * Its main purpose is to allow addition of data that's not returned by the commerceguys/addressing library,
 * like the GB counties data. It also triggers an event which allows developers to modify the subdivisions further.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class SubdivisionRepository extends BaseSubdivisionRepository
{
    /**
     * @inheritdoc
     */
    public function getList(array $parents, $locale = null): array
    {
        // get the list of subdivisions from commerceguys/addressing
        $options = parent::getList($parents, Craft::$app->language);

        // if the list is empty (like in case of GB), get the extra options from our files
        if (empty($options)) {
            $options = $this->_getExtraOptions($parents, Craft::$app->language);
        }

        // trigger the event to give devs a chance to modify further, and return the list
        return Craft::$app->getAddresses()->defineAddressSubdivisions($parents, $options);
    }

    /**
     * Get a list of extra subdivision options
     *
     * @param array $parents
     * @param string|null $lang
     * @return array
     */
    private function _getExtraOptions(array $parents, string $lang = null): array
    {
        $list = [];
        $fileName = implode('-', $parents);
        $filePath = __DIR__ . '/data/' . $fileName . '.json';

        if (@file_exists($filePath) && $data = @file_get_contents($filePath)) {
            $data = json_decode($data, true);

            if ($data['subdivisions']) {
                $useLocalName = Locale::matchCandidates($lang, $data['extraLocale'] ?? null);
                foreach ($data['subdivisions'] as $key => $value) {
                    $list[$key] = $useLocalName ? $value['local_name'] : $value['name'];
                }
            }
        }
        ksort($list);

        return $list;
    }
}
