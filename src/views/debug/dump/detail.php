<?php


/** @var craft\debug\DumpPanel $panel */
?>
<h1>Variable Dumps</h1>
<?php

?>

<?php if (empty($panel->data)): ?>
    <p>No variables were dumped on this request.</p>
<?php else: ?>
    <?php foreach ($panel->data as [$file, $line, $dump]): ?>
        <h3><code><?= $file ?>:<?= $line ?></code></h3>
        <?= $dump ?>
    <?php endforeach; ?>
<?php endif; ?>
