<?php

/**
 * QuickfilePlugin.class.php
 *
 * @author  Florian Bieringer <florian.bieringer@uni-passau.de>
 */
class Podium extends StudIPPlugin implements SystemPlugin
{

    public static $types = array();

    public function __construct()
    {
        parent::__construct();

        /* Init html and js */
        self::addStylesheet('/assets/style.less');
        PageLayout::addScript($this->getPluginURL() . '/assets/podium.js');
        PageLayout::addBodyElements('<div id="quickfilewrapper"><div id="quickfile"><h3>' . _('Podium') . '</h3><div id="quickfileinput"><input type="text" placeholder="' . _('Suchbegriff') . '"></div><ul id="quickfilelist"></ul></div></div>');

        /* Add podium icon */
        PageLayout::addBodyElements(Assets::img('icons/32/white/search.png', array('id' => 'podiumicon')));

        /* Init default types */
        self::addType('mycourses', _('Meine Veranstaltungen'), array($this, 'search_mycourse'), array($this, 'filter_course'));
        self::addType('courses', _('Veranstaltungen'), array($this, 'search_course'), array($this, 'filter_course'));
        self::addType('user', _('Benutzer'), array($this, 'search_user'), array($this, 'filter_user'));
        self::addType('file', _('Datei'), array($this, 'search_files'), array($this, 'filter_file'));
    }

    public static function addType($index, $name, $sql, $filter) {
        self::$types[$index] = array(
            'name' => $name,
            'sql' => $sql,
            'filter' => $filter);
    }

    public function find_action()
    {
        $types = self::$types;

        $search = trim(studip_utf8decode(Request::get('search')));

        foreach ($types as $type) {
            $partSQL = $type['sql']($search);
            if ($partSQL) {
                $sql[] = "(" . $type['sql']($search) . " LIMIT 10)";
            }
        }

        $fullSQL = join(' UNION ', $sql);

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

    /**
     * Returns SQL for the file search
     *
     * @param $search
     * @return string
     */
    public function search_files($search)
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

    public function filter_file($file_id, $search)
    {
        $file = StudipDocument::find($file_id);
        if ($file->checkAccess(User::findCurrent()->id)) {
            return array(
                'id' => $file->id,
                'name' => self::mark($file->name, $search),
                'url' => URLHelper::getURL("sendfile.php?type=0&file_id={$file->id}&file_name={$file->filename}"),
                'additional' => self::mark($file->course ? $file->course->getFullname() : '', $search, false),
                'date' => strftime('%x', $file->chdate)
            );
        }
    }

    public function search_user($search)
    {
        if (!$search) {
            return null;
        }

        $query = DBManager::get()->quote("%$search%");
        $sql = "SELECT 'user' as type, user.user_id as id FROM auth_user_md5 user WHERE CONCAT_WS(' ', user.nachname, user.vorname) LIKE $query OR  CONCAT_WS(' ', user.vorname, user.nachname) LIKE $query OR username LIKE $query AND " . get_vis_query('user');
        return $sql;
    }

    public function filter_user($user_id, $search)
    {
        $user = User::find($user_id);
        return array(
            'id' => $user->id,
            'name' => self::mark($user->getFullname(), $search),
            'url' => URLHelper::getURL("dispatch.php/profile", array('username' => $user->username)),
            'img' => Avatar::getAvatar($user->id)->getUrl(AVATAR::MEDIUM),
            'additional' => self::mark($user->username, $search)
        );
    }

    public function search_mycourse($search)
    {
        if (!$search) {
            return null;
        }
        $query = DBManager::get()->quote("%$search%");
        $user_id = DBManager::get()->quote(User::findCurrent()->id);
        $sql = "SELECT 'mycourses' as type, courses.seminar_id as id FROM seminare courses JOIN seminar_user USING (seminar_id) WHERE user_id = $user_id AND (courses.Name LIKE $query OR courses.VeranstaltungsNummer LIKE $query) ORDER BY start_time DESC";
        return $sql;
    }

    public function search_course($search)
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

    public function filter_course($course_id, $search)
    {
        $course = Course::find($course_id);
        return array(
            'id' => $course->id,
            'name' => self::mark($course->getFullname(), $search),
            'url' => URLHelper::getURL("dispatch.php/course/details", array('cid' => $course->id)),
            'img' => CourseAvatar::getAvatar($course->id)->getUrl(AVATAR::MEDIUM),
            'date' => $course->start_semester->name
        );
    }

}
