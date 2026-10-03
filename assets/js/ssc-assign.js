/**
 * StaySuite Companion — Assign Hotels bulk checkbox.
 *
 * The "select all" header checkbox toggles every row checkbox on the
 * Hotels → Assign Rooms screen. Vanilla JS, no dependencies.
 */
(function () {
    function init() {
        var all = document.getElementById( 'ssc_select_all' );
        if ( ! all ) {
            return;
        }
        all.addEventListener( 'change', function () {
            var checked = all.checked;
            document.querySelectorAll( '.ssc-bulk-check' ).forEach( function ( box ) {
                box.checked = checked;
            } );
        } );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
})();
