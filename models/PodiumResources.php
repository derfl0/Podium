<?php
/**
 * PodiumModule for resources
 */
class PodiumResources implements PodiumModule
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId()
    {
        return "resources";
    }

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName()
    {
        return _('Ressourcen');
    }

    public static function getPodiumSearch($search)
    {
        if (!$search || !$GLOBALS['perm']->have_perm('admin')) {
            return null;
        }
        $query = DBManager::get()->quote("%$search%");
        return "SELECT resource_id,name,description FROM resources_objects WHERE name LIKE $query OR description LIKE $query OR REPLACE(name, ' ', '') LIKE $query OR REPLACE(description, ' ', '') LIKE $query";
    }

    public static function podiumFilter($res, $search)
    {
        return array(
            'name' => Podium::mark($res['name'], $search),
            'url' => URLHelper::getURL("resources.php", array('view' => 'view_schedule', 'show_object' => $res['resource_id'])),
            'additional' => Podium::mark($res['description'], $search),
            'expand' => URLHelper::getURL('resources.php', array('view' => 'search', 'search_exp' => $search, 'start_search' => ''))
        );
    }
}