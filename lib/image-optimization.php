<?php
/**
 * Ultimate Image Optimization Helpers Plugin
 *
 * Contains Custom Admin Columns.
 *
 * @package Ultimate Image Optimization Helpers Plugin
 */


if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Set up and load our class.
 */
class HDEV_OPTIMG_Optimize
{
	// define our class properties
	private $optimization_settings,
		$optimization_default_settings,
		$mode,
		$imagick_optimization_active,
		$imagick_active,
		$root_imagick_object = null,
		$mode_independent_settings = array( 'convert' ),
		$convert;

	/**
	 * Load our hooks and filters.
	 *
	 * @return void
	 */
	public function init() {

		// Fetch the optimization setting options
		$this->optimization_settings = get_option( 'hdev_optimg' );

		// Get default optimization settings
		$this->optimization_default_settings = HDEV_OPTIMG_Helper::get_optimization_defaults();

		// Fetch and filter our mode setting.
		$this->mode  = apply_filters( 'hdev_optimg_set_mode',
			HDEV_OPTIMG_Helper::get_single_option(
				$this->optimization_settings,
				$this->optimization_default_settings['mode'],
				'mode' )
		);

		// Fetch and filter our conversion setting.
		$this->convert  = apply_filters( 'hdev_optimg_set_conversion',
			HDEV_OPTIMG_Helper::get_single_option(
				$this->optimization_settings,
				$this->optimization_default_settings['convert'],
				'convert' )
		);

		// Check and filter if optimization is active
		$this->imagick_optimization_active = apply_filters( 'hdev_activate_imagick_image_optimization', $this->mode !== 'disabled' );

		// Check if PHP version used has Imagick with the necessary active
		$this->imagick_active = HDEV_OPTIMG_Helper::test_imagick();

		// Optimize images after they are uploaded
		add_filter( 'wp_update_attachment_metadata',      array( $this, 'optimize_images'        ), 9999, 2  );

		// Make sure WordPress does not compress files on scale/crop...etc so our compression does not add up quality loss
		if( $this->imagick_optimization_active ) {
			add_filter( 'wp_editor_set_quality', function( $quality ) {

				// Store WP filtered compression rate
				$GLOBALS['optimg_wp_filtered_quality'] = $quality;

				return 100; // VERY IMPORTANT FOR AJAX SCALE/CROP ACTIONS
			}, 9999 );
		}

		// Handle cleanup when attachment is deleted
		add_action( 'delete_attachment',                  array( $this, 'cleanup_deleted_image'  ), 9999, 2  );

		// Handle image conversion
		add_filter( 'wp_handle_upload_prefilter',         array( $this, 'convert_image_to_jpeg'   ), 9999     );
	}

