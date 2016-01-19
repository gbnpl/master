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

define('EOL', "\r\n");

if (isset($_POST['invproject']))
	$invproject = intval($_POST['invproject']);
if (!$invproject)
	die;

$day = $_POST['day'];
if (!empty($day) && preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $day)) {
	list ($year, $month, $day) = explode('/', $day);
	$date = mktime(23, 59, 59, $month, $day, $year);

} else
	$date = time();

$nodes = $DB->GetAll('SELECT c.id AS cid, c.type AS ctype,
	lst.ident AS street_ident, lst.name AS street_name,
	lc.ident AS city_ident, lc.name AS city_name,
	lb.ident AS borough_ident, lb.name AS borough_name,
	ld.ident AS district_ident, ld.name AS district_name,
	ls.ident AS state_ident, ls.name AS state_name,
	n.location_house, n.location_flat FROM nodes n
	JOIN customers c ON c.id = n.ownerid
	LEFT JOIN location_streets lst ON lst.id = n.location_street
	JOIN location_cities lc ON lc.id = n.location_city
	JOIN location_boroughs lb ON lb.id = lc.boroughid
	JOIN location_districts ld ON ld.id = lb.districtid
	JOIN location_states ls ON ls.id = ld.stateid
	WHERE ownerid > 0 AND invprojectid = ?', array($invproject));
if (empty($nodes))
	die;

$personals = '';
$bussinesses = '';
$personalcount = 0;
$bussinesscount = 0;

foreach ($nodes as $node) {
	$docnumber = $DB->GetOne('SELECT fullnumber FROM documents d
		WHERE d.customerid = ? AND d.type = ? AND d.cdate <= ?
		ORDER BY cdate DESC LIMIT 1',
		array($node['cid'], DOC_CONTRACT, $date));
	if (empty($docnumber))
		continue;
	$address = (!empty($node['street_name']) ? $node['street_name'] : '');
	if (!empty($node['location_house']))
		$address .= (!empty($address) ? ' ' : '') . $node['location_house'];
	$customer = array(
		$docnumber,
		$node['borough_name'],
		$node['city_name'],
		$address,
	);
	if ($node['ctype'] == CTYPES_PRIVATE) {
		array_unshift($customer, ++$personalcount);
		$personals .= implode('|', $customer) . EOL;
	} else {
		array_unshift($customer, ++$bussinesscount);
		$bussinesses .= implode('|', $customer) . EOL;
	}
}

// prepare zip archive package containing all generated files
if (!extension_loaded('zip'))
	die ('<B>Zip extension not loaded! In order to use this extension you must compile PHP with zip support by using the --enable-zip configure option. </B>');

$zip = new ZipArchive();
$filename = tempnam('/tmp', 'LMS_WWPE_').'.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)) {
	$zip->addFromString('PERSONALS.csv', $personals);
	$zip->addFromString('BUSSINESSES.csv', $bussinesses);
	$zip->close();

	// send zip archive package to web browser
	header('Content-type: application/zip');
	header('Content-Disposition: attachment; filename="LMS_WWPE.zip"');
	header('Pragma: public');
	readfile($filename);

	// remove already unneeded zip archive package file
	unlink($filename);
}

?>
