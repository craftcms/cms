<?php

use CraftCms\Cms\Deprecator\Models\DeprecationError;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use yii\helpers\Markdown;
use function CraftCms\Cms\t;

/** @var DeprecationError $log */
/** @var craft\debug\DeprecatedPanel $panel */
?>
    <h1><?= $log->key ?></h1>

<?php

echo $this->render('../table', [
    'caption' => 'Error Info',
    'values' => [
        [
            t('Message'),
            Markdown::processParagraph(Html::encode($log->message)),
        ],
        [
            t('Origin'),
            '<code>' . str_replace('/', '/<wbr>', Html::encode($log->file)) . ($log->line ? ':' . $log->line : '') . '</code>',
        ],
        [
            t('Last Occurrence'),
            I18N::getFormatter()->asDatetime($log->lastOccurrence, 'short'),
        ],
    ],
]);


$values = [];
$totalTraces = count($log->traces);

foreach ($log->traces as $i => $trace) {
    if ($i === 0) {
        $info = '<strong>Deprecation error:</strong> ' . Html::encode($log->message);
    } else {
        $info = '<code>' . ($trace['objectClass'] || $trace['class'] ? str_replace('\\', '\\<wbr>', Html::encode($trace['objectClass'] ?: $trace['class'])) . '::<wbr>' : '') . Html::encode($trace['method'] . '(' . $trace['args'] . ')') . '</code>';
    }

    if (!empty($trace['file'])) {
        $info .= '<br><strong>From:</strong> ' . str_replace('/', '/<wbr>', Html::encode($trace['file'])) . ' (' . $trace['line'] . ')';
    }

    $values[] = [$totalTraces - $i, $info];
}

echo $this->render('../table', [
    'columnStyles' => ['width: 5%; text-align: center;', ''],
    'caption' => 'Stack Trace',
    'values' => $values,
]);
