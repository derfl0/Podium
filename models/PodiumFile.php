<?php
/**
 * PodiumModule for files
 */
class PodiumFile implements PodiumModule,PodiumFulltext
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId()
    {
        return 'file';
    }

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName()
    {
        return _('Datei');
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
        // Filter for own courses
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $user = DBManager::get()->quote(User::findCurrent()->id);
            $ownseminars = "JOIN seminar_user ON (dokumente.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = $user) ";
        }

        // Now check if we got a seminar
        if (strpos($search, '/') !== FALSE) {
            $args = explode('/', $search);
            $prequery = DBManager::get()->quote("%" . trim($args[0]) . "%");
            $query = DBManager::get()->quote("%" . trim($args[1]) . "%");
            $binary = DBManager::get()->quote('%' . join('%', str_split(strtoupper(trim($args[0])))) . '%');
            $comp = "AND";
            return "SELECT dokumente.* FROM dokumente "
            . "JOIN seminare USING (seminar_id) $ownseminars "
            . "WHERE (seminare.name LIKE BINARY $binary OR seminare.name LIKE $prequery ) "
            . "$comp dokumente.name LIKE $query "
            . "ORDER BY dokumente.chdate DESC LIMIT ".(2*Podium::MAX_RESULT_OF_TYPE * 2);
        } else {
            $query = DBManager::get()->quote("%$search%");
            return "SELECT dokumente.* FROM dokumente IGNORE INDEX (chdate) "
            . " $ownseminars "
            . "WHERE dokumente.name LIKE $query "
            . "ORDER BY dokumente.chdate DESC LIMIT ".(Podium::MAX_RESULT_OF_TYPE * 2);
        }
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
    public static function podiumFilter($file_id, $search)
    {
        $file = StudipDocument::buildExisting($file_id);
        if ($file->checkAccess(User::findCurrent()->id)) {
            return array(
                'id' => $file->id,
                'name' => Podium::mark($file->name, $search),
                'url' => URLHelper::getURL("sendfile.php?type=0&file_id={$file->id}&file_name={$file->filename}"),
                'additional' => Podium::mark($file->course ? $file->course->getFullname() : '', $search, false),
                'date' => strftime('%x', $file->chdate),
                'expand' => URLHelper::getURL("folder.php", array("cid" => $file->seminar_id, "cmd" => "tree"))
            );
        }
    }

    public static function enable()
    {
        DBManager::get()->exec("ALTER TABLE dokumente ADD FULLTEXT INDEX podium (name)");
    }

    public static function disable()
    {
        DBManager::get()->exec("DROP INDEX podium ON dokumente");
    }

    public static function getFulltextSearch($search)
    {
        // Filter for own courses
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $user = DBManager::get()->quote(User::findCurrent()->id);
            $ownseminars = "JOIN seminar_user ON (dokumente.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = $user) ";
        }

        // Now check if we got a seminar
        if (strpos($search, '/') !== FALSE) {
            $args = explode('/', $search);
            $prequery = DBManager::get()->quote("%" . trim($args[0]) . "%");
            $query = DBManager::get()->quote("%" . trim($args[1]) . "%");
            $binary = DBManager::get()->quote('%' . join('%', str_split(strtoupper(trim($args[0])))) . '%');
            $comp = "AND";
            return "SELECT dokumente.* FROM dokumente "
            . "JOIN seminare USING (seminar_id) $ownseminars "
            . "WHERE (seminare.name LIKE BINARY $binary OR seminare.name LIKE $prequery ) "
            . "$comp dokumente.name LIKE $query "
            . "ORDER BY dokumente.chdate DESC LIMIT ".(2*Podium::MAX_RESULT_OF_TYPE * 2);
        } else {
            $query = DBManager::get()->quote(preg_replace("/(\w+)[*]*\s?/", "+$1* ", $search));
            return "SELECT dokumente.* FROM dokumente IGNORE INDEX (chdate) "
            . " $ownseminars "
            . "WHERE MATCH(dokumente.name) AGAINST($query IN BOOLEAN MODE) "
            . "ORDER BY dokumente.chdate DESC LIMIT ".(Podium::MAX_RESULT_OF_TYPE * 2);
        }
    }
}