	/**
	 * Optimize images after they are resized
	 *
	 * @param $metadata
	 * @param $attachment_ID
	 * @return mixed
	 */
	public function optimize_images( $metadata, $attachment_ID ) {

		// Get the file mime type
		$attachment_mime_type = get_post_mime_type( $attachment_ID );

		// Make sure we use the default settings if user has not set any yet
		if( empty( $this->optimization_settings ) ) {
			$this->optimization_settings = $this->optimization_default_settings;
		}

		// Do nothing if the image type is not among the allowed mime types constant or if Imagick is not available or if optimization is deactivated
		if ( ! $this->imagick_optimization_active || ! in_array( $attachment_mime_type, unserialize( HDEV_OPTIMG_MIMES ) ) || ! $this->imagick_active ) {

			// Get rid of Imagick object in memory if is was set during file upload
			if( isset( $this->root_imagick_object ) && $this->root_imagick_object->clear() !== true ) $this->root_imagick_object->destroy();

			return $metadata;
		}

		// Get the file directory
		$file_dir = dirname( get_attached_file( $attachment_ID ) ) . '/';

		// Safely get attached file
		$_wp_attached_file = array_key_exists( 'file', $metadata ) && ! empty( $metadata['file'] ) ? $metadata['file'] : get_post_meta( $attachment_ID, 'wp_attached_file', true );

		// Get the original image path
		$original_file_path = $file_dir . basename( $_wp_attached_file );

		// Get original image imagick object
		if( empty( $this->root_imagick_object ) ) {

			// Get original image as imagick object from file
			$original_imagick = new Imagick();
			$original_imagick->readImage( $original_file_path );
		} else {

			// Get original image from root imagick object
			$original_imagick = $this->root_imagick_object;
		}

		// Set path info for copy of original image if set previously
		$original_copy_file = get_post_meta( $attachment_ID, '_hdev_optimg_original_bak', true );

		// Get saved metadata if any
		$metadata_backup = get_post_meta( $attachment_ID, '_hdev_optimg_metadata_bak', true );

		// Override metadata with backup if doing restore ajax action to avoid all WP known issues...
		if( isset( $_POST['do'] ) && in_array( $_POST['do'], array( 'restore' ) ) && ! empty( $metadata_backup ) ) {

			$metadata = $metadata_backup;
		}

		// Detect if image is animated or still
		$is_animated = $original_imagick->getNumberImages() > 1 ? true : false;

		/** Use copy of original instead of current when possible to make sure original quality is always maintained when new optimizations are processed on an existing image */

		if( ! empty( $original_copy_file ) ) {

			// Use the original file for future images/thumbnails regeneration
			if( ! isset( $_POST['do'] ) || $_POST['do'] == 'restore' ) { // TODO: maybe write algorithm for different cases save/scale
				$original_file_path = $file_dir . basename( $original_copy_file );
			}
		} elseif( ! $is_animated ) { // Create a backup of the original image only if it is not animated

			$file_path_parts = pathinfo( $_wp_attached_file );
			$new_original_file_name = $file_path_parts['filename'] . '__ORIGINAL.' . $file_path_parts['extension'];
			$new_original_file_path = $file_dir . $new_original_file_name;

			// Store a copy of the original uploaded file
			copy( $original_file_path, $new_original_file_path );

			// Store a copy of the original uploaded file metadata
			update_post_meta( $attachment_ID, '_hdev_optimg_metadata_bak', $metadata );

			// Keep track of the copy of original image to use for future processing and preserve best original quality
			update_post_meta( $attachment_ID, '_hdev_optimg_original_bak', $file_path_parts['dirname'] . '/' . $new_original_file_name );
		}

		// Init optimization status
		$_hdev_optimg_status = '';

		// Init optimization success rate
		$_hdev_optimg_rate = 0;

		// Init optimization log
		$_hdev_optimg_log = array();

		// Init our preset settings var
		$preset_settings = HDEV_OPTIMG_Helper::get_optimization_mode_data( $this->mode );

		/** Fetch and filter our other settings. */

		$quality  = self::get_optimization_setting( $preset_settings, 'quality' );
		$quality  = self::get_optimization_quality( $quality ); // filtered by this plugin and WordPress

		$sharpen = apply_filters( 'hdev_optimg_sharpen', true );

		// Always set color profile to sRGB (do we need to allow filtering of this? probably not...)
		$sRGB = true;

		$interlace  = apply_filters( 'hdev_optimg_set_interlace',
			self::get_optimization_setting( $preset_settings,'interlace'
			)
		);
		$interlace = $interlace == 'progressive' ? true : ( $interlace == 'deinterlace' ? false : null );

		$optimize_original  = apply_filters( 'hdev_optimg_optimize_original',
			self::get_optimization_setting( $preset_settings,'optimize_original'
			)
		);
		$optimize_original = $optimize_original == 'true' ? true : false;
		$optimize_original = $is_animated ? false : $optimize_original; // do not optimize original if it is an animated image

		$remove_metadata  = apply_filters( 'hdev_optimg_remove_metadata',
			self::get_optimization_setting( $preset_settings,'remove_metadata'
			)
		);
		$remove_metadata = $remove_metadata == 'true' ? true : false;

		// Update image metadata saved as postmeta but keep orientation info
		if( $remove_metadata ) {

			foreach( HDEV_OPTIMG_Helper::get_removable_meta() as $meta ) {
				$metadata['image_meta'][$meta] = '';
			}
		}

		// Prepare our optimization parameters
		$optimization_params = array(
			'quality' => $quality,
			'sharpen' => $sharpen,
			'interlace' => $interlace,
			'remove_metadata' => $remove_metadata,
			'set_sRGB' => $sRGB
		);

		/** Start original image optimization process if option is turned on *************/

		if( $optimize_original ) {

			// Clone original Imagick object from original (more efficient than creating new object every time)
			$imagick = clone $original_imagick;

			// get the processing image dimension
			$image_dimension = array(
				$imagick->getImageWidth(),
				$imagick->getImageHeight()
			);

			// Get original image aspect ratio
			$image_ratio = $image_dimension[0] / $image_dimension[1];

			// Get size parameters var
			$size_params = self::get_adjusted_size_params( $image_ratio, $image_dimension );

			$image_data = array(
				'file_path' => $original_file_path,
				'mime_type' => $attachment_mime_type,
				'width' => $size_params[0],
				'height' => $size_params[1],
				'crop' => false,
				'orientation' => $metadata['image_meta']['orientation']
			);

			// Init original image optimization parameters
			$original_img_optimization_params = $optimization_params;

			// Update sharpening parameter for original image so it is true only when image is resized
			$original_img_optimization_params['sharpen'] = $image_dimension[0] == $size_params[0] || $image_dimension[1] == $size_params[1] ? false : apply_filters( 'hdev_optimg_sharpen', true );

			// Handle doing ajax scale/crop action
			// Make sure original is not re-optimized the same way when editing/cropping/changing-orientation to preserve quality
			if( ! empty( $_POST['do'] ) && in_array( $_POST['do'], array( 'scale', 'crop', 'save' ) ) ) {
				//$original_img_optimization_params['lossless'] = true; // Do not compress any further, use scaled as is
				$original_img_optimization_params['sharpen'] = apply_filters( 'hdev_optimg_sharpen', true ); // Force sharpening
			}

			// Optimize and resize original image and update metadata with new dimension
			$original_image_optimized = self::wp_imagick_optimize_image( $imagick, $image_data, $original_img_optimization_params );

			// Get rid of the Imagick object in memory
			if( isset( $imagick ) && $imagick->clear() !== true ) $imagick->destroy();

			// Unsuccessful optimization validation event logging
			if( ! $original_image_optimized['optimized'] ) {

				// Log error
				error_log( 'Warning: (Plugin ' . HDEV_OPTIMG_PLUGIN_NAME . 'An Imagick error happened while optimizing or/and resizing the "original image" image. ' . $original_image_optimized, 0 );

				// Set the optimization status to partial if the process failed at least 1 time
				$_hdev_optimg_status = 'partial';

			} else {

				// Log optimization details in log var
				$_hdev_optimg_log['full'] = array(
					'optimized' => $original_image_optimized['optimized'],
					'remove_metadata' => $original_image_optimized['remove_metadata'],
					'set_sRGB' => $sRGB,
					'interlace' => $original_image_optimized['interlace'],
					'resized' => $original_image_optimized['resized'],
					'crop' => $original_image_optimized['crop'],
					'blur_sharpen' => $original_image_optimized['blur_sharpen'],
					'compression' => $original_image_optimized['compression']
				);

				// Increment success rate
				$_hdev_optimg_rate++;

				// Update metadata if original image was resized down
				if( (int) $metadata['width'] > (int) $original_image_optimized['width'] ) {
					$metadata['width'] = $original_image_optimized['width'];
				}
				if( (int) $metadata['height'] > (int) $original_image_optimized['height'] ) {
					$metadata['height'] = $original_image_optimized['height'];
				}
			}
		}

		/*********************** End original image optimization process */

		// Get the size metadata
		$image_sizes = $metadata['sizes'];

		// Get theme image sizes info
		$image_sizes_data = HDEV_OPTIMG_Helper::get_image_sizes();

		// Use original backed up image if doing scaling ajax action
		if( ! empty( $original_copy_file ) && isset( $_POST['do'] ) && $_POST['do'] == 'scale' ) {

			// Get rid of Imagick object in memory first
			if( isset( $original_imagick ) && $original_imagick->clear() !== true ) $original_imagick->destroy();

			// Get new imagick object from original backed up file
			$original_imagick = new Imagick();
			$original_imagick->readImage( $file_dir . basename( $original_copy_file ) );
		}

		/** Start intermediate images optimization process **************************/

		if( ! $is_animated ) { // optimize only if image is not animated
			foreach( $image_sizes as $theme_image_size => $theme_image_size_data ) {

				// If sub image does not exist, log error and do nothing
				if( empty( $theme_image_size_data ) ) { // TODO: do we need to optimize this? because it can be not empty but corrupted

					error_log( 'Notice: (Plugin ' . HDEV_OPTIMG_PLUGIN_NAME . ') Unable to optimize the"' . $theme_image_size . '"" image because the metadata is corrupted.', 0 );

					continue;
				}

				// Get the sub image path
				$sub_image_name = basename( $theme_image_size_data['file'] );
				$sub_image_path = dirname( get_attached_file( $attachment_ID, true ) ) . '/' . $sub_image_name;

				// Get Imagick object
				if( $attachment_mime_type == 'image/jpeg' ) {

					// Clone original Imagick object from original (more efficient than creating new object every time)
					$imagick = clone $original_imagick;
				} else {

					// Create new Imagick object from image resized by WP so we can apply only blur/sharpen optimization (not used for now because only jpeg optimization is active as png & gif optimization is not efficient with Imagick - may use the GD Library instead to deal with that)
					$imagick = new Imagick();
					$imagick->readImage( $file_dir . $metadata['sizes'][$theme_image_size]['file'] );
				}

				$sub_image_data = array(
					'file_path' => $sub_image_path,
					'mime_type' => $attachment_mime_type,
					'width' => intval( $metadata['sizes'][$theme_image_size]['width'] ),
					'height' => $image_sizes_data[$theme_image_size]['crop'] ? intval( $metadata['sizes'][$theme_image_size]['height'] ) : 0,
					'crop' => $image_sizes_data[$theme_image_size]['crop'],
					'orientation' => $metadata['image_meta']['orientation']
				);

				// Optimize and resize sub image
				$image_optimized = self::wp_imagick_optimize_image( $imagick, $sub_image_data, $optimization_params );

				// Get rid of the Imagick object in memory
				if( isset( $imagick ) && $imagick->clear() !== true ) $imagick->destroy();

				// Unsuccessful optimization validation event logging
				if( ! $image_optimized['optimized'] ) {

					// Log error
					error_log( 'Warning: (Plugin ' . HDEV_OPTIMG_PLUGIN_NAME . 'An Imagick error happened while optimizing and resizing the"' . $theme_image_size . '"" image. ' . $image_optimized, 0 );

					// Set the optimization status to partial if the process failed at least 1 time
					$_hdev_optimg_status = 'Partial';
				} else {

					// Log optimization details in log var
					$_hdev_optimg_log[$theme_image_size] = array(
						'optimized' => $image_optimized['optimized'],
						'remove_metadata' => $image_optimized['remove_metadata'],
						'set_sRGB' => $sRGB,
						'interlace' => $image_optimized['interlace'],
						'resized' => $image_optimized['resized'],
						'crop' => $image_optimized['crop'],
						'blur_sharpen' => $image_optimized['blur_sharpen'],
						'compression' => $image_optimized['compression']
					);

					// Increment success rate
					$_hdev_optimg_rate++;
				}
			}
		}

		/**************************** End intermediate images optimization process */

		// Set the final optimization status
		if( $_hdev_optimg_rate > 0 && empty( $_hdev_optimg_status ) ) {

			// all optimizations succeeded
			$_hdev_optimg_status = 'full';
		}

		//unlink( $new_original_file_path ); // delete image copy
		//$original_imagick->writeImage( $new_original_file_path );

		// Global optimization log data
		if( $_hdev_optimg_status == 'full' || $_hdev_optimg_status == 'partial' ) {

			$_hdev_optimg_log['remove_metadata'] = $remove_metadata;
			$_hdev_optimg_log['color_profile'] = $sRGB;
			$_hdev_optimg_log['interlace'] = $interlace;
			$_hdev_optimg_log['blur_sharpen'] = $sharpen;
			$_hdev_optimg_log['compression'] = $quality;
		}

		// Store orientation metadata
		$_hdev_optimg_log['orientation'] = $metadata['image_meta']['orientation'];

		// Store optimization data in post meta to use in reports...etc
		update_post_meta( $attachment_ID, '_hdev_optimg_status', $_hdev_optimg_status );
		update_post_meta( $attachment_ID, '_hdev_optimg_log', $_hdev_optimg_log );

		// Get rid of Imagick object in memory
		if( isset( $original_imagick ) && $original_imagick->clear() !== true ) $original_imagick->destroy();
		if( isset( $this->root_imagick_object ) && $this->root_imagick_object->clear() !== true ) $this->root_imagick_object->destroy();

		return $metadata;
	}

