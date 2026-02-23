<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BasePivot;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ElementSiteSettings extends BasePivot
{
    #[\Override]
    protected $table = Table::ELEMENTS_SITES;

    #[\Override]
    protected $casts = [
        'enabled' => 'bool',
        'content' => 'json',
    ];

    /**
     * @return BelongsTo<Element, $this>
     */
    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'elementId');
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'siteId');
    }
}
