<?php

use craft\helpers\StringHelper;

 if ($data['allElementTypes']): ?>
    <h3>Element Types</h3>
    <p>
        <?= Craft::t('app', 'A total of <strong>{count,spellout}</strong> {count,plural,=1{element type} other{element types}} were used by this request.', ['count' => count($data['allElementTypes'])]) ?>
    </p>
    <ul>
        <?php foreach ($data['allElementTypes'] as $elementType): ?>
            <li>
                <a href="<?= Craft::$app->getDocs()->classReferenceUrl($elementType) ?>" target="_blank"><code><?= $elementType ?></code></a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (isset($data['primaryElement'])): ?>
    <?php $ref = $data['primaryElement']::refHandle() ?>

    <h3>Primary Element</h3>

    <p>This route was rendered because it matched the URI of a <a href="<?= Craft::$app->getDocs()->classReferenceUrl($data['primaryElement']) ?>" target="_blank"><code><?= $data['primaryElement']::class ?></code></a> element.</p>

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
                <tr>
                    <td><code><?= $handle ?></code></td>
                    <td class="ws-normal">
                        <?= StringHelper::toString($data['primaryElement']->getFieldValue($handle)) ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <tr>
                <th colspan="3">
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
                    <td><code><?= $attr ?></code></td>
                    <td class="ws-normal">
                        <?= StringHelper::toString($value) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