	/**
	 * Optimize allowed mime types - Scale (resize), sharpen, interlace (make progressive) and compress
	 *
	 * @param $imagick_object
	 * @param $image_data
	 * @param $optimization_params
	 * @return array|bool
	 */
	public function wp_imagick_optimize_image( &$imagick_object, $image_data, $optimization_params ) {

		// Do nothing if the image type is not among the allowed mime types constant or if Imagick is not available
		if( ! in_array( $image_data['mime_type'], unserialize( HDEV_OPTIMG_MIMES ) ) || ! $this->imagick_active ) {
			return array(
				'optimized' => false,
				'error_message' => 'This media file was not optimized because ' . $image_data['mime_type'] . ' optimization is not enabled.'
			);
		} // TODO: return just false???

		try {

			// Remove all metadata info and profiles
			if( $optimization_params['remove_metadata'] ) {

				$imagick_object->stripImage();

				// Keep/restore image orientation exif data if available and has been collected by WP prior to optimization
				if( ! empty( $image_data ) ) {
					$imagick_object->setImageProperty( 'exif:Orientation', $image_data['orientation'] );
				}
			}

			// Set color profile
			if( $optimization_params['set_sRGB'] ) {
				$imagick_object->setImageColorspace( imagick::COLORSPACE_SRGB );
			}

			// Interlace image (make it "progressive")
			if( ! empty( $optimization_params['interlace'] ) && $interlace_scheme = self::get_imagick_interlace_scheme( $image_data['mime_type'], $optimization_params['interlace'] ) ) {

				$imagick_object->setInterlaceScheme( $interlace_scheme );
			}

			// Init resize image condition
			$resize_image = ! empty( $image_data['width'] ) || ! empty( $image_data['height'] );

			// Resize image when necessary and only if mime type is jpeg
			if( $resize_image && $image_data['crop'] && $image_data['mime_type'] == 'image/jpeg' ) {

				// Resize/crop new image
				$imagick_object->cropThumbnailImage(
					(int) $image_data['width'],
					(int) $image_data['height']
				);
			} elseif( $resize_image && $image_data['mime_type'] == 'image/jpeg' ) {

				// resize/scale
				$imagick_object->scaleImage(
					(int) $image_data['width'],
					(int) $image_data['height']
				);
			}

			// Handle png image depth
			if( $image_data['mime_type'] == 'image/png'  ) {

				// Set png image depth to 8-bits
				$imagick_object->setOption( 'png:format', 'png8' );
			}

			// Blur/Sharpen image slightly after resizing and compressing to recover quality (only if it was resized)
			if( $resize_image && $optimization_params['sharpen'] ) {

				// Blur image slightly always before sharpening.
				$blur_params = apply_filters('hdev_optimize_set_blur_params',
					array(
						'radius' => 2.7,
						'sigma' => 0.35,
						'channel' => Imagick::CHANNEL_ALL )
				); // filter parameters
				$imagick_object->blurImage(
					$blur_params['radius'],
					$blur_params['sigma'],
					$blur_params['channel']
				);

				// Sharpen image always after blur
				$sharpen_params = apply_filters('hdev_optimize_set_sharpen_params',
					array(
						'radius' => 2.7,
						'sigma' => 0.7,
						'channel' => Imagick::CHANNEL_ALL )
				); // filter parameters
				$imagick_object->sharpenimage(
					$sharpen_params['radius'],
					$sharpen_params['sigma'],
					$sharpen_params['channel']
				);
			}

			// Handle jpeg image compression
			if( $image_data['mime_type'] == 'image/jpeg' ) {

				// Set jpeg image compression type
				if( ! empty( $optimization_params['lossless'] ) && $optimization_params['lossless'] ) {

					$imagick_object->setImageCompression( Imagick::COMPRESSION_LOSSLESSJPEG ); // TODO: Lossless seems to make no difference, but we probably don't need that if we can improve plugin to re-use the backed up original file each time the image is edited and cropped or rotated
				} else {

					$imagick_object->setImageCompression( Imagick::COMPRESSION_JPEG );
				}

				// Set image compression quality
				$imagick_object->setImageCompressionQuality ( $optimization_params['quality'] ); // Originally set to 82 but 77 seems a good compromise and 65 will suite most people - 74 will match about the WP default rendered file size if sharpening is turned on
			}

			// Handle png image compression
			if( $image_data['mime_type'] == 'image/png'  ) {

				// Set gif image compression type
				$imagick_object->setImageCompression( Imagick::COMPRESSION_UNDEFINED );

				// Set image compression quality
				$imagick_object->setImageCompressionQuality ( 0 );

				$imagick_object->setOption( 'png:compression-level', 9 );
				$imagick_object->setOption( 'png:compression-strategy', 3 );
			}

			// Handle png & gif image compression
			if( $image_data['mime_type'] == 'image/gif' ) {

				// Set gif image compression type
				$imagick_object->setImageCompression( Imagick::COMPRESSION_UNDEFINED );

				// Set image compression quality
				$imagick_object->setImageCompressionQuality ( 0 );
			}

			unlink( $image_data['file_path'] ); // delete image resized by WP
			$imagick_object->writeImage( $image_data['file_path'] ); // write to file

		}
		catch( Exception $e ) {

			// Error handling
			return array(
				'optimized' => false,
				'error_message' => $e->getMessage()
			);
		}

		// Return optimization report
		return array(
			'optimized' => true,
			'remove_metadata' => $optimization_params['remove_metadata'],
			'set_sRGB' => $optimization_params['set_sRGB'],
			'interlace' => $optimization_params['interlace'],
			'resized' => $resize_image,
			'crop' => $resize_image && $image_data['crop'],
			'blur_sharpen' => $resize_image && $optimization_params['sharpen'],
			'compression' => $optimization_params['quality'],
			'width' => $imagick_object->getImageWidth(),
			'height' => $imagick_object->getImageHeight()
		);
	}

