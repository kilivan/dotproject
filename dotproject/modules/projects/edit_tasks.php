<?php /* PROJECTS $Id$ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$project_id = intval(dPgetParam($_GET, 'project_id', 0));

$titleBlock = new CTitleBlock("Organize Tasks", 'applet3-48.png', $m, "$m.$a");
$titleBlock->addCrumb("?m=projects&a=view&project_id=$project_id", "view this project");
$titleBlock->show();

//check permissions for this record
$canAccess = getPermission($m, 'access', $project_id);
$canRead = getPermission($m, 'view', $project_id);
$canEdit = getPermission($m, 'edit', $project_id);

$canAuthorTask = getPermission('tasks', 'add');

//Check if the proect is viewable.
if (!($canRead)) {
	$AppUI->redirect('m=public&a=access_denied');
}

//retrieve any state parameters
if (isset($_GET['tab'])) {
	$AppUI->setState('EditTasksVwTab', $_GET['tab']);
}
$tab = $AppUI->getState('EditTasksVwTab') !== NULL ? $AppUI->getState('EditTasksVwTab') : 0;

$tabBox = new CTabBox(('?m=projects&a=edit_tasks&project_id=' . $project_id), '', $tab);

$canAccessTask = getPermission('tasks', 'access');
if ($canAccessTask) {
	$tabBox->add(DP_BASE_DIR.'/modules/tasks/organize_tasks', 'Organize');
}

$tabBox->show();