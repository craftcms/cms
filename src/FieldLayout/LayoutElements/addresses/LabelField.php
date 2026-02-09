<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\FieldLayout\LayoutElements\addresses;

use craft\base\ElementInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Support\Arr;

use function CraftCms\Cms\t;

/**
 * Class LabelField.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class LabelField extends TitleField
{
    /**
     * {@inheritdoc}
     */
    public bool $requirable = true;

    /**
     * {@inheritdoc}
     */
    public bool $translatable = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        $this->required = Arr::pull($config, 'required', $this->required);
        unset($config['requirable']);
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['requirable']);
        $fields['required'] = 'required';

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Label');
    }
}
