@use(CraftCms\Cms\Support\Url)
@use(function CraftCms\Cms\t)

@if($logs)
    <form action="{{ Url::actionUrl('utilities/delete-all-deprecation-errors') }}" method="post">
        @csrf
        <craft-button type="submit">{{ t('Clear All') }}</craft-button>
    </form>
@endif
