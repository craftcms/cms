<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Conditions\DateModifiedConditionRule;
use CraftCms\Cms\Asset\Conditions\FilenameConditionRule;
use CraftCms\Cms\Asset\Conditions\FileSizeConditionRule;
use CraftCms\Cms\Asset\Conditions\FileTypeConditionRule;
use CraftCms\Cms\Asset\Conditions\HasAltConditionRule;
use CraftCms\Cms\Asset\Conditions\HeightConditionRule;
use CraftCms\Cms\Asset\Conditions\SavableConditionRule;
use CraftCms\Cms\Asset\Conditions\UploaderConditionRule;
use CraftCms\Cms\Asset\Conditions\ViewableConditionRule;
use CraftCms\Cms\Asset\Conditions\VolumeConditionRule;
use CraftCms\Cms\Asset\Conditions\WidthConditionRule;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

describe('SavableConditionRule', function () {
    it('matchElement returns true when value matches the acting user’s save permission', function () {
        $asset = AssetModel::factory()->createElement();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(SavableConditionRule::class);
        $rule->value = true;

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when value does not match the acting user’s save permission', function () {
        $asset = AssetModel::factory()->createElement();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(SavableConditionRule::class);
        $rule->value = false;

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery returns savable assets for the acting user', function () {
        AssetModel::factory()->createElement();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(SavableConditionRule::class);
        $rule->value = true;

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('FileTypeConditionRule', function () {
    it('matchElement returns true when the kind is in the selected values', function () {
        $asset = AssetModel::factory()->createElement(['kind' => 'image', 'filename' => 'photo.jpg']);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FileTypeConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['image'];

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when the kind is not in the selected values', function () {
        $asset = AssetModel::factory()->createElement(['kind' => 'video', 'filename' => 'clip.mov']);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FileTypeConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['image'];

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery filters assets by file type', function () {
        AssetModel::factory()->create(['kind' => 'image', 'filename' => 'photo.jpg']);
        AssetModel::factory()->create(['kind' => 'video', 'filename' => 'clip.mov']);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FileTypeConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['image'];

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBe(1)
            ->and($query->one()->kind)->toBe('image');
    });
});

describe('UploaderConditionRule', function () {
    it('matchElement returns true when the uploader is in the selected values', function () {
        $uploader = UserModel::factory()->create();
        $asset = AssetModel::factory()->createElement(['uploaderId' => $uploader->id]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(UploaderConditionRule::class);
        $rule->setElementIds([$uploader->id]);

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when the uploader is not in the selected values', function () {
        $uploader = UserModel::factory()->create();
        $otherUploader = UserModel::factory()->create();
        $asset = AssetModel::factory()->createElement(['uploaderId' => $uploader->id]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(UploaderConditionRule::class);
        $rule->setElementIds([$otherUploader->id]);

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery filters assets by uploader', function () {
        $uploader1 = UserModel::factory()->create();
        $uploader2 = UserModel::factory()->create();
        AssetModel::factory()->create(['uploaderId' => $uploader1->id]);
        AssetModel::factory()->create(['uploaderId' => $uploader2->id]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(UploaderConditionRule::class);
        $rule->setElementIds([$uploader1->id]);

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBe(1)
            ->and($query->one()->uploaderId)->toBe($uploader1->id);
    });
});

describe('FileSizeConditionRule', function () {
    it('matchElement returns true when the asset has a size', function () {
        $asset = AssetModel::factory()->createElement(['size' => 1024]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FileSizeConditionRule::class);
        $rule->unit = FileSizeConditionRule::UNIT_B;
        $rule->operator = '=';
        $rule->value = '1024';

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when the asset has no size', function () {
        $asset = AssetModel::factory()->createElement(['size' => 0]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FileSizeConditionRule::class);
        $rule->unit = FileSizeConditionRule::UNIT_B;
        $rule->operator = '=';
        $rule->value = '1024';

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('matchElement returns false when the asset’s size does not match', function () {
        $asset = AssetModel::factory()->createElement(['size' => 2048]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FileSizeConditionRule::class);
        $rule->unit = FileSizeConditionRule::UNIT_B;
        $rule->operator = '=';
        $rule->value = '1024';

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery filters assets by file size', function () {
        AssetModel::factory()->create(['size' => 1024]);
        AssetModel::factory()->create(['size' => 2048]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FileSizeConditionRule::class);
        $rule->unit = FileSizeConditionRule::UNIT_B;
        $rule->operator = '=';
        $rule->value = '1024';

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBe(1)
            ->and($query->one()->size)->toBe(1024);
    });
});

describe('DateModifiedConditionRule', function () {
    it('matchElement returns true with not-empty when the asset has a dateModified', function () {
        $asset = AssetModel::factory()->createElement(['dateModified' => new DateTime('2025-06-15')]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(DateModifiedConditionRule::class);
        $rule->rangeType = 'notempty';

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false with empty when the asset has a dateModified', function () {
        $asset = AssetModel::factory()->createElement(['dateModified' => new DateTime('2025-06-15')]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(DateModifiedConditionRule::class);
        $rule->rangeType = 'empty';

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery with not-empty only returns assets with a dateModified', function () {
        AssetModel::factory()->createElement(['dateModified' => new DateTime('2025-06-15')]);
        AssetModel::factory()->createElement();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(DateModifiedConditionRule::class);
        $rule->rangeType = 'notempty';

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBe(1);
    });
});

describe('FilenameConditionRule', function () {
    it('matchElement returns true when the filename matches', function () {
        $asset = AssetModel::factory()->createElement(['filename' => 'photo.jpg']);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FilenameConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'photo.jpg';

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when the filename does not match', function () {
        $asset = AssetModel::factory()->createElement(['filename' => 'other.jpg']);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FilenameConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'photo.jpg';

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery filters assets by filename', function () {
        AssetModel::factory()->create(['filename' => 'photo.jpg']);
        AssetModel::factory()->create(['filename' => 'other.jpg']);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(FilenameConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'photo.jpg';

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBe(1)
            ->and($query->one()->filename)->toBe('photo.jpg');
    });
});

describe('HeightConditionRule', function () {
    it('matchElement returns true when the height matches', function () {
        $asset = AssetModel::factory()->createElement(['height' => 800, 'width' => 600]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(HeightConditionRule::class);
        $rule->operator = '=';
        $rule->value = '800';

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when the height does not match', function () {
        $asset = AssetModel::factory()->createElement(['height' => 400, 'width' => 600]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(HeightConditionRule::class);
        $rule->operator = '=';
        $rule->value = '800';

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery filters assets by height', function () {
        AssetModel::factory()->create(['height' => 800, 'width' => 600]);
        AssetModel::factory()->create(['height' => 400, 'width' => 600]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(HeightConditionRule::class);
        $rule->operator = '=';
        $rule->value = '800';

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBe(1)
            ->and($query->one()->height)->toBe(800);
    });
});

describe('VolumeConditionRule', function () {
    it('matchElement returns true when the asset’s volume is in the selected values', function () {
        $volume1 = Volume::factory()->create(['fs' => 'disk:test-disk']);
        $folder1 = VolumeFolderModel::factory()->create(['volumeId' => $volume1->id]);
        $asset = AssetModel::factory()->createElement(['volumeId' => $volume1->id, 'folderId' => $folder1->id]);

        app()->forgetInstance(Volumes::class);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(VolumeConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$volume1->uid];

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when the asset’s volume is not in the selected values', function () {
        $volume1 = Volume::factory()->create(['fs' => 'disk:test-disk']);
        $volume2 = Volume::factory()->create(['fs' => 'disk:test-disk']);
        $folder2 = VolumeFolderModel::factory()->create(['volumeId' => $volume2->id]);
        $asset = AssetModel::factory()->createElement(['volumeId' => $volume2->id, 'folderId' => $folder2->id]);

        app()->forgetInstance(Volumes::class);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(VolumeConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$volume1->uid];

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery filters assets by volume', function () {
        $volume1 = Volume::factory()->create(['fs' => 'disk:test-disk']);
        $volume2 = Volume::factory()->create(['fs' => 'disk:test-disk']);
        $folder1 = VolumeFolderModel::factory()->create(['volumeId' => $volume1->id]);
        $folder2 = VolumeFolderModel::factory()->create(['volumeId' => $volume2->id]);
        AssetModel::factory()->create(['volumeId' => $volume1->id, 'folderId' => $folder1->id]);
        AssetModel::factory()->create(['volumeId' => $volume2->id, 'folderId' => $folder2->id]);

        app()->forgetInstance(Volumes::class);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(VolumeConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$volume1->uid];

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBe(1)
            ->and($query->one()->volumeId)->toBe($volume1->id);
    });
});

describe('WidthConditionRule', function () {
    it('matchElement returns true when the width matches', function () {
        $asset = AssetModel::factory()->createElement(['width' => 600, 'height' => 800]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(WidthConditionRule::class);
        $rule->operator = '=';
        $rule->value = '600';

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when the width does not match', function () {
        $asset = AssetModel::factory()->createElement(['width' => 300, 'height' => 800]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(WidthConditionRule::class);
        $rule->operator = '=';
        $rule->value = '600';

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery filters assets by width', function () {
        AssetModel::factory()->create(['width' => 600, 'height' => 800]);
        AssetModel::factory()->create(['width' => 300, 'height' => 800]);

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(WidthConditionRule::class);
        $rule->operator = '=';
        $rule->value = '600';

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBe(1)
            ->and($query->one()->width)->toBe(600);
    });
});

describe('HasAltConditionRule', function () {
    it('matchElement returns true when the asset has alternative text', function () {
        $model = AssetModel::factory()->create();
        $model->sites()->attach(Site::all(), ['alt' => 'Some description']);
        $asset = Asset::find()->id($model->id)->one();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(HasAltConditionRule::class);
        $rule->value = true;

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when the asset has no alternative text', function () {
        $asset = AssetModel::factory()->createElement();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(HasAltConditionRule::class);
        $rule->value = true;

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery filters assets by whether they have alternative text', function () {
        $model = AssetModel::factory()->create();
        $model->sites()->attach(Site::all(), ['alt' => 'Some description']);
        AssetModel::factory()->create();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(HasAltConditionRule::class);
        $rule->value = true;

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBe(1);
    });
});

describe('ViewableConditionRule', function () {
    it('matchElement returns true when value matches the acting user’s view permission', function () {
        $asset = AssetModel::factory()->createElement();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(ViewableConditionRule::class);
        $rule->value = true;

        expect($rule->matchElement($asset))->toBeTrue();
    });

    it('matchElement returns false when value does not match the acting user’s view permission', function () {
        $asset = AssetModel::factory()->createElement();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(ViewableConditionRule::class);
        $rule->value = false;

        expect($rule->matchElement($asset))->toBeFalse();
    });

    it('modifyQuery returns viewable assets for the acting user', function () {
        AssetModel::factory()->createElement();

        $condition = new AssetCondition(Asset::class);
        $rule = $condition->createConditionRule(ViewableConditionRule::class);
        $rule->value = true;

        $query = Asset::find();
        $rule->modifyQuery($query, $query);

        expect($query->count())->toBeGreaterThanOrEqual(1);
    });
});
