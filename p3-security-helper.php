<?php 

class P3SecurityHelper {
	
	
	/* about a dozen of our users experienced the same security issue (they had let their WP version get out of date)
	   where a malicious user was added, and a plugin called "post-layout" was used to add google adsense spam
	   this function cleans out that particular issue */
	public static function fixPostLayoutIssue() {

		if ( isset( $_GET['delete_trans'] ) ) {
			delete_transient( 'checked_post_layout_issue' );
		}
		
		delete_option( 'pstl' );
		
		if ( function_exists( 'p3_test' ) && function_exists( 'p3_navlink_search' )  && !p3_test( 'nav_search_onoff', 'off' ) ) {
			ob_start();
			p3_navlink_search();
			$headerCode = ob_get_clean();
			if ( strpos( $headerCode, '8693304123976148' ) !== false ) { // this is the spammers adsense ID
				add_action( 'p3_before_header', create_function( '', 'ob_start();' ) );
				add_action( 'p3_pre_contact_form_outer', 'P3SecurityHelper::scrubPostLayoutIssueSearchForm' );
			}
		}
		
		if ( !get_transient( 'checked_post_layout_issue' ) ) {
			
			$sidebarsWidgets = get_option( 'sidebars_widgets' );
			foreach ( $sidebarsWidgets as $sidebar => $widgets ) {
				if ( is_array( $widgets ) ) {
					foreach ( $widgets as $handle ) {
						list( $type, $id ) = P3CompatUtil::parseWidgetHandle( $handle );
						$typeWidgets = get_option( 'widget_' . $type );
						if ( isset( $typeWidgets[$id] ) ) {
							$widgetData = is_array( $typeWidgets[$id] ) ? serialize( $typeWidgets[$id] ) : $typeWidgets[$id];
							if ( strpos( $widgetData, '8693304123976148' ) !== false ) { // this is the spammers adsense ID
								P3CompatUtil::deleteWidget( $handle );
							}
						}
					}
				}
			}

			$activePlugins = get_option( 'active_plugins' );
			foreach ( $activePlugins as $plugin ) {
				if ( strpos( $plugin, 'post-layout' ) !== false ) {
					require_once( ABSPATH . 'wp-admin/includes/plugin.php');
					deactivate_plugins( $plugin );
				}
			}
			$postLayoutPath = dirname( plugin_dir_path( __FILE__ ) ) . '/post-layout/';
			if ( file_exists( $postLayoutPath ) ) {
				P3CompatUtil::recursiveRmDir( $postLayoutPath );
			}

			global $wpdb;
			$users = $wpdb->get_results( "SELECT * FROM $wpdb->users WHERE 1=1" );
			foreach ( $users as $user ) {
				if ( $user->user_email == 'w0nk@raisser.com' ) {
					require_once( ABSPATH . 'wp-admin/includes/user.php' );
					wp_delete_user( $user->ID );
				}
			}

			foreach ( $activePlugins as $plugin ) {
				if ( strpos( $plugin, 'quick-adsense' ) !== false ) {
					$adsenseOpts = array();
					for ( $i = 1; $i <= 10; $i++ ) { 
						$adsenseOpts[] = "AdsCode{$i}";
						$adsenseOpts[] = "WidCode{$i}";
					}
					$pluginBad = false;
					foreach ( $adsenseOpts as $opt ) {
						$val = get_option( $opt );
						if ( strpos( $val, '8693304123976148' ) !== false ) { // this is the spammers adsense ID
							delete_option( $opt );
							$pluginBad = true;
						}
					}
					if ( $pluginBad ) {
						require_once( ABSPATH . 'wp-admin/includes/plugin.php');
						deactivate_plugins( $plugin );
						$qaLayoutPath = dirname( plugin_dir_path( __FILE__ ) ) . '/quick-adsense/';
						if ( file_exists( $qaLayoutPath ) ) {
							P3CompatUtil::recursiveRmDir( $qaLayoutPath );
						}
					}
				}
			}
			
			set_transient( 'checked_post_layout_issue', 'recently', 60*60 * 3 );
		}

	}
	
	
	public static function scrubPostLayoutIssueSearchForm() {
		$headerMarkup = ob_get_clean();
		preg_match( '/<li id="search-top">(?:.)*<\/li>/s', $headerMarkup, $match );
		if ( isset( $match[0] ) ) {
			ob_start();
			$search_in_dropdown = p3_test( 'nav_search_dropdown', 'on' ); ?>

			<li id="search-top"><?php if ( $search_in_dropdown ) { ?>
				<a><?php p3_option( 'nav_search_dropdown_linktext' ); ?></a>
				<ul>
					<li><?php } ?>
						<form id="searchform-top" method="get" action="<?php echo P3_URL ?>">
							<div>
								<input id="s-top" name="s" type="text" value="<?php echo wp_specialchars( stripslashes( $_GET['s'] ), TRUE ) ?>" size="12" tabindex="1" />
								<input id="searchsubmit-top" name="searchsubmit-top" type="submit" value="<?php p3_option( 'nav_search_btn_text' ); ?>" />
							</div>	
						</form>
			<?php if ( $search_in_dropdown ) { ?>
					</li>
				</ul><?php } ?>
			</li>
		<?php
			$goodSearchCode = ob_get_clean();
			echo str_replace( $match[0], $goodSearchCode, $headerMarkup );
		} else {
			echo $headerMarkup;
		}
	}
}


