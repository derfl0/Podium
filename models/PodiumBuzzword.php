<?php

/**
 * Created by PhpStorm.
 * User: intelec
 * Date: 10.12.15
 * Time: 14:12
 */
class PodiumBuzzword extends SimpleORMap implements PodiumModule
{
    protected static function configure($config = array()) {
        $config['db_table'] = 'podium_buzzwords';
        $config['additional_fields']['rightsname'] = true;
        parent::configure($config);
    }

    public function getRightsname() {
        return array_search($this->rights, $GLOBALS['perm']->permissions);
    }

    public static function getPodiumId() {
        return "buzzword";
    }

    public static function getPodiumName() {
        return _('Stichwörter');
    }

    public static function getPodiumSearch($search) {
        if (!$search) {
            return null;
        }

        $query = DBManager::get()->quote("%$search%");
        $rights = $GLOBALS['perm']->permissions[$GLOBALS['perm']->get_perm()];
        return "SELECT 'buzzword' as type, buzz_id as id FROM podium_buzzwords WHERE buzzwords LIKE $query AND $rights >= rights";
    }

    public static function podiumFilter($buzz_id, $search)
    {
        $buzz = DBManager::get()->fetchOne("SELECT * FROM podium_buzzwords WHERE buzz_id = ?", array($buzz_id));
        return array(
            'name' => htmlReady($buzz['name']),
            'url' => $buzz['url'],
            'additional' => $buzz['subtitle']
        );
    }
}