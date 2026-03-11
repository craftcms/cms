<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Image\Contracts\ImageTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Events\ApplyingTransformDelete;
use CraftCms\Cms\Image\Events\DeletingTransform;
use CraftCms\Cms\Image\Events\RegisterImageTransformers;
use CraftCms\Cms\Image\Events\SavingTransform;
use CraftCms\Cms\Image\Events\TransformDeleted;
use CraftCms\Cms\Image\Events\TransformSaved;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Image\Models\ImageTransform as ImageTransformModel;
use CraftCms\Cms\Support\Facades\ImageTransforms as ImageTransformsFacade;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(ImageTransforms::class);
});

it('is a singleton', function () {
    expect(ImageTransformsFacade::getFacadeRoot())->toBe(app(ImageTransforms::class));
    expect($this->service)->toBe(app(ImageTransforms::class));
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
        Event::fake([SavingTransform::class, TransformSaved::class]);
        Event::listen(SavingTransform::class, fn () => null);
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

        Event::assertDispatchedOnce(SavingTransform::class);
        Event::assertDispatchedOnce(TransformSaved::class);
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

    it('fires SavingTransform with isNew true for new transforms', function () {
        Event::fake([SavingTransform::class, TransformSaved::class]);
        Event::listen(SavingTransform::class, fn () => null);
        Event::listen(TransformSaved::class, fn () => null);

        $this->service->saveTransform(new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
        ]));

        Event::assertDispatchedOnce(SavingTransform::class, fn (SavingTransform $event) => $event->isNew === true);
    });

    it('fires TransformSaved with isNew true for new transforms', function () {
        Event::fake([SavingTransform::class, TransformSaved::class]);
        Event::listen(SavingTransform::class, fn () => null);
        Event::listen(TransformSaved::class, fn () => null);

        $this->service->saveTransform(new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
        ]));

        Event::assertDispatchedOnce(TransformSaved::class, fn (TransformSaved $event) => $event->isNew === true);
    });

    it('fires TransformSaved with isNew false for existing transforms', function () {
        $this->service->saveTransform(new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
        ]));

        $this->service->reset();

        Event::fake([SavingTransform::class, TransformSaved::class]);
        Event::listen(SavingTransform::class, fn () => null);
        Event::listen(TransformSaved::class, fn () => null);

        $transform = $this->service->getTransformByHandle('test');
        $transform->name = 'Updated';

        $this->service->saveTransform($transform);

        Event::assertDispatchedOnce(TransformSaved::class, fn (TransformSaved $event) => $event->isNew === false);
    });
});

describe('deleteTransform', function () {
    it('deletes a transform', function () {
        Event::fake([DeletingTransform::class, ApplyingTransformDelete::class, TransformDeleted::class]);
        Event::listen(DeletingTransform::class, fn () => null);
        Event::listen(ApplyingTransformDelete::class, fn () => null);
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

        Event::assertDispatchedOnce(DeletingTransform::class);
        Event::assertDispatchedOnce(ApplyingTransformDelete::class);
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

describe('getAllImageTransformers', function () {
    it('includes the default ImageTransformer', function () {
        $transformers = $this->service->getAllImageTransformers();

        expect($transformers)->toContain(ImageTransformer::class);
    });

    it('fires RegisterImageTransformers event', function () {
        Event::fake([RegisterImageTransformers::class]);

        $this->service->getAllImageTransformers();

        Event::assertDispatchedOnce(RegisterImageTransformers::class);
    });

    it('allows adding custom transformers via event', function () {
        $customTransformer = (new class implements ImageTransformerInterface
        {
            public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
            {
                return '';
            }

            public function invalidateAssetTransforms(Asset $asset): void {}
        })::class;

        Event::listen(RegisterImageTransformers::class, function (RegisterImageTransformers $event) use ($customTransformer) {
            $event->types[] = $customTransformer;
        });

        $transformers = $this->service->getAllImageTransformers();

        expect($transformers)->toContain($customTransformer);
    });
});

describe('getImageTransformer', function () {
    it('returns an instance of the transformer', function () {
        $transformer = $this->service->getImageTransformer(ImageTransformer::class);

        expect($transformer)->toBeInstanceOf(ImageTransformerInterface::class);
    });

    it('memoizes transformer instances', function () {
        $first = $this->service->getImageTransformer(ImageTransformer::class);
        $second = $this->service->getImageTransformer(ImageTransformer::class);

        expect($first)->toBe($second);
    });

    it('throws for invalid transformer class', function () {
        $this->service->getImageTransformer(stdClass::class);
    })->throws(ImageTransformException::class, 'Invalid image transformer');
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
