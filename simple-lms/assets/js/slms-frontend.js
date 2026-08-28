/**
 * Simple LMS - front-end interactions (vanilla JS, no dependencies).
 *
 * Talks to the REST API (slms/v1) with an admin-ajax fallback.
 */
( function () {
	'use strict';

	var cfg = window.SLMS || {};
	var i18n = cfg.i18n || {};

	/**
	 * POST helper. Tries REST first, falls back to admin-ajax.
	 *
	 * @param {string} restPath   Path appended to cfg.restUrl.
	 * @param {string} ajaxAction admin-ajax action name.
	 * @param {Object} data       Payload.
	 * @return {Promise<Object>}
	 */
	function post( restPath, ajaxAction, data ) {
		if ( cfg.restUrl ) {
			return fetch( cfg.restUrl + restPath, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg.restNonce || ''
				},
				body: JSON.stringify( data )
			} ).then( readJson );
		}

		var body = new URLSearchParams();
		body.set( 'action', ajaxAction );
		body.set( 'nonce', cfg.nonce || '' );
		Object.keys( data ).forEach( function ( k ) {
			body.set( k, data[ k ] );
		} );

		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( readJson ).then( function ( json ) {
			// Normalise admin-ajax {success,data} envelope.
			if ( json && typeof json.success !== 'undefined' ) {
				if ( ! json.success ) {
					throw new Error( ( json.data && json.data.message ) || i18n.error );
				}
				return json.data;
			}
			return json;
		} );
	}

	function readJson( response ) {
		return response.json().catch( function () {
			return {};
		} ).then( function ( json ) {
			if ( ! response.ok ) {
				throw new Error( ( json && json.message ) || i18n.error || 'Error' );
			}
			return json;
		} );
	}

	/**
	 * Update every progress bar on the page from a progress object.
	 *
	 * @param {Object} progress { percent, completed, total }
	 */
	function paintProgress( progress ) {
		if ( ! progress ) {
			return;
		}
		document.querySelectorAll( '[data-slms-progress]' ).forEach( function ( el ) {
			var fill = el.querySelector( '.slms-progress__fill' );
			var track = el.querySelector( '.slms-progress__track' );
			var label = el.querySelector( '.slms-progress__label' );
			if ( fill ) {
				fill.style.width = progress.percent + '%';
			}
			if ( track ) {
				track.setAttribute( 'aria-valuenow', progress.percent );
			}
			if ( label && typeof progress.completed !== 'undefined' ) {
				label.textContent = progress.completed + ' / ' + progress.total + ' (' + progress.percent + '%)';
			}
		} );
	}

	/* ----- Enroll ----------------------------------------------------------- */

	document.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest( '[data-slms-enroll]' );
		if ( ! btn ) {
			return;
		}
		event.preventDefault();

		if ( ! cfg.loggedIn ) {
			window.alert( i18n.loginFirst || 'Please log in.' );
			return;
		}

		var courseId = parseInt( btn.getAttribute( 'data-slms-enroll' ), 10 );
		var msg = btn.parentNode.querySelector( '.slms-enroll__msg' );

		btn.disabled = true;
		btn.textContent = i18n.enrolling || 'Enrolling…';

		post( '/enroll', 'slms_enroll', { course_id: courseId } )
			.then( function ( res ) {
				btn.classList.add( 'slms-enroll--done' );
				btn.textContent = i18n.continue || 'Continue';
				btn.removeAttribute( 'data-slms-enroll' );
				btn.disabled = false;
				if ( msg ) {
					msg.textContent = i18n.enrolled || 'Enrolled';
				}
				paintProgress( res && res.progress );
				window.location.reload();
			} )
			.catch( function ( err ) {
				btn.disabled = false;
				btn.textContent = i18n.enrolled ? 'Enroll' : 'Enroll';
				if ( msg ) {
					msg.textContent = err.message || i18n.error;
				}
			} );
	} );

	/* ----- Mark lesson complete ------------------------------------------- */

	document.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest( '[data-slms-complete]' );
		if ( ! btn ) {
			return;
		}
		event.preventDefault();

		var lessonId = parseInt( btn.getAttribute( 'data-slms-complete' ), 10 );
		var makeComplete = btn.getAttribute( 'aria-pressed' ) !== 'true';
		var msg = btn.parentNode.querySelector( '.slms-complete__msg' );

		btn.disabled = true;
		btn.textContent = i18n.saving || 'Saving…';

		post( '/lessons/' + lessonId + '/complete', 'slms_complete_lesson', {
			lesson_id: lessonId,
			complete: makeComplete ? 1 : 0
		} )
			.then( function ( res ) {
				btn.disabled = false;
				btn.setAttribute( 'aria-pressed', makeComplete ? 'true' : 'false' );
				btn.classList.toggle( 'is-done', makeComplete );
				btn.textContent = makeComplete
					? ( i18n.completed || 'Completed' )
					: ( i18n.markDone || 'Mark as complete' );
				if ( msg ) {
					msg.textContent = '';
				}
				paintProgress( res && res.progress );

				var listItem = document.querySelector(
					'.slms-lesson-list__item.is-current'
				);
				if ( listItem ) {
					listItem.classList.toggle( 'is-done', makeComplete );
				}
			} )
			.catch( function ( err ) {
				btn.disabled = false;
				btn.textContent = i18n.markDone || 'Mark as complete';
				if ( msg ) {
					msg.textContent = err.message || i18n.error;
				}
			} );
	} );
} )();
