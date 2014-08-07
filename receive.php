<?php
error_reporting(E_NONE);
ini_set('display_errors', 0);

$base_folder = dirname( __FILE__ ) . DIRECTORY_SEPARATOR 
             . '..' . DIRECTORY_SEPARATOR
             . 'ukmno' . DIRECTORY_SEPARATOR 
             . 'videos' . DIRECTORY_SEPARATOR;

$file_path = $_POST['file_path'];
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
$res = move_uploaded_file($_FILES['file']['tmp_name'], $base_folder . $correct_path . $_POST['file_name']);

// Return data
die(
    json_encode(
                 array( 'success'       => $res,
                        'file_path'     => $file_path,
                        'file_abs_path' => $base_folder . $correct_path,
                        'file_name'     => $_POST['file_name']
                      )
               )
    );