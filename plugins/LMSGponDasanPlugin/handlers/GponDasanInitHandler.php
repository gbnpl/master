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
     * Sets plugin managers
     * 
     * @param LMS $hook_data Hook data
     */
	public function lmsInit(LMS $hook_data) {
		global $GPON;

		$db = $hook_data->getDb();
		$auth = $hook_data->getAuth();

		$hook_data->SetNetDevManager(
			new GponDasanLMSNetDevManager($db, $auth, $hook_data->getCache(), $hook_data->getSyslog())
		);

		$GPON = new GPON($db, $auth);

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
		$plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponDasanPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'templates';
		array_unshift($template_dirs, $plugin_templates);
		$hook_data->setTemplateDir($template_dirs);

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
			'GPON' => array(
				'name' => 'GPON DASAN',
				'img' => LMSGponDasanPlugin::plugin_directory_name . '/gponolt.gif',
				'link' =>'?m=gponoltlist',
				'tip' => 'Zarządzanie GPON',
				'accesskey' =>'k',
				'prio' => 11,
				'submenu' => array(
					array(
						'name' => 'Lista <b>OLT</b>',
						'link' => '?m=gponoltlist',
						'tip' => 'Lista OLT',
						'prio' => 10,
					),
					array(
						'name' => 'Nowy <b>OLT</b>',
						'link' => '?m=gponoltadd',
						'tip' => 'Dodaj OLT',
						'prio' => 20,
					),
					array(
						'name' => trans('Search').' <b>OLT</b>',
						'link' => '?m=gponoltsearch',
						'tip' => 'Szukaj OLT',
						'prio' => 30,
					),
					array(
						'name' => '------------',
						'prio' => 35,
					),
					array(
						'name' => 'Wykryj <b>ONU</b>',
						'link' => '?m=gpononucheck',
						'tip' => 'Wykryj ONU',
						'prio' => 37,
					),
					array(
						'name' => 'Lista <b>ONU</b>',
						'link' => '?m=gpononulist',
						'tip' => 'Lista ONU',
						'prio' => 40,
					),
					array(
						'name' => 'Nowy <b>ONU</b>',
						'link' => '?m=gpononuadd',
						'tip' => 'Dodaj ONU',
						'prio' => 50,
					),
					array(
						'name' => trans('Search').' <b>ONU</b>',
						'link' => '?m=gpononusearch',
						'tip' => 'Szukaj ONU',
						'prio' => 60,
					),
					array(
						'name' => '------------',
						'prio' => 65,
					),
					array(
						'name' => 'Lista modeli <b>ONU</b>',
						'link' => '?m=gpononumodelslist',
						'tip' => 'Lista modeli ONU',
						'prio' => 70,
					),
					array(
						'name' => 'Nowy model <b>ONU</b>',
						'link' => '?m=gpononumodelsadd',
						'tip' => 'Dodaj model ONU',
						'prio' => 80,
					),
					array(
						'name' => '------------',
						'prio' => 85,
					),
					array(
						'name' => 'Lista kanałów TV',
						'link' => '?m=gpononutvlist',
						'tip' => 'Lista kanałów TV',
						'prio' => 90,
					),
					array(
						'name' => 'Nowy kanał TV',
						'link' => '?m=gpononutvadd',
						'tip' => 'Dodaj kanał TV',
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
			array_push($menu_gpon['GPON']['submenu'], array(
				'name' => 'Auto podłączanie <b>ONU</b>',
				'link' => '?m=gpononuscript',
				'tip' => 'Auto podłączanie ONU',
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

		$access->insertPermission(new Permission('gpon_full_access', trans('GPON - module management'), '^gpon.*$'),
			AccessRights::FIRST_FORBIDDEN_PERMISSION);
		$access->insertPermission(new Permission('gpon_read_only', trans('GPON - information review'),
			'^((gponolt|gpononu|gpononumodels)(info|list|search|tvinfo|tvlist|signalimage)|gponoffline)$'),
			AccessRights::FIRST_FORBIDDEN_PERMISSION);
		$access->insertPermission(new Permission('gpon_auto_provisioning', trans('GPON - auto provisioning (new onu)'),
			'^(gpononu(add|script|edit|check))$'), AccessRights::FIRST_FORBIDDEN_PERMISSION);
	}
}

?>
