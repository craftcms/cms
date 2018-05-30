<?php

use craft\helpers\UrlHelper;
use craft\models\DeprecationError;

/** @var $panel craft\debug\DeprecatedPanel */
?>
<h1>Deprecation Errors</h1>
<?php

array_walk($panel->data, function(&$log) {
    $log = new DeprecationError($log);
});

/** @var DeprecationError[] $logs */
$logs = $panel->data;

?>

<?php if (empty($logs)): ?>
    <p>No deprecation errors were logged on this request.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-condensed table-bordered table-striped table-hover"
               style="table-layout: fixed;">
            <thead>
            <tr>
                <th style="nowrap"><?= Craft::t('app', 'Message') ?></th>
                <th><?= Craft::t('app', 'Origin') ?></th>
                <th><?= Craft::t('app', 'Stack Trace') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlentities($log->message, null, 'UTF-8') ?></td>
                    <td><?= str_replace('/', '/<wbr>', htmlentities($log->file, null, 'UTF-8')).($log->line ? ':'.$log->line : '') ?></td>
                    <td><a href="<?= $panel->getUrl().'&trace='.$log->id ?>"><?= Craft::t('app', 'Stack Trace') ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<p><a href="<?= UrlHelper::cpUrl('utilities/deprecation-errors') ?>"
      target="_parent">View all deprecation errors</a></p>
