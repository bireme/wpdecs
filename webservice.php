<?php

include_once 'functions.php';

$resultado = array();

$lang = 'p';
if(isset($_GET['lang']) and ($_GET['lang'] == 'p' or $_GET['lang'] == 'e' or $_GET['lang'] == 'i')) {
	$lang = $_GET['lang'];
}

if( isset( $_GET['words'] ) ) {
	$resultado = get_descriptors_by_words( $_GET['words'], $lang );

} elseif( isset($_GET['treeid']) ) {
	$resultado = get_descriptors_lang_by_tree_id( $_GET['treeid'] );

} 

// Send te result as json to the user
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
print json_encode($resultado);

exit;