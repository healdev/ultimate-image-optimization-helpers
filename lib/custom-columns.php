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
class HDEV_OPTIMG_Custom_Columns
{

    /**
     * Load our hooks and filters.
     *
     * @return void
     */
    public function init() {

        // Column creation
        add_filter( 'manage_media_columns',           array( $this, 'load_media_optimization_columns'          )           );
        add_filter( 'manage_upload_sortable_columns', array( $this, 'column_register_sortable'                 )           );

        // Column sorting and content display
        add_action( 'pre_get_posts',                  array( $this, 'sort_columns'                             )           );
        add_action( 'manage_media_custom_column',     array( $this, 'load_media_optimization_columns_content'  ), 10, 2    );
        add_filter( 'get_post_metadata',              array( $this, 'filter_image_postmeta'                    ), 10, 4    );

        // Debugging
        add_action( 'hdev_optimg_media_optimization_column_after_content', array( $this, 'display_file_info'   )           );
    }

    /**
     * Add our media optimization custom columns
     *
     * @param $columns
     * @return mixed
     */
    public function load_media_optimization_columns( $columns ) {

        $media_optimization_column_title = apply_filters( 'hdev_optimg_media_optimization_column_title', 'Optimization' );

        $columns['media-optimization'] = __( $media_optimization_column_title, 'ultimate-image-optimization-helpers' );

        return $columns;
    }

    /**
     * Register column as sortable
     *
     * @param $columns
     * @return mixed
     */
    public function column_register_sortable( $columns ) {
        $columns['media-optimization'] = 'media-optimization';

        return $columns;
    }

    /**
     * Display our media optimization custom column content
     *
     * @param $column_name
     * @param $post_id
     */
    public function load_media_optimization_columns_content( $column_name, $post_id ) {

        // Process the media optimization column
        if ( $column_name === 'media-optimization' ) {

            // Allow developers to echo stuff before the content
            do_action( 'hdev_optimg_media_optimization_column_before_content', $post_id );

            // Get the content capitalized
            $content = get_post_meta( $post_id, '_hdev_optimg_status', true );

            // Style the content conditionally
            if( $content == 'full'  ) {
                $content = '<div class="hdev-optimg-dashicons dashicons dashicons-yes"></div>' . 'Fully optimized';
            } elseif( $content == 'partial' ) {
                $content = '<div class="hdev-optimg-dashicons dashicons dashicons-flag"></div>' . 'Partially optimized';
            } else {
                $content = '<div class="hdev-optimg-dashicons dashicons dashicons-no-alt"></div>' . 'Not optimized';
            }

            $back = '<div class="hdev-optimg-dashicons dashicons dashicons-minus" aria-hidden="true"></div>';

            // Allow developers to customize the content array
            $content = apply_filters( 'hdev_optimg_media_optimization_column_content', $content, $post_id );

            echo $content;

            // Allow developers to echo stuff after the content
            do_action( 'hdev_optimg_media_optimization_column_after_content', $post_id );
        };

        return;
    }

    /**
     * Enable sorting of the query by meta_value
     *
     * @param $query
     */
    public function sort_columns( $query ) {

        // Run only in admin AND in the main WP query AND if an orderby query variable is designated
        if( is_admin() && $query->is_main_query() && ( $orderby = $query->get( 'orderby' ) ) ) {

            if( $orderby == 'media-optimization' ) {

                // Order the query by meta value
                $query->set( 'meta_query', array(
                    'relation' => 'OR',
                    'media-optimization' => array(
                        'key' => '_hdev_optimg_log',
                        'compare' => 'EXISTS'
                    ),
                    array(
                        'key' => '_hdev_optimg_log',
                        'compare' => 'NOT EXISTS'
                    )
                ) );
            }
        }

        return;
    }

