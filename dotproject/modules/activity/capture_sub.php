<!-- GT jQuery -->
<script type="text/javascript" src="modules/activity/jquery.js"></script>

<script language="javascript">
    $(document).ready(function(){

		var wds;
		var light_blue = '#A5CBF7';
		var light_grey = 'rgb(213, 211, 206)';
		var light_green = '#5FD35F';
		var light_yellow = '#FFE680';
		var light_red = '#FFAAAA';
		var orange = '#FF6600';

        $(":radio").change(
		function()
		{
			var dataStr = $(this).attr("id");
			var dataArray = dataStr.split("|");

			var d = dataArray[0];
			var tid = dataArray[1]
			var sdate = dataArray[2]

			eid = "d-"+d;
			colorize_Day(eid,calcDayTotal(eid));
			calcTaskTotal(tid, sdate);
			deactivateValidate();
        });

		calcDayTotal = function(eid)
		{
			total = 0;
			result = 0;
			$("."+eid+" input:radio:checked").each(
				function() {
						var val = $(this).val();
						var valArray = val.split("|");
						val = valArray[0]/2;
						result += val;
					}
			)
			
			return result;
		}

		colorize = function(timestamps)
		{
			wds = timestamps.split(',');

			for (mi=0; mi<wds.length; mi++) {
				eid="d-"+wds[mi];
 				dtotal = calcDayTotal(eid);
 				colorize_Day(eid,dtotal);
			}
			activateValidate();
		}
		
		colorize_Day = function(eid, result)
		{
				if (result == '-') {
					color = 'black';
					bgcolor = light_blue;
				} else if (result == 0) {
					color = 'black';
					bgcolor = 'white';
				} else if (result == 1){
					color = 'green';
					bgcolor = light_green;
				} else if (result > 1) {
					color = 'red';
					bgcolor = light_red;
				} else {
					color = orange;
					bgcolor = light_yellow;
				}
 				$("."+eid).css('background-color',bgcolor).css("color",color);
				$("#"+eid).text(result).css("font-weight", "bold").css("color",color);

        };

		calcTaskTotal = function(tid, sdate)
		{
			total = 0;
			for (i=1; i<6; i++){
				e = $("."+sdate+"-"+i+"-"+tid);
				if (e.length != 0) {
					for (j=0; j<3; j++){
						total += e[j].checked ? e[j].value.split("|")[0]/2:0;
					}
				}
			}	
			document.getElementById("t"+tid).innerHTML = total;
		}

		activateValidate = function()
		{
			//Delete HTML code for validate / invalidate buttons
			tot = 0;
			valid = true;
			for (zi=0; zi<wds.length; zi++) {
				eid = "d-"+wds[zi];
				tday = calcDayTotal(eid);
				if (tday == "-"){
					tot+=1;
				} else {
					tot+=tday;
					valid = (tday <= 1) && valid;
				}
			}
			if(valid && tot == wds.length){
				v = "visible";
			} else {
				v = "hidden";
			}
			if (document.getElementById("validate")) {
			document.getElementById("validate").style.visibility = v;
			}
		}

		deactivateValidate = function()
		{
			if (document.getElementById("validate")) {
			document.getElementById("validate").disabled = true;
			}
		}



    	var wd = $("#wd").val();
		colorize(wd);  
    });
</script>
<?php
require_once("./classes/date.class.php");
require_once("utils.class.php");

//get week to be displayed (#weeks ago current week being 0)
if (isset($_GET['wk'])) {
	$AppUI->setState('TmsWk', $_GET['wk']);
}
$wk = $AppUI->getState('TmsWk') !== NULL && $wk >= 0 ? $AppUI->getState('TmsWk') : 0;

$thisDate = new CDate(); // set date for displaying and timesheet_date

//get working days from config system
$working_days = explode(',', dPgetConfig('cal_working_days'));
$wd_nb = count($working_days);

// set the start of the week. 1 for monday, 0 for sunday.
if (in_array(LOCALE_FIRST_DAY, $working_days))
{
	$rollover_day = LOCALE_FIRST_DAY;
} else {
	//if the first day determined by user isn't an working day, retrieve the min working day
	$rollover_day = min($working_days);
}

$time_set = new CDate();
$time_set->addDays(-7 * $wk);

$time_set = CUtils::getStartOfWeek($time_set, $rollover_day);

//Get list of dates for current timesheet
$timestampArray = CUtils::getListDateOfTheWeek($time_set, $working_days);

//Build week header's string
$header = $AppUI->_( 'Week from' ) .  " ";
$header .= $AppUI->_(date("l", min($timestampArray))) . ' ' . date("d/m/Y", min($timestampArray));
$header .= ' ' . $AppUI->_( 'to' ) . ' ';
$header .= $AppUI->_(date("l", max($timestampArray))) . ' ' . date("d/m/Y", max($timestampArray));
?>

