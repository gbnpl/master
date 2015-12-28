<?php

$DB = LMSDB::getInstance();

switch ($mode) {
	case 'supplier':
		if(isset($_GET['ajax'])) // support for AutoSuggest
		{
			$candidates = $DB->GetAll("SELECT c.id, cc.contact AS email, address, post_name, post_address, deleted,
			    ".$DB->Concat('UPPER(lastname)',"' '",'c.name')." AS username
				FROM customerview c
				LEFT JOIN customercontacts cc ON cc.customerid = c.id AND cc.type = " . CONTACT_EMAIL . "
				WHERE ".(preg_match('/^[0-9]+$/', $search) ? 'c.id = '.intval($search).' OR ' : '')."
					LOWER(".$DB->Concat('lastname',"' '",'c.name').") ?LIKE? LOWER($sql_search)
					OR LOWER(address) ?LIKE? LOWER($sql_search)
					OR LOWER(post_name) ?LIKE? LOWER($sql_search)
					OR LOWER(post_address) ?LIKE? LOWER($sql_search)
					OR LOWER(cc.contact) ?LIKE? LOWER($sql_search)
				ORDER by deleted, username, email, address
				LIMIT 15");

			$eglible=array(); $actions=array(); $descriptions=array();
			if ($candidates)
			foreach($candidates as $idx => $row) {
				switch ($_GET['source']) {
					case 'rne':
						$actions[$row['id']] = '?m=stckreceivenoteedit&id='.$_GET['sid'].'&sid='.$row['id'];
						break;
					default:
						$actions[$row['id']] = '?m=stckreceiveadd&sid='.$row['id'];
						break;
				}
				$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
				    .truncate_str($row['username'], 50).($row['deleted'] ? '</font>' : ''));

				if (preg_match("~^$search\$~i",$row['id'])) {
				    $descriptions[$row['id']] = escape_js(trans('Id:').' '.$row['id']);
				    continue;
				}
				if (preg_match("~$search~i",$row['username'])) {
				    $descriptions[$row['id']] = '';
				    continue;
				}
				if (preg_match("~$search~i",$row['address'])) {
				    $descriptions[$row['id']] = escape_js(trans('Address:').' '.$row['address']);
				    continue;
				}
				else if (preg_match("~$search~i",$row['post_name'])) {
				    $descriptions[$row['id']] = escape_js(trans('Name:').' '.$row['post_name']);
				    continue;
				}
				else if (preg_match("~$search~i",$row['post_address'])) {
				    $descriptions[$row['id']] = escape_js(trans('Address:').' '.$row['post_address']);
				    continue;
				}
				if (preg_match("~$search~i",$row['email'])) {
				    $descriptions[$row['id']] = escape_js(trans('E-mail:').' '.$row['email']);
				    continue;
				}
				$descriptions[$row['id']] = '';
			}
			header('Content-type: text/plain');
			if ($eglible) {
				print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
				print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
				print "this.actions = [\"".implode('","',$actions)."\"];\n";
			} else {
				print "false;\n";
			}
			exit;
		}

		if(is_numeric($search)) // maybe it's customer ID
		{
			if($customerid = $DB->GetOne('SELECT id FROM customerview WHERE id = '.$search))
			{
				$target = '?m=customerinfo&id='.$customerid;
				break;
			}
	    }
		break;

	case 'product':
		if(isset($_GET['ajax'])) {
			$candidates = $DB->GetAll("SELECT ".$DB->Concat('m.name', "' '", 'p.name')." AS name, p.id, p.ean, p.quantity
			FROM stck_products p
			LEFT JOIN stck_manufacturers m ON p.manufacturerid =  m.id
			WHERE ".(preg_match('/^[0-9]+$/', $search) ? 'p.id = '.intval($search).' OR ' : '')."
				LOWER(".$DB->Concat('m.name',"' '",'p.name').") ?LIKE? LOWER($sql_search)
				OR LOWER(p.ean) ?LIKE? LOWER($sql_search)
				ORDER by name, id
				LIMIT 15");
			
			$eglible=array(); $actions=array(); $descriptions=array();
			if ($candidates)
				 foreach($candidates as $idx => $row) {
					$actions[$row['id']] = 'javascript:pad(\''.$row['id'].'\')';
					$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
					.truncate_str($row['name'], 150).($row['deleted'] ? '</font>' : ''));

					if (preg_match("~^$search\$~i",$row['id'])) {
						$descriptions[$row['id']] = escape_js(trans('Id:').' '.$row['id']);
						continue;
					}
					if (preg_match("~$search~i",$row['name'])) {
						$descriptions[$row['id']] = '';
						continue;
					}
					if (preg_match("~$search~i",$row['ean'])) {
						$descriptions[$row['id']] = escape_js(trans('EAN:').' '.$row['ean']);
						continue;
					}
					$descriptions[$row['id']] = '';
				 }

				 header('Content-type: text/plain');
				 if ($eglible) {
				 	print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
					print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
					print "this.actions = [\"".implode('","',$actions)."\"];\n";
				} else {
					print "false;\n";
				}
				exit;
			}
			
			if(is_numeric($search)) { // maybe it's product ID
				if($customerid = $DB->GetOne('SELECT id FROM stck_products WHERE id = '.$search)) {
					$target = '?m=customerinfo&id='.$customerid;
					break;
				}
			}
		break;

	case 'productinv':
		$search = str_replace(' ', '%', $search);
		if(isset($_GET['ajax'])) {
			$candidates = $DB->GetAll("SELECT s.id, s.productid, s.serialnumber, s.pricebuynet, s.pricebuygross, s.bdate,
				".$DB->Concat('m.name',"' '",'p.name')." as name, p.ean
				FROM stck_stock s
				LEFT JOIN stck_products p ON p.id = s.productid
				LEFT JOIN stck_manufacturers m ON m.id = p.manufacturerid
				LEFT JOIN stck_warehouses w ON s.warehouseid = w.id
				WHERE s.leavedate = 0 AND
				(".(preg_match('/^[0-9]+$/', $search) ? 's.productid = '.intval($search).' OR ' : '')."
				LOWER(".$DB->Concat('m.name',"' '",'p.name').") ?LIKE? LOWER(".$sql_search.")
				OR LOWER(p.ean) ?LIKE? LOWER(".$sql_search.")
				OR LOWER(s.serialnumber) ?LIKE? LOWER(".$sql_search."))
				AND w.commerce = 1
				ORDER BY s.creationdate ASC, name
				LIMIT 15");
			//	if ($DB->_error)
			/*$candidates = $DB->GetAll("SELECT ".$DB->Concat('m.name', '" "', 'p.name')." AS name, p.id, p.ean, p.quantity
				FROM stck_products p
				LEFT JOIN stck_manufacturers m ON p.manufacturerid = m.id
				WHERE ".(preg_match('/^[0-9]+$/', $search) ? 'p.id = '.intval($search).' OR ' : '')."
				LOWER(".$DB->Concat('m.name',"' '",'p.name').") ?LIKE? LOWER($sql_search)
				OR LOWER(p.ean) ?LIKE? LOWER($sql_search)
				ORDER by name, id
				LIMIT 15");*/

				$eglible=array(); $actions=array(); $descriptions=array();
				if ($candidates)
					foreach($candidates as $idx => $row) {
						$desc = $row['name'];
						
						if ($row['serialnumber'])
							$desc = $desc." (S/N: ".$row['serialnumber'].")";

						$actions[$row['id']] = 'javascript:pinv(\''.$row['id'].'\',\''.$row['pricebuynet'].'\',\''.$row['pricebuygross'].'\')';
						$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
						.truncate_str($desc, 100).($row['deleted'] ? '</font>' : ''));

						$descriptions[$row['id']] = '('.$row['id'].') '.trans("Bought:")." ".date("d/m/Y", $row['bdate']);;

					/*	if (preg_match("~^$search\$~i",$row['id'])) {
							$descriptions[$row['id']] = $descriptions[$row['id']].escape_js(trans('Id:').' '.$row['id']);
							continue;
						}
				
						if (preg_match("~$search~i",$row['name'])) {
							//$descriptions[$row['id']] = '';
							continue;
						}
						
						if (preg_match("~$search~i",$row['ean'])) {
							$descriptions[$row['id']] = $descriptions[$row['id']].escape_js(trans('EAN:').' '.$row['ean']);
							continue;
						}

						if (preg_match("~$search~i",$row['serialnumber'])) {
							$descriptions[$row['id']] = $descriptions[$row['id']].escape_js(trans('S/N:').' '.$row['serialnuber']);
							continue;
						}*/
						//continue;
					}

			header('Content-type: text/plain');
			if ($eglible) {
				print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
				print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
				print "this.actions = [\"".implode('","',$actions)."\"];\n";
			} else {
				print "false;\n";
			}
			exit;
			}

		/*	if(is_numeric($search)) { // maybe it's product ID
				if($customerid = $DB->GetOne('SELECT id FROM stck_products WHERE id = '.$search)) {
					$target = '?m=customerinfo&id='.$customerid;
					break;
				}
			}*/
		break;

	case 'productprice':
			$search = str_replace(' ', '%', $search);
			$sql_search = $DB->Escape("%$search%");
			if(isset($_GET['ajax'])) {
				$candidates = $DB->GetAll("SELECT s.id, s.productid, s.serialnumber, s.pricebuynet, s.pricebuygross, s.bdate,
					".$DB->Concat('m.name',"' '",'p.name')." as name, p.ean
					FROM stck_stock s
					LEFT JOIN stck_products p ON p.id = s.productid
					LEFT JOIN stck_manufacturers m ON m.id = p.manufacturerid
					LEFT JOIN stck_warehouses w ON s.warehouseid = w.id
					WHERE s.leavedate = 0 AND
					(".(preg_match('/^[0-9]+$/', $search) ? 's.productid = '.intval($search).' OR ' : '')."
					LOWER(".$DB->Concat('m.name',"' '",'p.name').") ?LIKE? LOWER(".$sql_search.")
					OR LOWER(p.ean) ?LIKE? LOWER(".$sql_search.")
					OR LOWER(s.serialnumber) ?LIKE? LOWER(".$sql_search."))
					AND w.commerce = 1
					ORDER BY s.creationdate ASC, name
					LIMIT 15");
				
				$eglible=array(); $actions=array(); $descriptions=array();
				if ($candidates)
					foreach($candidates as $idx => $row) {
						$desc = $row['name'];
						
						if ($row['serialnumber'])
							$desc = $desc." (S/N: ".$row['serialnumber'].")";

						//$actions[$row['id']] = 'javascript:pinv(\''.$row['id'].'\',\''.$row['pricebuynet'].'\',\''.$row['pricebuygross'].'\')';
						$actions[$row['id']] = '?m=stckproductinfo&id='.$row['productid'];
						$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
						.truncate_str($desc, 100).($row['deleted'] ? '</font>' : ''));

						$descriptions[$row['id']] = '<b>'.trans("Gross:")." ".$row['pricebuygross']."</b> ".trans("Bought:")." ".date("d/m/Y", $row['bdate']);;
					}

				header('Content-type: text/plain');
				if ($eglible) {
					print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
					print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
					print "this.actions = [\"".implode('","',$actions)."\"];\n";
				} else {
					print "false;\n";
				}


			}
			exit;
		break;

	case 'productwarranty':
			$search = str_replace(' ', '%', $search);
			$sql_search = $DB->Escape("%$search%");
			if(isset($_GET['ajax'])) {
				$candidates = $DB->GetAll("SELECT s.id, s.productid, s.serialnumber, s.pricesell, s.leavedate, s.warranty,
					".$DB->Concat('m.name',"' '",'p.name')." as name, p.ean
					FROM stck_stock s
					LEFT JOIN stck_products p ON p.id = s.productid
					LEFT JOIN stck_manufacturers m ON m.id = p.manufacturerid
					LEFT JOIN stck_warehouses w ON s.warehouseid = w.id
					WHERE
					LOWER(s.serialnumber) ?LIKE? LOWER(".$sql_search.")
					ORDER BY s.creationdate ASC, name
					LIMIT 15");
				
				$eglible=array(); $actions=array(); $descriptions=array();
				if ($candidates)
					foreach($candidates as $idx => $row) {
					$desc = $row['name'];

						if ($row['serialnumber'])
							$desc = $desc." (S/N: ".$row['serialnumber'].")";

						$actions[$row['id']] = '?m=stckproductinfo&id='.$row['productid'];
						$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
						.truncate_str($desc, 100).($row['deleted'] ? '</font>' : ''));

						if (is_null($row['warranty']))
							$row['warranty'] = 'b/d';

						if ($row['leavedate'] < 1)
							$row['leavedate'] = 'b/d';
						else
							$row['leavedate'] = date("d/m/Y", $row['leavedate']);

						$descriptions[$row['id']] = '<b>'.trans("Gross:")." ".moneyf($row['pricesell'])."</b> ".trans("Sold:")." ".$row['leavedate']." ".trans("Warranty:")." ".$row['warranty'];
					}

				header('Content-type: text/plain');
				if ($eglible) {
					print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
					print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
					print "this.actions = [\"".implode('","',$actions)."\"];\n";
				} else {
					print "false;\n";
				}


			}
			exit;			
		break;	
}

?>
