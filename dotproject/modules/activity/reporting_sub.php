<?php
//Retrieve all data
if($projectsId = CUtils::getProjectLeaderProjects($AppUI->user_id)){
} else {
  $AppUI->redirect();
}
$tasksId = CUtils::getProjectLeaderTasks($projectsId);

//Retrieve parameters from GET request
$ago = (isset($_GET['ago'])) ? $_GET['ago'] : 0;
$dago = (isset($_GET['ago'])) ? $_GET['ago'] : 0;
$vw = (isset($_GET['vw'])) ? $_GET['vw'] : 'week';

$time_set = new CDate();

//Build reporting period summary and determine
//the beginning ($sdate) and the end ($edate)
//of the period
$header = $AppUI->_("Activities of ");
switch ($vw) {
case 'week':
	$time_set->addDays(7 * $ago);
	$sdate = CUtils::getStartOfWeek($time_set, $rollover_day);
	$edate = new CDate(CUtils::getEndOfWeek($sdate));
	$header = $AppUI->_("Week's activities from") . " " . $AppUI->_($sdate->format('%A')) . " " . $sdate->format('%d/%m/%Y') . " " . $AppUI->_(" to ") . " " . $AppUI->_($edate->format('%A')) . " " . $edate->format('%d/%m/%Y'); 
	break;
case 'month':
	$time_set->addMonths($ago);
	$sdate = clone $time_set;
	$sdate->setDay(1);
	$edate = clone $sdate;
	$edate->addMonths(1);
	$edate->addDays(-1);
	$header .=  " " . $AppUI->_($time_set->format("%B")) . " " . $time_set->format("%Y");
	break;
case 'year':
	$time_set->addMonths(12 * $ago);
	$sdate = clone $time_set;
	$sdate->setDay(1);
	$sdate->setMonth(1);
	$edate = clone $sdate;
	$edate->addMonths(12);
	$edate->addDays(-1);
	$header .=  " " . $time_set->format("%Y");
	break;
case 'all':
	$header = $AppUI->_("All activities");
	break;
}

if (isset($sdate)){
$start_date = $sdate->getTime();
}
if (isset($edate)){
$end_date = $edate->getTime();
}
$rowTasks = CUtils::getTasksForThisPeriod($tasksId /*, $start_date, $end_date */);
$rowTasks2 = CUtils::getTasksForThisPeriod($tasksId /*, $start_date, $end_date */);
if($rowTasks2){
$rowUsers = CUtils::getAllAssignedUsers($rowTasks2);
}

?>

<!-- Build activity view header -->
<table width="100%" class="motitle" cellspacing="0" cellpading="3" border="0">
<tr>
<?php 
//Show prev/next arrows for all but 'all' view
if ($vw != all) {
	$alt = $AppUI->_('Previous ' . $vw);
	$ago -= 1;
	echo '<td align="left">
		<a href="index.php?m=activity&amp;a=reporting&amp;vw='. $vw .'&amp;ago=' . $ago . '" title="' . $alt . '">
			<img border=0 width=16 height=16 src="./images/prev.gif" alt=' . $alt . '/>
		</a>
	</td>';
}?>
	<th nowrap="nowrap" align="center"><?php echo $header ?></th>
<?php if ($vw != all) {
	$alt = $AppUI->_('Next ' . $vw);
	$ago += 2;
	echo '<td align="right">
		<a href="index.php?m=activity&amp;a=reporting&amp;vw='. $vw .'&amp;ago=' . $ago . '" title="' . $alt . '">
			<img border=0 width=16 height=16 src="./images/next.gif" alt=' . $alt . '/>
		</a>
	</td>';
}?>
</tr>
</table>

<!--Build reporting table-->
<table class='tbl' cellspacing="1" width="100%" cellpadding="2" border="0">
<th><?php echo $AppUI->_("Task name"); ?></th>
<!-- Fill table with appropriate users -->
<?php
if ($rowUsers)
{
  $userIdList = array();
  while($row = db_fetch_assoc($rowUsers))
  { $contact =  ucwords($row['contact_last_name']." ".$row['contact_first_name'][0])."." ?>
    <th nowrap="nowrap"><a class="hdr" href="index.php?m=activity&amp;a=capture&amp;uid=<?php echo $row['user_id']?>&amp;wk=0"><?php echo $contact?></th>
  <?php
    array_push($userIdList, $row['user_id']);
   }
   $nbUsers = count($userIdList);
   $nbSpan = $nbUsers+2;
} else { ?>
  <th nowrap="nowrap"></th>
<?php }
?>

<th><?php echo $AppUI->_("Total"); ?></th>
<!-- Build projects and tasks assignement -->
<?php
if ($rowTasks)
{
  $tasksId = array();
  while ($row = db_fetch_assoc($rowTasks))
  {
      array_push($tasksId, $row['task_id']);
      if($project_name != $row['project_name'])
      {
	$project_name = $row['project_name'];
	echo "<tr>";
	  echo "<td nowrap colspan=$nbSpan>";
	    echo "<table><tr><td style='border: 2px outset rgb(238,238,238); background-color:rgb(255,255,255);'><strong>";
	      echo $project_name;
	    echo "</strong></td></tr></table>";
	  echo "</td>"; 
	echo "</tr>";
      }
      echo "<tr>";
	echo "<td nowrap>"; 
	  echo $row['task_name'];
	echo "</td>";
	foreach($userIdList as $userId)
	{
	  //duration -> user / task
	  echo "<td nowrap align='center'>";
	    echo CUtils::getActivitiesDuration($userId, $row['task_id'], $start_date, $end_date);
	  echo "</td>";
	}
	//total duration -> users
	echo "<th>";
	  echo CUtils::getActivitiesDuration(null, $row['task_id'], $start_date, $end_date);
	echo "</th>";
      echo "</tr>";
  }
$tasksId = implode(',', $tasksId);
?>
<th><?php echo $AppUI->_("Total"); ?></th>
<?php
//total duration -> tasks
if(isset($userIdList)){
  foreach($userIdList as $userId)
  {
    echo "<th>";
      echo CUtils::getActivitiesDuration($userId, null, $start_date, $end_date, $tasksId);
    echo "</th>";
  }
} else {
  echo "<th>";
  echo "</th>";
}
?>
<th>&nbsp;</th>
<?php
} else {
  echo "<tr>";
    echo "<td nowrap='nowrap' colspan=3>";
      echo $AppUI->_("You haven't tasks yet.");
    echo "</td>";
  echo "</tr>";
}
?>
</table>
