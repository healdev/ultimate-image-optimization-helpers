## The Ultimate Image Optimization Helpers for WordPress

This plugin offers the following features and optimization capabilities:


#### Dashboard -> Settings -> Media

1. Displays a list of all image sizes added by the current theme and plugins for your referance.

2. Allows to choose between a variety of easy optimization presets.

3. Allows to enable/disable png to jpeg conversion (for non-trensparent png images only.)

4. Provides a completely custom optimization interface for advanced users:
- Adjust the image compression rate.
- Enhanced quality feature (applies a light blur/sharpening filter to compensate for the quality loss occuring when WordPress generates intermediate sizes by resizing the original image to unsure the best quality is preserved).
- Set tmage interlace scheme (pregressive, interlaced or preserve original.)
- Enable/disable original image optimization (keeps a copy of the original for reference or future processing.)
- Remove the meta/exif data to reduce image file size (orientation exif data is preserved)

#### Dashboard -> Media

1. Displays a new "Optimization" status column on the media upload page.
2. Provides a popover rich info box next to the optimization status for each media.

### HOOKS
The plugin provides the following actions & filters. 

```php
add_filter( 'hdev_optimg_set_conversion', 'yourprefix_filter_mode' );
if( ! function_exists( 'yourprefix_filter_mode' ) ) {
  function yourprefix_filter_mode( $convert ) {
    
    if( YOUR_CONDITION ) {
      return true;
    }
    return $convert;
  }
}
```
