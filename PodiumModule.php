<?php

/**
 * Interface PodiumModule
 *
 * Module for Podium extension
 */
interface PodiumModule
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId();

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName();

    /**
     * Has to return a SQL Query that discovers all objects. All retrieved data is passed row by row to getPodiumFilter
     *
     * @param $search the input query string
     * @return String SQL Query to discover elements for the search
     */
    public static function getPodiumSearch($search);

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
     * @param $data One row returned from getPodiumSearch SQL Query
     * @param $search The searchstring (Use for markup e.g. Podium::mark)
     * @return mixed Information Array
     */
    public static function podiumFilter($data, $search);
}