<?php

use yii\helpers\Html;

?>

<h3>Page Variables</h3>

<p>These variables were passed to the primary page template, <code><?= $data['pageTemplate'] ?></code>.</p>

<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Variable Name</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data['pageVariables'] as $key => $type): ?>
            <tr>
                <td><code><?= $key ?></code></td>
                <td class="ws-normal">
                    <?php if (strpos($type, 'craft') === 0): ?>
                        <?= Html::a(Html::tag('code', $type), Craft::$app->getDocs()->classReferenceUrl($type), [
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                        ]) ?>
                    <?php else: ?>
                        <code><?= $type ?></code>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
