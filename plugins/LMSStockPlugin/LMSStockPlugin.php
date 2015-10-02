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
 * LMSStockPlugin
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class LMSStockPlugin extends LMSPlugin {
	const plugin_directory_name = 'LMSStockPlugin';
	const PLUGIN_DBVERSION = '2015091400';
	const PLUGIN_NAME = 'Stock';
	const PLUGIN_DESCRIPTION = 'Stock Support';
	const PLUGIN_AUTHOR = 'Krzysztof Michalski &lt;k.michalski@maxcon.pl&gt;<br>poprawki - Grzegorz Cichowski &lt;gcichowski@gmail.com&gt;<br>Tomasz Chiliński &lt;tomasz.chilinski@chilan.com&gt;';

	public function registerHandlers() {
		$this->handlers = array(
			'lms_initialized' => array(
				'class' => 'StockInitHandler',
				'method' => 'lmsInit'
			),
			'smarty_initialized' => array(
				'class' => 'StockInitHandler',
				'method' => 'smartyInit'
			),
			'modules_dir_initialized' => array(
				'class' => 'StockInitHandler',
				'method' => 'modulesDirInit'
			),
			'menu_initialized' => array(
				'class' => 'StockInitHandler',
				'method' => 'menuInit'
			),
			'access_table_initialized' => array(
				'class' => 'StockInitHandler',
				'method' => 'accessTableInit'
			),
			'quicksearch_after_submit' => array(
				'class' => 'StockQuickSearchHandler',
				'method' => 'quickSearchAfterSubmit',
			),
			'invoicenew_save_before_submit' => array(
				'class' => 'StockInvoiceHandler',
				'method' => 'invoicenewSaveBeforeSubmit',
			),
			'invoicenew_save_after_submit' => array(
				'class' => 'StockInvoiceHandler',
				'method' => 'invoicenewSaveAfterSubmit',
			)
		);
	}
}
