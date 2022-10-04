<?php

class RDFIO {

	public static function initExtension() {

		global $smwgDefaultStore;
		global $wgRDFIOAutoload;

		define( 'RDFIO_VERSION', 'v3.0.2' );

		if ( $wgRDFIOAutoload ) {
			$smwgDefaultStore = 'SMWARC2Store';
		}
	}
}
