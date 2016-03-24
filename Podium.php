<?php
require "PodiumModule.php";

/**
 * Podium.php
 *
 * Quickaccess anything
 *
 * @author  Florian Bieringer <florian.bieringer@uni-passau.de>
 */
class Podium extends StudIPPlugin implements SystemPlugin
{
    const SHOW_ALL_AVATARS = false;
    const MAX_RESULT_OF_TYPE = 6;

    private static $types = array();

    public function __construct()
    {
        parent::__construct();

        /* Init html and js */
        bindtextdomain('podium', __DIR__ . '/locale');
        self::addStylesheet('/assets/style.less', array("media" => "all"));
        PageLayout::addScript($this->getPluginURL() . '/assets/podium.js');
        PageLayout::addBodyElements('<div id="podiumwrapper">
                                        <div id="podium">
                                            <div id="podiuminput">
                                                <input type="text" placeholder="' . dgettext('podium', 'Suchbegriff') . '">
                                                <div id="podiumclose">
                                                    '.Assets::img('icons/64/blue/decline.png').'
                                                </div>
                                            </div>
                                            <ul id="podiumlist"></ul>
                                            <div class="podium_help">
                                                <dl>
                                                    <dt>' . dgettext('podium', '[STRG] + [Leertaste]') . '</dt>
                                                    <dd>' . dgettext('podium', 'Tastenkombination zum Öffnen und Schließen') . '</dd>

                                                    <dt>' . dgettext('podium', '[ALT] oder Klick auf Überschrift') . '</dt>
                                                    <dd>' . dgettext('podium', 'Erweitert die ausgewählte Suchkategorie. Bei einem weiteren Klick wird an die entsprechende Vollsuche weitergeleitet.') . '</dd>

                                                    <dt>' . dgettext('podium', 'Dateisuche') . '</dt>
                                                    <dd>' . dgettext('podium', 'Die Dateisuche kann über einen Schrägstrich (/) verfeinert werden. Beispiel: "Meine Veranstaltung/Datei" zeigt alle Dateien die das Wort "Datei" enthalten und in "Meine Veranstaltung" sind an. Die Veranstaltung kann auch auf einen Teil (z.B. Veran/Datei) oder auf die Großbuchstaben bzw. auch deren Abkürzung (z.B. MV/Datei oder V/Datei) beschränkt werden.') . '</dd>

                                                    <dt>' . dgettext('podium', 'Platzhalter') . '</dt>
                                                    <dd>' . dgettext('podium', '_ ist Platzhalter für ein beliebiges Zeichen.') . '</dd>
                                                    <dd>' . dgettext('podium', '% ist Platzhalter für beliebig viele Zeichen.') . '</dd>
                                                    <dd>' . dgettext('podium', 'Me_er findet Treffer für Me<mark>y</mark>er und Me<mark>i</mark>er. M__er findet zusätzlich auch M<mark>ay</mark>er und M<mark>ai</mark>er. M%er findet alle vorherigen Treffer aber auch M<mark>ünchn</mark>er.') . '</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>');

        /* Add podium icon */
        PageLayout::addBodyElements(Assets::img('icons/16/white/search.png', array('id' => 'podiumicon')));

        /* Add podium navigation */
        try {
            Navigation::addItem('/admin/podium', new AutoNavigation(dgettext('podium', 'Podium'), PluginEngine::GetURL($this, array(), 'settings/modules')));
            Navigation::addItem('/admin/podium/modules', new AutoNavigation(dgettext('podium', 'Module'), PluginEngine::GetURL($this, array(), 'settings/modules')));
            Navigation::addItem('/admin/podium/buzzword', new AutoNavigation(dgettext('podium', 'Stichworte'), PluginEngine::GetURL($this, array(), 'settings/buzzwords')));
            Navigation::addItem('/admin/podium/faillog', new AutoNavigation(dgettext('podium', 'Erfolglose Suchen'), PluginEngine::GetURL($this, array(), 'settings/faillog')));
        } catch (InvalidArgumentException $e) {

        }
    }

    /**
     * Adds an PodiumModule to the search (must implement PodiumModule)
     * @param $class Your search class
     */
    public static function registerPodiumModule($class)
    {
        self::addType($class::getPodiumId(), $class::getPodiumName(), array($class, 'getPodiumSearch'), array($class, 'podiumFilter'));
    }

    /**
     * Add a type to podium
     *
     * @param $index string typeindexname
     * @param $name string typename
     * @param $sql function Callback to retrieve the sql
     * @param $filter function Callback to get array formated result
     */
    public static function addType($index, $name, $sql, $filter)
    {
        self::$types[$index] = array(
            'name' => $name,
            'sql' => $sql,
            'filter' => $filter);
    }

    /**
     * Removes a type from podium
     *
     * @param $index typeindexname
     */
    public static function removeType($index)
    {
        unset(self::$types[$index]);
    }

    public static function getTypes()
    {
        self::loadDefaultModules();
        return self::$types;
    }

    private static function loadDefaultModules()
    {
        foreach (glob(__DIR__ . '/models/*.php') as $file) {
            require_once $file;
            Podium::registerPodiumModule(basename($file, '.php'));
        }
    }

    /**
     * Kickoff function to start query
     */
    public function find_action()
    {
        // Now load all modules
        Podium::loadDefaultModules();
        $search = trim(studip_utf8decode(Request::get('search')));
        $sql = "";
        $result = array();
        $types = self::$types;
        foreach ($types as $id => $type) {
            if (self::isActiveModule($id)) {
                $partSQL = $type['sql']($search);
                if ($partSQL) {
                    $new = mysqli_connect($GLOBALS['DB_STUDIP_HOST'], $GLOBALS['DB_STUDIP_USER'], $GLOBALS['DB_STUDIP_PASSWORD'], $GLOBALS['DB_STUDIP_DATABASE']);
                    $new->query($type['sql']($search), MYSQLI_ASYNC);
                    $new->podiumid = $id;
                    $all_links[] = $new;
                }
            }
        }

        $read = $error = $reject = array();
        while (count($read) + count($error) + count($reject) < count($all_links)) {

            // Parse all links
            $error = $reject = $read = $all_links;

            // Poll will reject connection that have no query running
            mysqli_poll($read, $error, $reject, 1);

            foreach ($read as $r) {
                if ($r && $set = $r->reap_async_query()) {
                    $id = $r->podiumid;
                    while ($data = $set->fetch_assoc()) {
                        if (sizeof($result[$id]['content']) < self::MAX_RESULT_OF_TYPE) {
                            $arg = $data['type'] && count($data) == 2 ? $data['id'] : $data;
                            if ($item = $types[$id]['filter']($arg, $search)) {
                                $result[$id]['name'] = $types[$id]['name'];
                                $result[$id]['content'][] = $item;
                            }
                        }
                    }
                }
            }
        }

        // Sort
        $result = array_filter(array_merge(Config::get()->PODIUM_MODULES_ORDER, $result));

        // Write faillog if required
        if (!$result && Config::get()->PODIUM_FAILLOG) {
            DBManager::get()->execute("INSERT INTO podium_faillog (input, count) VALUES (?, 1) ON DUPLICATE KEY UPDATE count=count+1", array($search));
        }

        // Send me an answer
        echo json_encode(studip_utf8encode($result));
        die;

    }

    /**
     * Function to mark a querystring in a resultstring
     *
     * @param $string
     * @param $query
     * @param bool|true $filename
     * @return mixed
     */
    public static function mark($string, $query, $longtext = false, $filename = true)
    {
        // Secure
        $string = htmlReady($string);

        if (strpos($query, '/') !== FALSE) {
            $args = explode('/', $query);
            if ($filename) {
                return self::mark($string, trim($args[1]));
            }
            return self::mark($string, trim($args[0]));
        } else {
            $query = trim($query);
        }

        // Replace direct string
        $result = preg_replace("/$query/i", "<mark>$0</mark>", $string, -1, $found);
        if ($found) {

            // Check for overlength
            if ($longtext && strlen($result) > 200) {
                $start = max(array(0, stripos($result, '<mark>') - 20));
                $space = stripos($result, ' ', $start);
                $start = $space < $start + 20 ? $space : $start;
                return substr($result, $start, 200);
            }

            return $result;
        }

        // Replace camelcase
        $replacement = "$" . (++$i);
        foreach (str_split(strtoupper($query)) as $letter) {
            $queryletter[] = "($letter)";
            $replacement .= "<mark>$" . ++$i . "</mark>$" . ++$i;
        }


        $pattern = "/([\w\W]*)" . join('([\w\W]*)', $queryletter) . "/";
        $result = preg_replace($pattern, $replacement, $string, -1, $found);

        if ($found) {
            return $result;
        }

        return $string;
    }

    public static function isActiveModule($moduleId)
    {
        return !in_array($moduleId, Config::get()->PODIUM_MODULES);
    }
}
