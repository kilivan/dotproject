<?php
/**
 *	@package dotProject
 *	@subpackage modules
 *	@version $Revision$
*/
require_once ("activity.class.php");
require_once ($AppUI->getLibraryClass('PEAR/Date'));
require_once ($AppUI->getModuleClass('tasks'));

class CTimesheet
{
    private $_timesheetId = 0;
    private $_userId;
    private $_timeSet;

    private $_activitiesList = array();


    public function __construct($userId, $timeSet)
    {
      $this->_userId = $userId;
      $this->_timeSet = $timeSet;
    }

    public function getTimesheetId()
    {
      return $this->_timesheetId;
    }


    public function getActivities()
    {
      return $this->_activitiesList;
    }
    

    public function load()
    {
      $q = new DBQuery;
      $q->addTable('timesheet');
      $q->addQuery('timesheet_id');
      $q->addWhere('timeset = '.$this->_timeSet);
      $q->addWhere('user_id = '.$this->_userId);

      $psql = $q->prepare();
      $q->clear();

      $rowTimesheet = db_exec($psql);

      if ($rowTimesheet->fields)
      {
	$row = db_fetch_assoc($rowTimesheet);

	$this->_timesheetId = $row['timesheet_id'];

	$this->_loadActivitiesList();

	return true;
      } else {
	return false;
      }
    }

    /**
    * Load activities object List
    * @return bool
    */
    private function _loadActivitiesList()
    {  
      $q = new DBQuery;
      $q->addTable('activity');
      $q->addQuery('activity_id');
      $q->addWhere('activity_timesheet = '.$this->_timesheetId);

      $psql = $q->prepare();
      $q->clear();

      $rowActivities = db_exec($psql);

      if ($rowActivities->fields)
      {
	while ($row = db_fetch_assoc($rowActivities)) 
	{
	  $activityObj = new CActivity($row['activity_id']);
	  $activityObj->load();
	  array_push($this->_activitiesList, $activityObj);
	}
      }
    }

    public function save()
    {
      $q = new DBQuery;

      $q->addTable('timesheet');
      $q->addInsert('user_id', $this->_userId);
      $q->addInsert('timeset', $this->_timeSet);

      if (!$q->exec()) 
      {
	$q->clear();
	return $db->ErrorMsg();
      }
      return mysql_insert_id();
    }

    public function getStatus($taskId)
    {
      $q = new DBQuery;

      $q->addTable('task_timesheet');
      $q->addQuery('status');
      $q->addWhere('task_id ='. $taskId);
      $q->addWhere('timesheet_id ='. $this->_timesheetId);

      $psql = $q->prepare();
      $q->clear();

      $rowStatus = db_exec($psql);

      if ($rowStatus->fields)
      {
	$row = db_fetch_assoc($rowStatus);
	return $row['status'];	
      } else {
	return -1;
      }
    }

    /**
    * Update or save all timesheet's activities
    * @param int $userId
    * @param array $dataActivities
    * @param int $timesheetId
    * @param array $projectLeaderTasksIdList (optionnal)
    * @return bool
    */
    public static function saveActivities($userId, $dataActivities, $timesheetId)
    {
      
      $q = new DBQuery;

      $q->addTable('task_timesheet');
      $q->addQuery('task_id');    
      $q->addWhere('timesheet_id =' . $timesheetId);
      $q->addWhere('status = 0');

      //die($q->prepare());
      $psql = $q->prepare();
      $q->clear();

      $taskIdRows = db_exec($psql);
      
      $validatedTaskId = array();
      while ($row = db_fetch_assoc($taskIdRows))
      {
	  array_push($validatedTaskId, $row['task_id']);
      }


      //create activities where duration > 0
      foreach($dataActivities as $dataActivity)
      {
	  $dataActivityArray = explode('|', $dataActivity);
	
	  /* $dataActivityArray[1]: TaskId
	    $dataActivityArray[2]; date */
	  $activity = new CActivity();
	  $activity->setDuration($dataActivityArray[0]);
	  $activity->setTaskId($dataActivityArray[1]);
	  $activity->setADate($dataActivityArray[2]);
	  $activity->setUserId($userId);
	  $activity->setTimesheetId($timesheetId);
	  
	  //check if the task isn't already validate
	  if(!in_array($dataActivityArray[1], $validatedTaskId))
	  {
	    //if the activity exist
	    if($activity->loadActivityId())
	    {
	      $activity->update();
	    } else {
	      if ($dataActivityArray[0] > 0)
	      {
		$activity->save();
	      }
	    }
	  }
      }

      return true;   
    }

