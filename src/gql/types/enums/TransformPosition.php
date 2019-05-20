<?php
namespace craft\gql\types\enums;

/**
 * Class TransformPosition
 */
class TransformPosition extends BaseEnum
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'transformPosition';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return [
            'topLeft' => 'top-left',
            'topCenter' => 'top-center',
            'topRight' => 'top-right',
            'centerLeft' => 'center-left',
            'centerCenter' => 'center-center',
            'centerRight' => 'center-right',
            'bottomLeft' => 'bottom-left',
            'bottomCenter' => 'bottom-center',
            'bottomRight' => 'bottom-right',
        ];
    }
}
