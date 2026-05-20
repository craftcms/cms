<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Gql\Arguments\Transform as TransformArguments;
use CraftCms\Cms\Image\Contracts\AssetTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Events\AssetTransformersResolving;
use CraftCms\Cms\Image\Events\TransformDeleted;
use CraftCms\Cms\Image\Events\TransformDeleting;
use CraftCms\Cms\Image\Events\TransformDeletionApplying;
use CraftCms\Cms\Image\Events\TransformSaved;
use CraftCms\Cms\Image\Events\TransformSaving;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Image\Models\ImageTransform as ImageTransformModel;
use CraftCms\Cms\Support\Facades\AssetTransforms as AssetTransformsFacade;
use CraftCms\Cms\Support\Facades\ImageTransforms as ImageTransformsFacade;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(ImageTransforms::class);
    $this->assetTransforms = app(AssetTransforms::class);
});

it('is a singleton', function () {
    expect(ImageTransformsFacade::getFacadeRoot())->toBe(app(ImageTransforms::class));
    expect($this->service)->toBe(app(ImageTransforms::class));
});

it('has an asset transforms singleton', function () {
    expect(AssetTransformsFacade::getFacadeRoot())->toBe(app(AssetTransforms::class));
    expect($this->assetTransforms)->toBe(app(AssetTransforms::class));
});

describe('getAllTransforms', function () {
    it('returns empty collection when no transforms exist', function () {
        expect($this->service->getAllTransforms())->toBeEmpty();
    });

    it('returns all saved transforms', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Thumbnail',
            'handle' => 'thumb',
            'width' => 200,
            'height' => 200,
        ]));

        $this->service->saveTransform(new ImageTransform([
            'name' => 'Hero',
            'handle' => 'hero',
            'width' => 1200,
            'height' => 600,
        ]));

        $this->service->reset();

        $transforms = $this->service->getAllTransforms();

        expect($transforms)->toHaveCount(2);
    });

    it('orders transforms by name', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Zebra',
            'handle' => 'zebra',
            'width' => 100,
        ]));

        $this->service->saveTransform(new ImageTransform([
            'name' => 'Alpha',
            'handle' => 'alpha',
            'width' => 100,
        ]));

        $this->service->reset();

        $names = $this->service->getAllTransforms()->pluck('name')->all();

        expect($names)->toBe(['Alpha', 'Zebra']);
    });
});

describe('getTransformByHandle', function () {
    it('returns null for non-existent handle', function () {
        expect($this->service->getTransformByHandle('nonExistent'))->toBeNull();
    });

    it('finds a transform by handle', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Thumbnail',
            'handle' => 'thumb',
            'width' => 200,
        ]));

        $this->service->reset();

        $result = $this->service->getTransformByHandle('thumb');

        expect($result)->toBeInstanceOf(ImageTransform::class)
            ->and($result->handle)->toBe('thumb')
            ->and($result->name)->toBe('Thumbnail');
    });
});

describe('getTransformById', function () {
    it('returns null for non-existent id', function () {
        expect($this->service->getTransformById(999))->toBeNull();
    });

    it('finds a transform by id', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Thumbnail',
            'handle' => 'thumb',
            'width' => 200,
        ]));

        $this->service->reset();

        $transform = $this->service->getTransformByHandle('thumb');

        expect($this->service->getTransformById($transform->id))
            ->toBeInstanceOf(ImageTransform::class)
            ->handle->toBe('thumb');
    });
});

describe('getTransformByUid', function () {
    it('returns null for non-existent uid', function () {
        expect($this->service->getTransformByUid('non-existent-uid'))->toBeNull();
    });

    it('finds a transform by uid', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Thumbnail',
            'handle' => 'thumb',
            'width' => 200,
        ]));

        $this->service->reset();

        $transform = $this->service->getTransformByHandle('thumb');

        expect($this->service->getTransformByUid($transform->uid))
            ->toBeInstanceOf(ImageTransform::class)
            ->handle->toBe('thumb');
    });
});

