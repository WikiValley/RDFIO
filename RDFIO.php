<?php

/**
 * @file
 * Backward compatibility file to support require_once() in LocalSettings.
 *
 * Modern syntax (to enable RDFIO in LocalSettings.php) is
 * wfLoadExtension( 'RDFIO' );
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'RDFIO' );
} else {
	die( 'This version of the RDFIO extension requires MediaWiki 1.29+' );
}
