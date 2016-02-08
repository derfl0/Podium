<?php
/**
 * PodiumModule for user
 */
class PodiumUser implements PodiumModule
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId()
    {
        return 'user';
    }

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName()
    {
        return _('Benutzer');
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

        // if you're no admin respect visibilty
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $visQuery = get_vis_query('user', 'search') . " AND ";
        }
        $query = DBManager::get()->quote("%$search%");
        $sql = "SELECT user.user_id, user.vorname, user.nachname, user.username  FROM auth_user_md5 user LEFT JOIN user_visibility USING (user_id) WHERE $visQuery (CONCAT_WS(' ', user.nachname, user.vorname) LIKE $query OR  CONCAT_WS(' ', user.vorname, user.nachname) LIKE $query OR username LIKE $query) LIMIT ".Podium::MAX_RESULT_OF_TYPE;
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
        $user = User::buildExisting($data);
        $result = array(
            'id' => $user->id,
            'name' => Podium::mark($user->getFullname(), $search),
            'url' => URLHelper::getURL("dispatch.php/profile", array('username' => $user->username)),
            'additional' => Podium::mark($user->username, $search),
            'expand' => URLHelper::getURL("browse.php", array('name' => $search)),
        );
        $avatar = Avatar::getAvatar($user->id);
        if (Podium::SHOW_ALL_AVATARS || $avatar->is_customized()) {
            $result['img'] = $avatar->getUrl(AVATAR::MEDIUM);
        }
        return $result;
    }
}