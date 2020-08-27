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
            \yii\helpers\Markdown::processParagraph(\craft\helpers\Html::encode($log->message))
        ],
        [
            Craft::t('app', 'Origin'),
            '<code>' . str_replace('/', '/<wbr>', \craft\helpers\Html::encode($log->file)) . ($log->line ? ':' . $log->line : '') . '</code>'
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
        $info = '<strong>Deprecation error:</strong> ' . \craft\helpers\Html::encode($log->message);
    } else {
        $info = '<code>' . ($trace['objectClass'] || $trace['class'] ? str_replace('\\', '\\<wbr>', \craft\helpers\Html::encode($trace['objectClass'] ?: $trace['class'])) . '::<wbr>' : '') . \craft\helpers\Html::encode($trace['method'] . '(' . $trace['args'] . ')') . '</code>';
    }

    if (!empty($trace['file'])) {
        $info .= '<br><strong>From:</strong> ' . str_replace('/', '/<wbr>', \craft\helpers\Html::encode($trace['file'])) . ' (' . $trace['line'] . ')';
    }

    $values[] = [$totalTraces - $i, $info];
}

echo $this->render('../table', [
    'columnStyles' => ['width: 5%; text-align: center;', ''],
    'caption' => 'Stack Trace',
    'values' => $values
]);
