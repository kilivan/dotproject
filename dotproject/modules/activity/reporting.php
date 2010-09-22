<?php
require_once ("utils.class.php");
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
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

if(!isset($dago))
{
  $dago = 0;
}

// setup the title block
$titleBlock = new CTitleBlock('Team activity reporting', 'activities.png', $m, "$m.$a");
$titleBlock->addCrumb('?m=activity&a=capture','Capture my activities');
$titleBlock->addCell(('<form action="?m=activity&amp;a=reporting&amp;vw=week&amp;ago=' . $dago . '" method="post">' . "\n" 
                      . '<input type="submit" class="button" value="' 
                      . $AppUI->_('Week view') . '" />'. "\n" . '</form>' . "\n"));
$titleBlock->addCell(('<form action="?m=activity&amp;a=reporting&amp;vw=month&amp;ago=' . $dago . '" method="post">' . "\n" 
                      . '<input type="submit" class="button" value="' 
                      . $AppUI->_('Month view') . '" />'. "\n" . '</form>' . "\n"));
$titleBlock->addCell(('<form action="?m=activity&amp;a=reporting&amp;vw=year&amp;ago=' . $dago . '" method="post">' . "\n" 
                      . '<input type="submit" class="button" value="' 
                      . $AppUI->_('Year view') . '" />'. "\n" . '</form>' . "\n"));
$titleBlock->addCell(('<form action="?m=activity&amp;a=reporting&amp;vw=all" method="post">' . "\n" 
                      . '<input type="submit" class="button" value="' 
                      . $AppUI->_('All') . '" />'. "\n" . '</form>' . "\n"));
$titleBlock->addCell(('<form action="?m=activity&amp;a=reporting" method="post">' . "\n" 
                      . '<input type="submit" class="button" value="' 
                      . $AppUI->_('Today') . '" />'. "\n" . '</form>' . "\n"));
$titleBlock->show();


include(DP_BASE_DIR.'/modules/activity/reporting_sub.php');
?>
