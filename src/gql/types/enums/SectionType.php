<?php
namespace craft\gql\types\enums;

/**
 * Class Structure
 */
class SectionType extends BaseEnum
{
    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return [
            'single',
            'channel',
            'structure',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'sectionType';
    }
}