    /**
     * Make sure the _hdev_optimg_status postmeta will adapt and the image will not still show as fully optimized after the user changes the optimization settings option
     *
     * @param $null
     * @param $object_id
     * @param $meta_key
     * @param $single
     * @return null
     */
    public function filter_image_postmeta( $null, $object_id, $meta_key, $single ) {

        if ( '_hdev_optimg_status' == $meta_key ) {

            // Unhook to avoid infinite loop
            remove_filter( 'get_post_metadata',          array( $this, 'filter_image_postmeta'        ), 10    );

            // Check whether or not the optimization settings match the actual image optimization state
            $optimization_settings_match = HDEV_OPTIMG_Helper::get_optimization_settings_match( $object_id );

            $_hdev_optimg_status = get_post_meta( $object_id, '_hdev_optimg_status', true );

            // Hook back
            add_filter(         'get_post_metadata',          array( $this, 'filter_image_postmeta'        ), 10, 4    );

            if( $_hdev_optimg_status == 'full' && ! $optimization_settings_match ) {
                return 'partial';
            } elseif( empty( $_hdev_optimg_status ) ) {
                return 'none';
            }
        }

        return null;
    }

    /**
     * Display file info in debug mode
     *
     * @param $post_id
     */
    public function display_file_info( $post_id ) {

        // Get the file mime type
        $file_mime_type = get_post_mime_type( $post_id );

        // Do nothing if file is not a JPEG
        //if( ! in_array( $file_mime_type, unserialize( HDEV_OPTIMG_MIMES ) ) && $file_mime_type != '' ) return;

        // Get file metadata
        $metadata = wp_get_attachment_metadata( $post_id );

        if( empty( $metadata ) && ! is_array( $metadata ) ) $metadata = array( $metadata );

        // Get the file directory
        $file_dir = dirname( get_attached_file( $post_id ) ) . '/';

        // Get the original image path
        $original_file_path = $file_dir . basename( array_key_exists( 'file', $metadata ) && ! empty( $metadata['file'] ) ? $metadata['file'] : get_post_meta( $post_id, 'wp_attached_file', true ) );

        // Get the the image optimization data
        $_hdev_optimg_status = get_post_meta( $post_id, '_hdev_optimg_status', true );
        $_hdev_optimg_log = get_post_meta( $post_id, '_hdev_optimg_log', false );

        $original_optimization_status = ! empty( $_hdev_optimg_status ) && ! empty( $_hdev_optimg_log ) && array_key_exists( 'full', $_hdev_optimg_log[0] ) ? 'optimized' : '';

        // Init output
        $file_info_output =  '';

        // Init previous info var to ensure we can handle order the way we want
        $previous_file_info =  '';

	    // Init theme/plugins file info var
	    $other_file_info =  '&#xa;~~~~';

	    // Get all image size registered names
	    $default_image_sizes = HDEV_OPTIMG_Helper::get_image_sizes( 'default', true );

        // Get WP time zone setting and et default time zone to current user setting or fall back and use default
        // For debug use only
        $wp_time_zone = get_option('timezone_string');
        date_default_timezone_set(! empty( $wp_time_zone ) ? $wp_time_zone : 'UTC');

        if( array_key_exists( 'sizes', $metadata ) ) {
	        // Process intermediate images
	        foreach( $metadata['sizes'] as $image_size => $image_size_data ) {

		        // Get the intermediate image path
		        $file_path = $file_dir . basename( $image_size_data['file'] );

		        // Get current image optimization status
		        $optimization_status = ! empty( $_hdev_optimg_status ) && ! empty( $_hdev_optimg_log ) && array_key_exists( $image_size, $_hdev_optimg_log[0] ) ? '&#8212;Optimized' : '';

		        // Build output parts
		        if( ! in_array( $image_size, $default_image_sizes ) ) {

			        // Build additional images output
			        $other_file_info .= '&#xa;&#8226;&nbsp;' . $image_size . '&#8212;(' . ( array_key_exists( 'width', $image_size_data ) ? $image_size_data['width'] : '' ) . 'x' . ( array_key_exists( 'height', $image_size_data ) ? $image_size_data['height'] : '' ) . ')&#8212;' . ( ! file_exists( $file_path ) ? 'NONEXISTENT FILE!' : round( filesize( $file_path )/1000, 1 ) ) . '&nbsp;KB' . $optimization_status . ( HDEV_OPTIMG_DEBUG && file_exists( $file_path ) ? ' | ' . date("y-m-d H:i:s", filemtime( $file_path ) ) : '' );
		        } else {

			        // Add previous info to output to be able to add thumbnail info last for perfect descending order
			        $file_info_output = $previous_file_info . $file_info_output;

			        // Save file info as previous info for sorting
			        $previous_file_info = '&#xa;&#8226;&nbsp;' . $image_size . '&#8212;(' . ( array_key_exists( 'width', $image_size_data ) ? $image_size_data['width'] : '' ) . 'x' . ( array_key_exists( 'height', $image_size_data ) ? $image_size_data['height'] : '' ) . ')&#8212;' . ( ! file_exists( $file_path ) ? 'NONEXISTENT FILE!' : round( filesize( $file_path )/1000, 1 ) ) . '&nbsp;KB' . $optimization_status . ( HDEV_OPTIMG_DEBUG && file_exists( $file_path ) ? ' | ' . date("y-m-d H:i:s", filemtime( $file_path ) ) : '' );
		        }
	        }
        }

        // Build final output adding data from original file first
        $file_info_output = '&#8226;&nbsp;Full' . '&#8212;(' . ( array_key_exists( 'width', $metadata ) ? $metadata['width'] : '' ) . 'x' . ( array_key_exists( 'height', $metadata ) ? $metadata['height'] : '' ) . ')&#8212;' . ( ! file_exists( $original_file_path ) ? 'NONEXISTENT FILE!' : round( filesize( $original_file_path )/1000, 1 ) ) . '&nbsp;KB&#8212;' . ucwords( $original_optimization_status ) . ( HDEV_OPTIMG_DEBUG && file_exists( $original_file_path ) ? ' | ' . date("y-m-d H:i:s", filemtime( $original_file_path ) ) : '' ) . $previous_file_info . $file_info_output . $other_file_info;

        // Put together the optimization details
        if( ! empty( $_hdev_optimg_log ) ) {

            $optimization_details  = '&#xa;&#8212;&#8212;&#8212;&#8212;&#xa;Optimizations';
                $optimization_details .= '&#xa;&nbsp;- Original: ' . ( empty( $original_optimization_status ) ? 'not optimized' : $original_optimization_status );
            $optimization_details .= '&#xa;&nbsp;- Compression: ' . ( array_key_exists( 'compression', $_hdev_optimg_log[0] ) ? $_hdev_optimg_log[0]['compression'] : 'unknown' );
            $optimization_details .= '&#xa;&nbsp;- Interlace Scheme: ' . ( array_key_exists( 'interlace', $_hdev_optimg_log[0] ) && $_hdev_optimg_log[0]['interlace'] ? 'progressive' : ( array_key_exists( 'interlace', $_hdev_optimg_log[0] ) && $_hdev_optimg_log[0]['interlace'] === false ? 'non-progressive (deinterlaced)' : 'original preserved') );
            $optimization_details .= '&#xa;&nbsp;- Enhanced Quality: ' . ( array_key_exists( 'blur_sharpen', $_hdev_optimg_log[0] ) && $_hdev_optimg_log[0]['blur_sharpen'] ? 'yes' : 'no' );
            $optimization_details .= '&#xa;&nbsp;- Metadata/EXIF: ' . (  array_key_exists( 'remove_metadata', $_hdev_optimg_log[0] ) && $_hdev_optimg_log[0]['remove_metadata'] ? 'removed' : 'preserved original' );
            $optimization_details .= '&#xa;&nbsp;- Color Profile: ' . ( array_key_exists( 'color_profile', $_hdev_optimg_log[0] ) && $_hdev_optimg_log[0]['color_profile'] ? 'sRGB' : 'unknown' );
	        $optimization_details .= '&#xa;&nbsp;- Orientation: ' . ( array_key_exists( 'orientation', $_hdev_optimg_log[0] ) && ! empty( $_hdev_optimg_log[0]['orientation'] ) ? $_hdev_optimg_log[0]['orientation'] : 'default' );
        } else {
            $optimization_details = '';
        }

        // Put together the file info
        $file_info_output = '<br><br><span>&nbsp;image info </span><div class="hdev-tooltip hdev-tooltip-no-margin' . ( defined( 'HDEV_OPTIMG_DEBUG' ) ? ' hdev-debug' : '' ) . ' dashicons dashicons-editor-help" data-hdev-tooltip="' . esc_html( $file_info_output . $optimization_details ) . '"><span class="icon-help"></span></div>';

        echo $file_info_output;

    }

    // End the class.
}

// Instantiate our class.
$HDEV_OPTIMG_Custom_Columns = new HDEV_OPTIMG_Custom_Columns();
$HDEV_OPTIMG_Custom_Columns->init();