<!-- Display week header with appropriate arrows for prev/next  week-->
<table width="100%" class="motitle" cellspacing="0" cellpadding="3" border="0">
	<tr>
	<?php
		$prev = dPshowImage(dPfindImage('prev.gif'), 16, 16, $AppUI->_('Previous week'), 'Previous week');
		$next = dPshowImage(dPfindImage('next.gif'), 16, 16, $AppUI->_('Next week'), 'Next week');
	?>

		<td align="left"><a
			href="./index.php?m=activity&a=capture&uid=<?php echo $user_id ?>&wk=<?php echo $wk + 1 ?>"><?php echo $prev ?></a>
		</td>
		<th nowrap="nowrap" colspan="6" align="center"><?php echo $header ?></th>
		<td align="right">
			<!-- Don't show next week arrow if current period corresponds to last week -->
			<?php if ($wk > 0) { ?><a
			href="./index.php?m=activity&a=capture&uid=<?php echo $user_id ?>&wk=<?php echo $wk - 1?>"><?php echo $next ?></a>
			<?php } ?>
		</td>
	</tr>
</table>


<?php
$timesheetId = 0;
$status = 1;
$timesheet = new CTimesheet($user_id, $time_set->format('%Y%m%d'));


if ($timesheet->load())
{
  $timesheetId = $timesheet->getTimesheetId();
  $activities = $timesheet->getActivities();
}
?>
<!-- Create timesheet form -->
<form name="timesheetActivities" action="./index.php?m=activity&a=dosql" method="post">
<input name='timesheetId' type='hidden' value=<?php echo $timesheetId; ?> />
<input name='timeset' type='hidden' value=<?php echo $time_set->format('%Y%m%d'); ?> />
<input name='userId' type='hidden' value=<?php echo $user_id; ?> />

