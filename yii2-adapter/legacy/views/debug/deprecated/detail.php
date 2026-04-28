<?php

use CraftCms\Cms\Deprecator\Models\DeprecationError;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use yii\helpers\Markdown;
use function CraftCms\Cms\t;

/** @var craft\debug\DeprecatedPanel $panel */
?>
<h1>Deprecation Warnings</h1>
<?php

array_walk($panel->data, function(&$log) {
    $log = new DeprecationError($log);
});

/** @var DeprecationError[] $logs */
$logs = $panel->data;

?>

<?php if (empty($logs)): ?>
    <p>No deprecation warnings were logged on this request.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-condensed table-bordered table-striped table-hover"
               style="table-layout: fixed;">
            <thead>
            <tr>
                <th style="nowrap"><?= t('Message') ?></th>
                <th><?= t('Origin') ?></th>
                <th><?= t('Stack Trace') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= Markdown::processParagraph(Html::encode($log->message)) ?></td>
                    <td><code><?= str_replace('/', '/<wbr>', Html::encode($log->file)) . ($log->line ? ':' . $log->line : '') ?></code>
                    </td>
                    <td><?php if ($log->id): ?><a
                            href="<?= $panel->getUrl() . '&trace=' . $log->id ?>"><?= t('Stack Trace') ?></a><?php else: ?><?= t('See logs') ?><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<p><a href="<?= Url::cpUrl('utilities/deprecation-errors') ?>"
      target="_parent">View all deprecation warnings</a></p>