	/**
	 * Cleanup residual data and files
	 *
	 * @param $attachment_ID
	 */
	public function cleanup_deleted_image( $attachment_ID ) {

		// Get the backup image path if any
		$original_copy_file = get_post_meta( $attachment_ID, '_hdev_optimg_original_bak', true );

		// handle backup image cleanup
		if( ! empty( $original_copy_file ) ) {

			// Get the file directory
			$file_dir = dirname( get_attached_file( $attachment_ID ) ) . '/';

			// Get the original image path
			$original_file_path = $file_dir . basename( $original_copy_file );

			// delete backup image
			if( file_exists( $original_file_path ) ) {
				unlink( $original_file_path );
			}
		}
	}

	/**
	 * Gets the correct Imagick interlace constant
	 *
	 * @param $mime_type
	 * @param bool $interlace
	 *
	 * @return bool|int
	 */
	public function get_imagick_interlace_scheme( $mime_type, $interlace = true ) {

		// Deactivate interlacing if that's what the user wishes
		if( ! $interlace ) return Imagick::INTERLACE_NO;

		// Return the interlace scheme depending on mime type
		switch( $mime_type ) :

			case 'image/jpeg' :
				return Imagick::INTERLACE_JPEG;
			case 'image/png' :
				return Imagick::INTERLACE_PNG;
			case 'image/gif' :
				return Imagick::INTERLACE_GIF; // TODO: Make sure INTERLACE_GIF exists... Imagick 6.3.4 or higher
			default:
				return false;

		endswitch;
	}

