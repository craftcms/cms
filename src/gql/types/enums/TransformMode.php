<?php
namespace craft\gql\types\enums;

/**
 * Class TransformMode
 */
class TransformMode extends BaseEnum
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'transformMode';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return [
            'stretch',
            'fit',
            'crop',
        ];
    }
}
