<?php

namespace ARC2\Store\Adapter;

class MediaWikiDatabaseAdapter extends AbstractAdapter {

	/** @var \Wikimedia\Rdbms\DBConnRef Connection to MediaWiki database; */
	protected $db;

	/**
	 * @param array $configuration Default is array().
	 */
	public function __construct( $configuration ) {
		parent::__construct( $configuration );
		$this->db = $configuration['mediawiki_db_con'];
		if( !( $this->db instanceof \Wikimedia\Rdbms\DBConnRef ) ) {
			throw new \RuntimeException( '\ARC2\Store\Adapter\MediaWikiDatabaseAdapter must have a connection to MediaWiki’s database' );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function checkRequirements() {
		// nop: MediaWiki is already working
	}

	/**
	 * Connect to the database if needed.
	 */
	public function connect( $existingConnection = null ) {
		// nop: MediaWiki is managing its connection
	}

	/**
	 * Disconnect from the database.
	 */
	public function disconnect() {
		// nop: MediaWiki is managing its connection
	}

	/**
	 * Escape a value.
	 *
	 * @param string $value Value to be escaped
	 * @return string Escaped value
	 */
	public function escape( $value ) {
		return $this->db->strencode( $value );
	}

	/**
	 * Execute an SQL query.
	 *
	 * @param string $sql Query
	 * @return int Number of affected rows
	 */
	public function exec( $sql ) {
		try {
			$result = $this->db->query( $sql, __METHOD__ );
		} catch( \Wikimedia\Rdbms\DBError $e ) {
			$this->errors[] = $e->getMessage();
			return 0;
		}
		if( $result instanceof \Wikimedia\Rdbms\IResultWrapper ) {
			return $result->numRows();
		}
		return 0;
	}

	public function getAffectedRows() {
		return $this->db->affectedRows();
	}

	/**
	 * Obtain the list of rows for some SQL query.
	 *
	 * @param string $sql
	 * @return array[] List of rows
	 */
	public function fetchList( $sql ) {
		try {
			$result = $this->db->query( $sql, __METHOD__ );
		} catch( \Wikimedia\Rdbms\DBError $e ) {
			$this->errors[] = $e->getMessage();
			return [];
		}
		$rows = [];
		if( $result instanceof \Wikimedia\Rdbms\IResultWrapper ) {
			// We could use foreach but ResultWrapper returns objects by default instead of arrays as rows
			$result->rewind();
			if( !$result->valid() ) {
				return [];
			}
			$result->fetchRow();
			$rows[] = $result->current();
			while( $result->valid() ) {
				$rows[] = $result->current();
				$result->fetchRow();
			}
		}
		return $rows;
	}

	/**
	 * Obtain the first row for some SQL query.
	 *
	 * @param string $sql
	 * @return array|false First row
	 */
	public function fetchRow( $sql ) {
		try {
			$result = $this->db->query( $sql, __METHOD__ );
		} catch( \Wikimedia\Rdbms\DBError $e ) {
			$this->errors[] = $e->getMessage();
			return false;
		}
		if( $result instanceof \Wikimedia\Rdbms\IResultWrapper ) {
			$result->rewind();
			if( $result->valid() ) {
				$res = $result->fetchRow();
				return $res ? $res : false;
			}
		}
		return false;
	}

	/**
	 * Return the name of this specific database adapter.
	 */
	public function getAdapterName() {
		return 'MediaWiki';
	}

	/**
	 */
	public function getCollation() {
		return ''; // TODO
	}

	/**
	 * Return the name of the database system.
	 *
	 * @return ?string Database system
	 */
	public function getDBSName() {
		$type = $this->db->getType();
		if( $type === 'mysql' ) {
			$variant = $this->db->getMySqlServerVariant();
			return $variant[0];
		} elseif( $type === 'postgre' ) {
			return 'PostgreSQL';
		} elseif( $type === 'sqlite' ) {
			return 'SQLite';
		}
		return null;
	}

	/**
	 * Return the ID of the last insserted row.
	 *
	 * @return int ID of the last inserted row
	 */
	public function getLastInsertId() {
		return $this->db->insertId();
	}

	/**
	 * Return some information about the database server.
	 */
	public function getServerInfo() {
		return $this->db->getServerInfo();
	}

	/**
	 * Return the version of the database server.
	 */
	public function getServerVersion() {
		return $this->db->getServerVersion();
	}

	/**
	 */
	public function getErrorMessage() {
		$count = count( $this->errors );
		return $count ? $this->errors[$count-1] : '';
	}

	/**
	 * Get number of affected rows in an SQL query.
	 *
	 * @param string $sql Query
	 * @return int Number of affected rows
	 */
	public function getNumberOfRows( $sql ) {
		try {
			$result = $this->db->query( $sql, __METHOD__ );
		} catch( \Wikimedia\Rdbms\DBError $e ) {
			$this->errors[] = $e->getMessage();
			return 0;
		}
		if( $result instanceof \Wikimedia\Rdbms\IResultWrapper ) {
			return $result->numRows();
		}
		return 0;
	}

	/**
	 * Return the store name.
	 *
	 * @return string Store name
	 */
	public function getStoreName() {
		if( isset( $this->configuration['store_name'] ) ) {
			return $this->configuration['store_name'];
		}
		return 'arc';
	}

	/**
	 * Return the table prefix.
	 *
	 * @return string Table prefix
	 */
	public function getTablePrefix() {
		$prefix = '';
		if( isset( $this->configuration['db_table_prefix'] ) ) {
			$prefix = $this->configuration['db_table_prefix'] . '_';
		}
		$prefix .= $this->getStoreName() . '_';
		return $prefix;
	}

	/**
	 * $param string $sql
	 */
	public function simpleQuery( $sql ) {
		try {
			$this->db->query( $sql, __METHOD__ );
			return true;
		} catch( \Wikimedia\Rdbms\DBError $e ) {
			return false;
		}
	}
}
