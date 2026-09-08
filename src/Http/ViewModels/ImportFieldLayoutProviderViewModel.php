<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\Controllers\Import\ImportConfigController;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\ImportHelper;

use function CraftCms\Cms\t;

class ImportFieldLayoutProviderViewModel extends ViewModel
{
    public function __construct(
        private readonly ElementImporter $importer,
        private readonly FormResolver $formResolver,
        private readonly bool $readOnly = false,
        private readonly bool $canSave = true,
    ) {}

    public function form(): FormPayload
    {
        $values = [
            'uid' => $this->importer->uid,
            'fieldLayout' => old('fieldLayout', $this->importer->fieldLayout),
        ];
        $mode = $this->readOnly || ! $this->canSave ? ControlMode::ReadOnly : ControlMode::Editable;

        return $this->formResolver->resolve(Form::make([
            HiddenField::make('uid'),
            Field::make(
                t('Choose the field layout provider to import into (e.g. entry type, volume)'),
                Choice::make('fieldLayout')->options(ImportHelper::getAvailableFieldLayoutProviders($this->importer->className)),
            ),
        ]), new FormContext(
            values: $values,
            mode: $mode,
        ));
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([ImportConfigController::class, 'storeFieldLayoutProvider']),
        ];
    }
}
