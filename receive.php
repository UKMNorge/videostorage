<?php

// To restrict uploading files to the videoconverter, the payload is signed
// with an hmac (shared secret between videoconverter and this box).
// Procedure is thus
//      1. Verify correct signature
//          - This makes sure we don't input any data not signed by the videoconverter into the rest
//            of the system
//      2. Verify timestamp
//          - Makes sure the request is fresh (ie not replayed by a passive observer. An observer can submit an identical
//            request, but we assume that the videoconverter won't sign anything that's harmful. If an attacker
//            wants to cause harm he'd have to find a file with a hash collision to the one sent in the original
//            request before the timestamp is invalidated, this should be infeasible)
//      3. Verify that the file has the correct hash
//      4. Either discard the file if any of the previous steps failed or save it if valid.
//
// Clients submitting data to this script need to provide the following:
//      file_path: The path where the file should be stored, relative to /var/www/ukmno/videos.
//      file_hash: The SHA-256 hash of the file being submitted (ala hash_file('sha256', $path_to_file))
//      timestamp: Current time when submitting (time())
//      sign: The data above concatanated and signed with the shared key. Sign the following construct:
//          $msg = "file_path=$file_path&file_hash=$file_hash&timestamp=$timestamp";
//          $sign = hash_hmac('sha256', $msg, $secret);
//
// The shared secret is stored in UKMconfig.inc.php, as UKM_VIDEOSTORAGE_UPLOAD_KEY.

require_once('UKMconfig.inc.php');

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: charset=utf-8');

// Used for constant-time string comparison to avoid timing attacks
function constant_time_str_compare($a, $b) {
    if (!is_string($a) || !is_string($b)) {
        return false;
    }

    $len = strlen($a);
    if ($len !== strlen($b)) {
        return false;
    }

    $status = 0;
    for ($i = 0; $i < $len; $i++) {
        $status |= ord($a[$i]) ^ ord($b[$i]);
    }
    return $status === 0;
}

$file_path = $_POST['file_path'];
$file_hash = $_POST['file_hash'];
$timestamp = $_POST['timestamp'];
$valid_request = true;

// Create the string that is signed
$message = "file_path=$file_path&file_hash=$file_hash&timestamp=$timestamp";

// Check that the signature is valid
$sign = $_POST['sign'];
$computed_sign = hash_hmac("sha256", $message, UKM_VIDEOSTORAGE_UPLOAD_KEY);
if (!constant_time_str_compare($computed_sign, $sign)) {
    error_log('Invalid signature');
    error_log('Message was: ' . $message);
    $valid_request = false;
}

// Check that timestamp is valid, ie that the request is not being replayed
// Make sure both servers run ntp or similar to stay in sync
// We allow up to ten minutes of difference, enough to not be too strict about
// clocks being in sync, but not enough for someone to compute hash collisions
// for the attachment
$now = time();
$time_passed_since_signing = $now - $timestamp;
$max_signing_delay = 60*10;
if ($time_passed_since_signing > $max_signing_delay) {
    error_log('Aborting, old timestamp. Given '. date('Y-m-d H:i:s',$timestamp) .' @ local time'. date('Y-m-d H:i:s',$now));
    $valid_request = false;
}

// Check that the attachment has the same hash as the one signed
$temp_filename = $_FILES['file']['tmp_name'];
$computed_file_hash = hash_file('sha256', $temp_filename);
if (!constant_time_str_compare($computed_file_hash, $file_hash)) {
    error_log('Aborting, attachment hashes does not validate.');
    $valid_request = false;
}

// If request is invalid, terminate without any indication of what went wrong,
// do not leak details like that to an attacker
if (!$valid_request) {
    $error_response = json_encode(array(
        'message' => 'Invalid request.',
        'success' => false,
    ));
    header( 'HTTP/1.1 400: BAD REQUEST' );
    #http_response_code(400);
    die($error_response);
}


// Everything good, continue and save the file
$base_folder = dirname( __FILE__ ) . DIRECTORY_SEPARATOR
            # . '..' . DIRECTORY_SEPARATOR
             . 'ukmno' . DIRECTORY_SEPARATOR
             . 'videos' . DIRECTORY_SEPARATOR;

$file_path_parts = explode('/', $file_path);

// Create directory if not exists
$current_path = '';
for($i=0; $i < sizeof($file_path_parts); $i++) {
    $current_path .= $file_path_parts[ $i ] . DIRECTORY_SEPARATOR;
    if( !is_dir( $base_folder . $current_path ) ) {
        mkdir( $base_folder . $current_path );
    }
}

// Path corrected for windows
$correct_path = str_replace('/', DIRECTORY_SEPARATOR, $file_path);

// Move file to storage
$res = move_uploaded_file($temp_filename, $base_folder . $correct_path . $_POST['file_name']);


if( strpos( $_POST['file_name'], '_720p' ) !== false ) {
	error_log('Generate SMIL-file');
	$SMILpath = str_replace('_720p.mp4', '.smil', $_POST['file_name']);
	require_once('inc/smil.php');
	
	$SMILfile = $base_folder . $correct_path . $SMILpath;
	
	error_log('SMILpath: '. $SMILpath );
	error_log('SMIL: '. $SMILfile );
	error_log('SMILdata: '. $SMIL );

	
	
	$fh = fopen($SMILfile, 'w' );
	fwrite( $fh, $SMIL );
	fclose( $fh );
} else {
	error_log('Do not generate SMIL-file');
}


// Return data
die(
    json_encode(
                 array( 'success'       => $res,
                        'file_path'     => str_replace('\\','/',$file_path),
                        'file_abs_path' => str_replace('\\','/',$base_folder . $correct_path),
                        'file_name'     => $_POST['file_name']
                      )
               )
    );
