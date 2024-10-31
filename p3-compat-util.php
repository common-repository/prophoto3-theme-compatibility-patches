<?php 


class P3CompatUtil {
	
	
	public static function wpVersion() {
		$version = str_pad( intval( str_replace( '.', '', $GLOBALS['wp_version'] ) ), 3, '0' );
		return ( $version == '000' ) ? 999 : intval( $version );
	}
	
	
	public static function deleteWidget( $typeOrHandle, $id = null ) {
		if ( $id !== null ) {
			$type = $typeOrHandle;
		} else {
			$handle = $typeOrHandle;
			list( $type, $id ) = P3CompatUtil::parseWidgetHandle( $handle );
		}

		if ( !is_string( $type ) || !is_numeric( $id ) ) {
			return false;
		}
		
		$removedWidget = $removedFromSidebar = false;
		
		// first, remove the widget from it's type-specific option storage
		$typeWidgets = get_option( 'widget_' . $type );
		if ( $typeWidgets === false ) {
			return false;
		}

		if ( array_key_exists( $id, $typeWidgets ) ) {
			unset( $typeWidgets[$id] );
			update_option( 'widget_' . $type, $typeWidgets );
			$removedWidget = true;
		} else if ( is_numeric( $id ) && array_key_exists( intval( $id ), $typeWidgets ) ) {
			unset( $typeWidgets[ intval( $id ) ] );
			update_option( 'widget_' . $type, $typeWidgets );
			$removedWidget = true;
		}
		
		// next we remove it from it's sidebar
		$widgetAreas = get_option( 'sidebars_widgets' );
		foreach ( $widgetAreas as $widgetAreaName => $widgetArea ) {
			if ( is_array( $widgetArea ) ) {
				foreach ( $widgetArea as $index => $widgetHandle ) {
					if ( $widgetHandle == ( $type . '-' . $id ) ) {
						unset( $widgetAreas[$widgetAreaName][$index] );
						$removedFromSidebar = true;
					}
				}
			}
		}
		if ( $removedFromSidebar ) {
			update_option( 'sidebars_widgets', $widgetAreas );
		}
		
		if ( $removedWidget && $removedFromSidebar ) {
			return true;
		} else {
			return false;
		}
	}
	
	
	public static function parseWidgetHandle( $handle ) {
		$lastDashPos = strrpos( $handle, '-' );
		$handle[$lastDashPos] = '^';
		$parts = explode( '^', $handle );
		return array( $parts[0], $parts[1] );
	}
	
	
	public static function recursiveRmDir( $directory, $empty = false ) {
		if ( substr( $directory, -1 ) == '/' ) {
			$directory = @substr( $directory, 0, -1 );
		}
		
		if ( !@file_exists( $directory ) || !@is_dir( $directory ) ) {
			return false;
		
		} elseif ( @is_readable( $directory ) ) {
			$handle = @opendir( $directory );
			while ( false !== ( $item = @readdir( $handle ) ) ) {
				if ( $item != '.' && $item != '..' ) {
					$path = $directory . '/' . $item;
					if ( @is_dir( $path ) ) {
						P3CompatUtil::recursiveRmDir( $path );
					} else {
						@unlink( $path );
					}
				}
			}
			@closedir( $handle );
			if ( $empty == false ) {
				if ( !@rmdir( $directory ) ) {
					return false;
				}
			}
		}
		return true;
	}

}