/**
 * Keyboard image navigation on attachment pages: left/right arrow keys
 * move to the previous/next image. Vanilla JS (theme 1.4, jQuery dropped).
 */
( function() {

	document.addEventListener( 'keydown', function( e ) {

		var link = false;

		if ( e.key === 'ArrowLeft' ) {
			link = document.querySelector( '.previous-image a' );
		} else if ( e.key === 'ArrowRight' ) {
			link = document.querySelector( '.entry-attachment a' );
		} else {
			return;
		}

		var active = document.activeElement;
		var typing = active && ( active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' );

		if ( link && link.href && ! typing ) {
			window.location = link.href;
		}

	} );

} )();
