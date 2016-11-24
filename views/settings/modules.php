<form class="default" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend>
            <?= _('Podium Module') ?>
        </legend>
        <ul id="podium_modules">
            <? foreach ($modules as $id => $module): ?>
                <li>
                    <input type="checkbox" name="modules[<?= $id ?>]" id="modules[<?= $id ?>]"
                           value="1" <?= Podium::isActiveModule($id) ? 'checked' : '' ?>>
                    <label for="modules[<?= $id ?>]" class="undecorated">
                        <?= htmlReady($module['name']) ?>
                        </label>
                    <? if (Podium::isFulltextModule($module['sql'][0])): ?>
                        <input type="checkbox" name="fulltext[]" id="fulltext[<?= $id ?>]"
                               value="<?= $id ?>" <?= Podium::isActiveFulltextModule($id) ? 'checked' : '' ?>>
                        <label for="fulltext[<?= $id ?>]" class="undecorated">
                                <?= _('Volltext') ?>
                            </label>
                    <? endif; ?>
                </li>
            <? endforeach ?>
        </ul>
        <?= \Studip\Button::create(_('Speichern'), 'store') ?>
    </fieldset>
</form>

<script>
    $('ul#podium_modules').sortable();
</script>