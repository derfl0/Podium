<?php
class SettingsController extends StudipController {

    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->plugin;
    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox.php'));
    }

    public function modules_action()
    {
        $this->modules = Podium::getTypes();
    }

    public function faillog_action() {

        // fetch faillog
        $this->fails = DBManager::get()->fetchPairs("SELECT * FROM podium_faillog ORDER BY count DESC");

        // add switch to sidebar
        $optionsWidget = new OptionsWidget();
        $optionsWidget->addCheckbox(_('Aktiv'), Config::get()->PODIUM_FAILLOG , URLHelper::getLink('plugins.php/podium/settings/faillogswitch'));
        Sidebar::Get()->addWidget($optionsWidget);

        // add purge to sidebar
        $actionsWidget = new ActionsWidget();
        $actionsWidget->addLink(_('Leeren'), URLHelper::getLink('plugins.php/podium/settings/faillogpurge'), 'icons/16/blue/trash.png');
        Sidebar::Get()->addWidget($actionsWidget);
    }

    public function faillogswitch_action() {
        Config::get()->store(PODIUM_FAILLOG, !Config::get()->PODIUM_FAILLOG);
        $this->redirect('settings/faillog');
    }

    public function faillogpurge_action() {
        DBManager::get()->exec('TRUNCATE TABLE podium_faillog');
        $this->redirect('settings/faillog');
    }
}
