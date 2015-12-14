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
        return "SELECT 'resources' as type, resource_id as id FROM resources_objects WHERE name LIKE $query OR description LIKE $query OR REPLACE(name, ' ', '') LIKE $query OR REPLACE(description, ' ', '') LIKE $query";
    }

    public static function podiumFilter($resource_id, $search)
    {
        $res = DBManager::get()->fetchOne("SELECT name,description FROM resources_objects WHERE resource_id = ?", array($resource_id));
        return array(
            'name' => Podium::mark($res['name'], $search),
            'url' => URLHelper::getURL("resources.php", array('view' => 'view_schedule', 'show_object' => $resource_id)),
            'additional' => Podium::mark($res['description'], $search),
            'expand' => URLHelper::getURL('resources.php', array('view' => 'search', 'search_exp' => $search, 'start_search' => ''))
        );
    }
}