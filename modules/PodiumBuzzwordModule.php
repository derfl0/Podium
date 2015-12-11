<?php

/**
 * Created by PhpStorm.
 * User: intelec
 * Date: 11.12.15
 * Time: 11:48
 */
class PodiumBuzzwordModule extends PodiumModule
{

    public static function getName() {
        return _('Stichwörter');
    }

    public static function search($search) {
        if (!$search) {
            return null;
        }

        $query = DBManager::get()->quote("%$search%");
        $rights = $GLOBALS['perm']->permissions[$GLOBALS['perm']->get_perm()];
        return "SELECT 'PodiumBuzzwordModule' as type, buzz_id as id FROM podium_buzzwords WHERE buzzwords LIKE $query AND $rights >= rights";
    }

    public static function filter($buzz_id, $search)
    {
        $buzz = DBManager::get()->fetchOne("SELECT * FROM podium_buzzwords WHERE buzz_id = ?", array($buzz_id));
        return array(
            'name' => htmlReady($buzz['name']),
            'url' => $buzz['url'],
            'additional' => $buzz['subtitle']
        );
    }
}