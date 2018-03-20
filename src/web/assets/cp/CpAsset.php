<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\d3\D3Asset;
use craft\web\assets\datepickeri18n\DatepickerI18nAsset;
use craft\web\assets\elementresizedetector\ElementResizeDetectorAsset;
use craft\web\assets\fabric\FabricAsset;
use craft\web\assets\fileupload\FileUploadAsset;
use craft\web\assets\garnish\GarnishAsset;
use craft\web\assets\jquerypayment\JqueryPaymentAsset;
use craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset;
use craft\web\assets\jqueryui\JqueryUiAsset;
use craft\web\assets\picturefill\PicturefillAsset;
use craft\web\assets\selectize\SelectizeAsset;
use craft\web\assets\velocity\VelocityAsset;
use craft\web\assets\xregexp\XregexpAsset;
use craft\web\View;
use yii\web\JqueryAsset;

/**
 * Asset bundle for the Control Panel
 */
class CpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__.'/dist';

        $this->depends = [
            D3Asset::class,
            ElementResizeDetectorAsset::class,
            GarnishAsset::class,
            JqueryAsset::class,
            JqueryTouchEventsAsset::class,
            JqueryUiAsset::class,
            JqueryPaymentAsset::class,
            DatepickerI18nAsset::class,
            PicturefillAsset::class,
            SelectizeAsset::class,
            VelocityAsset::class,
            FileUploadAsset::class,
            XregexpAsset::class,
            FabricAsset::class,
        ];

        $this->css = [
            'css/craft.css',
            'css/charts.css',
        ];

        $this->js[] = 'js/Craft'.$this->dotJs();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                '(blank)',
                '1 Available Update',
                'Actions',
                'All',
                'An unknown error occurred.',
                'Any changes will be lost if you leave this page.',
                'Apply this to the {number} remaining conflicts?',
                'Are you sure you want to delete this image?',
                'Are you sure you want to delete “{name}”?',
                'Are you sure you want to transfer your license to this domain?',
                'Buy {name}',
                'Cancel',
                'Choose a user',
                'Choose which table columns should be visible for this source, and in which order.',
                'Close',
                'Close Live Preview',
                'Continue',
                'Couldn’t delete “{name}”.',
                'Couldn’t save new order.',
                'Create',
                'Delete',
                'Delete folder',
                'Delete heading',
                'Delete it',
                'Delete user',
                'Delete users',
                'Display as thumbnails',
                'Display in a table',
                'Done',
                'Edit',
                'Enter the name of the folder',
                'Enter your password to continue.',
                'Enter your password to log back in.',
                'Failed',
                'Give your tab a name.',
                'Handle',
                'Heading',
                'Hide',
                'Hide sidebar',
                'Incorrect password.',
                'Instructions',
                'Keep both',
                'Keep me logged in',
                'License transferred.',
                'Log out now',
                'Login',
                'Make not required',
                'Make required',
                'Merge the folder (any conflicting files will be replaced)',
                'More',
                'Move',
                'Name',
                'New category',
                'New child',
                'New entry',
                'New heading',
                'New order saved.',
                'New position saved.',
                'New subfolder',
                'New {group} category',
                'New {section} entry',
                'OK',
                'Options',
                'Password',
                'Pay {price}',
                'Pending',
                'Really delete folder “{folder}”?',
                'Remove',
                'Rename',
                'Rename folder',
                'Reorder',
                'Replace it',
                'Replace the folder (all existing files will be deleted)',
                'Save',
                'Score',
                'Search in subfolders',
                'Select',
                'Select transform',
                'Settings',
                'Show',
                'Show nav',
                'Show sidebar',
                'Show/hide children',
                'Sort by {attribute}',
                'Source settings saved',
                'Structure',
                'Submit',
                'Table Columns',
                'This can be left blank if you just want an unlabeled separator.',
                'Transfer it to:',
                'Try again',
                'Upload failed for {filename}',
                'Upload files',
                'What do you want to do with their content?',
                'What do you want to do?',
                'Your session has ended.',
                'Your session will expire in {time}.',
                'day',
                'days',
                'hour',
                'hours',
                'minute',
                'minutes',
                'second',
                'seconds',
                'week',
                'weeks',
                '{ctrl}C to copy.',
                '{num} Available Updates',
                '“{name}” deleted.',
            ]);
        }
    }
}
