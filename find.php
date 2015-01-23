<?php
//// GODKJENN AT SERVEREN SOM SØKER INFO HAR TILGANG
//$allow_ips[] = file_get_contents('http://videoconverter.ukm.no:88/api/my_ip.php');
//$allow_ips[] = gethostbyname('videoconverter.ukm.no');
//$allow_ips[] = '188.113.121.10';
//$allow_ips[] = '81.0.146.162';

//if(in_array($_SERVER['REMOTE_ADDR'], $allow_ips)) {
//    header('HTTP/1.0 404 Not Found');
//    exit();	
//}

/// SERVEREN HAR TILGANG
# Filer vi ikke bryr oss om
$ignoreExt = array('jpg','.php');
# Samle alle rekonverterte filer i dette arrayet
$found_files = array();
# Loop mappen vi forventer å finne filen i
getDirectory($_GET['path']);
# Fant ingen rekonverterte filer, bruk hurtigkonvertert

$data = new stdClass();
$data->found = sizeof($found_files) > 0;
 

if(sizeof($found_files) == 0) {
	$data->filepath	= $_GET['path'].'/'.$_GET['file'];
# Fant rekonverterte filer, bruk inntil videre den siste (beste kvalitet..?)
} else {
	$data->filepath = $_GET['path'].'/'.$found_files[sizeof($found_files)-1];
}

die( json_encode( $data ) );



function compareFile($found) {
	global $found_files;
	// Informasjon om filen som er funnet på lagringsserveren
	$found_lastdot = strrpos($found, '.');
	$found_filewoext = substr($found, 0, $found_lastdot);
	$found_len = strlen($found_filewoext);

	// Informasjon om filen vi søker
	$find_lastdot = strrpos($_GET['file'], '.');
	$find_filewoext = substr($_GET['file'], 0, $find_lastdot);
	$find_len = strlen($find_filewoext);
	
	// Hvis 
	//   <filnavnet vi søkers lengde> + <_XXXp> er helt likt <filen som er funnets lengde>
	// er dette en rekonvertert fil av originalen - bedre kvalitet, bedre filstørrelse
	if(($find_len+5 == $found_len) && strpos($found_filewoext, $find_filewoext) === 0) {
		$found_files[] = $found;
	}	
}
 


function getDirectory($path){
	global $ignoreExt;
	// Open the directory to the handle $dh
	$dh = @opendir( $path );
	// Loop through the directory
	while( false !== ( $file = readdir( $dh ) ) ){
		if( is_bool( $dh ) ) {
			die();
		}
		if(in_array($file, array('error_log','.','..')))	continue;
		#if( is_dir( "$path/$file" ) ){
		#	// Its a directory, so we need to keep reading down...
		#	getDirectory( "$path/$file");    
		#} else {    
            $extension = pathinfo($file, PATHINFO_EXTENSION);
			if(in_array(strtolower($extension),$ignoreExt))
				continue;
			compareFile($file);
		#}
	}
    // Close the directory handle
    closedir( $dh );
}
?>