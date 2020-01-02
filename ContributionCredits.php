<?php

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

			$database = wfGetDB( DB_REPLICA );
			$articleID = $title->getArticleID();
			$links = [];

			$result = $database->select(
				[ 'revision', 'user' ],
				[ 'distinct user.user_id', 'user.user_name', 'user.user_real_name' ],
				[ 'user.user_id = revision.rev_user', "rev_page = $articleID", 'rev_user > 0', 'rev_deleted = 0' ],
				__METHOD__,
				[ 'ORDER BY' => 'user.user_name ASC' ]
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
