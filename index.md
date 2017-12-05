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

For more details see [GitHub Flavored Markdown](https://guides.github.com/features/mastering-markdown/).

### Jekyll Themes

Your Pages site will use the layout and styles from the Jekyll theme you have selected in your [repository settings](https://github.com/healdev/wp-ultimate-image-optimization-helpers/settings). The name of this theme is saved in the Jekyll `_config.yml` configuration file.

### Support or Contact

Having trouble with Pages? Check out our [documentation](https://help.github.com/categories/github-pages-basics/) or [contact support](https://github.com/contact) and we’ll help you sort it out.
