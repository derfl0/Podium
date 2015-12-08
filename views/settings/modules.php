<h3><?= _('Podium Module') ?></h3>
<ul>
    <? foreach($modules as $module): ?>
        <li>
            <?= htmlReady($module['name']) ?>
        </li>
    <? endforeach ?>
</ul>