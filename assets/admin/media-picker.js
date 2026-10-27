/**
 * Wires each `.va-media-picker` block's "Média kiválasztása" button to the
 * standard WP media library modal (wp.media, enqueued via wp_enqueue_media())
 * -- selecting an attachment fills the existing numeric ID input and, for
 * images, swaps in a live preview, rather than making an admin type or
 * remember a raw attachment ID.
 */
( function ( $ ) {
	function initPicker( $container ) {
		const type = $container.data( 'type' ) || 'image';
		const $input = $container.find( '.va-media-picker__id' );
		const $button = $container.find( '.va-media-picker__button' );
		const $preview = $container.find( '.va-media-picker__preview' );

		let frame;

		$button.on( 'click', function ( event ) {
			event.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: 'Média kiválasztása',
				library: 'image' === type ? { type: 'image' } : { type: 'audio' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				const attachment = frame.state().get( 'selection' ).first().toJSON();
				$input.val( attachment.id ).trigger( 'change' );

				if ( 'image' === type ) {
					const src = attachment.sizes?.medium?.url || attachment.url;
					$preview.html( '<img src="' + src + '" style="max-width:320px;height:auto;display:block;margin-bottom:8px;" />' );
				} else {
					$preview.html( '<p class="va-media-picker__filename">' + attachment.filename + '</p>' );
				}
			} );

			frame.open();
		} );
	}

	$( function () {
		$( '.va-media-picker' ).each( function () {
			initPicker( $( this ) );
		} );
	} );
} )( jQuery );
