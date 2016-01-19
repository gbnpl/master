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
 * LMSGponDasanPlugin
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class LMSGponDasanPlugin extends LMSPlugin {
	const plugin_directory_name = 'LMSGponDasanPlugin';
	const PLUGIN_DBVERSION = '2015122900';
	const PLUGIN_NAME = 'GPON Dasan';
	const PLUGIN_DESCRIPTION = 'GPON Dasan Hardware Support';
	const PLUGIN_AUTHOR = 'AP-Media,<br>Tomasz Chiliński &lt;tomasz.chilinski@chilan.com&gt;';

	public function registerHandlers() {
		$this->handlers = array(
			'lms_initialized' => array(
				'class' => 'GponDasanInitHandler',
				'method' => 'lmsInit'
			),
			'smarty_initialized' => array(
				'class' => 'GponDasanInitHandler',
				'method' => 'smartyInit'
			),
			'modules_dir_initialized' => array(
				'class' => 'GponDasanInitHandler',
				'method' => 'modulesDirInit'
			),
			'menu_initialized' => array(
				'class' => 'GponDasanInitHandler',
				'method' => 'menuInit'
			),
			'access_table_initialized' => array(
				'class' => 'GponDasanInitHandler',
				'method' => 'accessTableInit'
			),
			'customerinfo_before_display' => array(
				'class' => 'GponDasanCustomerHandler',
				'method' => 'customerInfoBeforeDisplay'
			),
			'customeredit_before_display' => array(
				'class' => 'GponDasanCustomerHandler',
				'method' => 'customerEditBeforeDisplay'
			),
			'nodeadd_before_display' => array(
				'class' => 'GponDasanNodeHandler',
				'method' => 'nodeAddBeforeDisplay'
			),
			'nodeinfo_before_display' => array(
				'class' => 'GponDasanNodeHandler',
				'method' => 'nodeInfoBeforeDisplay'
			),
			'nodeedit_before_display' => array(
				'class' => 'GponDasanNodeHandler',
				'method' => 'nodeEditBeforeDisplay'
			),
			'nodescan_on_load' => array(
				'class' => 'GponDasanNodeHandler',
				'method' => 'nodeScanOnLoad'
			),
		);
	 }
}

?>
