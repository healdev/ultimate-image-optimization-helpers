<?php
/**
 * Ultimate Image Optimization Helpers Plugin - helper functions
 *
 * Contains various helper related functions.
 *
 * @package Ultimate Image Optimization Helpers Plugin
 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Set up and load our class.
 */
class HDEV_OPTIMG_Helper
{

	/**
	 * Gets the plugin default settings
	 *
	 * @return array|mixed
	 */
	public static function get_removable_meta() {

		// Get the defaults from the constant
		if( defined( 'HDEV_OPTIMG_REMOVABLE_META' ) ) {

			return unserialize( HDEV_OPTIMG_REMOVABLE_META );
		}

		// Fallback if constant is not defined
		return array(
			'aperture',
			'credit',
			'camera',
			'caption',
			'created_timestamp',
			'copyright',
			'focal_length',
			'iso',
			'shutter_speed',
			'title',
			'keywords'
		);
	}

    /**
     * Gets the current optimization presets if any
     *
     * @param $mode
     * @return mixed|null
     */
    public static function get_optimization_mode_data( $mode ) {

        // Get preset optimization settings
        $optimization_presets = self::get_optimization_mode_presets();

        // Populate our preset settings var to be able to override $_POST vars when a preset is selected by the user
        switch( $mode ) :
            case 'balanced' :
                return $optimization_presets['balanced'];
            case 'hd' :
                return $optimization_presets['hd'];
            case 'performance' :
                return $optimization_presets['performance'];
            default:
                return null;
        endswitch;
    }

    /**
     * Gets the plugin default settings
     *
     * @return array|mixed
     */
    public static function get_optimization_defaults() {

        // Get the defaults from the constant
        if( defined( 'HDEV_OPTIMG_DEFAULT_SETTINGS' ) ) {

            return unserialize( HDEV_OPTIMG_DEFAULT_SETTINGS );
        }

        // Fallback if constant is not defined
        return array(
	        'mode' => 'balanced',
	        'convert' => '0',
	        'quality'=> 'high',
	        'quality_val'=> '60',
	        'sharpen'=> '1',
	        'interlace'=> 'origin',
	        'optimize_original'=> 'true',
	        'remove_metadata'=> 'true'
        );
    }

    /**
     * Gets the plugin default settings
     *
     * @param null $preset
     * @return array|mixed
     */
    public static function get_optimization_mode_presets( $preset = null ) {

        switch ( $preset ) :
            case null || '':
                return defined( 'HDEV_OPTIMG_MODE_PRESETS' ) ? unserialize( HDEV_OPTIMG_MODE_PRESETS ) : array( // Fallback if constant is not defined
                    'balanced' => array(
                        'mode' => 'balanced',
                        'quality'=> 'high',
                        'quality_val'=> '65',
                        'sharpen'=> '1',
                        'interlace'=> 'origin',
                        'optimize_original'=> 'true',
                        'remove_metadata'=> 'true'
                    ),
                    'hd' => array(
                        'mode' => 'hd',
                        'quality'=> 'very-high',
                        'quality_val'=> '77',
                        'sharpen'=> '1',
                        'interlace'=> 'origin',
                        'optimize_original'=> 'true',
                        'remove_metadata'=> 'true'
                    ),
                    'performance' => array(
                        'mode' => 'performance',
                        'quality'=> 'medium',
                        'quality_val'=> '55',
                        'sharpen'=> '1',
                        'interlace'=> 'progressive',
                        'optimize_original'=> 'true',
                        'remove_metadata'=> 'true'
                    )
                );
            case 'balanced':
                return defined( 'HDEV_OPTIMG_DEFAULT_SETTINGS' ) ? unserialize( HDEV_OPTIMG_DEFAULT_SETTINGS ) : array( // Fallback if constant is not defined
                    'mode' => 'balanced',
                    'quality'=> 'good',
                    'quality_val'=> '65',
                    'sharpen'=> '1',
                    'interlace'=> 'origin',
                    'optimize_original'=> 'true',
                    'remove_metadata'=> 'true'
                );
            case 'hd':
                return defined( 'HDEV_OPTIMG_DEFAULT_SETTINGS' ) ? unserialize( HDEV_OPTIMG_DEFAULT_SETTINGS ) : array( // Fallback if constant is not defined
                    'mode' => 'hd',
                    'quality'=> 'high',
                    'quality_val'=> '77',
                    'sharpen'=> '1',
                    'interlace'=> 'origin',
                    'optimize_original'=> 'true',
                    'remove_metadata'=> 'true'
                );
            case 'performance':
                return defined( 'HDEV_OPTIMG_DEFAULT_SETTINGS' ) ? unserialize( HDEV_OPTIMG_DEFAULT_SETTINGS ) : array( // Fallback if constant is not defined
                    'mode' => 'performance',
                    'quality'=> 'good',
                    'quality_val'=> '55',
                    'sharpen'=> '1',
                    'interlace'=> 'true',
                    'optimize_original'=> 'true',
                    'remove_metadata'=> 'true'
                );
            default:
                return null;
        endswitch;
    }

