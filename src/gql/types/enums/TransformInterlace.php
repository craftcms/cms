<?php
namespace craft\gql\types\enums;

/**
 * Class TransformInterlace
 */
class TransformInterlace extends BaseEnum
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'transformInterlace';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return [
            'none',
            'line',
            'plane',
            'partition',
        ];
    }
}
