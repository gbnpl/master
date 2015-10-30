<?php

if (isset($_REQUEST['uploadTest'])) { 
        $size = 500; 
        $request = isset($_REQUEST)?$_REQUEST:$HTTP_POST_VARS; 
        foreach ($request as $key => $value) { 
          $size += (strlen($key) + strlen($value) + 3); 
        } 
        $v = sprintf("size=%s&x=%s&", $size, $_REQUEST['x']); 
        header('VSPEED: ' . $v); 
}
elseif (isset($_REQUEST['latencyTest'])) { 
        $v = sprintf("test=test&t=%d&", microtime(true)); 
        header('VSPEED: ' . $v); 
}
elseif (isset($_REQUEST['getIP'])) {
    printf("ip=%s", $_SERVER['REMOTE_ADDR']);
}
elseif (isset($_REQUEST['monitoring'])) {
    printf("server=%s", 'alive');
}

exit();

?>