describe('saveTransform', function () {
    it('saves a new transform', function () {
        Event::fake([TransformSaving::class, TransformSaved::class]);
        Event::listen(TransformSaving::class, fn () => null);
        Event::listen(TransformSaved::class, fn () => null);

        expect(ImageTransformModel::count())->toBe(0);

        $transform = new ImageTransform([
            'name' => 'Test Transform',
            'handle' => 'testTransform',
            'width' => 500,
            'height' => 400,
            'mode' => 'crop',
        ]);

        $result = $this->service->saveTransform($transform);

        expect($result)->toBeTrue()
            ->and(ImageTransformModel::count())->toBe(1);

        tap(ImageTransformModel::firstOrFail(), function ($model) {
            expect($model->name)->toBe('Test Transform')
                ->and($model->handle)->toBe('testTransform')
                ->and($model->width)->toBe(500)
                ->and($model->height)->toBe(400)
                ->and($model->mode)->toBe('crop');
        });

        Event::assertDispatchedOnce(TransformSaving::class);
        Event::assertDispatchedOnce(TransformSaved::class);
    });

    it('persists transformer and extra settings', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Blurred',
            'handle' => 'blurred',
            'width' => 500,
            'transformer' => 'custom',
            'settings' => ['blur' => 12],
        ]));

        $this->service->reset();
        $transform = $this->service->getTransformByHandle('blurred');

        expect($transform->getTransformer())->toBe('custom')
            ->and($transform->settings)->toBe(['blur' => 12]);
    });

    it('hydrates JSON encoded settings from stored transforms', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Blurred',
            'handle' => 'blurred',
            'width' => 500,
            'transformer' => 'custom',
            'settings' => ['blur' => 12],
        ]));

        DB::table(Table::IMAGETRANSFORMS)
            ->where('handle', 'blurred')
            ->update(['settings' => json_encode(['blur' => 12])]);

        $this->service->reset();

        expect($this->service->getTransformByHandle('blurred')->settings)
            ->toBe(['blur' => 12]);
    });

    it('assigns id to the transform after saving', function () {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'width' => 100,
        ]);

        expect($transform->id)->toBeNull();

        $this->service->saveTransform($transform);

        expect($transform->id)->not->toBeNull()
            ->and($transform->id)->toBeInt();
    });

    it('assigns uid to the transform after saving', function () {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'width' => 100,
        ]);

        expect($transform->uid)->toBeNull();

        $this->service->saveTransform($transform);

        expect($transform->uid)->not->toBeNull()
            ->and($transform->uid)->toBeString();
    });

    it('can update an existing transform', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Original',
            'handle' => 'original',
            'width' => 100,
        ]));

        $this->service->reset();
        $transform = $this->service->getTransformByHandle('original');
        $transform->name = 'Updated';
        $transform->width = 200;

        $this->service->saveTransform($transform);
        $this->service->reset();

        $updated = $this->service->getTransformByHandle('original');

        expect($updated->name)->toBe('Updated')
            ->and($updated->width)->toBe(200);
    });

    it('returns false when validation fails', function () {
        $result = $this->service->saveTransform(new ImageTransform([
            'name' => '',
            'handle' => '',
        ]));

        expect($result)->toBeFalse()
            ->and(ImageTransformModel::count())->toBe(0);
    });

    it('skips validation when runValidation is false', function () {
        $result = $this->service->saveTransform(new ImageTransform([
            'name' => 'No Validation',
            'handle' => 'noValidation',
            'width' => 0,
        ]), runValidation: false);

        expect($result)->toBeTrue()
            ->and(ImageTransformModel::count())->toBe(1);
    });

    it('fires TransformSaving with isNew true for new transforms', function () {
        Event::fake([TransformSaving::class, TransformSaved::class]);
        Event::listen(TransformSaving::class, fn () => null);
        Event::listen(TransformSaved::class, fn () => null);

        $this->service->saveTransform(new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
        ]));

        Event::assertDispatchedOnce(TransformSaving::class);
    });

    it('fires TransformSaved with isNew true for new transforms', function () {
        Event::fake([TransformSaving::class, TransformSaved::class]);
        Event::listen(TransformSaving::class, fn () => null);
        Event::listen(TransformSaved::class, fn () => null);

        $this->service->saveTransform(new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
        ]));

        Event::assertDispatchedOnce(TransformSaved::class);
    });

    it('fires TransformSaved with isNew false for existing transforms', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
        ]));

        $this->service->reset();

        Event::fake([TransformSaving::class, TransformSaved::class]);
        Event::listen(TransformSaving::class, fn () => null);
        Event::listen(TransformSaved::class, fn () => null);

        $transform = $this->service->getTransformByHandle('test');
        $transform->name = 'Updated';

        $this->service->saveTransform($transform);

        Event::assertDispatchedOnce(TransformSaved::class);
    });
});

