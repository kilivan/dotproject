<?php /* TASKS $Id$ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

if(isset($_POST['save']) && isset($_POST['taskList']))
{
	$taskArray = explode(',', $_POST['taskList']);

	if($taskArray[0] == ''){
	array_shift($taskArray);
	}
	$q = new DBQuery;
	foreach($taskArray as $k => $v)
	{
		$q->addTable('tasks');
		$q->addUpdate('task_order', $k);
		$q->addWhere('task_id = '.$v);
		$q->exec();
		$q->clear();
	}
}

if(isset($_POST['reset']))
{
	$q = new DBQuery;
	if($_POST['taskList']){
		$taskArray = explode(',', $_POST['taskList']);
		if($taskArray[0] == ''){
		array_shift($taskArray);
		}
		sort($taskArray);
	} else {
		$q->addTable('tasks');
		$q->addQuery('task_id, task_name');
		if($_POST['projectId']){
			$q->addWhere('task_project = '.$_POST['projectId']);
		} else if($_POST['taskId']){
			$q->addWhere('task_parent='.$_POST['taskId']);
			$q->addWhere('task_id !='.$_POST['taskId']);
		}
		$taskArray = $q->loadHashList();
		$taskArray = array_flip($taskArray);
		$q->clear();
	}
	$order = 0;
	foreach($taskArray as $v)
	{
		$q->addTable('tasks');
		$q->addUpdate('task_order', $order);
		$q->addWhere('task_id = '.$v);
		$q->exec();
		$q->clear();
		$order++;
	}
}
$AppUI->redirect();
?>
