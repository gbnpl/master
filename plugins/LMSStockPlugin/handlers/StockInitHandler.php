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
class StockInitHandler {
    /**
     * Sets plugin managers
     * 
     * @param LMS $hook_data Hook data
     */
	public function lmsInit(LMS $hook_data) {
		global $LMSST;

		$db = $hook_data->getDb();
		$auth = $hook_data->getAuth();
		$LMSST = new LMSST($db, $auth, $hook_data);

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
		$plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSStockPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'templates';
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
		$plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSStockPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'modules';
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
		$menu_stock = array(
			'stock' => array(
				'name' => trans('Warehouse'),
				'link' => '?m=stck',
				'img' => LMSStockPlugin::plugin_directory_name . '/stck.png',
				'tip' => trans('Stock management'),
				'prio' => 26,
				'submenu' => array(
					array(
						'name' => trans('Stock'),
						'link' => '?m=stckstock',
						'prio' => 1
					),
					array(
						'name' => trans('List receive notes'),
						'link' => '?m=stckreceivenotelist',
						'prio' => 5
					),
					array(
						'name' => trans('New receive note'),
						'link' => '?m=stckreceiveadd',
						'prio' => 6
					),
					array(
						'name' => trans('Manufacturers'),
						'link' => '?m=stckmanufacturerlist',
						'prio' => 10
					),
					array (
						'name' => trans('Add manufacturer'),
						'link' => '?m=stckmanufactureradd',
						'prio' => 11
					),
					array (
						'name' => trans('Product list'),
						'link' => '?m=stckproductlist',
						'prio' => 21
					),
					array (
						'name' => trans('New product'),
						'link' => '?m=stckproductadd',
						'prio' => 22
					),
					array (
						'name' => trans('Groups'),
						'link' => '?m=stckgrouplist',
						'prio' => 30
					),
					array (
						'name' => trans('New Group'),
						'link' => '?m=stckgroupadd',
						'prio' => 31
					),
					array (
						'name' => trans('Warehouses'),
						'link' => '?m=stckwarehouselist',
						'prio' => 80
					),
					array (
						'name' => trans('New warehouse'),
						'link' => '?m=stckwarehouseadd',
						'prio' => 81
					),
					array (
						'name' => trans('Reports'),
						'link' => '?m=printstock',
						'prio' => 99
					),
				),
			),
		);

		$menu_keys = array_keys($hook_data);
		$i = array_search('hosting', $menu_keys);

		$hook_data = array_merge(
			array_slice($hook_data, 0, $i, true),
			$menu_stock,
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

		$access->insertPermission(new Permission('stock_full_access', trans('stock management'),
			'^stck.*$'), AccessRights::FIRST_FORBIDDEN_PERMISSION);
		$access->insertPermission(new Permission('stock_reports', trans('stock reports'),
			'^printstock$'), AccessRights::FIRST_FORBIDDEN_PERMISSION);
	}
}

?>
