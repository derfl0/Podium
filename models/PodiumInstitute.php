<?php
/**
 * PodiumModule for institutes
 */
class PodiumInstitute implements PodiumModule
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId()
    {
        return 'inst';
    }

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName()
    {
        return _('Einrichtungen');
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
        $sql = "SELECT * FROM Institute WHERE Name LIKE $query ORDER BY name DESC";
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
    public static function podiumFilter($inst_id, $search)
    {
        $inst = Institute::buildExisting($inst_id);
        $result = array(
            'id' => $inst->id,
            'name' => Podium::mark($inst->getFullname(), $search),
            'url' => URLHelper::getURL("dispatch.php/institute/overview", array('cid' => $inst->id)),
            'expand' => URLHelper::getURL('institut_browse.php', array('cmd' => 'suche', 'search_name' => $search))
        );
        $avatar = InstituteAvatar::getAvatar($inst->id);
        if (Podium::SHOW_ALL_AVATARS || $avatar->is_customized()) {
            $result['img'] = $avatar->getUrl(AVATAR::MEDIUM);
        }
        return $result;
    }
}