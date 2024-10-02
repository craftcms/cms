<?php

use craft\helpers\Html;
use craft\helpers\StringHelper;

 if ($data['allElementTypes']): ?>
    <h3>Element Types</h3>
    <p>
        <?= Craft::t('app', 'A total of <strong>{count,spellout}</strong> {count,plural,=1{element type was} other{element types were}} used by this request.', ['count' => count($data['allElementTypes'])]) ?>
    </p>
    <ul>
        <?php foreach ($data['allElementTypes'] as $elementType): ?>
            <li><?= $docs->classReferenceLink($elementType) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (isset($data['primaryElement'])): ?>
    <h3>Primary Element</h3>

    <p>This route was rendered because it matched the URI of a <?= $docs->classReferenceLink($data['primaryElement']::class) ?> element.</p>

    <table class="table table-striped table-bordered">
        <tbody>
            <tr>
                <th colspan="2">
                    Custom Fields
                    <br>
                    Fields associated with this <code><?= $data['primaryElement']::lowerDisplayName() ?></code> via its field layout.
                </th>
            </tr>

            <tr>
                <th>Field Handle</th>
                <th>Value</th>
            </tr>

            <?php foreach ($data['primaryElement']->getFieldLayout()->getCustomFieldElements() as $fle): ?>
                <?php $handle = $fle->handle ?? $fle->getOriginalHandle() ?>
                <?php $value = $data['primaryElement']->getFieldValue($handle) ?>

                <tr>
                    <td class="ws-normal">
                        <code><?= $handle ?></code>
                        <?php if ($handle !== $fle->getOriginalHandle()): ?>
                            (Original handle: <code><?= $fle->getOriginalHandle() ?></code>)
                        <?php endif; ?>
                    </td>
                    <td><?= Craft::dump($value, depth: 2, return: true) ?></td>
                </tr>
            <?php endforeach; ?>

            <tr>
                <th colspan="2">
                    Attributes
                    <br>
                    Native properties of the <code><?= $data['primaryElement']::lowerDisplayName() ?></code>.
                </th>
            </tr>

            <tr>
                <th>Attribute</th>
                <th>Value</th>
            </tr>

            <?php foreach ($data['primaryElement']->getAttributes() as $attr => $value): ?>
                <tr>
                    <td class="ws-normal">
                        <a href="<?= $docs->classReferenceUrl($data['primaryElement'], $attr, 'property') ?>" target="_blank"><code><?= $attr ?></code></a>
                    </td>
                    <td><pre><?= var_dump($value) ?></pre></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
