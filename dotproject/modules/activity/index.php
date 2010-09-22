<?php
require_once ("utils.class.php");
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$AppUI->savePlace();

//get working days from config system
$working_days = explode(',', dPgetConfig('cal_working_days'));

// set the start of the week. 1 for monday, 0 for sunday.
if (in_array(LOCALE_FIRST_DAY, $working_days))
{
  $rollover_day = LOCALE_FIRST_DAY;
} else {
  //if the first day determined by user isn't an working day, retrieve the min working day
  $rollover_day = min($working_days);
}

//default display
if (CUtils::getProjectLeaderProjects($AppUI->user_id)){
  include(DP_BASE_DIR.'/modules/activity/reporting.php');
} else {
  include(DP_BASE_DIR.'/modules/activity/capture.php');
}

?>
