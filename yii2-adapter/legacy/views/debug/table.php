<?php

use yii\helpers\Html;

/** @var string $caption */
/** @var string[] $headings */
/** @var array $values */
/** @var array $columnStyles */
?>

<?php if (!empty($caption)): ?>
    <h3><?= $caption ?></h3>
<?php endif; ?>

<?php if (empty($values)): ?>
    <p>Empty.</p>
<?php else: ?>
    <div class="table-responsive">
        <table
                class="table table-condensed table-bordered table-striped table-hover"
                style="table-layout: fixed;">
            <?php if (!empty($headings)): ?>
                <thead>
                <tr>
                    <?php foreach ($headings as $i => $heading): ?>
                        <th<?php if ($i === 0): ?> style="nowrap"<?php endif; ?>><?= Html::encode($heading) ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
            <?php endif; ?>
            <tbody>
            <?php foreach ($values as $row): ?>
                <tr>
                    <?php $first = true; ?>
                    <?php foreach ($row as $i => $value): ?>
                        <?php if ($first): ?>
                            <th style="white-space: normal;<?= (!empty($columnStyles[$i]) ? $columnStyles[$i] : '') ?>"><?= $value ?></th>
                            <?php $first = false; ?>
                        <?php else: ?>
                            <td style="overflow:auto;<?= (!empty($columnStyles[$i]) ? $columnStyles[$i] : '') ?>"><?= $value ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
