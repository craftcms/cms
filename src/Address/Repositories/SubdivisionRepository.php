<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Repositories;

use CommerceGuys\Addressing\Locale;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository as BaseSubdivisionRepository;
use Craft;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Support\Json;
use Override;

/**
 * Craft's extension of the commerceguys/addressing SubdivisionRepository.
 * Its main purpose is to allow addition of data that's not returned by the commerceguys/addressing library,
 * like the GB counties data. It also triggers an event which allows developers to modify the subdivisions further.
 */
class SubdivisionRepository extends BaseSubdivisionRepository
{
    #[Override]
    public function getList(array $parents, $locale = null): array
    {
        // get the list of subdivisions from commerceguys/addressing
        $options = parent::getList($parents, app()->getLocale());

        // if the list is empty (like in case of GB), get the extra options from our files
        if (empty($options)) {
            $options = $this->getExtraOptions($parents, app()->getLocale());
        }

        // trigger the event to give devs a chance to modify further, and return the list
        return app(Addresses::class)->defineAddressSubdivisions($parents, $options);
    }

    /**
     * Get a list of extra subdivision options
     */
    private function getExtraOptions(array $parents, ?string $lang = null): array
    {
        $list = [];
        $fileName = implode('-', $parents);
        $filePath = __DIR__.'/data/'.$fileName.'.json';

        if (@file_exists($filePath) && $data = @file_get_contents($filePath)) {
            $data = Json::decode($data);

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
