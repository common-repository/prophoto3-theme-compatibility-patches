<?php 
/*
Plugin Name: ProPhoto3 Theme Compatibility Patches
Plugin URI: http://www.prophotoblogs.com/support/p3-compatibility-patch-plugin/
Description: Only for users of version 3 of the <a href="http://www.prophotoblogs.com/">ProPhoto</a> theme. Contains patches required for compatibility with WordPress 3.3+
Version: 1.6
Author: ProPhoto Blogs
Author URI: http://www.prophotoblogs.com/
License: GPLv2
*/


require_once( plugin_dir_path( __FILE__ ) . 'p3-compat-util.php' );
require_once( plugin_dir_path( __FILE__ ) . 'p3-security-helper.php' );

class P3Compat {
	
	
	public function __construct() {
		if ( !function_exists( 'p3_theme_startup' ) || P3CompatUtil::wpVersion() < 330 ) {
			return;
		}
		
		$this->adminCSS();
		$this->fixIFramedUploadWindows();
		$this->frontEndCSS();
		$this->mediaUploadGalleryFix();
		add_action( 'wp_head', create_function( '', 'P3Compat::wpHead();' ) );
		add_action( 'wp_loaded', create_function( '', 'P3Compat::wpLoaded();' ) );
	}
	
	
	public static function wpLoaded() {
		self::fixGalleryRawImagesSpewer();
		self::twitterPermalinksFix();
		P3SecurityHelper::fixPostLayoutIssue();
	}
	
	
	public static function wpHead() {
		self::iPadPptClassicLogoSplitterFix();
	}

	
	public static function twitterPermalinksFix() {
		if ( !function_exists( 'p3_test' ) || !function_exists( 'p3_store_options' ) ) {
			return;
		}
		
		add_filter( 'p3_static_file_content_js', create_function( '$content', "
			return str_replace( \"/'+username+'/statuses/'+tweets[i].id\", \"/#!/'+username+'/status/'+tweets[i].id_str\", \$content );
		" ) );
		
		global $p3;
		if ( !p3_test( 'twitter_permalink_fix_applied', 'true' ) && is_array( $p3 ) && isset( $p3['non_design'] ) ) {
			$p3['non_design']['twitter_permalink_fix_applied'] = 'true';
			p3_store_options();
		}
	}
	
	
	public static function fixGalleryRawImagesSpewer() {
		if ( !function_exists( 'p3_get_option' ) ) {
			return;
		}
		
		if ( is_feed() ) {
			add_filter( 'the_content', 'P3Compat::galleryImages', 1000 );
			remove_filter( 'the_content', 'p3_flash_gallery_markup', 1000 );
			remove_filter( 'the_content', 'p3_lightbox_gallery_markup', 1000 );
		
		} else if ( ( IS_IPAD || IS_IPHONE ) && p3_get_option( 'flash_gal_fallback' ) == 'images' ) {
			add_filter( 'the_content', 'P3Compat::galleryImages', 1000 );
			remove_filter( 'the_content', 'p3_flash_gallery_markup', 1000 );
		}
	}
	
	
	public static function galleryImages( $content ) {
		if ( !function_exists( 'p3_post_has_flash_gallery' ) || !function_exists( 'p3_get_gallery_images_data' ) ) {
			return;
		}
		
		global $post;	
		$id = $post->ID;

		if ( !p3_post_has_flash_gallery( $content, false ) ) {
			return $content;
		}

		$attachments = p3_get_gallery_images_data( $id );
		
		$imgMarkup = '';
		foreach ( $attachments as $the_id => $attachment ) {
			$imgMarkup .= wp_get_attachment_link( $the_id, 'fullsize', true ) . "\n";
		}
		
		return preg_replace( '/<img[^>]+p3-(flash|lightbox)-gal-placeholder\.gif[^>]+>/', $imgMarkup, $content );
	}
	
	
	protected function fixIFramedUploadWindows() {
		if ( $GLOBALS['pagenow'] == 'popup.php' ) {
			wp_enqueue_style( 'p3-compat-popup-css', plugin_dir_url( __FILE__ ) .'p3-compat-popup.css' );
			add_action( 'admin_head', create_function( '', "remove_action( 'post-upload-ui', 'media_upload_text_after', 5 );" ) );
		}
	}
	
	
	protected function adminCSS() {
		if ( is_admin() ) {
			wp_enqueue_style( 'p3-compat-admin-css', plugin_dir_url( __FILE__ ) .'p3-compat-admin.css' );
		}
	}
	
	
	protected function frontEndCSS() {
		if ( !is_admin() ) {
			wp_enqueue_style( 'p3-compat-frontend-css', plugin_dir_url( __FILE__ ) .'p3-compat-front-end.css' );
		}
	}
	
	
	protected function mediaUploadGalleryFix() {
		if ( is_admin() && $GLOBALS['pagenow'] == 'media-upload.php' && isset( $_GET['tab'] ) && $_GET['tab'] == 'gallery' ) {
			add_action( 'admin_head', create_function( '', 'echo "<script>
				var removeBars = function(){ jQuery(document).ready(function(\$){\$(\'div.bar\').remove();}); };
				removeBars();
				setTimeout( \'removeBars\', 500 );
				setTimeout( \'removeBars\', 1500 );
			</script>";' ) );
		}
	}
	
	
	protected static function iPadPptClassicLogoSplitterFix() {
		if ( !function_exists( 'p3_logo_masthead_sameline' ) || !function_exists( 'p3_get_option' ) ) {
			return;
		}
		if ( !p3_logo_masthead_sameline() && ( p3_get_option( 'logo_top_splitter' ) != '0' || p3_get_option( 'logo_btm_splitter' ) != '0' ) ) {
			$logo_top_splitter = p3_get_option( 'logo_top_splitter' );
			$logo_btm_splitter = p3_get_option( 'logo_btm_splitter' );
			echo "<style type=\"text/css\" media=\"screen\">#logo-wrap { margin: {$logo_top_splitter}px 0 {$logo_btm_splitter}px 0 !important; }</style>";
		}
	}
}



add_action( 'after_setup_theme', create_function( '', 'new P3Compat();' ) );



