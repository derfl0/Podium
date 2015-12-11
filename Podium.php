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

        /* Init default types */
        //self::addType('navigation', _('Navigation'), array($this, 'search_navigation'), array($this, 'filter_navigation'));
        //self::addType('buzzword', _('Stichworte'), array($this, 'search_buzzwords'), array($this, 'filter_buzzwords'));
        self::addType('resources', _('Ressourcen'), array($this, 'search_resources'), array($this, 'filter_resources'));
        self::addType('calendar', _('Termine'), array($this, 'search_calendar'), array($this, 'filter_calendar'));
        self::addType('mycourses', _('Meine Veranstaltungen'), array($this, 'search_mycourse'), array($this, 'filter_course'));
        self::addType('courses', _('Veranstaltungen'), array($this, 'search_course'), array($this, 'filter_course'));
        self::addType('user', _('Benutzer'), array($this, 'search_user'), array($this, 'filter_user'));
        self::addType('file', _('Datei'), array($this, 'search_files'), array($this, 'filter_file'));
        self::addType('inst', _('Einrichtungen'), array($this, 'search_inst'), array($this, 'filter_inst'));
        self::addType('semtree', _('Studienbereiche'), array($this, 'search_semtree'), array($this, 'filter_semtree'));

        require_once "modules/PodiumBuzzwordModule.php";
        PodiumBuzzwordModule::register();

        /* Add podium navigation */
        try {
            Navigation::addItem('/admin/podium', new AutoNavigation(dgettext('podium', 'Podium'), PluginEngine::GetURL($this, array(), 'settings/modules')));
            Navigation::addItem('/admin/podium/modules', new AutoNavigation(dgettext('podium', 'Module'), PluginEngine::GetURL($this, array(), 'settings/modules')));
            Navigation::addItem('/admin/podium/buzzword', new AutoNavigation(dgettext('podium', 'Stichworte'), PluginEngine::GetURL($this, array(), 'settings/buzzwords')));
            Navigation::addItem('/admin/podium/faillog', new AutoNavigation(dgettext('podium', 'Erfolglose Suchen'), PluginEngine::GetURL($this, array(), 'settings/faillog')));
        } catch(InvalidArgumentException $e) {

        }
    }

    public static function register($class) {
        $reflector = new ReflectionClass($class);
        $_SESSION['podium'][$reflector->getName()] = $reflector->getFileName();
    }

    private static function loadClasses() {
        foreach ($_SESSION['podium'] as $class => $filename) {
            require_once $filename;
            self::addType($class::getId(), $class::getName(), array($class, 'search'), array($class, 'filter'));
        }
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
        return self::$types;
    }

    /**
     * Kickoff function to start query
     */
    public function find_action()
    {
        self::loadClasses();
        $types = self::$types;

        $search = trim(studip_utf8decode(Request::get('search')));

        foreach ($types as $type) {
            $partSQL = $type['sql']($search);
            if ($partSQL) {
                $sql[] = "(" . $type['sql']($search) . " LIMIT 10)";
            }
        }

        $fullSQL = "SELECT type, id FROM (" . join(' UNION ', $sql) . ") as a GROUP BY id";

        // now query
        $stmt = DBManager::get()->prepare($fullSQL);
        $stmt->execute();
        $result = array();

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (sizeof($result[$data['type']]['content']) < 6) {
                if ($item = $types[$data['type']]['filter']($data['id'], $search)) {
                    $result[$data['type']]['name'] = $types[$data['type']]['name'];
                    $result[$data['type']]['content'][] = $item;
                }
            }
        }

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

    /*
     * ###DEFAULT FUNCTIONS!
     */

    private function search_files($search)
    {
        // Filter for own courses
        if (!$GLOBALS['perm']->have_perm('admin')) {
            if (!$GLOBALS['perm']->have_perm('admin')) {
                $user = DBManager::get()->quote(User::findCurrent()->id);
            }
            $ownseminars = "JOIN seminar_user ON (dokumente.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = $user) ";
        }

        // Now check if we got a seminar
        if (strpos($search, '/') !== FALSE) {
            $args = explode('/', $search);
            $prequery = DBManager::get()->quote("%" . trim($args[0]) . "%");
            $query = DBManager::get()->quote("%" . trim($args[1]) . "%");
            $binary = DBManager::get()->quote('%' . join('%', str_split(strtoupper(trim($args[0])))) . '%');
            $comp = "AND";
        } else {
            $query = DBManager::get()->quote("%$search%");
            $prequery = $query;
            $comp = "OR";
            $binary = DBManager::get()->quote('%' . join('%', str_split(strtoupper($search))) . '%');
        }

        // Build query
        $sql = "SELECT 'file' as type, dokumente.dokument_id as id FROM dokumente "
            . "JOIN seminare USING (seminar_id) $ownseminars $usersearch "
            . "WHERE (seminare.name LIKE BINARY $binary OR seminare.name LIKE $prequery $usercondition) "
            . "$comp dokumente.name LIKE $query "
            . "ORDER BY dokumente.chdate DESC";
        return $sql;
    }

    private function filter_file($file_id, $search)
    {
        $file = StudipDocument::find($file_id);
        if ($file->checkAccess(User::findCurrent()->id)) {
            return array(
                'id' => $file->id,
                'name' => self::mark($file->name, $search),
                'url' => URLHelper::getURL("sendfile.php?type=0&file_id={$file->id}&file_name={$file->filename}"),
                'additional' => self::mark($file->course ? $file->course->getFullname() : '', $search, false),
                'date' => strftime('%x', $file->chdate),
                'expand' => URLHelper::getURL("folder.php", array("cid" => $file->seminar_id, "cmd" => "tree"))
            );
        }
    }

    private function search_user($search)
    {
        if (!$search) {
            return null;
        }

        // if you're no admin respect visibilty
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $visQuery = get_vis_query('user', 'search') . " AND ";
        }
        $query = DBManager::get()->quote("%$search%");
        $sql = "SELECT 'user' as type, user.user_id as id FROM auth_user_md5 user LEFT JOIN user_visibility USING (user_id) WHERE $visQuery (CONCAT_WS(' ', user.nachname, user.vorname) LIKE $query OR  CONCAT_WS(' ', user.vorname, user.nachname) LIKE $query OR username LIKE $query)";
        return $sql;
    }

    private function filter_user($user_id, $search)
    {
        $user = User::find($user_id);
        $result = array(
            'id' => $user->id,
            'name' => self::mark($user->getFullname(), $search),
            'url' => URLHelper::getURL("dispatch.php/profile", array('username' => $user->username)),
            'additional' => self::mark($user->username, $search),
            'expand' => URLHelper::getURL("browse.php", array('name' => $search)),
        );
        $avatar = Avatar::getAvatar($user->id);
        if (self::SHOW_ALL_AVATARS || $avatar->is_customized()) {
            $result['img'] = $avatar->getUrl(AVATAR::MEDIUM);
        }
        return $result;
    }

    private function search_mycourse($search)
    {
        if (!$search) {
            return null;
        }
        $query = DBManager::get()->quote("%$search%");
        $user_id = DBManager::get()->quote(User::findCurrent()->id);
        $sql = "SELECT 'mycourses' as type, courses.seminar_id as id FROM seminare courses JOIN seminar_user USING (seminar_id) WHERE user_id = $user_id AND (courses.Name LIKE $query OR courses.VeranstaltungsNummer LIKE $query) ORDER BY start_time DESC";
        return $sql;
    }

    private function search_course($search)
    {
        if (!$search) {
            return null;
        }
        $query = DBManager::get()->quote("%$search%");

        // visibility
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $visibility = "courses.visible = 1 AND ";
        }

        $sql = "SELECT 'courses' as type, courses.seminar_id as id FROM seminare courses WHERE $visibility(courses.Name LIKE $query OR courses.VeranstaltungsNummer LIKE $query) ORDER BY ABS(start_time - unix_timestamp()) ASC";
        return $sql;
    }

    private function filter_course($course_id, $search)
    {
        $course = Course::find($course_id);
        $result = array(
            'id' => $course->id,
            'name' => self::mark($course->getFullname(), $search),
            'url' => URLHelper::getURL("dispatch.php/course/details/index/" . $course->id),
            'date' => $course->start_semester->name,
            'expand' => URLHelper::getURL("dispatch.php/search/courses", array(
                'reset_all' => 1,
                'search_sem_qs_choose' => 'title_lecturer_number',
                'search_sem_sem' => 'all',
                'search_sem_quick_search_parameter' => $search,
                'search_sem_1508068a50572e5faff81c27f7b3a72f' => 1 // Fuck you Stud.IP
            ))
        );
        $avatar = CourseAvatar::getAvatar($course->id);
        if (self::SHOW_ALL_AVATARS || $avatar->is_customized()) {
            $result['img'] = $avatar->getUrl(AVATAR::MEDIUM);
        }
        return $result;
    }

    private function search_semtree($search)
    {
        if (!$search) {
            return null;
        }
        $query = DBManager::get()->quote("%$search%");
        $sql = "SELECT 'semtree' as type, sem_tree_id as id FROM sem_tree WHERE name LIKE $query ORDER BY name DESC";
        return $sql;
    }

    private function filter_semtree($semtree_id, $search)
    {
        $semtree = StudipStudyArea::find($semtree_id);
        return array(
            'id' => $semtree->id,
            'name' => self::mark($semtree->name, $search),
            'url' => URLHelper::getURL("dispatch.php/search/courses", array('start_item_id' => $semtree->id, 'level' => 'vv', 'cmd' => 'qs'))
        );
    }

    private function search_inst($search)
    {
        if (!$search) {
            return null;
        }
        $query = DBManager::get()->quote("%$search%");
        $sql = "SELECT 'inst' as type, Institut_id as id FROM Institute WHERE Name LIKE $query ORDER BY name DESC";
        return $sql;
    }

    private function filter_inst($inst_id, $search)
    {
        $inst = Institute::find($inst_id);
        $result = array(
            'id' => $inst->id,
            'name' => self::mark($inst->getFullname(), $search),
            'url' => URLHelper::getURL("dispatch.php/institute/overview", array('cid' => $inst->id)),
            'expand' => URLHelper::getURL('institut_browse.php', array('cmd' => 'suche', 'search_name' => $search))
        );
        $avatar = InstituteAvatar::getAvatar($inst->id);
        if (self::SHOW_ALL_AVATARS || $avatar->is_customized()) {
            $result['img'] = $avatar->getUrl(AVATAR::MEDIUM);
        }
        return $result;
    }

    private function search_navigation($search)
    {
        if (!$search) {
            return null;
        }

        $result = array();
        $start = Navigation::getItem('/');
        foreach ($start->getSubNavigation() as $index => $sub) {
            $this->search_nav_recursive($sub, '', $index, $search, $result);
        }

        if (!$result) {
            return null;
        }
        return "SELECT type,id FROM (" . join(' UNION ', $result) . ") as navtable";
    }

    private function search_nav_recursive(Navigation $navigation, $path, $index, $search, &$result)
    {
        $fullpath = $path . '/' . $index;
        if (mb_strpos($navigation->getTitle(), $search) !== false) {
            $quotedPath = DBManager::get()->quote($fullpath);
            $result[] = "(SELECT 'navigation' as type, $quotedPath as id)";
        }
        foreach ($navigation->getSubNavigation() as $newindex => $sub) {
            $this->search_nav_recursive($sub, $fullpath, $newindex, $search, $result);
        }
    }

    private function filter_navigation($nav_path, $search)
    {
        $nav = Navigation::getItem($nav_path);
        return array(
            'name' => self::mark($nav->getTitle(), $search),
            'url' => $nav->getUrl(),
            'additional' => $nav_path
        );
    }

    private function search_calendar($query)
    {
        $time = strtotime($query);
        $endtime = $time + 86400;
        $user_id = DBManager::get()->quote(User::findCurrent()->id);
        if ($time) {
            return "SELECT 'calendar' as type, termin_id as id FROM termine JOIN seminar_user ON (range_id = seminar_id) WHERE user_id = $user_id AND date BETWEEN $time AND $endtime ORDER BY date";
        }
    }

    private function filter_calendar($termin_id, $search)
    {
        $termin = DBManager::get()->fetchOne("SELECT name,date,end_time,seminar_id FROM termine JOIN seminare ON (range_id = seminar_id) WHERE termin_id = ?", array($termin_id));
        return array(
            'name' => $termin['name'],
            'url' => URLHelper::getURL("dispatch.php/course/details", array('cid' => $termin['seminar_id'])),
            'additional' => strftime('%H:%M', $termin['date']) . " - " . strftime('%H:%M', $termin['end_time']) . ", " . strftime('%x', $termin['date']),
            'expand' => URLHelper::getURL('calendar.php', array('cmd' => 'showweek', 'atime' => strtotime($search)))
        );
    }

    private function search_resources($search)
    {
        if (!$search || !$GLOBALS['perm']->have_perm('admin')) {
            return null;
        }
        $query = DBManager::get()->quote("%$search%");
        return "SELECT 'resources' as type, resource_id as id FROM resources_objects WHERE name LIKE $query OR description LIKE $query OR REPLACE(name, ' ', '') LIKE $query OR REPLACE(description, ' ', '') LIKE $query";
    }

    private function filter_resources($resource_id, $search)
    {
        $res = DBManager::get()->fetchOne("SELECT name,description FROM resources_objects WHERE resource_id = ?", array($resource_id));
        return array(
            'name' => self::mark($res['name'], $search),
            'url' => URLHelper::getURL("resources.php", array('view' => 'view_schedule', 'show_object' => $resource_id)),
            'additional' => self::mark($res['description'], $search),
            'expand' => URLHelper::getURL('resources.php', array('view' => 'search', 'search_exp' => $search, 'start_search' => ''))
        );
    }
}
