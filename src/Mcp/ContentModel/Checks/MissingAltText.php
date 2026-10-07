<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ContentModel\Checks;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Mcp\ContentModel\Check;

/** @since 6.0.0 */
class MissingAltText implements Check
{
    public static function id(): string
    {
        return 'missing-alt-text';
    }

    public function run(int $limit): array
    {
        $count = $this->query()->count();
        /** @var list<Asset> $assets */
        $assets = $this->query()->limit($limit)->all();

        return [
            'count' => $count,
            'findings' => array_map($this->assetFinding(...), $assets),
        ];
    }

    private function query(): AssetQuery
    {
        return Asset::find()
            ->kind('image')
            ->site('*')
            ->unique()
            ->status(null)
            ->hasAlt(false);
    }

    /** @return array{id: int|null, uid: string|null, filename: string, title: string, volume: string} */
    private function assetFinding(Asset $asset): array
    {
        return [
            'id' => $asset->id,
            'uid' => $asset->uid,
            'filename' => $asset->filename,
            'title' => (string) $asset->title,
            'volume' => (string) $asset->getVolume()->handle,
        ];
    }
}
