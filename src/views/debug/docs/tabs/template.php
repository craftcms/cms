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
                    <?= Craft::$app->getDocs()->classReferenceLink($type) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
