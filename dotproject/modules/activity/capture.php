<?php
require_once ("utils.class.php");
require_once ("timesheet.class.php");
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$AppUI->savePlace();

$projectsIdList = CUtils::getProjectLeaderProjects($AppUI->user_id);

if($projectsIdList)
{
  $projectLeaderTasksIdList = CUtils::getProjectLeaderTasks($projectsIdList);
}

if($projectLeaderTasksIdList)
{
  if (isset($_GET['uid']) AND CUtils::isProjectLeaderOfThisUser($projectLeaderTasksIdList, $_GET['uid']))
  {
    $user_id = $_GET['uid'];
  } else {
    $user_id = $AppUI->user_id;
  }
  $projectLeaderTasksIdList = explode(',',$projectLeaderTasksIdList);
} else {
  $user_id = $AppUI->user_id;
}


$names = CUtils::getUserNames($user_id);

// setup the title block
$titleBlock = new CTitleBlock('User activities capture', 'activities.png', $m, "$m.$a");
$header = $AppUI->_( 'Activities of' ) . " ";
if($names){
  $header .= $names[1] . " " . $names[0] . "&nbsp;&nbsp;&nbsp;";
}
//Display reporting crumb only if project leader
if (CUtils::getProjectLeaderProjects($AppUI->user_id)){
	$titleBlock->addCrumb('?m=activity&a=reporting', 'View team activity reporting');
}
$titleBlock->addCell('<span class="title">' . $header . '</span>');
$titleBlock->addCell(('<form action="?m=activity&amp;a=capture&amp;uid='. $user_id .'&amp;wk=0" method="post">' . "\n" 
                      . '<input type="submit" class="button" value="' 
                      . $AppUI->_('Today') . '" />'. "\n" . '</form>' . "\n"));
$titleBlock->show();

$min_view = false;
$rollover_day = LOCALE_FIRST_DAY;
include(DP_BASE_DIR.'/modules/activity/capture_sub.php');
?>