	/**
	 * Detects image transparency
	 *
	 * @param $imagick_object
	 * @param $mime_type
	 *
	 * @return bool
	 */
	public function has_transparency( $imagick_object, $mime_type ) {

		// Detect transparency only for png images | else always false
		if( $mime_type != 'image/png' ) return false;

		// Get image attributes
		$image_attributes = $imagick_object->identifyImage( true );

		if( strpos( strtolower( $image_attributes['rawOutput'] ), 'rgba' ) === false ) return false;

		return ! preg_match( '/Alpha:\s+min:\s+(.*?)\s+\(1\)\s+max:/i', $image_attributes['rawOutput'] );
	}

	/**
	 * Gets a single optimization option setting
	 *
	 * @param $preset_settings
	 * @param $setting
	 *
	 * @return mixed
	 */
	public function get_optimization_setting( $preset_settings, $setting ) {

		return ! empty( $preset_settings ) && ! in_array( $setting, $this->mode_independent_settings )
			? $preset_settings[$setting]
			: HDEV_OPTIMG_Helper::get_single_option(
				$this->optimization_settings,
				$this->optimization_default_settings[$setting],
				$setting
			);
	}

	/**
	 * Gets the current optimization presets if any
	 *
	 * @param $quality
	 * @return null
	 */
	public function get_optimization_quality( $quality ) {

		// Populate our quality value
		switch( $quality ) :
			case 'custom' :
				return apply_filters( 'hdev_optimg_set_quality',
					(int) HDEV_OPTIMG_Helper::get_single_option(
						'hdev_optimg',
						HDEV_OPTIMG_Helper::get_optimization_defaults()['quality_val'],
						'quality_val' )
				);
			case 'wp-default' :
				return isset( $GLOBALS['optimg_wp_filtered_quality'] ) ?
					$GLOBALS['optimg_wp_filtered_quality'] :
					apply_filters( 'wp_editor_set_quality', apply_filters( 'hdev_optimg_set_quality',
						(int) HDEV_OPTIMG_Helper::get_optimization_quality_presets( $quality )
					));
			default :
				return apply_filters( 'hdev_optimg_set_quality',
					(int) HDEV_OPTIMG_Helper::get_optimization_quality_presets( $quality )
				);
		endswitch;
	}

