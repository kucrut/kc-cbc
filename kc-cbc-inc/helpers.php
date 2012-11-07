<?php

if ( !function_exists('kc_array_multi_get_value') ) {
/**
 * Get value of multidimensional array
 *
 * @param array $array Source array.
 * @param array $keys Array of keys of the $array value to get
 *
 * @return mixed
 */
function kc_array_multi_get_value( $array, $keys ) {
	foreach ( $keys as $idx => $key ) {
		unset( $keys[$idx] );
		if ( !isset($array[$key]) )
			return false;

		if ( count($keys) )
			$array = $array[$key];
	}

	if ( !isset($array[$key]) )
		return false;

	return $array[$key];
}
}
