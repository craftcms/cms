<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\elements\User as UserElement;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\helpers\Json;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class User extends Element
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            UserInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var UserElement $source */
        $fieldName = $resolveInfo->fieldName;

        if ($fieldName === 'preferences') {
            return Json::encode($source->getPreferences());
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