    /**
     * Gets the plugin default settings
     *
     * @param null $preset
     * @return array|mixed
     */
    public static function get_optimization_quality_presets( $preset = null ) {

    	$quality_presets = defined( 'HDEV_OPTIMG_QUALITY_PRESETS' ) ? unserialize( HDEV_OPTIMG_QUALITY_PRESETS ) : null;

        switch ( $preset ) :
            case null || '':
                return ! empty( $quality_presets ) ? $quality_presets : array( // Fallback if constant is not defined
                    'high' => '65',
                    'very-high' => '77',
                    'medium' => '55',
                    'wp-default' => '90'
                );
            case 'high':
                return ! empty( $quality_presets ) ? $quality_presets['high'] : '65';
            case 'very-high':
                return ! empty( $quality_presets ) ? $quality_presets['very-high'] : '77';
            case 'medium':
                return ! empty( $quality_presets ) ? $quality_presets['medium'] : '55';
            case 'wp-default':
                return ! empty( $quality_presets ) ? $quality_presets['wp-default'] : '90';
            default:
                return null;
        endswitch;
    }

    /**
     * Fetch an option from the database with a default fallback.
     *
     * @param  string $key      The option key.
     * @param  string $default  A default value.
     * @param  string $serial   If we have a serialized data, look for one piece.
     *
     * @return mixed  $option   Either the found info, or false.
     */
    public static function get_single_option( $key, $default = '', $serial = '' ) {

        // Bail without a key.
        if ( empty( $key ) ) {
            return false;
        }

        // If option passed instead key string, use it. Otherwise fetch the option.
        $option = is_array( $key ) ? $key : get_option( $key );

        // Bail if no option is found, and no default was set.
        if ( empty( $option ) && empty( $default ) ) {
            return false;
        }

        // Handle the serial.
        if ( ! empty( $serial ) ) {
            return ! empty( $option[ $serial ] ) ? $option[ $serial ] : $default;
        }

        // Return whichever one we have.
        return ! empty( $option ) ? $option : $default;
    }

    /**
     * Fetch an option from the database with a default fallback.
     *
     * @param  string $key      The option key.
     * @param  string $default  A default value.
     * @param  string $serial   If we have a serialized data, look for one piece.
     *
     * @return mixed  $option   Either the found info, or false.
     */
    public static function get_single_option_checkbox( $key = 'hdev_optimg', $default = '', $serial = '' ) {

        // Bail without a key.
        if ( empty( $key ) ) {
            return false;
        }

        // If option passed instead key string, use it. Otherwise fetch the option.
        $option = is_array( $key ) ? $key : get_option( $key );

        // Bail if no option is found, and no default was set.
        if ( empty( $option ) && empty( $default ) ) {
            return false;
        }

        // Handle the serial.
        if ( ! empty( $serial ) ) {
            if( isset( $option ) && is_array( $option ) && array_key_exists( $serial, $option ) && $option[ $serial ] === '0' ) { // Check to fix issue with checkbox always being checked
                return $option[ $serial ];
            } else {
                return ! empty( $option[ $serial ] ) ? $option[ $serial ] : $default;
            }
        }

        // Return whichever one we have.
        return ! empty( $option ) ? $option : $default;
    }

