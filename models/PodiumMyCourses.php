<?php
/**
 * PodiumModule for my courses
 */
class PodiumMyCourses implements PodiumModule
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId()
    {
        return 'mycourses';
    }

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName()
    {
        return _('Meine Veranstaltungen');
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
        $user_id = DBManager::get()->quote(User::findCurrent()->id);
        $sql = "SELECT courses.* FROM seminare courses JOIN seminar_user USING (seminar_id) JOIN sem_types ON (courses.status = sem_types.id)  WHERE user_id = $user_id AND (courses.Name LIKE $query OR courses.VeranstaltungsNummer LIKE $query OR CONCAT_WS(' ', sem_types.name,courses.Name) LIKE $query) ORDER BY start_time DESC";
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
    public static function podiumFilter($course_id, $search)
    {
        $course = Course::buildExisting($course_id);
        $result = array(
            'id' => $course->id,
            'name' => Podium::mark($course->getFullname(), $search),
            'url' => URLHelper::getURL("dispatch.php/course/details/index/" . $course->id),
            'date' => $course->start_semester->name,
            'expand' => URLHelper::getURL("dispatch.php/search/courses", array(
                'reset_all' => 1,
                'search_sem_qs_choose' => 'title_lecturer_number',
                'search_sem_sem' => 'all',
                'search_sem_quick_search_parameter' => $search,
                'search_sem_1508068a50572e5faff81c27f7b3a72f' => 1 // Fuck you Stud.IP
            ))
        );
        $avatar = CourseAvatar::getAvatar($course->id);
        if (Podium::SHOW_ALL_AVATARS || $avatar->is_customized()) {
            $result['img'] = $avatar->getUrl(AVATAR::MEDIUM);
        }
        return $result;
    }
}