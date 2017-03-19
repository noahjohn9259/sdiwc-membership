<?php 
function _dump_var($arr) {
	echo '<pre>';
	echo var_dump($arr);
	echo '</pre>';
}

function untrailingslashit( $string ) {
	return trim( $string, '/\\' );
}

function br2nl($string) {
	return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}  