<?php

use MediaWiki\MediaWikiServices;

class ContributionCredits {
	/**
	 * Handler for the BeforePageDisplay hook.
	 *
	 * This function runs before the page display process, allowing you to
	 * modify the HTML output, add JavaScript or CSS, or perform other actions.
	 *
	 * @param OutputPage $out The OutputPage object representing the page output.
	 * @param Skin $skin The current Skin object, which provides the look and feel.
	 * @return bool|void True or no return value to continue; false to abort.
	 */
	public static function onBeforePageDisplay( $out, $skin ) {
		$conf = MediaWikiServices::getInstance()->getMainConfig();
		$ContentNamespaces = $conf->get( 'ContentNamespaces' );
		$ContributionCreditsHeader = $conf->get( 'ContributionCreditsHeader' );
		$ContributionCreditsUseRealNames = $conf->get( 'ContributionCreditsUseRealNames' );
		$ContributionCreditsExcludedCategories = $conf->get( 'ContributionCreditsExcludedCategories' );
		$ContributionCreditsUsersExclude = $conf->get( 'ContributionCreditsUsersExclude' );
		$title = $skin->getTitle();
		$namespace = $title->getNamespace();
		$request = $skin->getRequest();
		$action = $request->getVal( 'action', 'view' );
		if ( in_array( $namespace, $ContentNamespaces ) && $action === 'view' ) {

			// If the page is in the list of excluded categories, don't show the credits
			$categories = $title->getParentCategories();
			foreach ( $categories as $key => $value ) {
				$category = str_ireplace( '_', ' ', $key );
				if ( in_array( $category, $ContributionCreditsExcludedCategories ) ) {
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
				if ( $ContributionCreditsUseRealNames && $row->user_real_name ) {
					if ( in_array( $row->user_real_name, $ContributionCreditsUsersExclude ) ) {
						continue;
					}
					$link = Linker::userLink( $row->user_id, $row->user_name, $row->user_real_name );
				} else {
					if ( in_array( $row->user_name, $ContributionCreditsUsersExclude ) ) {
						continue;
					}
					$link = Linker::userLink( $row->user_id, $row->user_name );
				}
				$links[] = $link;
			}
			$data = '';
			$header = wfMessage( 'contributioncredits-header' );
			if ( $ContributionCreditsHeader ) {
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
			$out->addHtml( $data );

		}
	}
}
