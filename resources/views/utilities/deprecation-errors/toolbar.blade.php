@use(CraftCms\Cms\Support\URL)
@use(function CraftCms\Cms\t)

@if($logs)
    <form action="{{ URL::actionUrl('utilities/delete-all-deprecation-errors') }}" method="post">
        @csrf
        <craft-button type="submit">{{ t('Clear All') }}</craft-button>
    </form>
@endif
