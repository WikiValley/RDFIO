<?php

class ARC2_StoreMW extends ARC2_Store {

	public function createDBCon() {

		$this->a['db_adapter'] = 'MediaWiki';
		if( !$this->db ) {
			$this->a['mediawiki_db_con'] = \MediaWiki\MediaWikiServices::getInstance()
				->getDBLoadBalancer()
				->getMaintenanceConnectionRef( DB_PRIMARY );
			$this->db = new \ARC2\Store\Adapter\MediaWikiDatabaseAdapter( $this->a );
		}
		$this->a['db_con'] = null;
		$this->a['db_object'] = $this->db;
		$this->a['store_write_buffer'] = 1;

		return true;
	}
}
