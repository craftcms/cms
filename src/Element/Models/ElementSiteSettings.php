<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BasePivot;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ElementSiteSettings extends BasePivot
{
    protected $table = Table::ELEMENTS_SITES;

    protected $casts = [
        'enabled' => 'bool',
        'content' => 'json',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\CraftCms\Cms\Element\Models\Element, $this>
     */
    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'elementId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\CraftCms\Cms\Site\Models\Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'siteId');
    }
}
