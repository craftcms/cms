<?php

declare(strict_types=1);

/**
 * Core classes `use` empty `Legacy*` traits from `craftcms/cms`. Aliasing the adapter's
 * traits to those names before any core class loads gives the core classes their legacy
 * constants and methods. This file is loaded through Composer's `autoload.files` because
 * the service provider runs too late.
 */
class_alias(CraftCms\Yii2Adapter\Address\Concerns\LegacyConstants::class, CraftCms\Cms\Address\Concerns\LegacyConstants::class);
class_alias(CraftCms\Yii2Adapter\Asset\Concerns\LegacyConstants::class, CraftCms\Cms\Asset\Concerns\LegacyConstants::class);
class_alias(CraftCms\Yii2Adapter\Condition\Concerns\LegacyConstants::class, CraftCms\Cms\Condition\Concerns\LegacyConstants::class);
class_alias(CraftCms\Yii2Adapter\Element\Concerns\LegacyConstants::class, CraftCms\Cms\Element\Concerns\LegacyConstants::class);
class_alias(CraftCms\Yii2Adapter\Element\Concerns\LegacyNestedElementManager::class, CraftCms\Cms\Element\Concerns\LegacyNestedElementManager::class);
class_alias(CraftCms\Yii2Adapter\Entry\Concerns\LegacyConstants::class, CraftCms\Cms\Entry\Concerns\LegacyConstants::class);
class_alias(CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldConstants::class, CraftCms\Cms\Field\Concerns\LegacyFieldConstants::class);
class_alias(CraftCms\Yii2Adapter\Field\Elements\Concerns\LegacyConstants::class, CraftCms\Cms\Field\Elements\Concerns\LegacyConstants::class);
class_alias(CraftCms\Yii2Adapter\FieldLayout\Concerns\LegacyConstants::class, CraftCms\Cms\FieldLayout\Concerns\LegacyConstants::class);
class_alias(CraftCms\Yii2Adapter\Shared\Concerns\LegacyEventConstants::class, CraftCms\Cms\Shared\Concerns\LegacyEventConstants::class);
class_alias(CraftCms\Yii2Adapter\User\Concerns\LegacyConstants::class, CraftCms\Cms\User\Concerns\LegacyConstants::class);
