<?php
/**
 * PodiumModule for courses
 */
class PodiumCourse implements PodiumModule,PodiumFulltext
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId()
    {
        return 'courses';
    }

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName()
    {
        return _('Veranstaltungen');
    }

    /**
     * Transforms the search request into an sql statement, that provides the id (same as getPodiumId) as type and
     * the object id, that is later passed to the podiumfilter.
     *
     * This function is required to make use of the mysql union parallelism
     *
     * @param $search the input query string
     * @return String SQL Query to discover elements for the search
     */
    public static function getPodiumSearch($search)
    {
        if (!$search) {
            return null;
        }
        $search = str_replace(" ", "% ", $search);
        $query = DBManager::get()->quote("%$search%");

        // visibility
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $visibility = "courses.visible = 1 AND ";
            $seminaruser = " AND NOT EXISTS (SELECT 1 FROM seminar_user WHERE seminar_id = courses.Seminar_id AND user_id = ".DBManager::get()->quote(User::findCurrent()->id).") ";
        }

        $sql = "SELECT courses.Seminar_id,courses.start_time,courses.name,courses.veranstaltungsnummer,courses.status FROM seminare courses JOIN sem_types ON (courses.status = sem_types.id) WHERE $visibility(courses.Name LIKE $query OR courses.VeranstaltungsNummer LIKE $query OR CONCAT_WS(' ', sem_types.name,courses.Name, sem_types.name) LIKE $query) $seminaruser ORDER BY ABS(start_time - unix_timestamp()) ASC LIMIT ".Podium::MAX_RESULT_OF_TYPE;
        return $sql;
    }

    /**
     * Returns an array of information for the found element. Following informations (key: description) are nessesary
     *
     * - name: The name of the object
     * - url: The url to send the user to when he clicks the link
     *
     * Additional informations are:
     *
     * - additional: Subtitle for the hit
     * - expand: Url if the user further expands the search
     * - img: Avatar for the
     *
     * @param $id
     * @param $search
     * @return mixed
     */
    public static function podiumFilter($data, $search)
    {
        $course = Course::buildExisting($data);
        $result = array(
            'id' => $course->id,
            'name' => Podium::mark($course->getFullname(), $search),
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
        if (Podium::SHOW_ALL_AVATARS || $avatar->is_customized()) {
            $result['img'] = $avatar->getUrl(AVATAR::MEDIUM);
        }
        return $result;
    }

    public static function enable()
    {
        DBManager::get()->exec("ALTER TABLE seminare ADD FULLTEXT INDEX podium (VeranstaltungsNummer, Name)");
        DBManager::get()->exec("ALTER TABLE sem_types ADD FULLTEXT INDEX podium (name)");
    }

    public static function disable()
    {
        DBManager::get()->exec("DROP INDEX podium ON seminare");
        DBManager::get()->exec("DROP INDEX podium ON sem_types");
    }

    public static function getFulltextSearch($search)
    {
        if (!$search) {
            return null;
        }

        $query = DBManager::get()->quote(preg_replace("/(\w+)[*]*\s?/", "+$1* ", $search));

        // visibility
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $visibility = "courses.visible = 1 AND ";
            $seminaruser = " AND NOT EXISTS (SELECT 1 FROM seminar_user WHERE seminar_id = courses.Seminar_id AND user_id = ".DBManager::get()->quote(User::findCurrent()->id).") ";
        }

        $semtype = DBManager::get()->query("SELECT id,name FROM sem_types WHERE MATCH (name) AGAINST ($query IN BOOLEAN MODE)");
        while ($type = $semtype->fetch(PDO::FETCH_ASSOC)) {

            $semtypes[] = $type['id'];
            // Get up some order criteria with the semtypes
            // Remove semtypes form query
                $replace = "/".$type['name'][0].chunk_split(substr($type['name'], 1), 1, '?')."\*\s?/i";
                $query = preg_replace($replace, "", $query);
        }

        if (isset($semtypes)) {
            $semstatus = "status IN (".join(",",$semtypes) .") DESC, ";
        }

        $sql = "SELECT courses.Seminar_id,courses.start_time,courses.name,courses.veranstaltungsnummer,courses.status
FROM seminare courses
WHERE MATCH(VeranstaltungsNummer, Name) AGAINST ($query IN BOOLEAN MODE)
ORDER BY $semstatus ABS(start_time - unix_timestamp()) ASC, MATCH(VeranstaltungsNummer, Name) AGAINST ($query IN BOOLEAN MODE) DESC LIMIT ".Podium::MAX_RESULT_OF_TYPE;
        return $sql;
    }
}