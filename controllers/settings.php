<?php
require_once dirname(__DIR__) . "/models/PodiumBuzzword.php";

class SettingsController extends StudipController
{

    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->plugin;

        // you can only end up here as admin
        $GLOBALS['perm']->check('root');

    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // Ajax decision
        if (Request::isXhr()) {
            $this->set_content_type('text/html;Charset=windows-1252');
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox.php'));
        }

    }

    public function modules_action()
    {
        $this->modules = Podium::getTypes();

        if (Request::submitted('store')) {
            CSRFProtection::verifyUnsafeRequest();
            $activeModules = Request::getArray('modules');
            foreach ($this->modules as $id => $module) {
                if (!$activeModules[$id]) {
                    $deactivated[] = $id;
                }
            }
            Config::get()->store(PODIUM_MODULES, $deactivated);
        }
    }

    public function faillog_action()
    {

        // fetch faillog
        $this->fails = DBManager::get()->fetchPairs("SELECT * FROM podium_faillog ORDER BY count DESC");

        // add switch to sidebar
        $optionsWidget = new OptionsWidget();
        $optionsWidget->addCheckbox(_('Aktiv'), Config::get()->PODIUM_FAILLOG, URLHelper::getLink('plugins.php/podium/settings/faillogswitch'));
        Sidebar::Get()->addWidget($optionsWidget);

        // add purge to sidebar
        $actionsWidget = new ActionsWidget();
        $actionsWidget->addLink(_('Leeren'), URLHelper::getLink('plugins.php/podium/settings/faillogpurge'), 'icons/16/blue/trash.png');
        Sidebar::Get()->addWidget($actionsWidget);
    }

    public function faillogswitch_action()
    {
        Config::get()->store(PODIUM_FAILLOG, !Config::get()->PODIUM_FAILLOG);
        $this->redirect('settings/faillog');
    }

    public function faillogpurge_action()
    {
        DBManager::get()->exec('TRUNCATE TABLE podium_faillog');
        $this->redirect('settings/faillog');
    }

    public function buzzwords_action()
    {

        // Check for update
        if (Request::submitted('store')) {
            CSRFProtection::verifyUnsafeRequest();
            $buzzword = new PodiumBuzzword(Request::get('buzz_id'));
            $buzzword->setData(Request::getArray("buzzword"));
            $buzzword->store();
        }

        $this->buzzwords = PodiumBuzzword::findBySQL("1 ORDER BY name DESC");

        // add buzzword in sidebar
        $actionsWidget = new ActionsWidget();
        $actionsWidget->addLink(_('Neues Stichwort'), URLHelper::getLink('plugins.php/podium/settings/edit_buzzword'), 'icons/16/blue/add.png', array('data-dialog' => 'size=auto'));
        Sidebar::Get()->addWidget($actionsWidget);
    }

    public function edit_buzzword_action($buzz_id = null)
    {
        if ($buzz_id) {
            $this->buzzword = new PodiumBuzzword($buzz_id);
        }
    }

    public function delete_buzzword_action($buzz_id) {
        PodiumBuzzword::deleteBySQL('buzz_id = ?', array($buzz_id));
        $this->redirect('settings/buzzwords');
    }
}
