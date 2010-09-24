<?php

if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

// $Id$
global $AppUI, $users, $task_id, $task_project, $obj, $projTasksWithEndDates, $tab, $loadFromTab, $company_id, $selected_departments;

//GT: EV 293
function getUserDptName($userId){
	$q = new DBQuery;
	//retrieve all assigned tasks 
	$q->addTable('users');
	$q->addQuery('c.contact_department');
	$q->addJoin('contacts', 'c', 'users.user_contact = c.contact_id');
	$q->addWhere("users.user_id = $userId");

	$psql = $q->prepare();
	$q->clear();

	$contactRow = db_exec($psql);
	$mydept = db_fetch_assoc($contactRow);

	return $mydept[0];
}

function getDepartmentArrayList($company_id, $checked_array = array(), 
                                    $dept_parent=0, $spaces=0) {
	global $AppUI;
	$q = new DBQuery();
	$deptsArray = array();
	$coArray = array();
	$distinctCompanyName = "";

	$q->addTable('departments');
	$q->addQuery('dept_id, dept_name, co.company_name');
	$q->addJoin('companies', 'co', 'departments.dept_company = co.company_id');
	$q->addWhere('dept_parent = ' . $dept_parent);
	$q->addOrder('co.company_name');
	//$q->addWhere('dept_company = ' . $company_id);
	require_once $AppUI->getModuleClass('companies');
	$obj = new CCompany();
	$sql = $q->prepare();
	$depts_list = db_loadHashList($sql, 'dept_id');
	$q->clear();
	
	foreach ($depts_list as $dept_id => $dept_info) {
		if (mb_strlen($dept_info['dept_name']) > 30) {
			$dept_info['dept_name'] = (mb_substr($dept_info['dept_name'], 0, 28) . '...');
		}
		$dept_name = str_repeat('&nbsp;', $spaces) . $dept_info['dept_name'];
		$deptsArray[$dept_id] = $dept_name;
		
		if ($distinctCompanyName !=  $dept_info['company_name']){
			$coArray[$dept_id] = $dept_info['company_name'];
			$distinctCompanyName = $dept_info['company_name'];
		}
		$childDeptsNCo = getDepartmentArrayList($company_id, $checked_array, $dept_id, $spaces+5);
		$childDepts = $childDeptsNCo[0];
		if(!empty($childDepts)){
			foreach($childDepts as $childDeptId => $childDeptName)
			{
				$deptsArray[$childDeptId] = $childDeptName;
			}
		}
	}
	$deptsNCoArray = array();

	array_push($deptsNCoArray, $deptsArray, $coArray);

	return $deptsNCoArray;
}

function getAllUsersGroupByDept(){
		$q	= new DBQuery;
		$q->addTable('users');
		$q->addQuery('user_id, contact_department, concat_ws(", ", contact_last_name, contact_first_name) as contact_name');
		$q->addJoin('contacts', 'con', 'contact_id = user_contact');
		$q->addOrder('contact_last_name');
		$res = $q->exec();
		$userlist = array();
		while ($row = $q->fetchRow()) {
			if($row['contact_department'] == null)
			{
				$row['contact_department'] = 0;
			}
			if(!isset($userlist[$row['contact_department']]))
			{
				$userlist[$row['contact_department']] = array();
			}
			$userlist[$row['contact_department']][$row['user_id']] = $row['contact_name'];
		}
		$q->clear();
		return $userlist;
}

function conv_tabjs($tableau, $nomjs, $prempass=true) {
	if($prempass) {
		$taille = count($tableau);

		echo "var ".$nomjs." = new Array(".$taille.");\n";
		foreach($tableau as $key => $val) {
			if(is_string($key)) $key = "'".$key."'";
			conv_tabjs($val, $nomjs."[".$key."]", false);
		}
	}
	else {
		if(is_array($tableau)) {
			echo($nomjs." = new Array(".count($tableau).");\n");
			foreach($tableau as $key => $val) {
			if(is_string($key)) $key = "'".$key."'";
				conv_tabjs($val, $nomjs."[".$key."]", false);
			}
		}
		else {
			if(is_string($tableau)) $tableau = "'".addcslashes($tableau,"'")."'";
			echo($nomjs." = ".$tableau.";\n");
		}
	}
} 

// Make sure that we can see users that are allocated to the task.

if ($task_id == 0) {
	// Add task creator to assigned users by default
	$assigned_perc = array($AppUI->user_id => array('contact_name' => $users[$AppUI->user_id], 'perc_assignment' => '100'));	
} else {
	// Pull users on this task
//			 SELECT u.user_id, CONCAT_WS(' ',u.user_first_name,u.user_last_name)
	$sql = "
			 SELECT user_tasks.user_id, perc_assignment, concat_ws(', ', contact_last_name, contact_first_name) as contact_name
			   FROM user_tasks
			 LEFT JOIN users USING (user_id)
			 LEFT JOIN contacts ON contacts.contact_id = users.user_contact
			 WHERE task_id =$task_id
			 AND task_id <> 0
			 ";
	$assigned_perc = db_loadHashList($sql, 'user_id');	
}

$initPercAsignment = "";
$assigned = array();
foreach ($assigned_perc as $user_id => $data) {
	$assigned[$user_id] = $data['contact_name'] . " [" . $data['perc_assignment'] . "%]";
	$initPercAsignment .= "$user_id={$data['perc_assignment']};";
}

