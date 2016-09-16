<?php

use craft\app\models\DeprecationError;

/** @var $log DeprecationError */
/** @var $panel craft\app\debug\DeprecatedPanel */
?>
    <h1><?= $log->key ?></h1>

<?php

echo $this->render('../table', [
    'caption' => 'Error Info',
    'values' => [
        ['Message', $log->message],
        ['Origin', $log->getOrigin()],
        [
            'Last Occurance',
            Craft::$app->getFormatter()->asDatetime($log->lastOccurrence, 'short')
        ],
    ]
]);


$values = [];
$totalTraces = count($log->traces);

foreach ($log->traces as $i => $trace) {
    if ($i === 0) {
        $info = '<strong>Deprecation error:</strong> '.htmlentities($log->message, null, 'UTF-8');
    } else if (!empty($trace['template'])) {
        $info = '<strong>Template:</strong> '.htmlentities($trace['template'], null, 'UTF-8');
    } else {
        $info = (!empty($trace['objectClass']) || !empty($trace['class']) ? str_replace('\\', '\<wbr>', ($trace['objectClass'] ?: $trace['class'])).'::<wbr>' : '').$trace['method'].'('.htmlentities($trace['args'], null, 'UTF-8').')';
    }

    if (!empty($trace['file'])) {
        $info .= '<br><strong>From:</strong> '.str_replace('/', '/<wbr>', $trace['file']).' ('.$trace['line'].')';
    }

    $values[] = [($totalTraces - $i), $info];
}

echo $this->render('../table', [
    'columnStyles' => ['width: 5%; text-align: center;', ''],
    'caption' => 'Stack Trace',
    'values' => $values
]);
