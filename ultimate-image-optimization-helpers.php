<?php
/**
 * Plugin Name: Ultimate Image Optimization Helpers
 * Plugin URI: https://github.com/healdev/ultimate-image-optimization-helpers
 * Description: Optimize images and compress file sizes to improve SEO & performance while preserving/improving image quality.
 * Version: 0.2.10
 * Author: Mehdi Salem
 * Author URI: https://healdev.com
 * GitHub Plugin URI: https://github.com/healdev/ultimate-image-optimization-helpers
 * Requires at least: 3.8
 * Tested up to: 4.9
 *
 * Text Domain: ultimate-image-optimization-helpers
 * Domain Path: /languages
 *
 * License: GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html

Ultimate Image Optimization Helpers is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Ultimate Image Optimization Helpers IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Ultimate Image Optimization Helpers. If not, see https://www.gnu.org/licenses/gpl-2.0.html.

 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// Set our defined minimum compatible PHP version.
if ( ! defined( 'HDEV_OPTIMG_PHP_MIN_VER' ) ) {
	define( 'HDEV_OPTIMG_PHP_MIN_VER', '5.6' );
}

// Set our defined minimum compatible WordPress version.
if ( ! defined( 'HDEV_OPTIMG_WP_MIN_VER' ) ) {
	define( 'HDEV_OPTIMG_WP_MIN_VER', '3.8' );
}

// Set our defined base.
if ( ! defined( 'HDEV_OPTIMG_BASE ' ) ) {
	define( 'HDEV_OPTIMG_BASE', plugin_basename( __FILE__ ) );
}

// Set our defined directory.
if ( ! defined( 'HDEV_OPTIMG_DIR' ) ) {
	define( 'HDEV_OPTIMG_DIR', plugin_dir_path( __FILE__ ) );
}

// Define all our other constants
require_once( HDEV_OPTIMG_DIR . 'lib/constants.php' );

/**
 * Set up and load our class.
 */
class HDEV_OPTIMG_Core
{

	/**
	 * Load our hooks and filters.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'plugins_loaded',               array( $this, 'textdomain'          )           );
		add_action( 'plugins_loaded',               array( $this, 'load_files'          )           );
	}

	/**
	 * Load textdomain for international goodness.
	 *
	 * @return void
	 */
	public function textdomain() {
		load_plugin_textdomain( 'ultimate-image-optimization-helpers', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Call our files in the appropriate place.
	 *
	 * @return void
	 */
	public function load_files() {

		// Load our back end.
		if ( is_admin() ) {
			// Load our helper file first.
			require_once( HDEV_OPTIMG_DIR . 'lib/helper.php' );

			// Load the plugin functionalities
			require_once( HDEV_OPTIMG_DIR . 'lib/admin.php' );
			require_once( HDEV_OPTIMG_DIR . 'lib/settings.php' );
			require_once( HDEV_OPTIMG_DIR . 'lib/custom-columns.php' );
			require_once( HDEV_OPTIMG_DIR . 'lib/image-optimization.php' );
		}
	}

	// End the class.
}

// Instantiate our class.
$HDEV_OPTIMG_Core = new HDEV_OPTIMG_Core();
$HDEV_OPTIMG_Core->init();