?>
<script language="javascript">
<?php
echo "var projTasksWithEndDates=new Array();\n";
$keys = array_keys($projTasksWithEndDates);
for ($i = 1, $xi = sizeof($keys); $i < $xi; $i++) {
	//array[task_is] = end_date, end_hour, end_minutes
	echo ('projTasksWithEndDates[' . $keys[$i] . ']=new Array("' 
	      . $projTasksWithEndDates[$keys[$i]][1] . '", "' 
	      . $projTasksWithEndDates[$keys[$i]][2] . '", "' 
	      . $projTasksWithEndDates[$keys[$i]][3] ."\");\n");
}
?>
</script>
<form action="?m=tasks&a=addedit&task_project=<?php echo $task_project; ?>"
  method="post" name="resourceFrm">
<input type="hidden" name="sub_form" value="1" />
<input type="hidden" name="task_id" value="<?php echo $task_id; ?>" />
<input type="hidden" name="dosql" value="do_task_aed" />
	<input name="hperc_assign" type="hidden" value="<?php echo
	$initPercAsignment;?>"/>
<table width="100%" border="1" cellpadding="4" cellspacing="0" class="std">
<tr>
	<td valign="top" align="center">
		<table cellspacing="0" cellpadding="2" border="0">
			<tr>
				<!-- GT -->
				<td><?php echo $AppUI->_('Departments');?>:</td>
				<td><?php echo $AppUI->_('Human Resources');?>:</td>
				<td><?php echo $AppUI->_('Assigned to Task');?>:</td>
			</tr>
			<tr>
				<td>
					<?php
						$deptsNCoArray = getDepartmentArrayList($company_id, $selected_departments);
						$deptsArray = $deptsNCoArray[0];
						$coArray = $deptsNCoArray[1];
						$usersList = getAllUsersGroupByDept();
						$mydept = getUserDptName($AppUI->user_id);
						$deptsArray[0]= 'No department';
						echo arraySelectWithDisabled($deptsArray, 'departments', 'style="width:220px" size="10" class="text" multiple="multiple" onChange="getUsersByDepts()" id="departments"', $mydept, false, $coArray);
					?>
				</td>
				<td>
					<?php
						$nouser = array();
						echo arraySelect($nouser, 'resources', 'style="width:220px" size="10" class="text" multiple="multiple" id="resources"', null); 
					?>
				</td>
				<td>
					<?php echo arraySelect($assigned, 'assigned', 'style="width:220px" size="10" class="text" multiple="multiple" ', null); ?>
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					<table>
					<tr>
						<td align="right"><input type="button" class="button" value="&gt;" onClick="addUser(document.resourceFrm)" /></td>
						<td>
							<select name="percentage_assignment" class="text" <?if (!dPgetConfig('percentage_assignment')) { echo 'disabled' ;} ?> > 
							<?php 
								for ($i = 5; $i <= 100; $i+=5) {
									echo ('<option ' . (($i==100) ? 'selected="true"' : '') 
									      . ' value="' . $i . '">' . $i . '%</option>');
								}
							?>
							</select>
						</td>				
						<td align="left"><input type="button" class="button" value="&lt;" onClick="removeUser(document.resourceFrm)" /></td>					
					</tr>
					</table>
				</td>
			</tr>
		</table>
	</td>
	<td valign="top" align="center">
		<table><tr><td align="left">
		<?php echo $AppUI->_('Additional Email Comments');?>:		
		<br />
		<textarea name="email_comment" class="textarea" cols="60" rows="10" wrap="virtual"></textarea><br />
		<input type="checkbox" name="task_notify" id="task_notify" value="1"<?php if ($obj->task_notify != 0) { echo ' checked="checked"'; } ?> /> <label for="task_notify"><?php echo $AppUI->_('notifyChange'); ?></label>
		</td></tr></table><br />
		
	</td>
</tr>
</table>
<input type="hidden" name="hassign" />
</form>
<script language="javascript">
  subForm.push(new FormDefinition(<?php echo $tab; ?>, document.resourceFrm, checkResource, saveResource));

	function getUsersByDepts()
	{
		<?php conv_tabjs($usersList, 'usersList'); ?>

		var selectElemUsr = document.getElementById('resources');
		selectElemUsr.innerHTML=null;
		var dptList = new Array();
		var dptListName = new Array();
		var selectElemDpt = document.getElementById('departments');

		//get selected departments id
		for (var i=0; i<selectElemDpt.options.length; i++) {
			if (selectElemDpt.options[i].selected) {
				dptList.push(selectElemDpt.options[i].value);
				dptListName.push(selectElemDpt.options[i].text);
			}
		}
		//complete ressources multi select box
		var optgroups = [];
		for (var i=0; i<dptList.length; i++) {
			if(usersList[dptList[i]])
			{
				var optgroup = document.createElement("optgroup");
				optgroup.setAttribute("label", dptListName[i].replace(/^\s+/, '').replace(/\s+$/, ''));
				var option;
				for(var e=0; e<usersList[dptList[i]].length; e++)
				//for(idUser in usersList[dptList[i]])
				{
					//IE 6
					if (usersList[dptList[i]][e] != 'filter' && usersList[dptList[i]][e]){
					//IE 6
					option = new Option(usersList[dptList[i]][e],e);
					option.innerHTML = usersList[dptList[i]][e];
					optgroup.appendChild(option);
					
					}
				}
				optgroups.push(optgroup);
			}
		}
		if (optgroups){
			for(var i = 0; i < optgroups.length; i ++) {

				selectElemUsr.appendChild(optgroups[i]);
			}
		}
		
	}
	getUsersByDepts();
</script>
