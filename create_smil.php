<?php
header('Content-Type: application/json');

$file = urldecode($_GET['file']);

$file_720p 		= str_replace('.mp4', '_720p.mp4', $file);
$file_mobile	= str_replace('.mp4', '_mobile.mp4', $file);

if( !file_exists( $file_720p ) || !file_exists( $file_mobile ) ) {
	die( json_encode( array('success'=>true, 'exists'=>false, 'file_720p'=>$file_720p, 'file_mobile'=>$file_mobile) ) );
}

// Generer SMIL-data
$basename = basename( $file );
$SMILpath = str_replace('.mp4','.smil', $basename);
require('inc/smil.php');

$SMILfile = str_replace( $basename, '', $file) . $SMILpath;

if( !file_exists( $SMILfile ) ) {
	$exists = false;
	$fh = fopen($SMILfile, 'w' );
	fwrite( $fh, $SMIL );
	fclose( $fh );
} else {
	$exists = true;
}


$data = array('success'=>true,
			  'file' => $file,
			  'exists' => true,
			  'already_exists' => $exists,
			  'basename' => $basename,
			  'smilpath' => $SMILpath,
			  'smilfile' => $SMILfile,
			  'smildata' => htmlentities($SMIL) );
			  
die( json_encode( $data ) );