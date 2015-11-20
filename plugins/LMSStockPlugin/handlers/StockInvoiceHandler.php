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
 * InvoiceHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class StockInvoiceHandler {
    /**
     * @param array $hook_data Hook data
     */
	public function invoicenewSaveBeforeSubmit(array $hook_data) {
		LMSDB::getInstance()->LockTables(array('documents', 'cash', 'invoicecontents', 'numberplans', 'divisions',
			'stck_stockassignments', 'stck_stock'));

		return $hook_data;
	}

	public function invoicenewSaveAfterSubmit(array $hook_data) {
		global $LMSST;

		$contents = &$hook_data['contents'];
		$invoice = &$hook_data['invoice'];
		foreach ($contents as $ct)
			if ($ct['stckproductid'])
				$LMSST->StockSell($invoice['id'], $ct['stckproductid'], $ct['valuebrutto'], $invoice['cdate']);

		return $hook_data;
	}
}

?>
