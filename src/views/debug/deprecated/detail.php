<?php

use craft\models\DeprecationError;
use craft\helpers\Url;

/** @var $panel craft\debug\DeprecatedPanel */
?>
    <h1>Deprecation Errors</h1>

    <p><a href="<?= Url::getCpUrl('utilities/deprecation-errors') ?>" target="_parent">See All Deprecation Errors</a></p>

<?php

array_walk($panel->data, function(&$log) {
    $log = new DeprecationError($log);
});

echo $this->render('logtable', [
    'panel' => $panel,
    'logs' => $panel->data
]);

?>
