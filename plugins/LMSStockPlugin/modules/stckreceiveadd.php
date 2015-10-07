<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

$layout['pagetitle'] = trans('Add receive note');
$error = NULL;

$receivenote = array();
$receivenote['value']['net'] = 0;
$receivenote['value']['gross'] = 0;

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SESSION->restore('receivenote', $receivenote);

if ($receivenote) {
	$SESSION->redirect('?m=stckreceiveproductlist');
}

if (ctype_digit($_GET['sid'])) {
	$receivenote['doc']['supplierid'] = $_GET['sid'];
	$receivenote['doc']['supplier'] = $LMS->GetCustomerName($receivenote['doc']['supplierid']);
}

if (isset($_POST['receivenote']) && !$_GET['sid']) {
	$receivenote = $_POST['receivenote'];

//	if (($receivenote['doc']['supplierid'] == '' || !ctype_digit($receivenote['doc']['supplierid'])) && !$receivenote['doc']['internal'])
//		$error['supplier'] = trans('Incorrect supplier!');
	
	if (!ctype_digit($receivenote['doc']['supplierid']) && !$receivenote['doc']['internal'])
		$error['supplier'] = trans('Incorrect supplier!');
	
	if ($receivenote['doc']['supplierid'] == '' && !$receivenote['doc']['internal'])
		$error['supplier'] = trans('Incorrect supplier!');
	
	if ($receivenote['doc']['internal'])
		$receivenote['doc']['supplierid'] = null;
	
	if ($receivenote['doc']['date']['settlement'] == '' || !isset($receivenote['doc']['date']['settlement']))
		$error['settlement'] = trans('Settlement date can\'t be empty!');
	
	if ($receivenote['doc']['date']['sale'] == '' || !isset($receivenote['doc']['date']['sale']))
		$error['sale'] = trans('Sale date can\'t be empty!');
	
	if ($receivenote['doc']['date']['deadline'] == '' || !isset($receivenote['doc']['date']['deadline']))
		$error['deadline'] = trans('Deadline date can\'t be empty!');

	if ($receivenote['doc']['number'] == '')
		$error['number'] = trans('Document number can\'t be empty!');
	else
		$receivenote['doc']['number'] = strtoupper($receivenote['doc']['number']);
	
	foreach ($receivenote['doc']['date'] as $k=>$v) {
		if ($v == '')
			$error[$k] = trans(ucfirst($k).' date can`t be empty!');
		else
			if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $v)) {
				list($y, $m, $d) = explode('/', $v);
				if(checkdate($m, $d, $y)) {
					$id = mktime(0, 0, 0, $m, $d, $y);
					if($id > time() && $k != 'deadline')
						$error[$k] = trans('Incorrect future date!');
				} else
					$error[$k] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
			} else
				$error[$k] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}
	
	if (!$error) {
		foreach ($receivenote['doc']['date'] as $k=>$v)
			$receivenote['doc']['date'][$k] = LMSST::DateChange($v);

		$SESSION->remove('receivenote');
		$SESSION->save('receivenote', $receivenote);
		$SESSION->redirect('?m=stckreceiveproductlist');
	}
}

if (!$receivenote['doc']['date']['settlement'])
	$receivenote['doc']['date']['settlement'] = date("Y/m/d"); 
if (!$receivenote['doc']['date']['sale'])
	$receivenote['doc']['date']['sale'] = date("Y/m/d");
if (!$receivenote['doc']['date']['deadline'])
	$receivenote['doc']['date']['deadline'] = date("Y/m/d");

$SMARTY->assign('error', $error);
$SMARTY->assign('receivenote', $receivenote);
$SMARTY->display('stckreceiveadd.html');

?>