	/**
	 * Get the size parameters and adjusting them to take into consideration the photo ratio...etc. for the resizing and scaling down.
	 *
	 * @param $image_ratio
	 * @param $image_dimension
	 *
	 * @return array
	 */
	public function get_adjusted_size_params( $image_ratio, $image_dimension ) {

		$size_params = array( 0, 0 );

		// Make sure the original image size <= max retina size
		if( $image_ratio <= 1 ) {

			$size_params[0] = 0;

			// Constrain to default max height
			if( $image_dimension[1] > HDEV_OPTIMG_MAX_RETINA_HEIGHT ) {
				$size_params[1] = HDEV_OPTIMG_MAX_RETINA_HEIGHT;
			}
		} else {

			$size_params[1] = 0;

			// Constrain to default max width
			if( $image_dimension[0] > HDEV_OPTIMG_MAX_RETINA_WIDTH ) {
				$size_params[0] = HDEV_OPTIMG_MAX_RETINA_WIDTH;
			}
		}

		return $size_params;
	}

	/**
	 * Return the correct error message depending on what type of photo is being uploaded by editors (admin can bypass some of these by uploading directly from the media library but Piklist field validation will catch up when trying to assign these images to file field types)
	 *
	 * // Optimize images using Imagick if active
	 *
	 * @param $file
	 * @return mixed
	 */
	public function convert_image_to_jpeg( $file ) {

		// Do nothing if the image isn't a png or if Imagick is not available or if optimization is deactivated
		if ( ! $this->imagick_optimization_active || $file['type'] != 'image/png' || ! $this->imagick_active || $file['error'] || ! $this->convert ) return $file;

		// Get image as imagick object from file
		$this->root_imagick_object = new Imagick();
		$this->root_imagick_object->readImage( $file['tmp_name'] );

		// Detect if image has transparency so png to jpeg is applied only when no transparency is detected
		$has_transparency = self::has_transparency( $this->root_imagick_object, $file['type'] );

		// Init conversion check var
		$was_converted_to_jpeg = false;

		// Convert png images to jpeg only if not transparent
		if( ! $has_transparency ) {

			$jpeg_image = imagecreatefrompng( $file['tmp_name'] );
			$jpeg_image_bg = imagecreatetruecolor( imagesx( $jpeg_image ), imagesy( $jpeg_image ) );

			imagefill( $jpeg_image_bg, 0, 0, imagecolorallocate( $jpeg_image_bg, 255, 255, 255 ) );
			imagealphablending( $jpeg_image_bg, true );

			// Proceed with conversion
			imagecopy( $jpeg_image_bg, $jpeg_image, 0, 0, 0, 0, imagesx( $jpeg_image ), imagesy( $jpeg_image ) );

			// Clear memory from png GD object
			imagedestroy( $jpeg_image );

			// create jpeg image and write it to file
			$was_converted_to_jpeg = imagejpeg( $jpeg_image_bg, $file['tmp_name'], 100 );

			// Clear memory from jpeg GD object
			imagedestroy( $jpeg_image_bg );
		}

		// Update converted file info
		if( $was_converted_to_jpeg ) {

			// Update file name extension
			$file['name'] = pathinfo( $file['name'] )['filename'] . '.jpg';

			// Update file type
			$file['type'] = 'image/jpeg';

			// Update file size
			$file['size'] = (int) filesize( $file['tmp_name'] );

			// Get rid of the root Imagick object in memory
			if( isset( $this->root_imagick_object ) && $this->root_imagick_object->clear() !== true ) $this->root_imagick_object->destroy();
			$this->root_imagick_object = null;
		}

		return $file;
	}

	// End the class.
}

// Instantiate our class.
$HDEV_OPTIMG_Optimize = new HDEV_OPTIMG_Optimize();
$HDEV_OPTIMG_Optimize->init();
