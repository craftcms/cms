<?php

namespace craft\events;

use yii\base\Event;

class AssetVolumeEventIndexing extends Event
{
	public array $assetVolumes = [];
}
