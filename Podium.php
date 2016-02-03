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
        self::addStylesheet('/assets/style.less');
        PageLayout::addScript($this->getPluginURL() . '/assets/podium.js');
        PageLayout::addBodyElements('<div id="podiumwrapper"><div id="podium"><div id="podiuminput"><input type="text" placeholder="' . _('Suchbegriff') . '"></div><ul id="podiumlist"></ul></div></div>');

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

    private static function getSQL($search)
    {
        // register all classes
        Podium::loadDefaultModules();

        // build all types
        foreach (self::$types as $id => $type) {

            // check if module is active
            if (self::isActiveModule($id)) {

                // add the sql part
                $partSQL = $type['sql']($search);
                if ($partSQL) {
                    $sql[] = "(" . $type['sql']($search) . " LIMIT 10)";
                }
            }
        }

        return "SELECT type, id FROM (" . join(' UNION ', $sql) . ") as a GROUP BY id ORDER BY null";
    }

    /**
     * Kickoff function to start query
     */
    public function find_action()
    {
        $time = microtime(1);
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
                    $all_links[$id] = $new;
                }
            }
        }
        $btime = microtime(1);
        while ($processed < count($all_links)) {
            $error = $reject = $read = $all_links;
            mysqli_poll ($read, $error, $reject, 1);
            if ($read[0] && $set = $read[0]->reap_async_query()) {
                $id = $read[0]->podiumid;
                while ($data = $set->fetch_assoc()) {
                    if (sizeof($result[$id]['content']) < self::MAX_RESULT_OF_TYPE) {
                        $arg = $data['type'] && count($data) == 2 ? $data['id'] : $data;
                        if ($item = $types[$id]['filter']($arg, $search)) {
                            $result[$id]['name'] = $types[$id]['name'];
                            $result[$id]['content'][] = $item;
                        }
                    }
                }
                $processed++;
            }
        }
var_dump(microtime(1)-$time);die;
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
    public static function mark($string, $query, $filename = true)
    {
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
