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
 * LMSRrdStatsStats
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class LMSRrdStatsPlugin extends LMSPlugin {
	const plugin_directory_name = 'LMSRrdStatsPlugin';
//	const PLUGIN_DBVERSION = '2015102800';
	const PLUGIN_NAME = 'RRD Statistics';
	const PLUGIN_DESCRIPTION = 'Rrdtool Node Traffic Statistics Support';
	const PLUGIN_AUTHOR = 'Tomasz Chiliński &lt;tomasz.chilinski@chilan.com&gt;';

	public static function getRrdDirectory() {
		return ConfigHelper::getConfig('rrdstats.directory', SYS_DIR . DIRECTORY_SEPARATOR
			. 'plugins' . DIRECTORY_SEPARATOR . self::plugin_directory_name . DIRECTORY_SEPARATOR . 'rrd');
	}

	public function registerHandlers() {
		$this->handlers = array(
			'lms_initialized' => array(
				'class' => 'RrdStatsInitHandler',
				'method' => 'lmsInit',
			),
			'smarty_initialized' => array(
				'class' => 'RrdStatsInitHandler',
				'method' => 'smartyInit',
			),
			'modules_dir_initialized' => array(
				'class' => 'RrdStatsInitHandler',
				'method' => 'ModulesDirInit',
			),
			'menu_initialized' => array(
				'class' => 'RrdStatsInitHandler',
				'method' => 'menuInit',
			),
			'access_table_initialized' => array(
				'class' => 'RrdStatsInitHandler',
				'method' => 'accessTableInit',
			),
			'welcome_before_module_display' => array(
				'class' => 'RrdStatsWelcomeHandler',
				'method' => 'welcomeBeforeModuleDisplay',
			),
			'nodeinfo_before_display' => array(
				'class' => 'RrdStatsNodeHandler',
				'method' => 'nodeInfoBeforeDisplay',
			),
		);
	}
}

?>