    /**
     * Fetch a postmeta data from the database with a default fallback.
     *
     * @param  integer $post_id  The post ID to retrieve it from.
     * @param  string  $key      The postmeta key.
     * @param  string  $default  A default value.
     * @param  string  $serial   If we have a serialized data, look for one piece.
     *
     * @return mixed   $data     Either the found info, or false.
     */
    public static function get_single_postmeta( $post_id = 0, $key = '', $default = '', $serial = '' ) {

        // Bail without a post ID or a key.
        if ( empty( $post_id ) || empty( $key ) ) {
            return false;
        }

        // Fetch the option.
        $data   = get_post_meta( $post_id, $key, true );

        // Bail if no data is found, and no default was set.
        if ( empty( $data ) && empty( $default ) ) {
            return is_array( $default ) ? $default : false;
        }

        // Handle the serial.
        if ( ! empty( $serial ) ) {
            return ! empty( $data[ $serial ] ) ? $data[ $serial ] : $default;
        }

        // Return whichever one we have.
        return ! empty( $data ) ? $data : $default;
    }

    /**
     * Get the selected sharpening setting.
     *
     * @param  integer $post_id  The potential post ID being viewed.
     *
     * @return string  $place    The placement option.
     */
    public static function get_sharpening_setting( $post_id = 0 ) {

        // Fetch our placement setup
        $place  = self::get_single_postmeta( $post_id, '_hdev_optimg_postmeta', '', 'place' );

        // If we had no postmeta placement, then pull our global and return it.
        return ! empty( $place ) ? $place : self::get_single_option( 'hdev_optimg', 'below', 'place' );
    }

    /**
     * Preset our allowed post types for content modification with filter.
     *
     * @return array $types  The post types we are using.
     */
    public static function get_supported_types() {
        return apply_filters( 'hdev_optimg_post_types', array( 'post' ) );
    }

    /**
     * Set and return our args for WP_Editor, filtered.
     *
     * @param  string $name  The unique textarea name.
     *
     * @return array  $args  The WP_Editor args.
     */
    public static function get_wp_editor_args( $name = '' ) {

        // Set our settings for the WP_Editor call.
        $args   = array(
            'textarea_rows' => 6,
            'textarea_name' => esc_attr( $name ),
            'quicktags'     => array( 'buttons' => 'strong,em,ul,ol,li,link,img' ),
        );

        // Return our editor args, with a filter.
        return apply_filters( 'hdev_optimg_editor_args', $args, $name );
    }

