/**
 * StaySuite Companion — term featured-photo picker.
 *
 * Opens the media frame from the Pick button, stores the attachment id in
 * the hidden field and refreshes the preview; Clear empties both. Scoped
 * per .ssc-term-image-field so add and edit screens behave the same.
 * Title copy arrives via wp_localize_script (sscTermImage.title).
 */
(function () {
    var frame = null;

    function previewUrl( picked ) {
        var attrs = picked.attributes || {};
        if ( attrs.sizes && attrs.sizes.thumbnail && attrs.sizes.thumbnail.url ) {
            return attrs.sizes.thumbnail.url;
        }
        return picked.get( 'url' );
    }

    function renderPreview( root, url ) {
        var box = root.querySelector( '.ssc-term-image-preview' );
        if ( ! box ) {
            return;
        }
        box.innerHTML = '';
        if ( ! url ) {
            return;
        }
        var img = document.createElement( 'img' );
        img.src = url;
        img.alt = '';
        img.style.maxWidth = '200px';
        img.style.height = 'auto';
        img.style.borderRadius = '8px';
        box.appendChild( img );
    }

    function initField( root ) {
        var field = root.querySelector( 'input[name="ssc_term_image_id"]' );
        var pick = root.querySelector( '.ssc-term-image-pick' );
        var clear = root.querySelector( '.ssc-term-image-clear' );
        if ( ! field || ! pick || ! clear ) {
            return;
        }
        pick.addEventListener( 'click', function ( e ) {
            e.preventDefault();
            if ( frame ) {
                frame.open();
                return;
            }
            frame = wp.media( {
                title: window.sscTermImage && window.sscTermImage.title ? window.sscTermImage.title : '',
                multiple: false,
                library: { type: 'image' },
            } );
            frame.on( 'select', function () {
                var picked = frame.state().get( 'selection' ).first();
                if ( ! picked ) {
                    return;
                }
                field.value = picked.id;
                renderPreview( root, previewUrl( picked ) );
            } );
            frame.open();
        } );
        clear.addEventListener( 'click', function ( e ) {
            e.preventDefault();
            field.value = 0;
            renderPreview( root, '' );
        } );
    }

    function init() {
        if ( typeof wp === 'undefined' || ! wp.media ) {
            return;
        }
        document.querySelectorAll( '.ssc-term-image-field' ).forEach( initField );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
})();
