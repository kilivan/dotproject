<?php
require_once ("timesheet.class.php");
if(isset($_POST['submit']))
{
  $dataActivities = array();

  $params = array('userId', 'submit', 'timesheetId', 'timeset', 'newTaskIdList', 'projectLeaderTasksIdList');
  foreach ($_POST as $name => $value)
  {
    if(!in_array($name, $params))
    {
      array_push($dataActivities, $value);
    }
  }

  if(isset($dataActivities))
  {
    if($_POST['timesheetId'] != 0)
    {
      $timesheetId = $_POST['timesheetId'];
    } else {
      //create timesheet
      $timesheet = new CTimesheet($_POST['userId'], $_POST['timeset']);
      $timesheetId = $timesheet->save();
    }
    CTimesheet::updateTaskTimesheet($timesheetId, $_POST['newTaskIdList']);
    CTimesheet::saveActivities($_POST['userId'], $dataActivities, $timesheetId);
  }
}

if(isset($_POST['validate']))
{
  if($_POST['timesheetId'])
  {
    CTimesheet::validateTimesheet($_POST['timesheetId'], $_POST['projectLeaderTasksIdList']);
  }
}

if(isset($_POST['devalidate']))
{
  if($_POST['timesheetId'])
  {
    CTimesheet::devalidateTimesheet($_POST['timesheetId'], $_POST['projectLeaderTasksIdList']);
  }
}

$AppUI->redirect();
?>