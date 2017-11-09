<?php

use craft\models\DeprecationError;

/** @var $log DeprecationError */
/** @var $panel craft\debug\DeprecatedPanel */
?>
    <h1><?= $log->key ?></h1>

<?php

echo $this->render('../table', [
    'caption' => 'Error Info',
    'values' => [
        [
            Craft::t('app', 'Message'),
            $log->message
        ],
        [
            Craft::t('app', 'Origin'),
            '<code>'.str_replace('/', '/<wbr>', htmlentities($log->file, null, 'UTF-8')).($log->line ? ':'.$log->line : '').'</code>'
        ],
        [
            Craft::t('app', 'Last Occurrence'),
            Craft::$app->getFormatter()->asDatetime($log->lastOccurrence, 'short')
        ],
    ]
]);


$values = [];
$totalTraces = count($log->traces);

foreach ($log->traces as $i => $trace) {
    if ($i === 0) {
        $info = '<strong>Deprecation error:</strong> '.htmlentities($log->message, null, 'UTF-8');
    } else {
        $info = '<code>'.($trace['objectClass'] || $trace['class'] ? str_replace('\\', '\\<wbr>', htmlentities($trace['objectClass'] ?: $trace['class'], null, 'UTF-8')).'::<wbr>' : '').htmlentities($trace['method'].'('.$trace['args'].')', null, 'UTF-8').'</code>';
    }

    if (!empty($trace['file'])) {
        $info .= '<br><strong>From:</strong> '.str_replace('/', '/<wbr>', htmlentities($trace['file'], null, 'UTF-8')).' ('.$trace['line'].')';
    }

    $values[] = [$totalTraces - $i, $info];
}

echo $this->render('../table', [
    'columnStyles' => ['width: 5%; text-align: center;', ''],
    'caption' => 'Stack Trace',
    'values' => $values
]);
