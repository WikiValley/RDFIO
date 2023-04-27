<?php

/**
 * SpecialRDFIOAdmin is a Special page for setting up the database tables for an ARC2 RDF Store
 * @author samuel.lampa@gmail.com
 * @package RDFIO
 */
class RDFIOAdmin extends RDFIOSpecialPage {

	private $wOut;

	function __construct() {
		parent::__construct( 'RDFIOAdmin', 'rdfio-admin' );
	}

	/**
	 * @param string $par (unused)
	 */
	function execute( $par ) {
		unset( $par ); // Needed to suppress warning about unused variable which we include just for consistency.

		// Require rdfio-admin permission for the current user
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			throw new PermissionsError( 'rdfio-admin', array( 'rdfio-specialpage-access-permission-missing' ) );
		}

		global $wgDBprefix;

		$wUser = $this->getUser();
		$wRequest = $this->getRequest();
		$this->wOut = $this->getOutput();

		$this->setHeaders();

		$rdfiogAction = $wRequest->getText( 'rdfio-action', '' );

		$this->addHTML('<h3>' . $this->msg( 'rdfio-triplestore-setup' )->parse() . '</h3>' );

		$arc2StoreConfig = array(
			'db_adapter' => 'MediaWiki',
			'mediawiki_db_con' => \MediaWiki\MediaWikiServices::getInstance()
				->getDBLoadBalancer()
				->getMaintenanceConnectionRef( DB_PRIMARY ),
			'store_name' => $wgDBprefix . 'arc2store', // Determines table prefix
		);
		$store = ARC2::getStore( $arc2StoreConfig );
		$store->createDBCon();

		if ( $store->isSetUp() ) {
			$this->infoMsg( $this->msg( 'rdfio-triplestore-is-already-setup' )->parse() );
		} else {
			if ( $rdfiogAction === 'setup' ) {
				$this->setUpStore( $store, $wUser, $wRequest );
			} else {
				$this->infoMsg( 'Store is <b>not</b> set up' );
				$setupStoreForm = '
				<form method="post"
					action=""
					name="createEditQuery">
					<input
						type="submit"
						name="submit-button"
						value="' . $this->msg( 'rdfio-set-up-triplestore' )->parse() . '">' .
					Html::Hidden(  'rdfio-action', 'setup' ) .
					Html::Hidden( 'token', $wUser->getEditToken() ) .
				'</form>';
				$this->addHTML( $setupStoreForm );
			}
		}

		$this->addWikiText( "\n===" . $this->msg( 'rdfio-data-sources' )->parse() . "===\n" );
		$this->addWikiText( "\n{{#ask: [[Category:RDFIO Data Source]]
					|?Equivalent URI
					|?RDFIO Import Type
					|format=table
					|mainlabel=Data Source
					|limit=10
					}}\n" );

		$this->addWikiText( "\n===" . $this->msg( 'rdfio-pages-and-templates' )->parse() . "===\n" );
		$this->addHTML( $this->msg( 'rdfio-associate-template-with-category-howto' )->parse() );
		$this->addWikiText( "{{#ask:  [[:Category:+]]
					|?Equivalent URI
					|?Has template
					|format=table
					|mainlabel=" . $this->msg( 'rdfio-category' )->parse() . "
					|limit=10
					}}" );
	}

	/**
	 * Add wiki text to output. Requires that $this->wOut is already
	 * initialized to $this->getOutput();
	 * @param $text The wiki text to add.
	 */
	private function addWikiText( $text ) {
		if ( method_exists( $this->wOut, 'addWikiTextAsInterface' ) ) {
			$this->wOut->addWikiTextAsInterface( $text );
		} else {
			$this->wOut->addWikiText( $text );
		}
	}

	/**
	 * Add HTML content to output. Requires that $this->wOut is already
	 * initialized to $this->getOutput();
	 * @param $text The HTML content to add.
	 */
	private function addHTML( $html ) {
		$this->wOut->addHTML( $html );
	}

	/**
	 * Check permissions and set up the ARC2 store if allowed.
	 * @param $store
	 * @param $wUser
	 * @param $wRequest
	 */
	private function setUpStore( $store, $wUser, $wRequest ) {
		if ( !$this->editTokenOk( $wUser, $wRequest ) ) {
			$this->errorMsg( $this->msg( 'rdfio-csrf-detected' )->parse() );
			return;
		}

		if ( !in_array( 'sysop', $wUser->getGroups() ) ) {
			$this->errorMsg( $this->msg( 'rdfio-permission-error-only-sysops' )->parse() );
			return;
		}

		$store->setUp();

		if ( $store->getErrors() ) {
			$this->errorMsg( $this->msg( 'rdfio-error-setting-up-store' )->parse() . ': ' . implode( "\n", $store->getErrors() ));
			return;
		}

		if ( !$store->isSetUp() ) {
			$this->errorMsg( $this->msg( 'rdfio-error-setting-up-store' )->parse() . '. ' . $this->msg( 'rdfio-error-reason-unknown-no-errors-reported' )->parse() );
			return;
		}

		$this->successMsg( $this->msg( 'rdfio-triplestore-successfully-set-up' )->parse() );
	}
}
