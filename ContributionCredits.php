<?php

use MediaWiki\MediaWikiServices;

class ContributionCredits {

	/**
	 * @param string &$data
	 * @param Skin $skin
	 */
	public static function onSkinAfterContent( &$data, Skin $skin ) {
		global $wgContentNamespaces,
			$wgContributionCreditsHeader,
			$wgContributionCreditsUseRealNames,
			$wgContributionCreditsExcludedCategories;

		$title = $skin->getTitle();
		$namespace = $title->getNamespace();
		$request = $skin->getRequest();
		$action = $request->getVal( 'action', 'view' );
		if ( in_array( $namespace, $wgContentNamespaces ) && $action === 'view' ) {

			// If the page is in the list of excluded categories, don't show the credits
			$categories = $title->getParentCategories();
			foreach ( $categories as $key => $value ) {
				$category = str_ireplace( '_', ' ', $key );
				if ( in_array( $category, $wgContributionCreditsExcludedCategories ) ) {
					return;
				}
			}

			$database = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
			$articleID = $title->getArticleID();
			$links = [];

			$actorQuery = ActorMigration::newMigration()->getJoin( 'rev_user' );
			$fieldRevUser = $actorQuery['fields']['rev_user'];
			$result = $database->select(
				[ 'revision' => 'revision' ] + $actorQuery['tables'] + [ 'user' => 'user' ],
				[ 'user_id', 'user_name', 'user_real_name' ],
				[ 'rev_page' => $articleID, $fieldRevUser . ' > 0', 'rev_deleted = 0' ],
				__METHOD__,
				[ 'DISTINCT', 'ORDER BY' => 'user_name ASC' ],
				$actorQuery['joins'] + [ 'user' => [ 'JOIN', 'user_id = ' . $fieldRevUser ] ]
			);

			foreach ( $result as $row ) {
				if ( $wgContributionCreditsUseRealNames && $row->user_real_name ) {
					$link = Linker::userLink( $row->user_id, $row->user_name, $row->user_real_name );
				} else {
					$link = Linker::userLink( $row->user_id, $row->user_name );
				}
				$links[] = $link;
			}

			$header = wfMessage( 'contributioncredits-header' );
			if ( $wgContributionCreditsHeader ) {
				$data .= "<h2>$header</h2>";
				$data .= "<ul>";
				foreach ( $links as $link ) {
					$data .= "<li>$link</li>";
				}
				$data .= "</ul>";
			} else {
				$links = implode( ', ', $links );
				$data .= "<p>$header: $links</p>";
			}
		}
	}
}
