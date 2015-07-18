<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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
	const DBVERSION = '2015071800';

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
			'customerinfo_on_load' => array(
				'class' => 'HiperusCustomerHandler',
				'method' => 'customerInfoOnLoad'
			),
			'customeredit_on_load' => array(
				'class' => 'HiperusCustomerHandler',
				'method' => 'customerEditOnLoad'
			),
			'nodeadd_on_load' => array(
				'class' => 'HiperusNodeHandler',
				'method' => 'NodeAddOnLoad'
			),
			'nodeinfo_on_load' => array(
				'class' => 'HiperusNodeHandler',
				'method' => 'NodeInfoOnLoad'
			),
			'nodeedit_on_load' => array(
				'class' => 'HiperusNodeHandler',
				'method' => 'NodeEditOnLoad'
			),
		);
	 }
}

?>
