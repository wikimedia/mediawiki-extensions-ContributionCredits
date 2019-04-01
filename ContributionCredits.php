<?php

class ContributionCredits {

	public static function onSkinAfterContent( &$data, Skin $skin ) {
		global $wgContributionCreditsHeader, $wgContributionCreditsUseRealNames;

		$title = $skin->getTitle();
		$namespace = $title->getNamespace();
		$request = $skin->getRequest();
		$action = $request->getVal( 'action', 'view' );
		if ( $namespace === NS_MAIN and $action === 'view' ) {

			$database = wfGetDB( DB_REPLICA );
			$articleID = $title->getArticleID();
			$authors = [];

			$result = $database->select(
				[ 'revision', 'user' ],
				[ 'distinct user.user_id', 'user.user_name', 'user.user_real_name' ],
				[ 'user.user_id = revision.rev_user', "rev_page = $articleID", 'rev_user > 0', 'rev_deleted = 0' ],
				__METHOD__,
				[ 'ORDER BY' => 'user.user_name ASC' ]
			);

			foreach ( $result as $row ) {
				if ( $wgContributionCreditsUseRealNames and $row->user_real_name ) {
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