<?php

it('can apply params to a builder', function () {
    \CraftCms\Cms\Support\Query::whereParam(
        \Illuminate\Support\Facades\DB::query(),
        'id',
        'and 1, 2, 3',
    );
});
