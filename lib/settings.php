<?php
/**
 * Ultimate Image Optimization Helpers Plugin - settings functions
 *
 * Contains settings page related functions.
 *
 * @package Ultimate Image Optimization Helpers Plugin
 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Set up and load our class.
 */
class HDEV_OPTIMG_Settings
{
	private $default_settings,
		$optimization_options;

	/**
	 * Load our hooks and filters.
	 *
	 * @return void
	 */
	public function init() {

		// Get default settings
		$this->default_settings = HDEV_OPTIMG_Helper::get_optimization_defaults();

		// Fetch the optimization options
		$this->optimization_options = get_option( 'hdev_optimg' );

		// Make sure we use the default settings if no options set by user yet (fixes issue with checkboxes when no options are set yet)
		if( empty( $this->optimization_options ) ) {
			$this->optimization_options = $this->default_settings;

			// Populate the database option
			update_option( 'hdev_optimg', $this->default_settings );
		}

		// Load our settings
		add_action( 'admin_init',                   array( $this, 'load_settings'       )           );

		// Add link to plugin settings page on the plugin.php page
		add_filter( 'plugin_action_links_' . HDEV_OPTIMG_BASE, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Register our new settings and load our settings fields.
	 *
	 * @return void
	 */
	public function load_settings() {

		// Add our setting for the serialized array of items in the default optimization.
		register_setting( 'media', 'hdev_optimg', array( $this, 'data_sanitize' ) );

        /** Create our setting sections, hooked into the "media" section. */
        // Display extra image sizes set by plugins & themes
        add_settings_section( 'hdev_plugin_theme_image_sizes', __( 'Image Sizes Added by Theme/Plugins', 'ultimate-image-optimization-helpers' ), array( $this, 'settings_plugin_theme_image_sizes' ), 'media' );
        // Optimization settings
		add_settings_section( 'hdev_optimg', __( 'Image Optimization Settings', 'ultimate-image-optimization-helpers' ), array( $this, 'settings_optimg' ), 'media' );
	}

	/**
	 * Generate the plugin settings link to be added on the plugin.php admin page
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {

		$links[] = '<a href="'. esc_url( get_admin_url(null, 'options-media.php#' . 'hdev_optimg-settings-anchor') ) .'" title="Dashboard -> Settings -> Media">Settings</a>';

		return $links;
	}

    /**
     * Our settings section.
     *
     * @param  array $args  The arguments from the `add_settings_section` call.
     */
    public function settings_plugin_theme_image_sizes( $args ) {

        // Get all extra image sizes
        $image_sizes = HDEV_OPTIMG_Helper::get_image_sizes( 'additional' );

        // Add a div to wrap our whole thing for clean.
        echo '<div class="' . esc_attr( $args['id'] ) . '-wrap">';

            // Add our intro content.
            echo '<p>' . esc_html__( 'The sizes listed below determine additional maximum dimensions in pixels added by the current theme and/or plugins to use when adding an image to the Media Library.' , 'ultimate-image-optimization-helpers' ) . '</p>';

            // Now set up the table with each value.
            echo '<table id="' . esc_attr( $args['id'] ) . '" class="hdev-optimg-settings-table form-table">';
            echo '<tbody>';


            // Init ID count
            $ID_i = 1;
            // Get array size
            $array_size = sizeof( $image_sizes );

            foreach( $image_sizes as $size_name => $image_size ) {

                // Init cropped image message
                $crop_message  = '';
                // Generate cropped image message conditionally
                if( $image_size['crop'] ) {

                    $crop_message .= '<br><label style="cursor:auto;" for="hdev-optimg-image-sizes' . $ID_i . '_crop">';
                    $crop_message .= $image_size['width'] != $image_size['height'] ? esc_html__( 'This image is cropped to exact dimensions.', 'ultimate-image-optimization-helpers' ) : esc_html__( 'This image is cropped to exact dimensions and is proportional.', 'ultimate-image-optimization-helpers' );
                    $crop_message .= '</label>';
                }

                // Our additional image sizes field.
                echo $array_size == $ID_i ?  '<tr id="' . 'hdev_optimg-settings-anchor">' : '<tr>';

                    // The field label.
                    echo '<th scope="row">';
                    echo esc_html__( $size_name, 'ultimate-image-optimization-helpers' );
                    echo '</th>';

                    // The input fields.
                    echo '<td class="hdev-optimg-image-sizes" style="vertical-align:top;">';

                        echo '<fieldset>';

                            echo '<legend class="screen-reader-text"><span>' . esc_html__( $size_name, 'ultimate-image-optimization-helpers' ) . '</span></legend>';

                            echo '<label for="hdev-optimg-image-sizes-width">' . esc_html__( 'Max Width&nbsp;' ) . '</label>';

                            echo '<input type="number" id="hdev-optimg-image-sizes-width' . $ID_i . '" name="hdev-optimg-image-sizes-width" value="' . $image_size['width'] . '" step="1" min="0" class="small-text" disabled>';
                            echo '<label for="hdev-optimg-image-sizes-height">' . esc_html__( '&nbsp;Max Height&nbsp;' ) . '</label>';

                            echo '<input type="number" id="hdev-optimg-image-sizes-height' . $ID_i . '" name="hdev-optimg-image-sizes-height" value="' . $image_size['height'] . '" step="1" min="0" class="small-text" disabled>';

                            echo $crop_message;

                        echo '</fieldset>';

                    echo '</td>';

                // Close our additional image sizes field.
                echo '</tr>';

                // Increment count
                $ID_i++;
            }

            // Call our action to include any extra settings.
            do_action( 'hdev_optimg_settings_plugin_theme_image_sizes_page', $args );

            // Close the table.
            echo '</tbody>';
            echo '</table>';

        echo '</div>';
    }

	/**
	 * Our settings section.
	 *
	 * @param  array $args  The arguments from the `add_settings_section` call.
	 */
	public function settings_optimg( $args ) {

        // Get preset optimization settings
        $mode_preset_settings = HDEV_OPTIMG_Helper::get_optimization_mode_presets();

		// Fetch our stored settings.
        $mode  = HDEV_OPTIMG_Helper::get_single_option( $this->optimization_options, $this->default_settings['mode'], 'mode' );

		$convert = HDEV_OPTIMG_Helper::get_single_option( $this->optimization_options, $this->default_settings['convert'], 'convert' ); // To save correct setting - use in data_sanitize function and checkbox value

        $quality  = HDEV_OPTIMG_Helper::get_single_option( $this->optimization_options, $this->default_settings['quality'], 'quality' );

        $quality_val  = HDEV_OPTIMG_Helper::get_single_option( $this->optimization_options, $this->default_settings['quality_val'], 'quality_val' );

		$sharpen = HDEV_OPTIMG_Helper::get_single_option( $this->optimization_options, $this->default_settings['sharpen'], 'sharpen' ); // To save correct setting - use in data_sanitize function and checkbox value

        $interlace  = HDEV_OPTIMG_Helper::get_single_option( $this->optimization_options, $this->default_settings['interlace'], 'interlace' );

        $optimize_original  = HDEV_OPTIMG_Helper::get_single_option( $this->optimization_options, $this->default_settings['optimize_original'], 'optimize_original' );

        $remove_metadata  = HDEV_OPTIMG_Helper::get_single_option( $this->optimization_options, $this->default_settings['remove_metadata'], 'remove_metadata' );

        // To fix checkbox always checked - use with checked() function only
        $sharpen_checkbox_check  = HDEV_OPTIMG_Helper::get_single_option_checkbox( $this->optimization_options, $this->default_settings['sharpen'], 'sharpen' );
		$conversion_checkbox_check  = HDEV_OPTIMG_Helper::get_single_option_checkbox( $this->optimization_options, $this->default_settings['convert'], 'convert' );

        // Modes visibility
        $mode_hidden = $mode != 'advanced' ? 'style="display:none;"' : '';
        $mode_hidden_class = $mode != 'advanced' ? ' hdev-toggled-hide' : '';

        // Modes visibility
        $quality_val_hidden = $quality != 'custom' ? 'style="display:none;"' : '';
        $quality_val_hidden_class = $quality != 'custom' ? ' hdev-toggled-hide' : '';

		// Set our settings for the WP_Editor call.
		//$editor = HDEV_OPTIMG_Helper::get_wp_editor_args( 'hdev_optimg[text]' );

		// Add a div to wrap our whole thing for clean.
		echo '<div class="' . esc_attr( $args['id'] ) . '-wrap">';

		    // Display error message if PHP Imagick not supported
            if( ! HDEV_OPTIMG_Helper::test_imagick() ) {

                echo '<div style="color:#a00;" class="hdev-admin-box"><p><strong>' . esc_html__( 'Warning! ' , 'ultimate-image-optimization-helpers' ) . '</strong> ' . esc_html__( 'All image optimization features have been deactivated because your PHP environment does not support Imagick or your current version of Imagick does not support all the following methods: (clear, destroy, valid, getimage, writeimage, getimageblob, getimagegeometry, getimageformat, setimageformat, setimagecompression, setimagecompressionquality, setimagepage, setoption, setInterlaceScheme, setImageColorspace, scaleimage, cropimage, cropThumbnailImage, rotateimage, flipimage, flopimage, readimage, sharpenimage, stripImage).' , 'ultimate-image-optimization-helpers' ) . '</p><p>' . esc_html__( 'Please contact your host or server administrator to resolve this issue.' , 'ultimate-image-optimization-helpers' ) . '</p></div>';
            } else {

                // Add our intro content.
                echo '<p>' . esc_html__( 'Easily optimize JPEG images for better quality and faster page load.' , 'ultimate-image-optimization-helpers' ) . '</p>';

                echo '<p style="margin:0;"><strong><u>' . esc_html__( 'Important Note' , 'ultimate-image-optimization-helpers' ) . '</u></strong>: ' . esc_html__( 'New optimization settings will only affect future uploaded images.' , 'ultimate-image-optimization-helpers' ) . '</p><p style="margin:0;">' . esc_html__( 'To apply new optimizations to previously uploaded images, we recommend regenerating them using this plugin: ' , 'ultimate-image-optimization-helpers' ) . ' <a href="https://wordpress.org/plugins/regenerate-thumbnails/" target="_blank">' . esc_html__('Regenerate Thumbnails', 'ultimate-image-optimization-helpers' ) . '</a> ' . esc_html__('by Alex Mills', 'ultimate-image-optimization-helpers' ) . '</p>';

                // Now set up the table with each value.
                echo '<table id="' . esc_attr( $args['id'] ) . '" class="hdev-optimg-settings-table form-table">';
                echo '<tbody>';

                    // Our mode radio field.
                    echo '<tr id="hdev-optimg-mode-container">';

                        // The field label.
                        echo '<th scope="row">';
                            echo esc_html__( 'Mode', 'ultimate-image-optimization-helpers' ) . '<div class="hdev-tooltip dashicons dashicons-editor-help" data-hdev-tooltip="' . esc_html__( 'Choose the optimization preset that best match your use case, or go custom if you are an advanced user and know what you\'re doing.', 'ultimate-image-optimization-helpers' ) . '"><span class="icon-help"></span></div>';
                        echo '</th>';

                        // The input field.
                        echo '<td class="hdev-optimg-mode">';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-mode-balanced">';
                            echo '<input type="radio" id="hdev-optimg-mode-balanced" name="hdev_optimg[mode]" value="balanced" ' . checked( $mode, 'balanced', false ) . ' />';
                            echo esc_html__( 'Balanced (recommended for most websites)', 'ultimate-image-optimization-helpers' ) . '</label>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-mode-hd">';
                            echo '<input type="radio" id="hdev-optimg-mode-hd" name="hdev_optimg[mode]" value="hd" ' . checked( $mode, 'hd', false ) . ' />';
                            echo '<span style="color:#000;background-color:rgba(0,0,0,.05);">' . 'HD (' . esc_html__( 'high image quality | descent compression', 'ultimate-image-optimization-helpers' ) . ', <u>' . esc_html__( 'highly recommended', 'ultimate-image-optimization-helpers' ) . '</u> - ' . esc_html__( 'for image driven websites)', 'ultimate-image-optimization-helpers' ) . '</span></label>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-mode-performance">';
                            echo '<input type="radio" id="hdev-optimg-mode-performance" name="hdev_optimg[mode]" value="performance" ' . checked( $mode, 'performance', false ) . ' />';
                            echo esc_html__( 'Performance (higher compression | lower image quality)', 'ultimate-image-optimization-helpers' ) . '</label>';

	                        echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-mode-advanced">';
                            echo '<input type="radio" id="hdev-optimg-mode-advanced" name="hdev_optimg[mode]" value="advanced" ' . checked( $mode, 'advanced', false ) . ' />';
                            echo esc_html__( 'Custom', 'ultimate-image-optimization-helpers' ) . ' (' .esc_html__( 'for advanced users', 'ultimate-image-optimization-helpers' ) . ')' . '</label>';

				            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-mode-disabled">';
				            echo '<input type="radio" id="hdev-optimg-mode-disabled" name="hdev_optimg[mode]" value="disabled" ' . checked( $mode, 'disabled', false ) . ' />';
				            echo esc_html__( 'Disabled (no optimization)', 'ultimate-image-optimization-helpers' ) . '</label>';

                        echo '</td>';

                    // Close our mode radio field.
                    echo '</tr>';

					// Our conversion checkbox field.
		            echo '<tr id="hdev-optimg-conversion-container">';

		                // The field label.
			            echo '<th scope="row">';
			                echo esc_html__( 'PNG to JPEG Conversion', 'ultimate-image-optimization-helpers' ) . '<div class="hdev-tooltip dashicons dashicons-editor-help" data-hdev-tooltip="' . esc_html__( 'Message here', 'ultimate-image-optimization-helpers' ) . '"><span class="icon-help"></span></div>';
			            echo '</th>';

			            // The input field.
			            echo '<td>';

				            echo '<input name="hdev_optimg[convert]" type="checkbox" id="hdev-optimg-convert" value="' . $convert . '" ' . checked( $conversion_checkbox_check, '1', false ) . ' />';
				            echo '<label for="hdev-optimg-convert"> ' .  esc_html__( 'Convert non-transparent PNG images to JPEG (recommended)', 'ultimate-image-optimization-helpers' ) . '</label>';

			            echo '</td>';

		            // Close our blur/sharpening radio field.
		            echo '</tr>';

                    // Our quality radio field.
                    echo '<tr class="hdev-optimg-mode-target' . $mode_hidden_class . '" ' . $mode_hidden . '>';

                        // The field label.
                        echo '<th scope="row">';
                        echo esc_html__( 'Quality', 'ultimate-image-optimization-helpers' ) . '<div class="hdev-tooltip dashicons dashicons-editor-help" data-hdev-tooltip="' . esc_html__( 'Lower quality settings reduce file size and improves page load. However,  bear in mind that quality loss starts to become obvious to the eye bellow the recommended value of 77.', 'ultimate-image-optimization-helpers' ) . '"><span class="icon-help"></span></div>';
                        echo '</th>';

                        // The input field.
                        echo '<td id="hdev-optimg-quality">';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-quality-high">';
                            echo '<input class="hdev-customQualityInput-trigger" type="radio" id="hdev-optimg-quality-high" name="hdev_optimg[quality]" value="high" ' . checked( $quality, 'high', false ) . ' />';
                            echo esc_html__( 'High', 'ultimate-image-optimization-helpers' ) . ' (' . $mode_preset_settings['balanced']['quality_val'] . ', ' . esc_html__( 'recommended', 'ultimate-image-optimization-helpers' ) . ')' . '</label>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-quality-very-high">';
                            echo '<input class="hdev-customQualityInput-trigger" type="radio" id="hdev-optimg-quality-very-high" name="hdev_optimg[quality]" value="very-high" ' . checked( $quality, 'very-high', false ) . ' />';
                            echo esc_html__( 'Very High', 'ultimate-image-optimization-helpers' ) . ' (' . $mode_preset_settings['hd']['quality_val'] . ', best for photography portfolios)' . '</label>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-quality-medium">';
                            echo '<input class="hdev-customQualityInput-trigger" type="radio" id="hdev-optimg-quality-medium" name="hdev_optimg[quality]" value="medium" ' . checked( $quality, 'medium', false ) . ' />';
                            echo esc_html__( 'Medium', 'ultimate-image-optimization-helpers' ) . ' (' . $mode_preset_settings['performance']['quality_val'] . ', for performance)' . '</label>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-quality-wp-default">';
                            echo '<input class="hdev-customQualityInput-trigger" type="radio" id="hdev-optimg-quality-wp-default" name="hdev_optimg[quality]" value="wp-default" ' . checked( $quality, 'wp-default', false ) . ' />';
                            echo esc_html__( 'WP Default', 'ultimate-image-optimization-helpers' ) . ' (' . esc_html__( 'not recommended', 'ultimate-image-optimization-helpers' ) . ')' . '</label>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-quality-custom">';
                            echo '<input type="radio" id="hdev-optimg-quality-custom" name="hdev_optimg[quality]" value="custom" ' . checked( $quality, 'custom', false ) . ' />';
                            echo esc_html__( 'Custom', 'ultimate-image-optimization-helpers' ) . ' ' . esc_html__( 'compression rate', 'ultimate-image-optimization-helpers' );

                            echo '<input ' . $quality_val_hidden . ' type="number" id="hdev-optimg-quality-custom-val" name="hdev_optimg[quality_val]" value="' . $quality_val . '" step="1" min="0" max="100" class="small-text ' . $quality_val_hidden_class . '" >' . '</label>';

                        echo '</td>';

                    // Close our quality radio field.
                    echo '</tr>';

                    // Our blur/sharpening checkbox field.
                    echo '<tr class="hdev-optimg-mode-target' . $mode_hidden_class . '" ' . $mode_hidden . '>';

                        // The field label.
                        echo '<th scope="row">';
                            echo esc_html__( 'Enhanced quality', 'ultimate-image-optimization-helpers' ) . '<div class="hdev-tooltip dashicons dashicons-editor-help" data-hdev-tooltip="' . esc_html__( 'This ImageMagick enhancement applies state of the art blur/sharpen method to compensate for image quality loss caused by resizing. It is highly recommended for all websites &#8212; can be deactivated only by advanced users using filter hdev_optimg_sharpen', 'ultimate-image-optimization-helpers' ) . '"><span class="icon-help"></span></div>';
                        echo '</th>';

                        // The input field.
                        echo '<td>';

                            echo '<input name="hdev_optimg[sharpen]" type="checkbox" id="hdev-optimg-sharpen" value="' . $sharpen . '" ' . checked( $sharpen_checkbox_check, '1', false ) . ' disabled />';
                            echo '<label for="hdev-optimg-sharpen"> ' .  esc_html__( 'Improve resized images quality (highly recommended & always active)', 'ultimate-image-optimization-helpers' ) . '</label>';

                        echo '</td>';

                    // Close our blur/sharpening radio field.
                    echo '</tr>';

                    // Our interlace radio field.
                    echo '<tr class="hdev-optimg-mode-target' . $mode_hidden_class . '" ' . $mode_hidden . '>';

                        // The field label.
                        echo '<th scope="row">';
                            echo esc_html__( 'Interlace Scheme', 'ultimate-image-optimization-helpers' ) . '<div class="hdev-tooltip dashicons dashicons-editor-help" data-hdev-tooltip="' . esc_html__( 'Making images "progressive" reduces their size a bit and also allows some browsers to start displaying the full image faster. However some studies have shown that visually, most people prefer "deinterlaced" images because of the way they load in full resolution, unfolding from top to bottom&#8212;as opposed to progressive images which are displayed fully but in low resolution first, before they sharpen progressively as the browser finishes loading them...', 'ultimate-image-optimization-helpers' ) . '"><span class="icon-help"></span></div>';
                        echo '</th>';

                        // The input field.
                        echo '<td>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-interlace-origin">';
                            echo '<input type="radio" id="hdev-optimg-interlace-origin" name="hdev_optimg[interlace]" value="origin" ' . checked( $interlace, 'origin', false ) . ' />';
                            echo esc_html__( 'Preserve original', 'ultimate-image-optimization-helpers' ) . '</label>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-interlace-progressive">';
                            echo '<input type="radio" id="hdev-optimg-interlace-progressive" name="hdev_optimg[interlace]" value="progressive" ' . checked( $interlace, 'progressive', false ) . ' />';
                            echo esc_html__( 'Progressive', 'ultimate-image-optimization-helpers' ) . '</label>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-interlace-deinterlace">';
                            echo '<input type="radio" id="hdev-optimg-interlace-deinterlace" name="hdev_optimg[interlace]" value="deinterlace" ' . checked( $interlace, 'deinterlace', false ) . ' />';
                            echo esc_html__( 'Deinterlaced', 'ultimate-image-optimization-helpers' ) . '</label>';

                        echo '</td>';

                    // Close our interlace radio field.
                    echo '</tr>';

                    // Our original img compression radio field.
                    echo '<tr class="hdev-optimg-mode-target' . $mode_hidden_class . '" ' . $mode_hidden . '>';

                        // The field label.
                        echo '<th scope="row">';
                            echo esc_html__( 'Optimize Original?', 'ultimate-image-optimization-helpers' ) . '<div class="hdev-tooltip dashicons dashicons-editor-help" data-hdev-tooltip="' . esc_html__( 'Choose if you want to also apply the above quality/compression settings to the original image.&#xa;Highly recommended &#8212; unless you\'re sure you\'ll be uploading only images that have already been fully optimized by other means...&#xa;And no worries, the non-optimized original version will be backed-up for reference and future processing.', 'ultimate-image-optimization-helpers' ) . '"><span class="icon-help"></span></div>';
                        echo '</th>';

                        // The input field.
                        echo '<td>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-compress-original-true">';
                            echo '<input type="radio" id="hdev-optimg-compress-original-true" name="hdev_optimg[optimize_original]" value="true" ' . checked( $optimize_original, 'true', false ) . ' />';
                            echo esc_html__( 'Yes (highly recommended)', 'ultimate-image-optimization-helpers' ) . '</label>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-compress-original-false">';
                            echo '<input type="radio" id="hdev-optimg-compress-original-false" name="hdev_optimg[optimize_original]" value="false" ' . checked( $optimize_original, 'false', false ) . ' />';
                            echo esc_html__( 'No', 'ultimate-image-optimization-helpers' ) . '</label>';

                        echo '</td>';

                    // Close our original img compression radio field.
                    echo '</tr>';

                    // Our remove meta/exif data radio field.
                    echo '<tr class="hdev-optimg-mode-target' . $mode_hidden_class . '" ' . $mode_hidden . '>';

                        // The field label.
                        echo '<th scope="row">';
                            echo esc_html__( 'Remove metadata?', 'ultimate-image-optimization-helpers' ) . '<div class="hdev-tooltip dashicons dashicons-editor-help" data-hdev-tooltip="' . esc_html__( 'Removing the meta/exif data reduces the images\' file size.', 'ultimate-image-optimization-helpers' ) . '"><span class="icon-help"></span></div>';
                        echo '</th>';

                        // The input field.
                        echo '<td>';

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-metadata-true">';
                            echo '<input type="radio" id="hdev-optimg-metadata-true" name="hdev_optimg[remove_metadata]" value="true" ' . checked( $remove_metadata, 'true', false ) . ' />';
                            echo esc_html__( 'Yes (recommended)', 'ultimate-image-optimization-helpers' ) . '</label>';

                            /*echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-metadata-resized">';
                            echo '<input type="radio" id="hdev-optimg-metadata-resized" name="hdev_optimg[remove_metadata]" value="resized" ' . checked( $remove_metadata, 'no', false ) . ' />';
                            echo esc_html__( 'Resized images only', 'ultimate-image-optimization-helpers' ) . '</label>';*/

                            echo '<label class="hdev-label-radio-stacked" for="hdev-optimg-metadata-false">';
                            echo '<input type="radio" id="hdev-optimg-metadata-false" name="hdev_optimg[remove_metadata]" value="false" ' . checked( $remove_metadata, 'false', false ) . ' />';
                            echo esc_html__( 'No, preserve my original meta/exif data', 'ultimate-image-optimization-helpers' ) . '</label>';

                        echo '</td>';

                    // Close our remove meta/exif data radio field.
                    echo '</tr>';

                    // Call our action to include any extra settings.
                    do_action( 'hdev_optimg_settings_optimg_page', $args );

                // Close the table.
                echo '</tbody>';
                echo '</table>';
            }

        echo '</div>';
	}

	/**
	 * Sanitize the user data inputs.
	 *
	 * @param  array $input  The data entered in a settings field.
	 *
	 * @return array $input  The sanitized data.
	 */
	public function data_sanitize( $input = null ) {

	    // Populated our input
		if( empty( $input ) ) {
            $input = $_POST['hdev_optimg'];
        }

        // Make sure we have an array.
        $input  = (array) $input;

        // Sanitize the quality radio input.
        $mode  = ! empty( $input['mode'] ) ? sanitize_text_field( $input['mode'] ) : $this->default_settings['mode'];

        // Init our preset settings var
        $mode_preset_settings = HDEV_OPTIMG_Helper::get_optimization_mode_data( $mode );

		// Sanitize the conversion checkbox input.
		$convert  =  isset( $input['convert'] ) ? '1' : '0';

		// Sanitize the quality radio input.
		$quality  = isset( $mode_preset_settings ) ? $mode_preset_settings['quality'] : ( ! empty( $input['quality'] ) ? sanitize_text_field( $input['quality'] ) : $this->default_settings['quality'] );

        // Sanitize the quality radio input.
        $quality_val  = isset( $mode_preset_settings ) ? $mode_preset_settings['quality_val'] : ( ! empty( $input['quality_val'] ) ? sanitize_text_field( $input['quality_val'] ) : $this->default_settings['quality_val'] );

        // Sanitize the blur/sharpening checkbox input.
        $sharpen  = isset( $input['sharpen'] ) ? '1' : $this->default_settings['sharpen']; // Always = 1 unless filtered later on

        // Sanitize the interlace scheme radio input.
        $interlace  = isset( $mode_preset_settings ) ? $mode_preset_settings['interlace'] : ( ! empty( $input['interlace'] ) ? sanitize_text_field( $input['interlace'] ) : $this->default_settings['interlace'] );

        // Sanitize the original compression radio input.
        $optimize_original  = isset( $mode_preset_settings ) ? $mode_preset_settings['optimize_original'] : ( isset( $mode_preset_settings ) ? $mode_preset_settings['quality_val'] : ( ! empty( $input['optimize_original'] ) ? sanitize_text_field( $input['optimize_original'] ) : $this->default_settings['optimize_original'] ) );

        // Sanitize the remove_metadata radio input.
        $remove_metadata  = isset( $mode_preset_settings ) ? $mode_preset_settings['remove_metadata'] : ( ! empty( $input['remove_metadata'] ) ? sanitize_text_field( $input['remove_metadata'] ) : $this->default_settings['remove_metadata'] );

		// Set our new input array.
		$input  = array( 'mode' => $mode, 'convert' => $convert, 'quality' => $quality, 'quality_val' => $quality_val, 'sharpen' => $sharpen, 'interlace' => $interlace, 'optimize_original' => $optimize_original, 'remove_metadata' => $remove_metadata );

		// And return our input with a filter to allow
		// additional settings to be added later.
		return apply_filters( 'hdev_optimg_data_sanitize', $input );
	}

	// End the class.
}

// Instantiate our class.
$HDEV_OPTIMG_Settings = new HDEV_OPTIMG_Settings();
$HDEV_OPTIMG_Settings->init();
