<?php


/** @var craft\debug\DumpPanel $panel */
?>
<h1>Variable Dumps</h1>
<?php

?>

<?php if (empty($panel->data)): ?>
    <p>No variables were dumped on this request.</p>
<?php else: ?>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th style="width: 200px">Template + Line</th>
                <th>Content</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($panel->data as $i => [$file, $line, $dump]): ?>
                <tr>
                    <td><code><?= $file ?>:<?= $line ?></code></td>
                    <td><?= $dump ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
