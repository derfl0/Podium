<form class="studip_form" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend>
            <?= _('Podium Module') ?>
        </legend>
        <? foreach ($modules as $id => $module): ?>
            <label>
                <input type="checkbox" name="modules[<?= $id ?>]"
                       value="1" <?= Podium::isActiveModule($id) ? 'checked' : '' ?>>
                <?= htmlReady($module['name']) ?>
            </label>
        <? endforeach ?>
        <?= \Studip\Button::create(_('Speichern'), 'store') ?>
    </fieldset>
</form>