<?php
/**
 * PodiumModule for calendar
 */
class PodiumCalendar implements PodiumModule
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId()
    {
        return 'calendar';
    }

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName()
    {
        return _('Termine');
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
        $time = strtotime($search);
        $endtime = $time + 86400;
        $user_id = DBManager::get()->quote(User::findCurrent()->id);
        if ($time) {
            return "SELECT 'calendar' as type, termin_id as id FROM termine JOIN seminar_user ON (range_id = seminar_id) WHERE user_id = $user_id AND date BETWEEN $time AND $endtime ORDER BY date";
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
    public static function podiumFilter($termin_id, $search)
    {
        $termin = DBManager::get()->fetchOne("SELECT name,date,end_time,seminar_id FROM termine JOIN seminare ON (range_id = seminar_id) WHERE termin_id = ?", array($termin_id));
        return array(
            'name' => $termin['name'],
            'url' => URLHelper::getURL("dispatch.php/course/details", array('cid' => $termin['seminar_id'])),
            'additional' => strftime('%H:%M', $termin['date']) . " - " . strftime('%H:%M', $termin['end_time']) . ", " . strftime('%x', $termin['date']),
            'expand' => URLHelper::getURL('calendar.php', array('cmd' => 'showweek', 'atime' => strtotime($search)))
        );
    }
}