<?php

/**
 * PodiumModule for resources
 */
class PodiumNavigation implements PodiumModule
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId()
    {
        return 'navigation';
    }

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName()
    {
        return _('Navigation');
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

        $result = array();
        $start = Navigation::getItem('/');
        foreach ($start->getSubNavigation() as $index => $sub) {
            self::search_nav_recursive($sub, '', $index, $search, $result);
        }

        if (!$result) {
            return null;
        }
        return "SELECT type,id FROM (" . join(' UNION ', $result) . ") as navtable";
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
    public static function podiumFilter($nav_path, $search)
    {
        $nav = Navigation::getItem($nav_path);
        return array(
            'name' => Podium::mark($nav->getTitle(), $search),
            'url' => $nav->getUrl(),
            'additional' => $nav_path
        );
    }

    private static function search_nav_recursive(Navigation $navigation, $path, $index, $search, &$result)
    {
        $fullpath = $path . '/' . $index;
        if (mb_strpos($navigation->getTitle(), $search) !== false) {
            $quotedPath = DBManager::get()->quote($fullpath);
            $result[] = "(SELECT 'navigation' as type, $quotedPath as id)";
        }
        foreach ($navigation->getSubNavigation() as $newindex => $sub) {
            self::search_nav_recursive($sub, $fullpath, $newindex, $search, $result);
        }
    }
}