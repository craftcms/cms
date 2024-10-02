<?php

use craft\helpers\Html;
?>
<h3>Search Documentation</h3>

<?= Html::beginForm($panel->getUrl(), 'get', ['class' => 'form-inline']) ?>
    <div class="form-group mr-2">
        <input
            type="text"
            name="search"
            value="<?= Html::encode($data['search'] ?? null) ?>"
            class="form-control form-control-sm"
            style="font-size: 16px;
  padding: 4px;">
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">Search</button>
    </div>
<?= Html::endForm() ?>

<?php if (isset($data['search'])): ?>
    <?php if (count($data['searchResults']) > 0): ?>
        <div class="callout callout-info my-4">
            Found <?= count($data['searchResults']) ?> result(s).
        </div>

        <ul>
            <?php foreach ($data['searchResults'] as $result): ?>
                <li>
                    <strong><?= Html::a($result['title'], $result['url']) ?></strong>
                    <small class="text-muted ml-2"><?= $result['type'] ?></small>
                    <p style="max-width: 45em"><?= $result['summary'] ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="callout callout-warning my-4">
            Sorry, there were no results for “<?= Html::encode($data['search']) ?>”
        </div>
    <?php endif; ?>
<?php endif; ?>
