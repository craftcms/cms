<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

/**
 * Craft dispatches this alongside {@see TransformGenerated} when something listens to it.
 *
 * @since 6.0.0
 * @deprecated in 6.0.0. {@see TransformGenerated} should be used instead.
 */
readonly class AfterGenerateTransform extends TransformGenerated {}
