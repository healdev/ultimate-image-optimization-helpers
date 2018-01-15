=== Ultimate Image Optimization Helpers ===
Contributors: healdev
Tags: image, optimize, compress, performance, sharpen, jpeg, optimise, Imagick, ImageMagick, lossless, sharpen, sharpening, tool, helper
Donate link: https://healdev.com/donate
Requires at least: 3.8
Tested up to: 4.9.1
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize your images the right way—maintain the quality of your resized images.

== Description ==
The most integrated, flexible and efficient solution available to reduce image file sizes and improve SEO & performance while preserving/improving image quality.

*Use this plugin if you're tired of loosing image quality every time WordPress generates alternative image sizes and disappointed in all these image optimization plugins that do a great job at compressing but nothing about the quality loss...*

**REQUIRES -> PHP 5.6 OR GREATER + Imagick 2.2.0 OR GREATER**

= Features =

*Dashboard -> Settings -> Media*

* Adjust the image compression rate.
* Apply a light blur/sharpening filter to compensate for the quality loss occurring when WordPress resizes images.
* Set the image interlace scheme (progressive|interlaced|preserve original)
* Optimize of the original images.
* Remove the meta/exif data to reduce image file size (orientation exif data is preserved)
* List of all image sizes added by the current theme and plugins.
* Convert non-transparent PNG images to JPEG.

*Dashboard -> Media*

* Adds a new "Optimization" column to the media upload page
* Provides information about the image sizes and optimization applied

*LET ME KNOW YOUR IMPROVEMENT SUGGESTIONS - TOGETHER WE CAN MAKE THIS PLUGIN BETTER!*

= Developers =
You'll love this plugin for its advanced configuration options, hooks, seamless integration, performance and smart blur & sharpen feature!

== Installation ==
1. Upload \"test-plugin.php\" to the \"/wp-content/plugins/\" directory.
2. Activate the plugin through the \"Plugins\" menu in WordPress.
3. Configure your desired settings via the Dashboard -> Settings -> Media page.
4. Start uploading/optimizing new images!

== Frequently Asked Questions ==
= How to apply new optimization settings to previously uploaded images? =
To apply any new optimizations to previously uploaded images, you can regenerate thumbnails  using this plugin: <a href="https://wordpress.org/plugins/regenerate-thumbnails">Regenerate Thumbnails</a> by Alex Mills

== Screenshots ==
1. Dashboard -> Settings -> Media.
2. Dashboard -> Settings -> Media -> Mode Custom.
3. Dashboard -> Media -> Optimization info column.

== Changelog ==
= 0.2.10 =
* Fix: Setting the quality to "WP Default" not working and deactivating completely compression.

= 0.2.9 =
* Added feature: constrain original image dimension so the full size is never bigger than Max Retina resolution x2: Max Width = 5012 & Max Height = 2880.

= 0.2.8 =
* Fix: PHP notice.

= 0.2.7 =
* Updated README file.

= 0.2.6 =
* Fix: removed unsafe & unnecessary testing code.

= 0.2.5 =
* Changed plugin slug & text-domain.

= 0.2.3 =
* Fix: filter hdev_optimg_set_conversion wrongly set to hdev_optimg_set_mode in class HDEV_OPTIMG_Optimize.

= 0.2.2 =
* Added png to jpeg conversion feature
* Admin interface tweaks.
* Fix: unable to set the custom compression rate (field was disabled.)
* Minor fixes and adjustments.

= 0.1.3 =
* Code modularity improvements: added methods get_optimization_quality, get_optimization_setting & get_adjusted_size_params to class HDEV_OPTIMG_Optimize.
* Fix: jpeg quality was too low when image was cropped, scaled or rotated from the edit image screen.

= 0.1.1 =
* Minor fix: wp_editor_set_quality filter set to 100 to disable WP compression added only if optimization is active.

= 0.1.0 =
* Initial release.

== Upgrade Notice ==
= 0.2.5 =
This version sets a new plugin slug and text-domain. Please upgrade immediately.

= 0.2.2 =
This version fixes several issues and brings a new feature. Upgrade immediately.