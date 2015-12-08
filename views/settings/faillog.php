<table class="default">
    <caption>
        <?= _('Erfolglose Suchen') ?>
    </caption>
    <thead>
        <tr>
            <th>
                <?= _('Suchbegriff') ?>
            </th>
            <th>
                <?= _('Versuche') ?>
            </th>
        </tr>
    </thead>
    <tbody>
    <? foreach($fails as $fail => $count): ?>
        <tr>
            <td>
                <?= htmlReady($fail) ?>
            </td>
            <td>
                <?= $count ?>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>