describe('deleteTransform', function () {
    it('deletes a transform', function () {
        Event::fake([TransformDeleting::class, TransformDeletionApplying::class, TransformDeleted::class]);
        Event::listen(TransformDeleting::class, fn () => null);
        Event::listen(TransformDeletionApplying::class, fn () => null);
        Event::listen(TransformDeleted::class, fn () => null);

        $this->service->saveTransform(new ImageTransform([
            'name' => 'Delete Me',
            'handle' => 'deleteMe',
            'width' => 100,
        ]));

        $this->service->reset();

        expect(ImageTransformModel::count())->toBe(1);

        $transform = $this->service->getTransformByHandle('deleteMe');
        ProjectConfig::rebuild();

        $result = $this->service->deleteTransform($transform);

        expect($result)->toBeTrue()
            ->and(ImageTransformModel::count())->toBe(0);

        Event::assertDispatchedOnce(TransformDeleting::class);
        Event::assertDispatchedOnce(TransformDeletionApplying::class);
        Event::assertDispatchedOnce(TransformDeleted::class);
    });

    it('deletes a transform by id', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Delete Me',
            'handle' => 'deleteMe',
            'width' => 100,
        ]));

        $this->service->reset();

        $transform = $this->service->getTransformByHandle('deleteMe');
        ProjectConfig::rebuild();

        expect($this->service->deleteTransformById($transform->id))->toBeTrue()
            ->and(ImageTransformModel::count())->toBe(0);
    });

    it('returns false when deleting non-existent id', function () {
        expect($this->service->deleteTransformById(999))->toBeFalse();
    });
});

