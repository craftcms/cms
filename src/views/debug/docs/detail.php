<?php
/** @var craft\debug\DocsPanel $panel */

use yii\helpers\Html;

$tabs = [];

if (count($panel->data['allElementTypes'])) {
    $tabs['elements'] = 'Elements';
}

if ($panel->data['pageTemplate']) {
    $tabs['template'] = 'Template';
}

$tabs['search'] = 'Search';
?>

<h1>Documentation + Resources</h1>

<div class="mb-4">
    <a class="btn btn-primary" href="<?= Craft::$app->getDocs()->docsUrl() ?>" target="_blank" rel="noopener noreferrer">Documentation &nearr;</a>
    <a class="btn btn-secondary" href="<?= Craft::$app->getDocs()->kbUrl() ?>" target="_blank" rel="noopener noreferrer">Knowledge Base &nearr;</a>
    <a class="btn btn-secondary" href="<?= Craft::$app->getDocs()->classReferenceUrl() ?>" target="_blank" rel="noopener noreferrer">Class Reference &nearr;</a>
</div>

<?php $defaultTab = array_keys($tabs)[0]; ?>

<ul class="nav nav-tabs">
    <?php foreach ($tabs as $id => $label): ?>
        <li class="nav-item">
            <?= Html::a($label, "#craft-debug-docs-$id", [
                'class' => $id === $defaultTab ? 'nav-link active' : 'nav-link',
                'data-toggle' => 'tab',
                'role' => 'tab',
                'aria-controls' => "craft-debug-docs-$id",
                'aria-selected' => $id = $defaultTab ? 'true' : 'false',
            ]) ?>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">
    <?php foreach ($tabs as $id => $label): ?>
        <?= Html::tag('div', $this->render("@app/views/debug/docs/tabs/$id", [
            'panel' => $panel,
            'data' => $panel->data,
        ]), [
            'class' => $id === $defaultTab ? 'tab-pane fade active show' : 'tab-pane fade',
            'id' => "craft-debug-docs-$id",
        ]) ?>
    <?php endforeach; ?>
</div>
