<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Form\Controls\AssetSelect;

/** An Assets field with nothing configured beyond its identity. */
function assetsField(string $name = 'Images', string $handle = 'images'): Assets
{
    return new Assets(['name' => $name, 'handle' => $handle]);
}

describe('formWarning', function () {
    // `_uploadFolder()` re-throws as an `InvalidFsException` carrying a message
    // written for the author, naming the field and the setting at fault. That
    // message is what the field reports.

    it('warns when the upload location points at no volume', function () {
        $field = assetsField();
        $field->allowUploads = true;
        $field->defaultUploadLocationSource = null;

        expect($field->formWarning())
            ->toBe('The Images field’s Default Upload Location setting is set to an invalid volume.');
    });

    it('names the restricted setting rather than the default one', function () {
        $field = assetsField('Docs', 'docs');
        $field->allowUploads = false;
        $field->restrictLocation = true;
        // Unset rather than pointing at a missing UID, which would need the
        // volumes table; either way `_uploadFolder()` never resolves a volume.
        $field->restrictedLocationSource = null;

        expect($field->formWarning())
            ->toBe('The Docs field’s Asset Location setting is set to an invalid volume.');
    });

    it('stays quiet for a field that neither uploads nor restricts', function () {
        $field = assetsField();
        $field->allowUploads = false;
        $field->restrictLocation = false;
        // Never resolved for such a field, so its absence isn't a
        // misconfiguration to complain about.
        $field->defaultUploadLocationSource = null;

        expect($field->formWarning())->toBeNull();
    });
});

describe('formControl', function () {
    it('drops the upload affordance instead of throwing on a bad location', function () {
        $field = assetsField();
        $field->allowUploads = true;
        $field->defaultUploadLocationSource = null;

        $control = $field->formControl(new FieldContext(path: 'images'));

        expect($control)->toBeInstanceOf(AssetSelect::class)
            ->and($control->props()['canUpload'])->toBeFalse()
            ->and($control->props()['uploadFolderId'])->toBeNull();
    });
});
