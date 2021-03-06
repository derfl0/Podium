<?php
/**
 * PodiumModule for courses
 */
class PodiumForum implements PodiumModule,PodiumFulltext
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId()
    {
        return 'forum';
    }

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName()
    {
        return _('Forenbeiträge');
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
        $search = str_replace(" ", "% ", $search);
        $query = DBManager::get()->quote("%$search%");

        // visibility
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $seminaruser = " AND EXISTS (SELECT 1 FROM seminar_user WHERE forum_entries.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = ".DBManager::get()->quote(User::findCurrent()->id).") ";
        }

        $sql = "SELECT forum_entries.* FROM forum_entries WHERE (name LIKE $query OR content LIKE $query) $seminaruser ORDER BY chdate DESC LIMIT ".Podium::MAX_RESULT_OF_TYPE;
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
        $user = User::find($data['user_id']);
        $course = Course::find($data['seminar_id']);
        $result = array(
            'id' => $data['topic_id'],
            'name' => $data['name'] ? Podium::mark($data['name'], $search)  : ($course ? htmlReady($course->getFullname()) : _('Ohne Titel')),
            'url' => URLHelper::getURL("plugins.php/coreforum/index/index/" . $data['topic_id']."#".$data['topic_id'], array('cid' => $data['seminar_id'])),
            'date' => strftime('%x %X', $data['chdate']),
            'description' => Podium::mark($data['content'], $search, true),
            'additional' => htmlReady((($user && !$data['anonymous']) ? $user->getFullname() : _('Anonym'))." "._('in')." ".($course ? $course->getFullname() : '')),
            'expand' => URLHelper::getURL("plugins.php/coreforum/index/search", array(
                'cid' => $data['seminar_id'],
                'backend' => 'search',
                'searchfor' => $search,
                'search_title' => 1,
                'search_content' => 1,
                'search_author' => 1
            ))
        );
        return $result;
    }

    public static function enable()
    {
        DBManager::get()->exec("ALTER TABLE forum_entries ADD FULLTEXT INDEX podium (name, content)");
    }

    public static function disable()
    {
        DBManager::get()->exec("DROP INDEX podium ON forum_entries");
    }

    public static function getFulltextSearch($search)
    {
        $search = str_replace(" ", "% ", $search);
        $query = DBManager::get()->quote(preg_replace("/(\w+)[*]*\s?/", "+$1* ", $search));
        $words = substr(preg_replace("/\W*(\w+)\W*/", "$1|", $search), 0, -1);
        $quoteRegex = 'content REGEXP "[[]quote=.*['.$words.'].*[]]|[<]admin_msg autor=.*[.'.$words.'.].*[>]" ASC, ';

        // visibility
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $seminaruser = " AND EXISTS (SELECT 1 FROM seminar_user WHERE forum_entries.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = ".DBManager::get()->quote(User::findCurrent()->id).") ";
        }

        $sql = "SELECT forum_entries.* FROM forum_entries WHERE MATCH(name, content) AGAINST($query IN BOOLEAN MODE) $seminaruser ORDER BY $quoteRegex chdate DESC LIMIT ".Podium::MAX_RESULT_OF_TYPE;
        return $sql;
    }
}