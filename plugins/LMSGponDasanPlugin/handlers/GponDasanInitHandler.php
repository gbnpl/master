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
class GponDasanInitHandler {
    /**
     * Sets plugin Smarty templates directory
     * 
     * @param Smarty $hook_data Hook data
     * @return \Smarty Hook data
     */
	public function smartyInit(Smarty $hook_data) {
		$template_dirs = $hook_data->getTemplateDir();
		$plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponDasanPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'templates';
		array_unshift($template_dirs, $plugin_templates);
		$hook_data->setTemplateDir($template_dirs);

		$SMARTY = $hook_data;
		require_once(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponDasanPlugin::plugin_directory_name . DIRECTORY_SEPARATOR
			. 'lib' . DIRECTORY_SEPARATOR . 'definitions.php');

		return $hook_data;
	}

    /**
     * Sets plugin Smarty modules directory
     * 
     * @param array $hook_data Hook data
     * @return array Hook data
     */
	public function modulesDirInit(array $hook_data = array()) {
		$plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponDasanPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'modules';
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
		$menu_gpon = array(
			'GPON_DASAN' => array(
				'name' => 'GPON DASAN',
				'img' => LMSGponDasanPlugin::plugin_directory_name . '/gponolt.gif',
				'link' =>'?m=gpondasanoltlist',
				'tip' => 'Zarządzanie GPON',
				'accesskey' =>'k',
				'prio' => 11,
				'submenu' => array(
					array(
						'name' => trans('OLT list'),
						'link' => '?m=gpondasanoltlist',
						'tip' => trans('OLT list'),
						'prio' => 10,
					),
					array(
						'name' => trans('New OLT'),
						'link' => '?m=gpondasanoltadd',
						'tip' => trans('New OLT'),
						'prio' => 20,
					),
					array(
						'name' => trans('OLT search'),
						'link' => '?m=gpondasanoltsearch',
						'tip' => trans('OLT search'),
						'prio' => 30,
					),
					array(
						'name' => '------------',
						'prio' => 35,
					),
					array(
						'name' => trans('Detect ONU'),
						'link' => '?m=gpondasanonucheck',
						'tip' => trans('Detect ONU'),
						'prio' => 37,
					),
					array(
						'name' => trans('ONU list'),
						'link' => '?m=gpondasanonulist',
						'tip' => trans('ONU list'),
						'prio' => 40,
					),
					array(
						'name' => trans('New ONU'),
						'link' => '?m=gpondasanonuadd',
						'tip' => trans('New ONU'),
						'prio' => 50,
					),
					array(
						'name' => trans('ONU search'),
						'link' => '?m=gpondasanonusearch',
						'tip' => trans('ONU search'),
						'prio' => 60,
					),
					array(
						'name' => '------------',
						'prio' => 65,
					),
					array(
						'name' => trans('ONU model list'),
						'link' => '?m=gpondasanonumodelslist',
						'tip' => trans('ONU model list'),
						'prio' => 70,
					),
					array(
						'name' => trans('New ONU model'),
						'link' => '?m=gpondasanonumodelsadd',
						'tip' => trans('New ONU model'),
						'prio' => 80,
					),
					array(
						'name' => '------------',
						'prio' => 85,
					),
					array(
						'name' => trans('TV channel list'),
						'link' => '?m=gpondasanonutvlist',
						'tip' => trans('TV channel list'),
						'prio' => 90,
					),
					array(
						'name' => trans('New TV channel'),
						'link' => '?m=gpondasanonutvadd',
						'tip' => trans('New TV channel'),
						'prio' => 100,
					),
					array(
						'name' => '------------',
						'prio' => 110,
					),
					array(
						'name' => trans('Configuration'),
						'link' => '?m=configlist&s=gpon-dasan',
						'tip' => trans('Configuration'),
						'prio' => 120,
					),
				),
			),
		);

		if (!ConfigHelper::getConfig('phpui.gpon_use_radius')) {
			array_push($menu_gpon['GPON_DASAN']['submenu'], array(
				'name' => trans('ONU auto-connection'),
				'link' => '?m=gpondasanonuscript',
				'tip' => trans('ONU auto-connection'),
				'prio' => 38,
			));
		}

		$menu_keys = array_keys($hook_data);
		$i = array_search('netdevices', $menu_keys);
		array_splice($hook_data, $i + 1, 0, $menu_gpon);

		return $hook_data;
	}

    /**
     * Modifies access table
     * 
     */
	public function accessTableInit() {
		$access = AccessRights::getInstance();

		$access->insertPermission(new Permission('gpondasan_full_access', trans('GPON - module management'), '^gpon.*$'),
			AccessRights::FIRST_FORBIDDEN_PERMISSION);
		$access->insertPermission(new Permission('gpondasan_read_only', trans('GPON - information review'),
			'^((gponolt|gpononu|gpononumodels)(info|list|search|tvinfo|tvlist|signalimage)|gponoffline)$'),
			AccessRights::FIRST_FORBIDDEN_PERMISSION);
		$access->insertPermission(new Permission('gpondasan_auto_provisioning', trans('GPON - auto provisioning (new onu)'),
			'^(gpononu(add|script|edit|check))$'), AccessRights::FIRST_FORBIDDEN_PERMISSION);
	}
}

?>
