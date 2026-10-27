/**
 * Tanszékek → Hotspotok elrendezése (Admin\HotspotEditorPage).
 *
 * One room's lesson hotspots, three synced views of the same positions:
 *   - the 360° Pannellum view (as learners see it) -- drag a numbered square;
 *   - the flat equirectangular overview of the whole panorama -- drag too;
 *   - the list -- yaw/pitch number fields (these are what the form posts),
 *     a click turns the 360° view to that lesson.
 * The selected hotspot also moves with the arrow keys (Shift = 5°).
 *
 * Pannellum 2.5 has no draggable hotspots: while dragging, the hotspot's
 * live config object is updated from viewer.mouseEventToCoords() and the
 * view is re-rendered.
 */
( function () {
	const form = document.querySelector( '[data-va-hse-form]' );
	const dataEl = document.querySelector( '[data-va-hse-data]' );

	if ( ! form || ! dataEl || ! window.pannellum ) {
		return;
	}

	const data = JSON.parse( dataEl.textContent );
	const lessons = data.lessons;
	const saved = new Map( lessons.map( ( l ) => [ l.id, { yaw: l.yaw, pitch: l.pitch } ] ) );
	const pos = new Map( lessons.map( ( l ) => [ l.id, { yaw: l.yaw, pitch: l.pitch } ] ) );

	const listEl = form.querySelector( '[data-va-hse-list]' );
	const flatEl = form.querySelector( '[data-va-hse-flat]' );
	const dirtyEl = form.querySelector( '[data-va-hse-dirty]' );
	const resetBtn = form.querySelector( '[data-va-hse-reset]' );

	let active = lessons[ 0 ]?.id ?? null;
	let submitting = false;

	const round = ( n ) => Math.round( n * 10 ) / 10;
	const wrapYaw = ( yaw ) => ( ( ( ( yaw + 180 ) % 360 ) + 360 ) % 360 ) - 180;
	const clampPitch = ( pitch ) => Math.max( -90, Math.min( 90, pitch ) );

	// --- List -----------------------------------------------------------------

	listEl.innerHTML = lessons
		.map(
			( l ) => `
			<li class="va-hse__item" data-id="${ l.id }">
				<button type="button" class="va-hse__pick" data-look="${ l.id }">
					<span class="va-hse__num">${ l.number }</span>
					<span class="va-hse__title"></span>
				</button>
				<span class="va-hse__coords">
					<label>Yaw <input type="number" step="0.1" min="-180" max="180" name="hotspot[${ l.id }][yaw]" data-axis="yaw" /></label>
					<label>Pitch <input type="number" step="0.1" min="-90" max="90" name="hotspot[${ l.id }][pitch]" data-axis="pitch" /></label>
					<a href="${ l.editUrl }" class="va-hse__edit" title="Lecke szerkesztése" aria-label="Lecke szerkesztése">✎</a>
				</span>
			</li>`
		)
		.join( '' );

	lessons.forEach( ( l ) => {
		listEl.querySelector( `[data-id="${ l.id }"] .va-hse__title` ).textContent = l.title;
	} );

	// --- Flat overview --------------------------------------------------------

	flatEl.innerHTML = `<div class="va-hse__flat-wrap"><img src="${ data.preview || data.panorama }" alt="" draggable="false" /></div>
		<p class="va-hse__hint">A teljes panoráma kiterítve – ide is húzhatod a hotspotokat.</p>`;
	const flatWrap = flatEl.querySelector( '.va-hse__flat-wrap' );

	const flatMarkers = new Map();

	const addFlatMarker = ( key, label, className ) => {
		const marker = document.createElement( 'span' );
		marker.className = `va-hse__flat-marker ${ className }`;
		marker.textContent = label;
		flatWrap.appendChild( marker );
		flatMarkers.set( key, marker );
		return marker;
	};

	const placeFlat = ( marker, { yaw, pitch } ) => {
		marker.style.left = `${ ( ( yaw + 180 ) / 360 ) * 100 }%`;
		marker.style.top = `${ ( ( 90 - pitch ) / 180 ) * 100 }%`;
	};

	data.doors.forEach( ( door, i ) => {
		const marker = addFlatMarker( `door-${ i }`, '🚪', 'is-door' );
		marker.title = door.label;
		placeFlat( marker, door );
	} );

	lessons.forEach( ( l ) => {
		const marker = addFlatMarker( l.id, String( l.number ), 'is-lesson' );
		marker.title = l.title;
		marker.dataset.id = l.id;
	} );

	// --- 360° view ------------------------------------------------------------

	const hotSpots = [
		...data.doors.map( ( door, i ) => ( {
			id: `door-${ i }`,
			pitch: door.pitch,
			yaw: door.yaw,
			cssClass: 'va-hse__hs va-hse__hs--door',
			createTooltipFunc: ( div ) => {
				div.innerHTML = `<span>🚪</span><em>${ door.label }</em>`;
			},
		} ) ),
		...lessons.map( ( l ) => ( {
			id: `lesson-${ l.id }`,
			pitch: l.pitch,
			yaw: l.yaw,
			cssClass: 'va-hse__hs va-hse__hs--lesson',
			createTooltipFunc: ( div ) => {
				div.dataset.id = l.id;
				div.innerHTML = `<span>${ l.number }</span><em></em>`;
				div.querySelector( 'em' ).textContent = l.title;
				bindViewerDrag( div, l.id );
			},
		} ) ),
	];

	const first = lessons[ 0 ];
	const viewer = window.pannellum.viewer( form.querySelector( '[data-va-hse-viewer]' ), {
		type: 'equirectangular',
		panorama: data.panorama,
		autoLoad: true,
		showFullscreenCtrl: true,
		yaw: first ? first.yaw : 0,
		pitch: first ? first.pitch : 0,
		hfov: 100,
		hotSpots,
	} );

	let loaded = false;
	viewer.on( 'load', () => ( loaded = true ) );
	const redraw = () => loaded && viewer.setYaw( viewer.getYaw(), false );

	function bindViewerDrag( div, id ) {
		const stop = ( event ) => event.stopPropagation();
		div.addEventListener( 'mousedown', stop );
		div.addEventListener( 'touchstart', stop, { passive: true } );

		div.addEventListener( 'pointerdown', ( event ) => {
			event.preventDefault();
			event.stopPropagation();
			select( id, false );
			div.setPointerCapture( event.pointerId );
			div.classList.add( 'is-dragging' );

			const move = ( e ) => {
				const [ pitch, yaw ] = viewer.mouseEventToCoords( e );
				set( id, { yaw, pitch } );
			};
			const end = () => {
				div.classList.remove( 'is-dragging' );
				div.removeEventListener( 'pointermove', move );
				div.removeEventListener( 'pointerup', end );
				div.removeEventListener( 'pointercancel', end );
			};

			div.addEventListener( 'pointermove', move );
			div.addEventListener( 'pointerup', end );
			div.addEventListener( 'pointercancel', end );
		} );
	}

	// Flat overview drag.
	flatWrap.addEventListener( 'pointerdown', ( event ) => {
		const marker = event.target.closest( '.va-hse__flat-marker.is-lesson' );

		if ( ! marker ) {
			return;
		}

		event.preventDefault();
		const id = Number( marker.dataset.id );
		select( id, false );
		marker.setPointerCapture( event.pointerId );
		marker.classList.add( 'is-dragging' );

		const move = ( e ) => {
			const rect = flatWrap.getBoundingClientRect();
			const x = Math.max( 0, Math.min( 1, ( e.clientX - rect.left ) / rect.width ) );
			const y = Math.max( 0, Math.min( 1, ( e.clientY - rect.top ) / rect.height ) );
			set( id, { yaw: x * 360 - 180, pitch: 90 - y * 180 } );
		};
		const end = () => {
			marker.classList.remove( 'is-dragging' );
			marker.removeEventListener( 'pointermove', move );
			marker.removeEventListener( 'pointerup', end );
			marker.removeEventListener( 'pointercancel', end );
			look( id );
		};

		marker.addEventListener( 'pointermove', move );
		marker.addEventListener( 'pointerup', end );
		marker.addEventListener( 'pointercancel', end );
	} );

	// --- State ----------------------------------------------------------------

	function set( id, { yaw, pitch }, { fromInput = false } = {} ) {
		const next = { yaw: round( wrapYaw( yaw ) ), pitch: round( clampPitch( pitch ) ) };
		pos.set( id, next );

		const hs = viewer.getConfig().hotSpots.find( ( h ) => h.id === `lesson-${ id }` );
		if ( hs ) {
			hs.yaw = next.yaw;
			hs.pitch = next.pitch;
			redraw();
		}

		placeFlat( flatMarkers.get( id ), next );

		if ( ! fromInput ) {
			const item = listEl.querySelector( `[data-id="${ id }"]` );
			item.querySelector( '[data-axis="yaw"]' ).value = next.yaw;
			item.querySelector( '[data-axis="pitch"]' ).value = next.pitch;
		}

		updateDirty();
	}

	function look( id ) {
		const p = pos.get( id );
		if ( p && loaded ) {
			viewer.lookAt( p.pitch, p.yaw, viewer.getHfov(), 600 );
		}
	}

	function select( id, turn = true ) {
		active = id;
		listEl.querySelectorAll( '.va-hse__item' ).forEach( ( li ) => li.classList.toggle( 'is-active', Number( li.dataset.id ) === id ) );
		flatMarkers.forEach( ( marker, key ) => marker.classList.toggle( 'is-active', key === id ) );
		form.querySelectorAll( '.va-hse__hs--lesson' ).forEach( ( el ) => el.classList.toggle( 'is-active', Number( el.dataset.id ) === id ) );
		if ( turn ) {
			look( id );
		}
	}

	function isDirty() {
		return lessons.some( ( l ) => {
			const a = pos.get( l.id );
			const b = saved.get( l.id );
			return Math.abs( a.yaw - b.yaw ) > 0.05 || Math.abs( a.pitch - b.pitch ) > 0.05;
		} );
	}

	function updateDirty() {
		const dirty = isDirty();
		dirtyEl.hidden = ! dirty;
		resetBtn.disabled = ! dirty;
		lessons.forEach( ( l ) => {
			const a = pos.get( l.id );
			const b = saved.get( l.id );
			listEl.querySelector( `[data-id="${ l.id }"]` ).classList.toggle( 'is-changed', Math.abs( a.yaw - b.yaw ) > 0.05 || Math.abs( a.pitch - b.pitch ) > 0.05 );
		} );
	}

	// --- Wiring ---------------------------------------------------------------

	listEl.addEventListener( 'click', ( event ) => {
		const pick = event.target.closest( '[data-look]' );
		if ( pick ) {
			select( Number( pick.dataset.look ) );
		}
	} );

	listEl.addEventListener( 'input', ( event ) => {
		const input = event.target.closest( 'input[data-axis]' );
		if ( ! input || '' === input.value || Number.isNaN( Number( input.value ) ) ) {
			return;
		}
		const id = Number( input.closest( '.va-hse__item' ).dataset.id );
		const current = pos.get( id );
		set( id, { ...current, [ input.dataset.axis ]: Number( input.value ) }, { fromInput: true } );
	} );

	listEl.addEventListener( 'change', ( event ) => {
		const input = event.target.closest( 'input[data-axis]' );
		if ( input ) {
			const id = Number( input.closest( '.va-hse__item' ).dataset.id );
			set( id, pos.get( id ) );
			look( id );
		}
	} );

	// Arrow keys nudge the selected hotspot (not while typing in a field).
	document.addEventListener( 'keydown', ( event ) => {
		if ( null === active || event.target.closest( 'input, textarea, select' ) ) {
			return;
		}
		const step = event.shiftKey ? 5 : 0.5;
		const delta = { ArrowLeft: [ -step, 0 ], ArrowRight: [ step, 0 ], ArrowUp: [ 0, step ], ArrowDown: [ 0, -step ] }[ event.key ];
		if ( ! delta || ! form.contains( document.activeElement ) && document.activeElement !== document.body ) {
			return;
		}
		event.preventDefault();
		const p = pos.get( active );
		set( active, { yaw: p.yaw + delta[ 0 ], pitch: p.pitch + delta[ 1 ] } );
	} );

	resetBtn.addEventListener( 'click', () => {
		saved.forEach( ( p, id ) => set( id, p ) );
		if ( null !== active ) {
			look( active );
		}
	} );

	form.addEventListener( 'submit', () => {
		submitting = true;
	} );

	window.addEventListener( 'beforeunload', ( event ) => {
		if ( ! submitting && isDirty() ) {
			event.preventDefault();
			event.returnValue = '';
		}
	} );

	document.querySelectorAll( '[data-va-hse-tab]' ).forEach( ( tab ) => {
		tab.addEventListener( 'click', ( event ) => {
			if ( isDirty() && ! window.confirm( 'Nem mentett változásaid vannak. Biztosan másik tanszékre lépsz?' ) ) {
				event.preventDefault();
			} else {
				submitting = true; // Already confirmed -- skip the browser's own prompt.
			}
		} );
	} );

	lessons.forEach( ( l ) => set( l.id, saved.get( l.id ) ) );
	if ( null !== active ) {
		select( active, false );
	}
}() );
