<?php

/*
 * LMS version 2.00 Ap-media
 *
 *  $Id$
 */


if ($_GET['id'])
{
    $rdata = $LMS->DB->GetRow("SELECT INET_NTOA(ipaddr) AS nas, numport AS oltport, g.name, d.secret FROM gpononu g
			JOIN gpononu2olt go ON go.gpononuid = g.id
			JOIN netdevices d ON go.netdevicesid = d.id
			JOIN nodes n ON n.netdev = d.id
			WHERE g.id = ? AND ownerid = 0", array($_GET['id']));

    $q="echo \"Dasan-Gpon-Olt-Id=".$rdata['oltport'].",Dasan-Gpon-Onu-Serial-Num=".$rdata['name']." \"| radclient -r 1 ".$rdata['nas']."  disconnect ".$rdata['secret'];
    system($q." >/dev/null");
    $GPON->Log(4, 'gpononu', $_GET['id'], 'Disconnect Message send.');
}

$SESSION->redirect('?m=gpononuinfo&id='.$_GET['id']);

?>
