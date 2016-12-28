<?php

use craft\models\DeprecationError;

/** @var $panel craft\debug\DeprecatedPanel */
?>
    <h1>Deprecation Errors</h1>

<?php

array_walk($panel->data, function(&$log) {
    $log = new DeprecationError($log);
});

echo $this->render('logtable', [
    'panel' => $panel,
    'logs' => $panel->data
]);

?>