    /**
     * Get size information for all currently-registered image sizes.
     *
     * @param string $sizes_type
     * @param bool $name_only
     * @global $_wp_additional_image_sizes
     * @uses   get_intermediate_image_sizes()
     * @return array $sizes Data for all currently-registered image sizes.
     */
    public static function get_image_sizes( $sizes_type = 'all', $name_only = false ) {

        global $_wp_additional_image_sizes;

        $sizes = array();

	    // if $name_only is true, return only array containing image size registered names
	    if( $name_only ) {
		    foreach ( get_intermediate_image_sizes() as $_size ) {
			    if ( ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) && ( $sizes_type == 'all' || $sizes_type == 'default' ) ) || ( isset( $_wp_additional_image_sizes[ $_size ] ) && ( $sizes_type == 'all' || $sizes_type == 'additional' ) ) ) {
				    $sizes[] = $_size;
			    }
		    }
	    } else {
		    foreach ( get_intermediate_image_sizes() as $_size ) {
			    if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) && ( $sizes_type == 'all' || $sizes_type == 'default' ) ) {
				    $sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
				    $sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
				    $sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
			    } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) && ( $sizes_type == 'all' || $sizes_type == 'additional' ) ) {
				    $sizes[ $_size ] = array(
					    'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					    'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					    'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				    );
			    }
		    }
	    }

        return $sizes;
    }

    /**
     * Get size information for a specific image size.
     *
     * @uses   get_image_sizes()
     * @param  string $size The image size for which to retrieve data.
     * @return bool|array $size Size data about an image size or false if the size doesn't exist.
     */
    public static function get_image_size( $size ) {
        $sizes = self::get_image_sizes();

        if ( isset( $sizes[ $size ] ) ) {
            return $sizes[ $size ];
        }

        return false;
    }

    /**
     * Get the width of a specific image size.
     *
     * @uses   get_image_size()
     * @param  string $size The image size for which to retrieve data.
     * @return bool|string $size Width of an image size or false if the size doesn't exist.
     */
    public static function get_image_width( $size ) {
        if ( ! $size = self::get_image_size( $size ) ) {
            return false;
        }

        if ( isset( $size['width'] ) ) {
            return $size['width'];
        }

        return false;
    }

    /**
     * Get the height of a specific image size.
     *
     * @uses   get_image_size()
     * @param  string $size The image size for which to retrieve data.
     * @return bool|string $size Height of an image size or false if the size doesn't exist.
     */
    public static function get_image_height( $size ) {
        if ( ! $size = self::get_image_size( $size ) ) {
            return false;
        }

        if ( isset( $size['height'] ) ) {
            return $size['height'];
        }

        return false;
    }

    /**
     * Add ?ftime=[last modified time] to a file url to force browsers to reload updated files like scripts, stylesheets, images...etc
     *
     * @uses file_exists(), filemtime()
     * @param string $relative_url The file's relative url
     * @param string $path_to_scripts
     * @return string The new file's relative url appended with its modified timestamp as a version parameter
     */
    public static function versioned_resource( $relative_url, $path_to_scripts = '' ) {

        // Get the file
        $file = str_replace( '//', '/', HDEV_OPTIMG_DIR . $path_to_scripts . $relative_url );

        // Get the file version
        $file_version = file_exists( $file ) && ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'HDEV_VERSIONED_SCRIPTS' ) && HDEV_VERSIONED_SCRIPTS ) ) ? '?ftime='.filemtime( $file ) : '';

        // Append the file version to the relative url and return it
        return $relative_url . $file_version;
    }

    /**
     * Checks to see if current environment supports Imagick.
     *
     * We require Imagick 2.2.0 or greater, based on whether the queryFormats()
     * method can be called statically.
     *
     * @access public
     *
     * @param array $args
     * @return bool
     */
    public static function test_imagick( $args = array() ) {

        // First, test Imagick's extension and classes.
        if ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick', false ) || ! class_exists( 'ImagickPixel', false ) )
            return false;

        if ( version_compare( phpversion( 'imagick' ), '2.2.0', '<' ) )
            return false;

        $required_methods = array(
            'clear',
            'destroy',
            'valid',
            'getimage',
            'writeimage',
            'getimageblob',
            'getimagegeometry',
            'getimageformat',
            'setimageformat',
            'setimagecompression',
            'setimagecompressionquality',
            'setimagepage',
            'setoption',
            'setinterlacescheme',
            'setimagecolorspace',
            'scaleimage',
            'cropimage',
            'cropthumbnailimage',
            'rotateimage',
            'flipimage',
            'flopimage',
            'readimage',
            'sharpenimage',
            'stripimage',
	        'getimageproperties',
	        'setimageproperty',
	        'coalesceimages',
	        'getimageiterations'
        );

        // Now, test for deep requirements within Imagick.
        if ( ! defined( 'imagick::COMPRESSION_JPEG' ) )
            return false;

        $class_methods = array_map( 'strtolower', get_class_methods( 'Imagick' ) );
        if ( array_diff( $required_methods, $class_methods ) ) {
            return false;
        }

        // HHVM Imagick does not support loading from URL, so fail to allow fallback to GD.
        if ( defined( 'HHVM_VERSION' ) && isset( $args['path'] ) && preg_match( '|^https?://|', $args['path'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether or not the optimization settings match the actual image optimization state (necessary when the user changes the optimization settings so the image shows as Partially optimized if it was processed previously with less options turned on)
     *
     * @param $attachment_ID
     * @return bool|mixed
     */
    public static function get_optimization_settings_match( $attachment_ID ) {

        // Get optimization log data
        $optimization_data =  HDEV_OPTIMG_Helper::get_single_postmeta( $attachment_ID, '_hdev_optimg_log', array() );

	    // Fetch the optimization setting options
	    $optimization_settings = get_option( 'hdev_optimg' );

        // Check the different condition
        $cond1 = $optimization_settings['optimize_original'] == 'true'
	        ? array_key_exists( 'full', $optimization_data )
	        : true;
	    $cond2 = $optimization_settings['sharpen'] == '1'
		    ? array_key_exists( 'blur_sharpen', $optimization_data ) && $optimization_data['blur_sharpen'] == '1'
		    : true;
	    //cond3 = $optimization_settings['interlace'] == 'true' ? array_key_exists( 'interlace', $optimization_data ) : true; // TODO: Figure out how to get uploaded image interlace scheme first to be able to check condition
	    $cond4 = $optimization_settings['remove_metadata'] == 'true'
		    ? array_key_exists( 'remove_metadata', $optimization_data ) && $optimization_data['remove_metadata'] == 'true'
		    : true;

        // Check against all conditions and return
        return $cond1 && $cond2 && $cond4;
    }

    // End the class.
}

// Instantiate our class.
$HDEV_OPTIMG_Helper = new HDEV_OPTIMG_Helper();
