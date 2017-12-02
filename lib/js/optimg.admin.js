;
/** Reusable debounse global function */
if( typeof hdevDebounce !== 'function' ) {
    function hdevDebounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            }, wait);
            if (immediate && !timeout) func.apply(context, args);
        };
    }
}

/** Very useful global function to execute a function only after all events have been executed */
if( typeof hdevExecFunctAfterEvents !== 'function' ) {
    hdevExecFunctAfterEvents = function(func) {
        window.setTimeout(func(),0);
    };
}

/*************************
 * Start the main admin engine.
 *************************/
(function ($, hdevOptImg, undefined) {

	// Init vars
    var qualityRadio = $('#hdev-optimg-quality')
        ,customQualityInput = $('#hdev-optimg-quality-custom-val')
        ,customQualityInputTrigger =$('.hdev-customQualityInput-trigger')
        ,modeRadio = $('#hdev-optimg-mode-container')
        ,modeRadioTarget = $('.hdev-optimg-mode-target')
        ,toggleTarget;

    // This is a public function
    hdevOptImg.init = function() {

        // Helper function to toggle field view
        toggleTarget = hdevDebounce(function(target, speedDown, speedUp) {

            // Set defaults
            if( typeof(speedDown) === 'undefined') speedDown = 250;
            if( typeof(speedUp) === 'undefined') speedUp = 150;

            if( target.hasClass('hdev-toggled-hide') ) {
                target.toggleClass('hdev-toggled-hide').slideDown(speedDown);
            } else {
                target.toggleClass('hdev-toggled-hide').slideUp(speedUp);
            }
        }, 17);

        if( qualityRadio.length > 0 && customQualityInputTrigger.length > 0 ) {

            qualityRadio.change( function() {
                if( ( qualityRadio.find('input[name="hdev_optimg[quality]"]:checked').val() !== 'custom' && ! customQualityInput.hasClass('hdev-toggled-hide') ) || ( qualityRadio.find('input[name="hdev_optimg[quality]"]:checked').val() === 'custom' && customQualityInput.hasClass('hdev-toggled-hide') ) ) {
                    toggleTarget(customQualityInput);
                }
            });
        }

        if( modeRadio.length > 0 && modeRadioTarget.length > 0 ) {

            modeRadio.change( function() {
                if( ( modeRadio.find('input[name="hdev_optimg[mode]"]:checked').val() !== 'advanced' && ! modeRadioTarget.hasClass('hdev-toggled-hide') ) || ( modeRadio.find('input[name="hdev_optimg[mode]"]:checked').val() === 'advanced' && modeRadioTarget.hasClass('hdev-toggled-hide') ) ) {
                    toggleTarget(modeRadioTarget, 500, 150);
                }
            });
        }

    }
}(jQuery, window.hdevOptImg = window.hdevOptImg || {}));

// Initialize my object using a wrapper for doc ready jQuery(function(){
jQuery(function($){
    hdevOptImg.init();
});

/*************************
 * End the main admin engine.
 *************************/