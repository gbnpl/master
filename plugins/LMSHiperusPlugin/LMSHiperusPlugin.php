<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2016 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

/**
 * LMSHiperusPlugin
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class LMSHiperusPlugin extends LMSPlugin {
	const plugin_directory_name = 'LMSHiperusPlugin';
	const PLUGIN_DBVERSION = '2015110300';
	const PLUGIN_NAME = 'Hiperus C5';
	const PLUGIN_DESCRIPTION = 'Hiperus C5 Platform Support';
	const PLUGIN_AUTHOR = 'Sylwester Kondracki &lt;sylwester.kondracki@gmail.com&gt;,<br>Tomasz Chiliński &lt;tomasz.chilinski@chilan.com&gt;';

	public function registerHandlers() {
		$this->handlers = array(
			'lms_initialized' => array(
				'class' => 'HiperusInitHandler',
				'method' => 'lmsInit'
			),
			'smarty_initialized' => array(
				'class' => 'HiperusInitHandler',
				'method' => 'smartyInit'
			),
			'modules_dir_initialized' => array(
				'class' => 'HiperusInitHandler',
				'method' => 'modulesDirInit'
			),
			'menu_initialized' => array(
				'class' => 'HiperusInitHandler',
				'method' => 'menuInit'
			),
			'access_table_initialized' => array(
				'class' => 'HiperusInitHandler',
				'method' => 'accessTableInit'
			),
			'userpanel_lms_initialized' => array(
				'class' => 'HiperusInitHandler',
				'method' => 'lmsInit'
			),
			'userpanel_smarty_initialized' => array(
				'class' => 'HiperusInitHandler',
				'method' => 'smartyInit'
			),
			'userpanel_modules_dir_initialized' => array(
				'class' => 'HiperusInitHandler',
				'method' => 'userpanelModulesDirInit'
			),
			'customerinfo_before_display' => array(
				'class' => 'HiperusCustomerHandler',
				'method' => 'customerInfoBeforeDisplay'
			),
			'customeredit_before_display' => array(
				'class' => 'HiperusCustomerHandler',
				'method' => 'customerEditBeforeDisplay'
			),
			'nodeadd_before_display' => array(
				'class' => 'HiperusNodeHandler',
				'method' => 'nodeAddBeforeDisplay'
			),
			'nodeinfo_before_display' => array(
				'class' => 'HiperusNodeHandler',
				'method' => 'nodeInfoBeforeDisplay'
			),
			'nodeedit_before_display' => array(
				'class' => 'HiperusNodeHandler',
				'method' => 'nodeEditBeforeDisplay'
			),
			'quicksearch_after_submit' => array(
				'class' => 'HiperusQuickSearchHandler',
				'method' => 'quicksearchAfterSubmit',
			),
		);
	 }
}

?>