    /**
    * update TaskList
    * @param int $timesheetId
    * @return bool
    */
    public static function updateTaskTimesheet($timesheetId, $newTaskIdList)
    {
      $newTaskIdList = explode(',', $newTaskIdList);

      $q = new DBQuery;

      $q->addTable('task_timesheet');
      $q->addQuery('task_id');
      $q->addWhere('timesheet_id =' . $timesheetId);
     
      $psql = $q->prepare();
      $q->clear();

      $oldTaskIdList = db_exec($psql);

      $oldTaskId = array();
      if ($oldTaskIdList->fields)
      {
	while ($rowOldTaskIdList = db_fetch_assoc($oldTaskIdList))
	{
	   array_push($oldTaskId, $rowOldTaskIdList['task_id']);
	}
      }

      //Retrieve changes, tasks to Add and tasks to Remove
      $tasksToAdd = array_diff($newTaskIdList, $oldTaskId);
      $tasksToRemove = array_diff($oldTaskId, $newTaskIdList);

      if($tasksToAdd)
      {
	foreach($tasksToAdd as $taskToAdd)
	{
	  $q->addTable('task_timesheet');
	  $q->addInsert('task_id', $taskToAdd);
	  $q->addInsert('timesheet_id', $timesheetId);
	  $q->addInsert('status', 1);

	  if (!$q->exec()) 
	  {
	    $q->clear();
	    return $db->ErrorMsg();
	  }
	}
      }
	

      if($tasksToRemove)
      {
	//delete old tasks
	$tasksToRemove = implode(',', $tasksToRemove);

	$q = new DBQuery;
	$q->setDelete('task_timesheet');
	$q->addWhere('task_id IN (' . $tasksToRemove . ')');

	if (!$q->exec()) 
	{
	  $q->clear();
	  return $db->ErrorMsg();
	}
	
      }

      return true;   
    }


    /**
    * Validate timesheet
    * @param int $timesheetId
    * @return bool
    */
    public static function validateTimesheet($timesheetId, $taskIdList)
    {
      $q = new DBQuery;

      $q->addTable('task_timesheet');
      $q->addUpdate('status',0);
      $q->addWhere('timesheet_id =' . $timesheetId);
      $q->addWhere('task_id IN(' . $taskIdList . ')');
   
      if (!$q->exec()) 
      {
	$q->clear();
	return $db->ErrorMsg();
      }

      return true;   
    }

    /**
    * deValidate timesheet
    * @param int $timesheetId
    * @return bool
    */
    public static function devalidateTimesheet($timesheetId, $taskIdList)
    {
      $q = new DBQuery;

      $q->addTable('task_timesheet');
      $q->addUpdate('status',1);
      $q->addWhere('timesheet_id =' . $timesheetId);
      $q->addWhere('task_id IN(' . $taskIdList . ')');
    
      if (!$q->exec()) 
      {
	$q->clear();
	return $db->ErrorMsg();
      }
    
      return true;   
    }

    public function isValidate()
    {
      $q = new DBQuery;

      $q->addTable('task_timesheet');
      $q->addQuery('status');
      $q->addWhere('timesheet_id =' . $this->_timesheetId);
     
      $psql = $q->prepare();
      $q->clear();

      $statusList = db_exec($psql);

      if ($statusList->fields)
      {
	while ($rowStatusList = db_fetch_assoc($statusList))
	{
	   if ($rowStatusList['status'] == 1)
	   {
	      return false;
	   }
	}
      }
      return true;
    }

    public function isValidateForTheProjectLeader($projectLeaderTasksIdList)
    {
      $q = new DBQuery;

      $q->addTable('task_timesheet');
      $q->addQuery('status');
      $q->addWhere('timesheet_id =' . $this->_timesheetId);
      $q->addWhere('task_id IN(' . $projectLeaderTasksIdList . ')');
     
      $psql = $q->prepare();
      $q->clear();

      $statusList = db_exec($psql);

      if ($statusList->fields)
      {
	while ($rowStatusList = db_fetch_assoc($statusList))
	{
	   if ($rowStatusList['status'] == 1)
	   {
	      return false;
	   }
	}
      }
      return true;
    }

    
}
?>