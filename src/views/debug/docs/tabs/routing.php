<h3>Controller + Action</h3>

<p>This request was routed to the <code><?= $panel->data['action'] ?></code> method of <code><?= $panel->data['controller'] ?></code>.</p>

<a href="<?= Craft::$app->getDocs()->classReferenceUrl($panel->data['controller'], $panel->data['action'], 'method') ?>" target="_blank">
    <code><?= $panel->data['controller'] ?>::<?= $panel->data['action'] ?>()</code>
</a> &rarr;

<div class="mt-4">
    <a class="btn btn-secondary" href="<?= Craft::$app->getModule('debug')->panels['router']->getUrl() ?>">Route info &rarr;</a>
    <a class="btn btn-secondary" href="<?= Craft::$app->getModule('debug')->panels['request']->getUrl() ?>">Request info &rarr;</a>
</div>
