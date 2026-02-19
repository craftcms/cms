<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Twig\Nodes;

use Twig\Attribute\YieldReady;
use Twig\Node\Node;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.15.0
 */
#[YieldReady]
class BaseNode extends Node {}
