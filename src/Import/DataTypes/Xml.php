<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Support\Json as JsonHelper;
use Exception;

class Xml implements DataTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public static function format(string $data): array
    {
        try {
            $xmlObj = simplexml_load_string($data);
            $array = JsonHelper::decode(json_encode($xmlObj));

            $firstKey = array_key_first($array);
            if (is_array($array[$firstKey])) {
                $array = $array[$firstKey];
            }
        } catch (Exception $e) {
            $error = "Invalid XML: {$e->getMessage()}";

            return ['success' => false, 'error' => $error];
        }

        return ['success' => true, 'data' => $array];
    }
}
