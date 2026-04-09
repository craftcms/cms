<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Support\Json as JsonHelper;
use InvalidArgumentException;

class Json implements DataTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public static function format(string $data): array
    {
        // Parse the JSON string
        try {
            $array = JsonHelper::decode($data);
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid JSON: '.$e->getMessage();

            return ['success' => false, 'error' => $error];
        }

        return ['success' => true, 'data' => $array];
    }
}
