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
class JamboxInitHandler {
    /**
     * Sets plugin managers
     * 
     * @param LMS $hook_data Hook data
     */
	public function lmsInit(LMS $hook_data) {
		global $LMSTV;

		$LMSTV = new LMSTV($hook_data->getDb(), $hook_data->getAuth(), $hook_data->getSyslog());

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
		$plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSJamboxPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'templates';
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
		$plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSJamboxPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'modules';
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
		$menu_jambox = array(
			'Jambox' => array(
				'name' => 'TV',
				'img' => LMSJamboxPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'tv_icon.png',
				'tip' => 'TV Management',
				'accesskey' =>'t',
				'prio' => 12,
				'submenu' => array(
					array(
						'name' => trans('Lista klientów'),
						'link' => '?m=tvcustomers',
						'tip' => trans('Lista klientów'),
						'prio' => 10,
					),
					array(
						'name' => trans('Lista dostępnych pakietów'),
						'link' => '?m=tvpackageslist',
						'tip' => trans('Lista dostępnych pakietów'),
						'prio' => 20,
					),
					array(
						'name' => trans('Lista STB'),
						'link' => '?m=tvstblist',
						'tip' => trans('Lista STB'),
						'prio' => 30,
					),
					array(
						'name' => trans('Podziel podsieć'),
						'link' => '?m=tvsubnetlist',
						'tip' => trans('Podziel podsieć'),
						'prio' => 40,
					),
					array(
						'name' => trans('Lista zdarzeń bilingowych'),
						'link' => '?m=tvbillingevents',
						'tip' => trans('Lista zdarzeń bilingowych'),
						'prio' => 50,
					),
					array(
						'name' => trans('Lista wiadomości'),
						'link' => '?m=tvmessages',
						'tip' => trans('Lista wiadomości'),
						'prio' => 60,
					),
					array(
						'name' => trans('Nowa wiadomość'),
						'link' => '?m=tvmessagessend',
						'tip' => trans('Nowa wiadomość'),
						'prio' => 61,
					),
					array(
						'name' => trans('Odśwież dane'),
						'link' => '?m=tvcleancache',
						'tip' => trans('Odśwież dane'),
						'prio' => 71,
					),
					array(
						'name' => trans('Configuration'),
						'link' => '?m=configlist&s=jambox',
						'tip' => trans('Configuration'),
						'prio' => 81,
					)
				),
			),
		);
		$menu_keys = array_keys($hook_data);
		$i = array_search('networks', $menu_keys);

		$hook_data = array_merge(
			array_slice($hook_data, 0, $i, true),
			$menu_jambox,
			array_slice($hook_data, $i, null, true)
		);

		return $hook_data;
	}

	/**
	 * Modifies access table
	 *
	*/
	public function accessTableInit() {
		$access = AccessRights::getInstance();
		$access->insertPermission(new Permission('jambox_full_access', 'JAMBOX - Pełny dostęp',
			'^tv.*'), AccessRights::FIRST_FORBIDDEN_PERMISSION);
		$access->insertPermission(new Permission('jambox_read_only', 'JAMBOX - Tylko do odczytu',
			'^(tv(cleancache|messagessend|export|stb(add|remove)))$'),
			AccessRights::FIRST_FORBIDDEN_PERMISSION);
	}
}

?>
