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
 * LMSJamboxPlugin
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class LMSJamboxPlugin extends LMSPlugin {
	const plugin_directory_name = 'LMSJamboxPlugin';
	const PLUGIN_DBVERSION = '2015121500';
	const PLUGIN_NAME = 'Jambox';
	const PLUGIN_DESCRIPTION = 'Jambox Platform Support';
	const PLUGIN_AUTHOR = 'SGT/ITM Soft,<br>Tomasz Chiliński &lt;tomasz.chilinski@chilan.com&gt;';

	public function registerHandlers() {
		$this->handlers = array(
			'lms_initialized' => array(
				'class' => 'JamboxInitHandler',
				'method' => 'lmsInit'
			),
			'smarty_initialized' => array(
				'class' => 'JamboxInitHandler',
				'method' => 'smartyInit'
			),
			'modules_dir_initialized' => array(
				'class' => 'JamboxInitHandler',
				'method' => 'modulesDirInit'
			),
			'menu_initialized' => array(
				'class' => 'JamboxInitHandler',
				'method' => 'menuInit'
			),
			'customerinfo_before_display' => array(
				'class' => 'JamboxCustomerHandler',
				'method' => 'customerInfoBeforeDisplay'
			),
			'customeredit_before_display' => array(
				'class' => 'JamboxCustomerHandler',
				'method' => 'customerEditBeforeDisplay'
			),
			'customeredit_after_submit' => array(
				'class' => 'JamboxCustomerHandler',
				'method' => 'customerEditAfterSubmit'
			),
		);
	 }
}

?>
