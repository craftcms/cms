<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Rules;

use Closure;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use Exception;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Function\String\Lower;
use Tpetry\QueryExpressions\Language\Alias;

use function CraftCms\Cms\t;

final class SingleSectionUriRule implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sectionId = $this->data['sectionId'];
        $section = Sections::getSectionById($sectionId);

        // Make sure no other elements are using this URI already
        $query = DB::table(Table::ELEMENTS_SITES, 'elements_sites')
            ->join(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', '=', 'elements_sites.elementId')
            ->where('elements_sites.siteId', $this->data['siteId'])
            ->whereNull(['elements.draftId', 'elements.revisionId', 'elements.dateDeleted'])
            ->where(new Lower('elements_sites.uri'), mb_strtolower((string) $this->data['uriFormat']))
            ->when(
                $section->id,
                fn (Builder $query) => $query->join(new Alias(Table::ENTRIES, 'entries'), 'entries.id', '=', 'elements.id')
                    ->whereNot('entries.sectionId', $section->id),
            );

        if (! $query->exists()) {
            return;
        }

        $site = Sites::getSiteById($this->data['siteId']);

        if (! $site) {
            throw new Exception('Invalid site ID: '.$this->data['siteId']);
        }

        if ($this->data['uriFormat'] === '__home__') {
            $message = '{site} already has a homepage.';
        } else {
            $message = '{site} already has an element with the URI “{value}”.';
        }

        $fail(t($message, [
            'site' => t($site->getName(), category: 'site'),
        ]));
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
