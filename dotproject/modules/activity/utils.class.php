<?php
/**
 *	@package dotProject
 *	@subpackage modules
 *	@version $Revision$
*/

class CUtils
{
  /**
  * Gets the start of the week based on date passed
  *
  * @param $date date to find start of week
  * @param $rollover_day day on which week starts.  1 for monday, 0 for sunday.
  * @return object Date the new Date object
  */
  public static function getStartOfWeek($date, $rollover_day) {
	  //Do not change value of input $date...
	  $date = clone $date;
	  
	  $today_weekday = $date->getDayOfWeek();
	  $new_start_offset = $rollover_day - (int)$today_weekday;

	  $date->addDays($new_start_offset);
	  return $date;
  }
  public static function getListDateOfTheWeek($time_set, $working_days) {
	  //Do not change value of input $time_set...
	  $date = clone $time_set;
	  $timestampArray = array();
	  
	  // get list of the timestamp for each working day
	  for ($day = 0; $day < 7; $day++) {
		  //Push date into working days if relevant
		  if(in_array($date->format("%w"), $working_days)) { 
			  array_push($timestampArray, $date->getTime());
		  }
		  $date->addDays(1);
	  }
	  return $timestampArray;
    }

  public static function getEndOfWeek($time_set) {
	  $working_days = explode(',', dPgetConfig('cal_working_days'));
	  return date('Ymd', end(self::getListDateOfTheWeek($time_set, $working_days)));
  }

  /**
  * Get public holidays for this year the previous year and the next year
  *
  */
  public static function getPublicHolidays()
  {

      $now = getDate();

      //Initilized with french public holidays: easter ..
      $list = array(
	date("Ymd", easter_date($now['year'] - 1)),
	date("Ymd", easter_date($now['year'] - 1) + 39*(24 * 3600)),
	date("Ymd", easter_date($now['year'] - 1) + 50*(24 * 3600)),
	date("Ymd", easter_date($now['year'])),
	date("Ymd", easter_date($now['year']) + 39*(24 * 3600)),
	date("Ymd", easter_date($now['year']) + 50*(24 * 3600)),
	date("Ymd", easter_date($now['year'] + 1)),
	date("Ymd", easter_date($now['year'] + 1) + 39*(24 * 3600)),
	date("Ymd", easter_date($now['year'] + 1) + 50*(24 * 3600))
      );
      
      //get others public holidays from file
      $arrayHolidays = file(dirname(__FILE__).'/../../misc/holidays/fr');

      //remove easter
      array_shift($arrayHolidays);

      $holifile = array();
      foreach ($arrayHolidays as $holidays)
      {
	preg_match('/(.*)\t(.*)\t(Bank)/', $holidays, $matches);
	if($matches[0])
	{
	  $strholi= substr($matches[0], 0, 5);
	  $strholiArray=explode('-', $strholi);
	  array_push($list, date("Ymd", mktime(0, 0, 0, $strholiArray[0], $strholiArray[1], $now['year'] - 1)));
	  array_push($list, date("Ymd", mktime(0, 0, 0, $strholiArray[0], $strholiArray[1], $now['year'])));
	  array_push($list, date("Ymd", mktime(0, 0, 0, $strholiArray[0], $strholiArray[1], $now['year'] + 1)));
	}
      }

      return $list;
  }

  /** Retrieve all assigned tasks for the user
  *
  *@param string $user_id
  *@return string $assignedTasks list of task_id
  **/

  public static function getAllAssignedTasks($user_id)
  {

    $q = new DBQuery;
    //retrieve all assigned tasks 
    $q->addTable('user_tasks', 'ut');
    $q->addQuery('ut.task_id');
    $q->addJoin('tasks', 't', 'ut.task_id = t.task_id');
    $q->addWhere("ut.user_id = $user_id");

    $psql = $q->prepare();
    $q->clear();

    $assignedTasksRows = db_exec($psql);

    while ($row = db_fetch_assoc($assignedTasksRows))
    {
	$assignedTasks .= $row['task_id'].',';
    }

    if (isset($assignedTasks))
    {
      //remove the coma
      $assignedTasks = rtrim($assignedTasks);
      $assignedTasks = substr($assignedTasks, 0, -1);

      return $assignedTasks;

    } else {
      return false;
    }
  }


  /** Retrieve assigned, active, and available tasks for the period
  *
  *@param timestamp $start_date
  *@param timestamp $end_date
  *@param string $tasksId list of task_id
  *@return rows object
  **/
  public static function getTasksForThisPeriod($tasksId, $start_date = null, $end_date = null)
  {

      if (!$tasksId) {
	return false;
      }
      $q = new DBQuery;

      $q->addTable('tasks');
      $q->addQuery('tasks.task_name, tasks.task_id, p.project_name');
      $q->addJoin('projects', 'p', 'tasks.task_project = p.project_id');
      $q->addWhere("tasks.task_id IN ($tasksId)");
      if($start_date != null){
      $q->addWhere("tasks.task_end_date >= '".date('Y-m-d 00:00:00', $start_date)."'" );
      }
      if($end_date != null){
      $q->addWhere("tasks.task_start_date <= '".date('Y-m-d 00:00:00', $end_date)."'" );
      }
      $q->addOrder('p.project_name');

      $psql = $q->prepare();
      $q->clear();

      $tasksRows = db_exec($psql);

      if($tasksRows->fields)
      {
	return $tasksRows;
      } else {
	return false;
      }
  }

  /* Reporting */

