/**
 * Visual yaw/pitch picker for equirectangular panorama admin fields.
 *
 * Each `.va-hotspot-picker` container declares its image and one or more
 * yaw/pitch input pairs via a `data-points` JSON attribute (see
 * HotspotPickerField::render()). Clicking the image places whichever point
 * is currently "active" (selectable via its own marker or legend pill);
 * the underlying number inputs are updated directly, and vice versa --
 * editing a number input by hand moves its marker too.
 *
 * Equirectangular mapping: yaw -180..180 maps to the image's left..right
 * edge, pitch +90..-90 maps to the image's top..bottom edge (matching
 * Pannellum's own convention, see Viewer.js in the frontend bundle).
 */
( function () {
	function yawPitchToPercent( yaw, pitch ) {
		return {
			x: ( ( yaw + 180 ) / 360 ) * 100,
			y: ( ( 90 - pitch ) / 180 ) * 100,
		};
	}

	function percentToYawPitch( xPercent, yPercent ) {
		return {
			yaw: Math.round( ( xPercent * 3.6 - 180 ) * 10 ) / 10,
			pitch: Math.round( ( 90 - yPercent * 1.8 ) * 10 ) / 10,
		};
	}

	function initPicker( container ) {
		let points;

		try {
			points = JSON.parse( container.dataset.points || '[]' );
		} catch ( e ) {
			return;
		}

		const imageUrl = container.dataset.image;

		if ( ! imageUrl || ! points.length ) {
			return;
		}

		const wrap = document.createElement( 'div' );
		wrap.className = 'va-hotspot-picker__wrap';

		const img = document.createElement( 'img' );
		img.src = imageUrl;
		img.className = 'va-hotspot-picker__image';
		img.alt = '';
		wrap.appendChild( img );

		const legend = document.createElement( 'div' );
		legend.className = 'va-hotspot-picker__legend';

		let activeIndex = 0;
		const markers = [];
		const legendItems = [];

		function getInputs( point ) {
			return {
				yawInput: document.getElementById( point.yawField ),
				pitchInput: document.getElementById( point.pitchField ),
			};
		}

		function setActive( index ) {
			activeIndex = index;
			markers.forEach( ( marker, i ) => marker.classList.toggle( 'is-active', i === index ) );
			legendItems.forEach( ( item, i ) => item.classList.toggle( 'is-active', i === index ) );
		}

		function updateMarker( index ) {
			const point = points[ index ];
			const { yawInput, pitchInput } = getInputs( point );

			if ( ! yawInput || ! pitchInput ) {
				return;
			}

			const yaw = parseFloat( yawInput.value ) || 0;
			const pitch = parseFloat( pitchInput.value ) || 0;
			const { x, y } = yawPitchToPercent( yaw, pitch );

			markers[ index ].style.left = x + '%';
			markers[ index ].style.top = y + '%';
		}

		points.forEach( ( point, index ) => {
			const marker = document.createElement( 'button' );
			marker.type = 'button';
			marker.className = 'va-hotspot-picker__marker';
			marker.style.setProperty( '--va-marker-color', point.color || '#2271b1' );
			marker.setAttribute( 'aria-label', point.label || '' );

			const label = document.createElement( 'span' );
			label.className = 'va-hotspot-picker__marker-label';
			label.textContent = point.label || '';
			marker.appendChild( label );

			marker.addEventListener( 'click', ( event ) => {
				event.stopPropagation();
				setActive( index );
			} );

			wrap.appendChild( marker );
			markers.push( marker );

			if ( points.length > 1 ) {
				const legendItem = document.createElement( 'button' );
				legendItem.type = 'button';
				legendItem.className = 'va-hotspot-picker__legend-item';
				legendItem.style.setProperty( '--va-marker-color', point.color || '#2271b1' );

				const dot = document.createElement( 'span' );
				dot.className = 'va-hotspot-picker__legend-dot';
				legendItem.appendChild( dot );
				legendItem.appendChild( document.createTextNode( point.label || '' ) );

				legendItem.addEventListener( 'click', () => setActive( index ) );

				legend.appendChild( legendItem );
				legendItems.push( legendItem );
			}

			const { yawInput, pitchInput } = getInputs( point );
			[ yawInput, pitchInput ].forEach( ( input ) => {
				input?.addEventListener( 'input', () => updateMarker( index ) );
			} );

			updateMarker( index );
		} );

		setActive( 0 );

		wrap.addEventListener( 'click', ( event ) => {
			const rect = img.getBoundingClientRect();
			const xPercent = ( ( event.clientX - rect.left ) / rect.width ) * 100;
			const yPercent = ( ( event.clientY - rect.top ) / rect.height ) * 100;
			const { yaw, pitch } = percentToYawPitch(
				Math.min( 100, Math.max( 0, xPercent ) ),
				Math.min( 100, Math.max( 0, yPercent ) )
			);

			const point = points[ activeIndex ];
			const { yawInput, pitchInput } = getInputs( point );

			if ( ! yawInput || ! pitchInput ) {
				return;
			}

			yawInput.value = yaw;
			pitchInput.value = pitch;
			updateMarker( activeIndex );

			yawInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			pitchInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} );

		container.appendChild( wrap );

		if ( legendItems.length ) {
			container.appendChild( legend );
		}

		const hint = document.createElement( 'p' );
		hint.className = 'va-hotspot-picker__hint';
		hint.textContent = points.length > 1
			? 'Válaszd ki lent, melyik pontot mozgatod, majd kattints a képre az elhelyezéshez.'
			: 'Kattints a képre a pont elhelyezéséhez.';
		container.appendChild( hint );
	}

	function init() {
		document.querySelectorAll( '.va-hotspot-picker' ).forEach( initPicker );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
