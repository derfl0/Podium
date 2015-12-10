<table class="default">
    <caption>
        <?= _('Stichwörter') ?>
    </caption>
    <thead>
        <tr>
            <th><?= _('Name') ?></th>
            <th><?= _('Rechte') ?></th>
            <th><?= _('Stichwörter') ?></th>
            <th><?= _('Untertitel') ?></th>
            <th class="actions"><?= _('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach($buzzwords as $buzzword): ?>
        <tr>
            <td><?= htmlReady($buzzword->name) ?></td>
            <td><?= htmlReady($buzzword->rightsname) ?></td>
            <td><?= htmlReady($buzzword->buzzwords) ?></td>
            <td><?= htmlReady($buzzword->subtitle) ?></td>
            <td class="actions">
                <a href="<?= URLHelper::getLink('plugins.php/podium/settings/edit_buzzword/'.$buzzword->id) ?>" title="<?= htmlReady($buzzword->name) ?>" data-dialog="size=auto">
                    <?= Assets::img('icons/16/blue/edit.png')?>
                </a>
                <a href="<?= URLHelper::getLink('plugins.php/podium/settings/delete_buzzword/'.$buzzword->id) ?>">
                    <?= Assets::img('icons/16/blue/trash.png')?>
                </a>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>