  /** Retrieve all projects where project leader is an owner
  *
  *@param int $userId
  *@return string $projectsId list of projectId
  **/
  public static function getProjectLeaderProjects($userId)
  {
    $q = new DBQuery;
    //retrieve all assigned tasks 
    $q->addTable('projects');
    $q->addQuery('project_id');
    $q->addWhere("project_owner = $userId");

    $psql = $q->prepare();
    $q->clear();

    $projectsRows = db_exec($psql);

    while ($row = db_fetch_assoc($projectsRows))
    {
	$projectsId .= $row['project_id'].',';
    }

    if (isset($projectsId))
    {
      //remove the coma
      $projectsId = rtrim($projectsId);
      $projectsId = substr($projectsId, 0, -1);

      return $projectsId;

    } else {
      return false;
    }
  }

  /** Determine if the project leader is the projectLeader of this user
  *
  *@param int $projectLeaderId
  *@param int $userId
  *@return bool
  **/
  public static function isProjectLeaderOfThisUser($projectLeaderTasksIdList, $userId)
  {

    if ($userTasksIdList= self::getAllAssignedTasks($userId))
    {

      $projectLeaderTasksIdList = explode(',', $projectLeaderTasksIdList);
      $userTasksIdList = explode(',', $userTasksIdList);
      if (array_intersect($userTasksIdList, $projectLeaderTasksIdList))
      {
	//return array_diff($userTasksIdList, $projectLeaderTasksIdList);
	return true;

      } else {
	return false;
      }
    } else {
      return false;
    }
  }


  /** Retrieve all projects leader tasks
  *
  *@param string $projectsId list of projectId
  *@return string $tasksId list of taskId
  **/
  public static function getProjectLeaderTasks($projectsId)
  {

    /* Retrieve all tasks of these projects */
    $q = new DBQuery;
    //retrieve all assigned tasks 
    $q->addTable('tasks');
    $q->addQuery('task_id');
    $q->addWhere("task_project IN ($projectsId)");

    $psql = $q->prepare();
    $q->clear();

    $taskRows = db_exec($psql);

    while ($row = db_fetch_assoc($taskRows))
    {
	$taskId .= $row['task_id'].',';
    }

    if (isset($taskId))
    {
      //remove the coma
      $taskId = rtrim($taskId);
      $taskId = substr($taskId, 0, -1);

      return $taskId;

    } else {
      return false;
    }
  }


  /** Retrieve all assigned users
  *
  *@param row object row Tasks
  *@return row object $rowUsers
  **/

  public static function getAllAssignedUsers($rowTasks)
  {
    while ($row = db_fetch_assoc($rowTasks))
    {
	$taskId .= $row['task_id'].',';
    }

    if (isset($taskId))
    {
      //remove the coma
      $taskId = rtrim($taskId);
      $taskId = substr($taskId, 0, -1);
    } else { 
      return false;
    }

    $q = new DBQuery;
    //retrieve all assigned user
    $q->addTable('user_tasks');
    $q->addQuery('DISTINCT u.user_id, u.user_username, c.contact_first_name, c.contact_last_name');
    $q->addJoin('users', 'u', 'user_tasks.user_id = u.user_id');
    $q->addJoin('contacts', 'c', 'user_tasks.user_id = c.contact_id');
    $q->addWhere("user_tasks.task_id IN ($taskId)");

    $psql = $q->prepare();
    $q->clear();

    $rowUsers = db_exec($psql);

    if($rowUsers->fields)
    {
      return $rowUsers;
    } else {
      return false;
    }
  
  }


  /** get activities duration
  *
  *@param int $userId
  *@param int $taskId
  *@param timestamp $start_date
  *@param timestamp $end_date
  *@return int $duration
  **/
  public static function getActivitiesDuration($userId, $taskId, $start_date, $end_date, $tasksId = null)
  {
    $q = new DBQuery;
    //retrieve all assigned tasks 
    $q->addTable('activity');
    $q->addQuery('duration');
    if($userId != null){
      $q->addWhere("user_id = $userId");
    }
    if($taskId != null){
      $q->addWhere("task_id = $taskId");
    }
    if($tasksId != null){
      $q->addWhere("task_id IN(". $tasksId .")");
    }
    if($start_date != null){
      $q->addWhere("date >= '".date('Y-m-d', $start_date)."'" );
    }
    if($end_date != null){
      $q->addWhere("date <= '".date('Y-m-d', $end_date)."'" );
    }

    $psql = $q->prepare();
    $q->clear();

    $durationActivityRows = db_exec($psql);

    while ($row = db_fetch_assoc($durationActivityRows))
    {
	$duration += $row['duration'].',';
    }

    if (isset($duration))
    {
      return $duration/2;
    } else {
      return 0;
    }
  }

  /** get user firstname and lastname
  *
  *@param int $userId
  *@return array $names
  **/
  public static function getUserNames($userId)
  {
    $q = new DBQuery;
    //retrieve all assigned tasks 
    $q->addTable('users');
    $q->addQuery('c.contact_first_name, c.contact_last_name');
    $q->addJoin('contacts', 'c', 'users.user_contact = c.contact_id');
    $q->addWhere("users.user_id = $userId");


    $psql = $q->prepare();
    $q->clear();

    $contactRows = db_exec($psql);

    $names = array();
    while ($row = db_fetch_assoc($contactRows))
    {
	array_push($names, $row['contact_first_name']);
	array_push($names, $row['contact_last_name']);
    }

    if (isset($names))
    {
      return $names;
    } else {
      return false;
    }
  }
}
?>
