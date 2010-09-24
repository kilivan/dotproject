<!-- GT jQuery UI-->
<link type="text/css" href="jQuery/css/ui-lightness/jquery-ui-1.8.4.custom.css" rel="Stylesheet" />	
<script type="text/javascript" src="jQuery/js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="jQuery/js/jquery-ui-1.8.4.custom.min.js"></script>


<script language="javascript">
	$(document).ready(function(){
		$('#task_sort').sortable({
			update: function(event, ui) {
				var taskOrder = $(this).sortable('toArray').toString();
				$('input[name="taskList"]').val(taskOrder);
			}
		});

	});
</script>


<?php /* TASKS $Id$ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $m, $a, $project_id, $task_id, $f;
global $canEdit, $canAccessTask;

$q = new DBQuery;

	$q->addTable('tasks');
	$q->addQuery('task_id, task_parent, task_name, task_start_date, task_end_date,
	task_dynamic, task_milestone');
	if(isset($project_id))
	{
		$q->addWhere('task_project='.$project_id);
	} else if($task_id) {
		$q->addWhere('task_parent='.$task_id);
		$q->addWhere('task_id !='.$task_id);
	}
	$q->addOrder('task_order');

	$p['tasks'] = $q->loadList();
	$q->clear();



if ($p['tasks']) {
		global $tasks_filtered, $children_of;
		//get list of task ids and set-up array of children

		foreach ($p['tasks'] as $i => $t) {
			$tasks_filtered[] = $t['task_id'];
			$children_of[$t['task_parent']] = (($children_of[$t['task_parent']])
												?$children_of[$t['task_parent']]:
												array());
			if ($t['task_parent'] != $t['task_id']) {
				array_push($children_of[$t['task_parent']], $t['task_id']);
			}
		} ?>

		
		<?php
		//start displaying tasks
		echo '<div id="task_sort">';
		if(isset($project_id))
		{
			echo '<div>';
		}
		foreach ($p['tasks'] as $i => $t1) {
			if ($t1['task_parent'] == $t1['task_id']) {
				$is_opened = (!($t1['task_dynamic']) || !(in_array($t1['task_id'], $tasks_closed)));
				
				//check for child
				$no_children = empty($children_of[$t1['task_id']]);
				
				showtaskToOrganize($t1, 0, $is_opened, false, $no_children);
				if ($is_opened && !($no_children)) {
					findchild($p['tasks'], $t1['task_id'], 0, false, true);
				}
			} else if (!(in_array($t1['task_parent'], $tasks_filtered))) {
				/*
					* don't "mess with" display when showing certain views 
					* (or similiar filters that don't involve "breaking apart" a task tree 
					* even though they might not use this page ever)
					*/
				if (!$task_id && (in_array($f, $never_show_with_dots))) {
					showtaskToOrganize($t1, 1, true, false, true); 
				} else {
					//display as close to "tree-like" as possible
					$is_opened = (!($t1['task_dynamic']) || !(in_array($t1['task_id'], $tasks_closed)));
					
					//check for child
					$no_children = empty($children_of[$t1['task_id']]);
					
					$my_level = (($task_id && $t1['task_parent'] == $task_id) ? 0 : -1);
					showtaskToOrganize($t1, $my_level, $is_opened, false, $no_children); // indeterminate depth for child task
					if ($is_opened && !($no_children)) {
						findchild($p['tasks'], $t1['task_id'], 0, false, true);
					}
				}
			}
		}
		echo '</div>';
		if(isset($project_id))
		{
			echo '</div>';
		}
		?>
		<form name="taskOrder" action="?m=tasks&a=do_task_order" method="post">
		<input id="taskList" name="taskList" type="hidden" />
		<input id="projectId" name="projectId" type="hidden" value="<?php echo $project_id; ?>" />
		<input id="taskId" name="taskId" type="hidden" value="<?php echo $task_id; ?>" />
		<div align='right'>
			<input class="button" type="submit" id="save" name="save" value="<?php echo $AppUI->_('save'); ?>" />
		</div>
		<div>
			<input class="button" type="submit" id="reset" name="reset" value="<?php echo $AppUI->_('reset'); ?>" />
		</div>

<?php
}

$AppUI->savePlace();

?>
