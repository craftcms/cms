<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Event used to register or modify a list of migration tracks.
 *
 * @author Michael Rog <michael@michaelrog.com>
 * @since 3.7.38
 */
class MigrationTracksEvent extends Event
{
	/**
	 * @var string[] The list of migration tracks
	 */
	public array $tracks = [];
}
