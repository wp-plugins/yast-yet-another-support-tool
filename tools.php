<?php
global $YAST_tools;
$YAST_tools=new YAST_tools();
class YAST_tools {

    function YAST_tools() {

    }

    function host($url){
	if(false !== $url_attr = parse_url($url)){
	    return $url_attr['host'];
	}
	return false;
    }

    function navigator($navigator = false) {
	$nav = array(
	    'device'=>array( 'type' => ''),
	    'os' => array('name' => '', 'version' => ''),
	    'browser' => array('name' => '', 'version' => ''),
	);
	if (!$navigator) {
	    $navigator = $_SERVER['HTTP_USER_AGENT'];
	}
	//DEVICE
	if (strpos($navigator, 'tablet') !== FALSE || strpos($navigator, 'Tablet') !== FALSE) {
	    $nav['device']['type'] = 'tablet';
	}
	elseif (strpos($navigator, 'mobil') !== FALSE || strpos($navigator, 'Mobil') !== FALSE) {
	    $nav['device']['type'] = 'mobile';
	}
	else {
	    $nav['device']['type'] = 'desktop';
	}
	//OS
	if (strpos($navigator, 'Windows') !== FALSE) {
	    $nav['os']['name'] = 'Windows';
	    if (strpos($navigator, 'NT 6.0') !== FALSE) {
		$nav['os']['version'] = 'vista';
	    }
	    if (strpos($navigator, 'NT 6.1') !== FALSE) {
		$nav['os']['version'] = '7';
	    }
	    if (strpos($navigator, 'NT 6.3') !== FALSE) {
		$nav['os']['version'] = '8';
	    }
	}
	elseif (strpos($navigator, 'Android') !== FALSE) {
	    $nav['os']['name'] = 'Android';
	    preg_match_all('#Android ([0-9][0-9]?\.?[0-9]?[0-9]?)#i',$navigator,$v);
	    if (false !== $v) {
		$nav['os']['version'] = $v[1][0];
	    }
	}
	elseif (strpos($navigator, 'Ubuntu') !== FALSE) {
	    $nav['os']['name'] = 'Linux';
	}
	elseif (strpos($navigator, 'Linux') !== FALSE) {
	    $nav['os']['name'] = 'Linux';
	}
	elseif (strpos($navigator, 'Ios') !== FALSE) {
	    $nav['os']['name'] = 'iOs';
	    preg_match_all('#iP[a-Z \.] OS ([0-9][0-9]?_?[0-9]?[0-9]?)#i',$navigator,$v);
	    if (false !== $v) {
		$nav['os']['version'] = $v[1][0];
	    }
	}
	elseif (strpos($navigator, 'Apple') !== FALSE || strpos($navigator, 'Macintosh') !== FALSE) {
	    $nav['os']['name'] = 'MacOS';
	    if (strpos($navigator, 'Mac OS X') !== FALSE) {
		$nav['os']['version'] = 'X';
	    }
	    if (strpos($navigator, 'Mac_PowerPC') !== FALSE) {
		$nav['os']['version'] = 'X';
	    }
	}
	elseif (strpos($navigator, 'BlackBerry') !== FALSE) {
	    $nav['os']['name'] = 'BlackBerry ';
	}

/*
	// BROWSER
	if (strpos($navigator, 'WebKit') !== FALSE) {
	    $nav['browser']['engine'] = 'webkit';
	}
	elseif (strpos($navigator, 'Gecko') !== FALSE) {
	    $nav['browser']['engine'] = 'gecko';
	}
	elseif (strpos($navigator, 'Trident') !== FALSE) {
	    $nav['browser']['engine'] = 'trident';
	}*/

	//
	if (strpos($navigator, 'Firefox') !== FALSE) {
	    $nav['browser']['name'] = 'Firefox';
	    preg_match_all('#Firefox/([0-9][0-9]?\.?[0-9]?)#i',$navigator,$v);
	    if (false !== $v) {
		$nav['browser']['version'] = $v[1][0];
	    }
	}
	elseif (strpos($navigator, 'Opera') !== FALSE) {
	    $nav['browser']['name'] = 'Opera';
	    preg_match_all('#Opera ([0-9][0-9]?\.?[0-9]?[0-9]?)#i',$navigator,$v);
	    if (false !== $v) {
		$nav['browser']['version'] = $v[1][0];
	    }
	}
	elseif (strpos($navigator, 'Safari') !== FALSE) {
	    $nav['browser']['name'] = 'Safari';
	    preg_match_all('#Chrome/([0-9][0-9]?)#i',$navigator,$v);
	    if (false !== $v) {
		$nav['browser']['version'] = $v[1][0];
	    }
	}
	elseif (strpos($navigator, 'Chrome') !== FALSE) {
	    $nav['browser']['name'] = 'Chrome';
	    preg_match_all('#Chrome/([0-9][0-9]?)#i',$navigator,$v);
	    if (false !== $v) {
		$nav['browser']['version'] = $v[1][0];
	    }
	}
	elseif (strpos($navigator, 'MSIE') !== FALSE) {
	    $nav['browser']['name'] = 'IE';
	    preg_match_all('#MSIE ([0-9][0-9]?)#i',$navigator,$v);
	    if (false !== $v) {
		$nav['browser']['version'] = $v[1][0];
	    }
	}
	else {
	    $nav['browser']['name'] = 'robot';
	}
	return $nav;
    }
}