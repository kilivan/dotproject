<?php
/**
 *	@package dotProject
 *	@subpackage modules
 *	@version $Revision$
*/

class CActivity
{
    private $_activityId;
    private $_userId;
    private $_taskId;
    private $_date;
    private $_duration = 0;
    private $_timesheetId;

    /**
    * Constructor
    * @param int $activityId
    */
    public function __construct($activityId = 0)
    {
      $this->_activityId = $activityId;
    }

    public function getActivityId()
    {
      return $this->_activityId;
    }

    public function getTaskId()
    {
      return $this->_taskId;
    }

    public function getADate()
    {
      return str_replace('-', '', $this->_date);
    }

    public function getDuration()
    {
      return $this->_duration;
    }


    public function setTaskId($taskId)
    {
      $this->_taskId = $taskId;
    }

    public function setADate($date)
    {
      $this->_date = $date;
    }

    public function setDuration($duration)
    {
      $this->_duration = $duration;
    }

    public function setTimesheetId($timesheetId)
    {
      $this->_timesheetId = $timesheetId;
    }

    public function setUserId($userId)
    {
      $this->_userId = $userId;
    }

    /**
    * Load data from activity table: task_id, date, duration
    * @return bool
    */
    public function load()
    {
      $q = new DBQuery;
      $q->addTable('activity');
      $q->addQuery('task_id, date, duration');
      $q->addWhere('activity_id = '.$this->_activityId);

      $psql = $q->prepare();
      $q->clear();

      $rowActivity = db_exec($psql);

      if ($rowActivity->fields)
      {
	$dataActivity = db_fetch_assoc($rowActivity);
	$this->_taskId = $dataActivity['task_id'];
	$this->_date = $dataActivity['date'];
	$this->_duration = $dataActivity['duration'];
	return true;
      } else {
	return false;
      }
    }

    /**
    * Load activityId from activity table
    * @return bool
    */
    public function loadActivityId()
    {
      $q = new DBQuery;
      $q->addTable('activity');
      $q->addQuery('activity_id');
      $q->addWhere('task_id = '.$this->_taskId);
      $q->addWhere('user_id = '.$this->_userId);
      $q->addWhere('date = '.$this->_date);

      $psql = $q->prepare();
      $q->clear();

      $rowActivity = db_exec($psql);

      if ($rowActivity->fields)
      {
	$dataActivity = db_fetch_assoc($rowActivity);
	$this->_activityId = $dataActivity['activity_id'];
	return true;
      } else {
	return false;
      }
    }

    /**
    * update activity table
    * @return bool
    */
    public function update()
    {
      $q = new DBQuery;
      if($this->_duration > 0)
      {
	$q->addTable('activity');
	$q->addUpdate('duration', $this->_duration);
      } else {
	$q->setDelete('activity');
      }
      $q->addWhere('activity_id = '.$this->_activityId);
      if (!$q->exec()) 
      {
	$q->clear();
	return $db->ErrorMsg();
      }

      return true;
    }

    /**
    * save activity
    * @return bool
    */
    public function save()
    {
      $q = new DBQuery;

      $q->addTable('activity');
      $q->addInsert('user_id', $this->_userId);
      $q->addInsert('task_id', $this->_taskId);
      $q->addInsert('date', $this->_date);
      $q->addInsert('duration', $this->_duration);
      $q->addInsert('activity_timesheet', $this->_timesheetId);
      
      if (!$q->exec()) 
      {
	$q->clear();
	return $db->ErrorMsg();
      }
      return true;    
    }
}