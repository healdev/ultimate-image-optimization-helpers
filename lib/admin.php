<?php
/**
 * Ultimate Image Optimization Helpers Plugin - admin functions.
 *
 * Contains more generic admin related functions.
 *
 * @package Ultimate Image Optimization Helpers Plugin
 */

/**
 * Set up and load our class.
 */
class HDEV_OPTIMG_Admin
{

	/**
	 * Load our hooks and filters.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts',        array( $this, 'load_stylesheet'     )           );
		add_action( 'admin_enqueue_scripts',        array( $this, 'load_javascript'     )           );
	}

	/**
	 * Call our stylesheet for laying out the settings page.
	 *
	 * @param  string $hook  The admin page hook being called.
	 *
	 * @return void
	 */
	public function load_stylesheet( $hook ) {

		// Only load on our reading page or a single post editor.
		if ( ! in_array( $hook, array( 'options-media.php', 'upload.php' ) ) ) {
			return;
		}

		// Set a suffix for loading the minified or normal.
		$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'optimg.admin.css' : 'optimg.admin.min.css';
		$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : HDEV_OPTIMG_VER;

		// Load the CSS file itself.
		wp_enqueue_style( 'hdev-optimg', plugins_url( HDEV_OPTIMG_Helper::versioned_resource('/css/' . $file, 'lib/' ), __FILE__ ), array(), $vers, 'all' );
	}

	/**
	 * Call our JS file on the media settings and media upload pages.
	 *
	 * @param  string $hook  The admin page hook being called.
	 *
	 * @return void
	 */
	public function load_javascript( $hook ) {

        // Only load on our reading page or a single post editor.
        if ( ! in_array( $hook, array( 'options-media.php', 'upload.php' ) ) ) {
            return;
        }

		// Set a suffix for loading the minified or normal.
		$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'optimg.admin.js' : 'optimg.admin.min.js';
		$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : HDEV_OPTIMG_VER;

		// Load the JS file itself.
		wp_enqueue_script( 'hdev-optimg', plugins_url( HDEV_OPTIMG_Helper::versioned_resource( '/js/' . $file, 'lib/' ), __FILE__ ) , array( 'jquery' ), $vers, true );
	}

	// End the class.
}

// Instantiate our class.
$HDEV_OPTIMG_Admin = new HDEV_OPTIMG_Admin();
$HDEV_OPTIMG_Admin->init();
