<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\Contracts\ShouldBeRetained;

/**
 * @since 6.0.0
 */
class CommentCreated extends CommentEvent implements ShouldBeRetained {}
