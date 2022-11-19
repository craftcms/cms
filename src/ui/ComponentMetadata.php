<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui;

use yii\base\BaseObject;

class ComponentMetadata extends BaseObject
{
    /**
     * @var string Name of the component.
     */
    public string $name;

    /**
     * @var string Path to the template.
     */
    public string $template;

    /**
     * @var string Class of the component.
     */
    public string $class;

    /**
     * @var bool If props should be exposed by default.
     */
    public bool $exposePublicProps = false;

    /**
     * @var string Name of the attributes variable.
     */
    public string $attributesVar = 'attributes';
}