<!-- Draw timesheet table -->
<table width="100%" class="tbl" cellspacing="1" cellpadding="2"	border="0">
	<!-- Timesheet header row -->
	<tr>
		<th nowrap="nowrap" width="150"><?php echo $AppUI->_("Tasks"); ?></th>
		<?php
			
			foreach($timestampArray as $timestamp) {
				echo '<th nowrap="nowrap">'.$AppUI->_(date("l", $timestamp)).' '.date("d/m/Y", $timestamp).'</th>';
			}
			echo '<th nowrap="nowrap" width="100">'.$AppUI->_("Total").'</th>';
		?>
	</tr>
	
	<!-- Timesheet contents : Projects and tasks -->
	<?php
		
		//Get tasks list
		$allAssignedTasks = CUtils::getAllAssignedTasks($user_id);
		$rowTasks = CUtils::getTasksForThisPeriod($allAssignedTasks, min($timestampArray), max($timestampArray) );
		//There are avalaible tasks
		if ($rowTasks) {
			$publicHolidays = CUtils::getPublicHolidays();
			$project_name = '';
			$newTaskIdList = array();
			//Display them
			while ($row = db_fetch_assoc($rowTasks)) 
			{
				array_push($newTaskIdList, $row['task_id']);
				if($timesheetId != 0 && $timesheet->getStatus($row['task_id']) != -1)
				{
				   $status = $timesheet->getStatus($row['task_id']);
				}
				//Used to count total activities 
				$task_total = 0;
				//Group tasks by project
				if($project_name != $row['project_name']) 
				{
				  $project_name = $row['project_name'];
				  echo "<tr><td nowrap colspan='".($wd_nb + 2)."'>";
				  echo "<table><tr><td style='border: 2px outset rgb(238,238,238); background-color:rgb(255,255,255);'><strong>";
				  echo $project_name;
				  echo "</strong></td></tr></table>";
				  echo "</td></tr>";
				}
				//Display each task
				echo "<tr>";
				echo "<td nowrap>".$row['task_name']."</td>";
				//Used to store dates of working days
				$wd="";
				$i = 0;
				foreach($timestampArray as $timestamp) {
					$i++;
					$date = date("Ymd", $timestamp);
					$wd .= $date . ",";
					// Disable public holidays columns
					if (in_array($date, $publicHolidays)){
						echo "<td nowrap style='background-color:#A5CBF7'>";
						echo "<center>".$AppUI->_("public holidays")."</center>";
						echo "</td>";
					} else {
					    $duration = 0;
					    if(isset($activities) && !empty($activities))
					    {
					      //determine if there is an activity already stored in database in this loop
					      foreach($activities as $activity)
					      {
						if($activity->getTaskId() == $row['task_id'] && $activity->getADate() == $date)
						{
						  $duration = $activity->getDuration();
						}
					      }
					    }

					    //If there are projects with different Leader Project
					    if($projectLeaderTasksIdList)
					    {
					      //check if the project leader owns this task
					      $projectLeaderTask = true;
					      if(!in_array($row['task_id'], $projectLeaderTasksIdList))
					      {
						$projectLeaderTask = false;
					      }
					    }
					    
					    $bgcolor = "";
					    if (!$projectLeaderTask && $projectLeaderTasksIdList && ($user_id != $AppUI->user_id || $status == 0))
					    {
						$bgcolor = "style='background-color:#D5D3CE'";
					    }

					    echo "<td nowrap class=d-$date $bgcolor>";

					    //Define radio button options
					    $optionValues = array("0"."|".$row['task_id']."|".$date=>$AppUI->_('None'),
								  "1"."|".$row['task_id']."|".$date=>$AppUI->_('Half day'), 
								  "2"."|".$row['task_id']."|".$date=>$AppUI->_('Full day')
					    );
					    $fid = $date."-".$row['task_id'];
					    foreach($optionValues as $value => $text) 
					    { ?>
						<!-- Display radio buttons -->
							<input
								type="radio"
								class="<?php echo $time_set->format('%Y%m%d')."-".$i."-".$row['task_id'];?>"
								id= "<?php echo $date.'|'.$row['task_id'].'|'.$time_set->format('%Y%m%d')?>"
								name="<?php echo $fid;?>"
							 	value="<?php echo $value;?>"
								<?php if ($status == 0 || (!$projectLeaderTask && $projectLeaderTasksIdList && $user_id != $AppUI->user_id)) { echo ' disabled = "disabled"'; }
								      //Check correct option of radio button
								      if ($duration == $value[0]) 
								      {
                                        $task_total += $duration;
                                        echo 'checked="checked"'; 
								      }
								?>
							/>
							<!-- Display radio button label -->
							<?php echo $text ?>
					    <?php
					    } ?>
					</td>
			<?php
					}
				}
			?>	
			<!-- Display total row for the day -->
			<td align="center" id="<?php echo 't'.$row['task_id'] ?>"><?php echo $task_total/2; ?></td>
			</tr>
			<?php
			} ?>
			<!-- Display footer -->
			<tr>
				<td>
				<!-- Validate/Invalidate buttons -->
				<table width="100%">
					<tr>
					<?php 	if ($projectLeaderTasksIdList)
						{
						  $projectLeaderTasksIdList = implode(',', $projectLeaderTasksIdList);
						  if (CUtils::isProjectLeaderOfThisUser($projectLeaderTasksIdList, $user_id))
						  { ?>
					      <?php if ($timesheetId != 0 && $timesheet->isValidateForTheProjectLeader($projectLeaderTasksIdList))
						    { ?>
						      <td align="center"><input type="submit" name="devalidate"
							      value="<?php echo $AppUI->_('Invalidate'); ?>" class="button"></td>
					      <?php } else { ?>
						      <td align="center"><input type="submit" name="validate" id="validate"
							      value="<?php echo $AppUI->_('Validate'); ?>" class="button"></td>
					      <?php } 
						 } 
						} ?>
					</tr>
				</table>
				<!-- Total cell for tasks of the day -->
				</td>
				<?php
				foreach($timestampArray as $timestamp){
					$date = date("Ymd",$timestamp);
					echo '<td align="center" id=d-'.$date.'>';
					echo '</td>';
				}
				?>
				<!-- Update button -->
				<td align="center">
				      <!-- if the timesheet exist and validated -->
				      <?php if($timesheetId == 0 || (!CUtils::isProjectLeaderOfThisUser($projectLeaderTasksIdList, $user_id) && !$timesheet->isValidate()) 
								 || (CUtils::isProjectLeaderOfThisUser($projectLeaderTasksIdList, $user_id) && !$timesheet->isValidateForTheProjectLeader($projectLeaderTasksIdList)))
				      { ?>
					<input type="submit" name="submit" value="<?php echo $AppUI->_('submit'); ?>" class="button">
				<?php } ?>

					<!-- Task Id of the timesheet and project leader Task Id !-->
					<input name='newTaskIdList' type='hidden' value='<?php echo implode(',', $newTaskIdList); ?>' />
					<input name='projectLeaderTasksIdList' type='hidden' value='<?php echo $projectLeaderTasksIdList ?>' />
					<input name='wd' id='wd' type='hidden' value='<?php echo substr($wd,0,-1); ?>' />
				</td>
			</tr>
		<?php } else {
		//there are no tasks available on this timesheet
		?>
			<tr>
				<td nowrap='nowrap' colspan='<?php echo $wd_nb + 2;?>'>
					<?php echo $AppUI->_("You haven't tasks yet.");?>
				</td>
			</tr>
		<?php
		}
		?>
	</tr>
</table>
</form>

