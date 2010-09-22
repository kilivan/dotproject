<?php
/*
dotProject Module

Name:      Activity
Directory: activity
Version:   1.0
Class:     user
UI Name:   Activity
UI Icon:   activities.png

This file does no action in itself.
If it is accessed directory it will give a summary of the module parameters.
*/

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Activity';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'activity';
$config['mod_setup_class'] = 'CSetupActivity';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Activity';
$config['mod_ui_icon'] = 'activities.png';
$config['mod_description'] = 'This is a timesheet module, user captures his activity (date <-> task <-> user)';

if (@$a == 'setup') {
	echo dPshowModuleConfig($config);
}

/**
* MODULE SETUP CLASS
* This class must contain the following methods:
* install - creates the required db tables
* remove - drop the appropriate db tables
* upgrade - upgrades tables from previous versions
*/
class CSetupActivity {

	function install() {

		$sql = '(
			timesheet_id INT(11)  NOT NULL AUTO_INCREMENT,
 			user_id INT(11) ,
			timeset DATE ,
			PRIMARY KEY (`timesheet_id`)
		)
		ENGINE = MyISAM';

		$sql2 = '(
			activity_id INT(12)  NOT NULL AUTO_INCREMENT,
			user_id INT(11) ,
			task_id INT(11) ,
			date DATE ,
			duration TINYINT(1) ,
			activity_timesheet INT(11),
			PRIMARY KEY (`activity_id`),
			FOREIGN KEY (`activity_timesheet`) REFERENCES timesheet (`timesheet_id`)
		)
		ENGINE = MyISAM';

		$sql3 = '(
			task_id INT(11)  NOT NULL,
			timesheet_id INT(11)  NOT NULL,
			status TINYINT(1) ,
			PRIMARY KEY (`task_id`, `timesheet_id`)
		)
		ENGINE = MyISAM';
      

		$q = new DBQuery;

		$q->createTable('timesheet');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();

		$q->createTable('activity');
		$q->createDefinition($sql2);
		$q->exec();
		$q->clear();

		$q->createTable('task_timesheet');
		$q->createDefinition($sql3);
		$q->exec();
		$q->clear();

		return db_error();
	}

	function remove() {
		$q = new DBQuery;

		$q->dropTable('activity');
		$q->exec();
		$q->clear();

		$q->dropTable('timesheet');
		$q->exec();
		$q->clear();

		$q->dropTable('task_timesheet');
		$q->exec();
		$q->clear();


		return db_error();
	}

	function upgrade() {
		$q = new DBQuery;
		switch ($old_version) {
			case '0.1':
				break;
		}
		return db_error();
	}
}

?>