describe('asset transformers', function () {
    it('includes the craft transformer', function () {
        expect($this->assetTransforms->getAllAssetTransformers())
            ->toHaveKey('craft', ImageTransformer::class);
    });

    it('allows adding custom transformers by handle', function () {
        $customTransformer = (new class implements AssetTransformerInterface
        {
            public static function handle(): string
            {
                return 'custom';
            }

            public static function displayName(): string
            {
                return 'Custom';
            }

            public static function gqlArguments(): array
            {
                return [];
            }

            public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
            {
                return 'https://example.test/custom';
            }

            public function invalidateAssetTransforms(Asset $asset): void {}

            public function getTransformString(ImageTransform $imageTransform, bool $ignoreHandle = false): string
            {
                return 'custom';
            }

            public function getImageTransformSettingsHtml(ImageTransform $imageTransform, bool $readOnly = false): ?string
            {
                return null;
            }

            public function getFilesystemSettingsHtml(FsInterface $filesystem, bool $readOnly = false): ?string
            {
                return null;
            }
        })::class;

        Event::listen(AssetTransformersResolving::class, function (AssetTransformersResolving $event) use ($customTransformer) {
            $event->types['custom'] = $customTransformer;
        });

        expect($this->assetTransforms->getAllAssetTransformers())->toHaveKey('custom', $customTransformer)
            ->and($this->assetTransforms->getAssetTransformer('custom'))->toBeInstanceOf(AssetTransformerInterface::class);
    });

    it('allows adding custom transformer instances by handle', function () {
        $customTransformer = new class implements AssetTransformerInterface
        {
            public static function handle(): string
            {
                return 'custom';
            }

            public static function displayName(): string
            {
                return 'Custom';
            }

            public static function gqlArguments(): array
            {
                return [];
            }

            public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
            {
                return 'https://example.test/custom';
            }

            public function invalidateAssetTransforms(Asset $asset): void {}

            public function getTransformString(ImageTransform $imageTransform, bool $ignoreHandle = false): string
            {
                return 'custom';
            }

            public function getImageTransformSettingsHtml(ImageTransform $imageTransform, bool $readOnly = false): ?string
            {
                return null;
            }

            public function getFilesystemSettingsHtml(FsInterface $filesystem, bool $readOnly = false): ?string
            {
                return null;
            }
        };

        Event::listen(AssetTransformersResolving::class, function (AssetTransformersResolving $event) use ($customTransformer) {
            $event->types['custom'] = $customTransformer;
        });

        expect($this->assetTransforms->getAssetTransformer('custom'))->toBe($customTransformer);
    });

    it('adds transformer GraphQL arguments', function () {
        $customTransformer = (new class implements AssetTransformerInterface
        {
            public static function handle(): string
            {
                return 'custom';
            }

            public static function displayName(): string
            {
                return 'Custom';
            }

            public static function gqlArguments(): array
            {
                return [
                    'blur' => [
                        'type' => Type::int(),
                    ],
                ];
            }

            public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
            {
                return '';
            }

            public function invalidateAssetTransforms(Asset $asset): void {}

            public function getTransformString(ImageTransform $imageTransform, bool $ignoreHandle = false): string
            {
                return '';
            }

            public function getImageTransformSettingsHtml(ImageTransform $imageTransform, bool $readOnly = false): ?string
            {
                return null;
            }

            public function getFilesystemSettingsHtml(FsInterface $filesystem, bool $readOnly = false): ?string
            {
                return null;
            }
        })::class;

        Event::listen(AssetTransformersResolving::class, function (AssetTransformersResolving $event) use ($customTransformer) {
            $event->types['custom'] = $customTransformer;
        });

        expect(TransformArguments::getArguments())->toHaveKey('blur');
    });

    it('rejects transformer GraphQL argument collisions', function () {
        $customTransformer = (new class implements AssetTransformerInterface
        {
            public static function handle(): string
            {
                return 'custom';
            }

            public static function displayName(): string
            {
                return 'Custom';
            }

            public static function gqlArguments(): array
            {
                return [
                    'width' => [
                        'type' => Type::int(),
                    ],
                ];
            }

            public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
            {
                return '';
            }

            public function invalidateAssetTransforms(Asset $asset): void {}

            public function getTransformString(ImageTransform $imageTransform, bool $ignoreHandle = false): string
            {
                return '';
            }

            public function getImageTransformSettingsHtml(ImageTransform $imageTransform, bool $readOnly = false): ?string
            {
                return null;
            }

            public function getFilesystemSettingsHtml(FsInterface $filesystem, bool $readOnly = false): ?string
            {
                return null;
            }
        })::class;

        Event::listen(AssetTransformersResolving::class, function (AssetTransformersResolving $event) use ($customTransformer) {
            $event->types['custom'] = $customTransformer;
        });

        TransformArguments::getArguments();
    })->throws(ImageTransformException::class);
});

describe('reset', function () {
    it('clears the memoized transforms', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'width' => 100,
        ]));

        expect($this->service->getAllTransforms())->toHaveCount(1);

        ImageTransformModel::query()->delete();
        $this->service->reset();

        expect($this->service->getAllTransforms())->toBeEmpty();
    });
});
