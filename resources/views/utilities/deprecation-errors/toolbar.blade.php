@use(craft\helpers\UrlHelper)
@use(function CraftCms\Cms\t)

@if($logs)
    <form action="{{ UrlHelper::actionUrl('utilities/delete-all-deprecation-errors') }}" method="post">
        @csrf
        <craft-button type="submit">{{ t('Clear All') }}</craft-button>
    </form>
@endif
