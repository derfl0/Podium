<form class='studip_form default' method='post' action='<?= URLHelper::getLink('plugins.php/podium/settings/buzzwords') ?>'>

    <?= CSRFProtection::tokenTag() ?>

    <input type='hidden' name='buzz_id' value='<?= $buzzword->buzz_id ?>'>

    <label>
        <?= _('Name') ?>
        <input type='text' placeholder="<?= _('Name') ?>" name='buzzword[name]' value='<?= htmlReady($buzzword->name) ?>'>
    </label>

    <label>
        <?= _('Rechtestufe') ?>
        <select name='buzzword[rights]' value='<?= $buzzword->rights ?>'>
            <? foreach($GLOBALS['perm']->permissions as $permname => $permval): ?>
                <option value="<?= $permval ?>" <?= $buzzword->rights == $permval ? 'selected' : '' ?>><?= htmlReady($permname) ?></option>
            <? endforeach; ?>
        </select>
    </label>

    <label>
        <?= _('Stichwörter') ?>
        <input type='text' name='buzzword[buzzwords]' value='<?= htmlReady($buzzword->buzzwords) ?>'>
    </label>

    <label>
        <?= _('Untertitel') ?>
        <input type='text' name='buzzword[subtitle]' value='<?= htmlReady($buzzword->subtitle) ?>'>
    </label>

    <label>
        <?= _('URL') ?>
        <input type='text' name='buzzword[url]' value='<?= $buzzword->url ?>'>
    </label>

    <?= \Studip\Button::create(_('Speichern'), 'store', array('data-dialog-button' => 'true')) ?>

</form>