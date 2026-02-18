<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\addresses;

use craft\base\ElementInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Support\Arr;
use Override;

use function CraftCms\Cms\t;

class LabelField extends TitleField
{
    public bool $requirable = true;

    public bool $translatable = false;

    public function __construct($config = [])
    {
        $this->required = Arr::pull($config, 'required', $this->required);
        unset($config['requirable']);
        parent::__construct($config);
    }

    #[Override]
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['requirable']);
        $fields['required'] = 'required';

        return $fields;
    }

    #[Override]
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Label');
    }
}
