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
class HiperusInitHandler {
    /**
     * Sets plugin managers
     * 
     * @param LMS $hook_data Hook data
     */
	public function lmsInit(LMS $hook_data) {
		global $HIPERUS;

		$db = $hook_data->getDb();
		$HIPERUS = new LMSHiperus($db);

		return $hook_data;
	}

    /**
     * Sets plugin Smarty templates directory
     * 
     * @param Smarty $hook_data Hook data
     * @return \Smarty Hook data
     */
	public function smartyInit(Smarty $hook_data) {
		$hook_data->registerPlugin('modifier', 'seconds_to_hours', array('SecondsToHoursHelper', 'SecondsToHours'));

		$template_dirs = $hook_data->getTemplateDir();
		$plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSHiperusPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'templates';
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
		$plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSHiperusPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'modules';
		array_unshift($hook_data, $plugin_modules);
		return $hook_data;
	}

    /**
     * Sets plugin userpanel modules directory
     * 
     * @param array $hook_data Hook data
     * @return array Hook data
     */
	public function userpanelModulesDirInit(array $hook_data = array()) {
		$plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSHiperusPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'userpanel' . DIRECTORY_SEPARATOR;
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
		//unset($hook_data['VoIP']);
		$hook_data['VoIP'] = array(
			'name' => 'VoIP Hiperus C5',
			'img' =>'voip.gif',
			'link' =>'',
			'tip' => 'Telefonia Internetowa Hiperus',
			'accesskey' => '',
			'prio' => 12,
			'submenu' => array(
				array(
					'name' => 'Lista kont',
					'link' => '?m=hv_accountlist',
					'tip' => 'Lista Klientów',
					'prio' => 10,
				),
				array(
					'name' => 'Nowe konto',
					'link' => '?m=hv_accountadd',
					'tip' => 'Tworzenie nowego konta VoIP w  Hiperus C5',
					'prio' => 30,
				),
				array(
					'name' => 'Numery PSTN',
					'link' => '?m=hv_pstnrangelist',
					'tip' => 'Lista pól numerów PSTN',
					'prio' => 50,
				),
				array(
					'name' => 'Lista Terminali',
					'link' => '?m=hv_terminallist',
					'tip' => 'Lista Terminali',
					'prio' => 60,
				),
				array(
					'name' => 'Konfiguracja',
					'link' => '?m=configlist&page=1&s=hiperus_c5&n=',
					'tip' => '',
					'prio' => 70,
				),
			),
		);
		return $hook_data;
	}

    /**
     * Modifies access table
     * 
     */
	public function accessTableInit() {
		$access = AccessRights::getInstance();
		$access->insertPermission(new Permission('hiperus_full_access', 'Obsługa VoIP HIPERUS C5 - Pełny dostęp',
			'^hv_*'), AccessRights::FIRST_FORBIDDEN_PERMISSION);
		$access->insertPermission(new Permission('hiperus_read_only', 'Obsługa VoIP HIPERUS C5 - Tylko do odczytu',
			'^(hv_+(accountinfo|accountlist|billinginfoext|billinginfoext_print|pstnrangelist|pstnusagelist|searchemptypstn|searchterminallocation|terminallist))$'),
			AccessRights::FIRST_FORBIDDEN_PERMISSION);
	}
}

?>
