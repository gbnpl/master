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
 * QuickSearchHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class StockQuickSearchHandler {
    /**
     * @param array $hook_data Hook data
     */
	public function quickSearchAfterSubmit(array $hook_data) {
		$mode = &$hook_data['mode'];
		$search = &$hook_data['search'];
		$sql_search = &$hook_data['sql_search'];
		$SESSION = &$hook_data['session'];
		$target = &$hook_data['target'];

		require_once(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSStockPlugin::plugin_directory_name
			. DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'stckquicksearch.php');

		return $hook_data;
	}
}

?>
