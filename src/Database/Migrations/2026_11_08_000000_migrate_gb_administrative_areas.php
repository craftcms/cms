<?php

declare(strict_types=1);

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // GB counties come from commerceguys/addressing now, which stores them by their ISO codes rather than the
        // English names Craft stored before. Use the unmodified upstream list in English to match the old names.
        $codesByName = array_flip(new SubdivisionRepository()->getList(['GB'], 'en'));
        $codesByName += [
            'Bristol, City of' => $codesByName['City of Bristol'],
            'Edinburgh, City of' => $codesByName['City of Edinburgh'],
            'London, City of' => $codesByName['City of London'],
            'Durham, County' => $codesByName['County Durham'],
            'Vale of Glamorgan, The' => $codesByName['The Vale of Glamorgan'],
        ];

        DB::table(Table::ADDRESSES)
            ->select(['id', 'administrativeArea'])
            ->where('countryCode', 'GB')
            ->whereNotNull('administrativeArea')
            ->orderBy('id')
            ->each(function (object $row) use ($codesByName): void {
                $code = $codesByName[$row->administrativeArea] ?? null;

                if ($code !== null && $code !== $row->administrativeArea) {
                    DB::table(Table::ADDRESSES)->where('id', $row->id)->update(['administrativeArea' => $code]);
                }
            });
    }

    public function down(): void {}
};
