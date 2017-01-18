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
 * InitHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class RrdStatsInitHandler {
    /**
     * Sets plugin managers
     * 
     * @param LMS $hook_data Hook data
     */
	public function lmsInit(LMS $hook_data) {
		define('RRDTOOL_BINARY', ConfigHelper::getConfig('rrdstats.rrdtool_binary', '/usr/bin/rrdtool'));

		return $hook_data;
	}

    /**
     * Sets plugin Smarty templates directory
     * 
     * @param Smarty $hook_data Hook data
     * @return \Smarty Hook data
     */
	public function smartyInit(Smarty $hook_data) {
		$template_dirs = $hook_data->getTemplateDir();
		$plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSRrdStatsPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'templates';
		array_unshift($template_dirs, $plugin_templates);
		$hook_data->setTemplateDir($template_dirs);
		return $hook_data;
	}

    /**
     * Sets plugin managers
     * 
     * @param LMS $hook_data Hook data
     */
	public function userpanelLmsInit(LMS $hook_data) {
		define('RRDTOOL_BINARY', ConfigHelper::getConfig('rrdstats.rrdtool_binary', '/usr/bin/rrdtool'));

		return $hook_data;
	}

    /**
     * Sets plugin userpanel modules directory
     * 
     * @param array $hook_data Hook data
     * @return array Hook data
     */
	public function userpanelModulesDirInit(array $hook_data = array()) {
		$plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSRrdStatsPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'userpanel' . DIRECTORY_SEPARATOR;
		array_unshift($hook_data, $plugin_modules);
		return $hook_data;
	}

    /**
     * Sets plugin userpanel modules directory
     * 
     * @param array $hook_data Hook data
     * @return array Hook data
     */
	public function ModulesDirInit(array $hook_data = array()) {
		$plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSRrdStatsPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'modules';
		array_unshift($hook_data, $plugin_modules);
		return $hook_data;
	}

    /**
     * Sets plugin menu entries
     * 
     * @param array $hook_data Hook data
     * @return array Hook data
     */
	public function menuInit(array $hook_data = array()) {
		$menu_rrd = array(
			'rrdstats' =>  array(
				'name' => trans('RRD Stats'),
				'img' =>'traffic.gif',
				'link' =>'?m=rrdtraffic',
				'tip' => trans('Statistics of Internet Link Usage'),
//				'accesskey' =>'x',
				'prio' => 47,
				'submenu' => array(
					array(
						'name' => trans('Filter'),
						'link' => '?m=rrdtraffic',
						'tip' => trans('User-defined stats'),
						'prio' => 10,
					),
					array(
						'name' => trans('Last Hour'),
						'link' => '?m=rrdtraffic&bar=hour',
						'tip' => trans('Last hour stats for all networks'),
						'prio' => 20,
					),
					array(
						'name' => trans('Last Day'),
						'link' => '?m=rrdtraffic&bar=day',
						'tip' => trans('Last day stats for all networks'),
						'prio' => 30,
					),
					array(
						'name' => trans('Last 30 Days'),
						'link' => '?m=rrdtraffic&bar=month',
						'tip' => trans('Last month stats for all networks'),
						'prio' => 40,
					),
					array(
						'name' => trans('Last Year'),
						'link' => '?m=rrdtraffic&bar=year',
						'tip' => trans('Last year stats for all networks'),
						'prio' => 50,
					),
/*
					array(
						'name' => trans('Reports'),
						'link' => '?m=trafficprint',
						'tip' => trans('Lists and reports printing'),
						'prio' => 70,
					),
*/
					array(
						'name' => '------------',
						'prio' => 110,
					),
					array(
						'name' => trans('Configuration'),
						'link' => '?m=configlist&s=rrdstats',
						'tip' => trans('Configuration'),
						'prio' => 120,
					),
				),
			),
		);

		$menu_keys = array_keys($hook_data);
		$i = array_search('stats', $menu_keys);
		array_splice($hook_data, $i + 1, 0, $menu_rrd);

		return $hook_data;
	}

    /**
     * Modifies access table
     * 
     */
	public function accessTableInit() {
		$access = AccessRights::getInstance();

		$access->insertPermission(new Permission('rrdstats_access', trans('RRD traffic stats'), '^rrdtraffic.*$'),
			AccessRights::FIRST_FORBIDDEN_PERMISSION);
	}
}

?>
