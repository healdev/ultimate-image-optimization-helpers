<?php

/**
 * Ultimate Image Optimization Helpers Plugin - constants
 *
 * @package Ultimate Image Optimization Helpers Plugin
 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// Set our defined dev mode.
if ( ! defined( 'HDEV_OPTIMG_DEV' ) ) {
    define( 'HDEV_OPTIMG_DEV', true );
}

// Set our defined debug mode.
if ( ! defined( 'HDEV_OPTIMG_DEBUG' ) ) {
    define( 'HDEV_OPTIMG_DEBUG', false );
}

// Set our defined plugin name.
if ( ! defined( 'HDEV_OPTIMG_PLUGIN_NAME' ) ) {
    define( 'HDEV_OPTIMG_PLUGIN_NAME', 'Ultimate Image Optimization Helpers' );
}

// Set our defined supported mime types. Uses serialize method to store arrays for sake of PHP backward compatibility
if ( ! defined( 'HDEV_OPTIMG_MIMES' ) ) {
    define( 'HDEV_OPTIMG_MIMES', serialize(
        array(
            'image/jpeg'
        )
    ) );
}

// Set our defined optimization default settings
if ( ! defined( 'HDEV_OPTIMG_DEFAULT_SETTINGS' ) ) {
    define( 'HDEV_OPTIMG_DEFAULT_SETTINGS', serialize(
        array(
            'mode' => 'balanced',
            'convert' => '0',
            'quality'=> 'high',
            'quality_val'=> '60',
            'sharpen'=> '1',
            'interlace'=> 'origin',
            'optimize_original'=> 'true',
            'remove_metadata'=> 'true'
        )
    ) );
}

// Set our defined optimization presets
if ( ! defined( 'HDEV_OPTIMG_MODE_PRESETS' ) ) {
    define( 'HDEV_OPTIMG_MODE_PRESETS', serialize(
        array(
            'balanced' => array(
                'mode' => 'balanced',
                'quality'=> 'high',
                'quality_val'=> '60',
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
                'quality_val'=> '50',
                'sharpen'=> '1',
                'interlace'=> 'progressive',
                'optimize_original'=> 'true',
                'remove_metadata'=> 'true'
            )
        )
    ) );
}

// Set our defined optimization presets
if ( ! defined( 'HDEV_OPTIMG_QUALITY_PRESETS' ) ) {
    define( 'HDEV_OPTIMG_QUALITY_PRESETS', serialize(
        array(
            'high' => '60',
            'very-high' => '77',
            'medium' => '50',
            'wp-default' => '90'
        )
    ) );
}

// Set our defined optimization presets
if ( ! defined( 'HDEV_OPTIMG_REMOVABLE_META' ) ) {
	define( 'HDEV_OPTIMG_REMOVABLE_META', serialize(
		array(
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
		)
	) );
}

// Set our defined debug mode.
if ( ! defined( 'HDEV_OPTIMG_WP_QUALITY' ) ) {
    define( 'HDEV_OPTIMG_WP_QUALITY', 90 );
}

// Set our defined debug mode.
if ( ! defined( 'HDEV_OPTIMG_MAX_RETINA_WIDTH' ) ) {
    define( 'HDEV_OPTIMG_MAX_RETINA_WIDTH', 5120 );
}

// Set our defined debug mode.
if ( ! defined( 'HDEV_OPTIMG_MAX_RETINA_HEIGHT' ) ) {
    define( 'HDEV_OPTIMG_MAX_RETINA_HEIGHT', 2880 );
}

// Set our defined version.
if ( ! defined( 'HDEV_OPTIMG_VER' ) ) {
    define( 'HDEV_OPTIMG_VER', '0.1.0' );
}
