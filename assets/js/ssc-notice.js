/**
 * StaySuite Companion — persist dismissal of admin notices.
 *
 * WordPress hides .is-dismissible notices for the page load only; this
 * posts the dismissal back so it is remembered per user (user meta).
 * Vanilla JS, no dependencies.
 */
(function () {
    function init() {
        if ( ! window.sscNotice || ! window.sscNotice.ajaxurl || ! window.sscNotice.nonce ) {
            return;
        }
        document.querySelectorAll( '[data-ssc-dismissible]' ).forEach( function ( box ) {
            box.addEventListener( 'click', function ( event ) {
                var dismiss = event.target.classList && event.target.classList.contains( 'notice-dismiss' )
                    ? event.target
                    : event.target.closest && event.target.closest( '.notice-dismiss' );
                if ( ! dismiss ) {
                    return;
                }
                var body = new URLSearchParams();
                body.append( 'action', 'ssc_dismiss_notice' );
                body.append( 'nonce', window.sscNotice.nonce );
                body.append( 'key', box.getAttribute( 'data-ssc-dismissible' ) );
                fetch( window.sscNotice.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } );
            } );
        } );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
})();
