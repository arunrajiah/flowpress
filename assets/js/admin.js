/* global flowpressAdmin, flowpressBuilderData, jQuery */
( function ( $ ) {
	'use strict';

	// ── Utilities ─────────────────────────────────────────────────────────────

	function escHtml( str ) {
		return $( '<div>' ).text( String( str ) ).html();
	}

	// Insert text at cursor position in an input/textarea.
	function insertAtCursor( el, text ) {
		var start = el.selectionStart;
		var end   = el.selectionEnd;
		var val   = el.value;
		el.value  = val.slice( 0, start ) + text + val.slice( end );
		el.selectionStart = el.selectionEnd = start + text.length;
		el.focus();
		el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	}

	// ── Builder state ─────────────────────────────────────────────────────────

	var state = {
		selectedTrigger: null, // trigger object from flowpressAdmin.triggers
		actions: [],           // array of { type, config: {} }
	};

	// ── Catalogue helpers ──────────────────────────────────────────────────────

	function getTriggerByType( type ) {
		return ( flowpressAdmin.triggers || [] ).find( function ( t ) { return t.type === type; } ) || null;
	}

	function getActionByType( type ) {
		return ( flowpressAdmin.actions || [] ).find( function ( a ) { return a.type === type; } ) || null;
	}

	// ── Trigger catalogue ──────────────────────────────────────────────────────

	function renderTriggerCatalogue() {
		var $list = $( '#fp-trigger-list' );
		$list.empty();

		( flowpressAdmin.triggers || [] ).forEach( function ( trigger ) {
			var $card = $(
				'<div class="fp-catalogue-card" role="option" tabindex="0"' +
				' data-type="' + escHtml( trigger.type ) + '"' +
				' aria-label="' + escHtml( trigger.label ) + '">' +
					'<span class="fp-card-icon dashicons ' + escHtml( trigger.icon ) + '" aria-hidden="true"></span>' +
					'<div class="fp-card-text">' +
						'<strong class="fp-card-label">' + escHtml( trigger.label ) + '</strong>' +
						'<span class="fp-card-desc">' + escHtml( trigger.description ) + '</span>' +
					'</div>' +
				'</div>'
			);

			$card.on( 'click keydown', function ( e ) {
				if ( e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ' ) { return; }
				e.preventDefault();
				selectTrigger( trigger.type );
			} );

			$list.append( $card );
		} );
	}

	function selectTrigger( type ) {
		var trigger = getTriggerByType( type );
		if ( ! trigger ) { return; }

		state.selectedTrigger = trigger;
		$( '#fp-trigger-value' ).val( type );

		// Show selected-trigger bar, hide catalogue.
		$( '#fp-selected-trigger-icon' ).removeClass().addClass( 'fp-item-icon dashicons ' + trigger.icon );
		$( '#fp-selected-trigger-label' ).text( trigger.label );
		$( '#fp-selected-trigger-desc' ).text( trigger.description );
		$( '#fp-selected-trigger' ).show();
		$( '#fp-trigger-catalogue' ).hide();
		$( '#fp-error-trigger' ).text( '' );

		// Re-render token dropdowns in all actions with the new token set.
		$( '#fp-actions-list .fp-token-dropdown' ).each( function () {
			renderTokenDropdownMenu( $( this ), trigger.tokens );
		} );

		// Show webhook config panel only for incoming_webhook trigger.
		if ( 'incoming_webhook' === type ) {
			$( '#fp-webhook-config' ).show();
		} else {
			$( '#fp-webhook-config' ).hide();
		}

		$( document ).trigger( 'flowpress:triggerSelected', [ trigger ] );

		updateSummary();
	}

	// ── Trigger search ─────────────────────────────────────────────────────────

	$( document ).on( 'input', '#fp-trigger-search', function () {
		var q = $( this ).val().toLowerCase();
		var $cards = $( '#fp-trigger-list .fp-catalogue-card' );
		var visible = 0;

		$cards.each( function () {
			var label = $( this ).find( '.fp-card-label' ).text().toLowerCase();
			var desc  = $( this ).find( '.fp-card-desc' ).text().toLowerCase();
			var show  = ! q || label.indexOf( q ) !== -1 || desc.indexOf( q ) !== -1;
			$( this ).toggle( show );
			if ( show ) { visible++; }
		} );

		$( '#fp-trigger-no-results' ).toggle( visible === 0 );
	} );

	// Change trigger button.
	$( document ).on( 'click', '#fp-change-trigger', function () {
		state.selectedTrigger = null;
		$( '#fp-trigger-value' ).val( '' );
		$( '#fp-selected-trigger' ).hide();
		$( '#fp-trigger-catalogue' ).show();
		$( '#fp-trigger-search' ).val( '' ).trigger( 'input' ).focus();
		updateSummary();
	} );

	// ── Action blocks ──────────────────────────────────────────────────────────

	// tokens is always [{token, label}, ...] (indexed array format, same as operators).
	function buildTokenDropdown( tokens ) {
		if ( ! tokens || ! tokens.length ) {
			return '';
		}

		var items = tokens.map( function ( tok ) {
			return '<button type="button" class="fp-token-item" data-token="{{' + escHtml( tok.token ) + '}}"' +
				' aria-label="' + escHtml( tok.label ) + '">' +
				'<code>{{' + escHtml( tok.token ) + '}}</code>' +
				'<span class="fp-token-label">' + escHtml( tok.label ) + '</span>' +
				'</button>';
		} ).join( '' );

		return '<div class="fp-token-menu" role="menu" aria-label="' + escHtml( flowpressAdmin.strings.insertToken ) + '">' + items + '</div>';
	}

	function renderTokenDropdownMenu( $dropdown, tokens ) {
		$dropdown.find( '.fp-token-menu' ).remove();
		if ( tokens && tokens.length ) {
			$dropdown.append( buildTokenDropdown( tokens ) );
		}
	}

	function buildActionBlock( index, actionType, config ) {
		var action = getActionByType( actionType );
		var tokens = state.selectedTrigger ? ( state.selectedTrigger.tokens || [] ) : [];

		// Action type selector.
		var typeOptions = '<option value="">' + escHtml( flowpressAdmin.strings.chooseAction ) + '</option>';
		( flowpressAdmin.actions || [] ).forEach( function ( a ) {
			var sel = a.type === actionType ? ' selected' : '';
			typeOptions += '<option value="' + escHtml( a.type ) + '"' + sel + '>' + escHtml( a.label ) + '</option>';
		} );

		var $block = $(
			'<div class="fp-action-block" role="listitem" data-action-index="' + index + '">' +
				'<div class="fp-action-block-header">' +
					'<span class="fp-action-number dashicons dashicons-admin-generic" aria-hidden="true"></span>' +
					'<select class="fp-action-type-select" aria-label="' + escHtml( flowpressAdmin.strings.chooseAction ) + '">' +
						typeOptions +
					'</select>' +
					'<button type="button" class="fp-remove-action" aria-label="' + escHtml( flowpressAdmin.strings.removeAction ) + '">' +
						'<span class="dashicons dashicons-trash" aria-hidden="true"></span>' +
					'</button>' +
				'</div>' +
				'<div class="fp-action-fields"></div>' +
			'</div>'
		);

		if ( action ) {
			$block.find( '.fp-action-number' ).removeClass( 'dashicons-admin-generic' ).addClass( action.icon );
			renderActionFields( $block.find( '.fp-action-fields' ), action, config, tokens );
		}

		return $block;
	}

	function renderActionFields( $container, action, config, tokens ) {
		$container.empty();

		if ( ! action || ! action.fields ) { return; }

		action.fields.forEach( function ( field ) {
			var val = ( config && config[ field.key ] !== undefined ) ? config[ field.key ] : '';
			var required = field.required ? 'required aria-required="true"' : '';
			var fieldId  = 'fp-action-field-' + field.key + '-' + Math.random().toString( 36 ).slice( 2, 7 );

			var inputHtml;
			if ( field.type === 'textarea' ) {
				inputHtml = '<textarea' +
					' id="' + fieldId + '"' +
					' class="fp-input fp-textarea fp-action-field-input"' +
					' data-field-key="' + escHtml( field.key ) + '"' +
					' placeholder="' + escHtml( field.placeholder || '' ) + '"' +
					' rows="4"' +
					' ' + required + '>' +
					escHtml( val ) +
					'</textarea>';
			} else {
				inputHtml = '<input' +
					' type="' + escHtml( field.type || 'text' ) + '"' +
					' id="' + fieldId + '"' +
					' class="fp-input fp-action-field-input"' +
					' data-field-key="' + escHtml( field.key ) + '"' +
					' value="' + escHtml( val ) + '"' +
					' placeholder="' + escHtml( field.placeholder || '' ) + '"' +
					' ' + required + '>';
			}

			var tokenDropdownHtml = '<div class="fp-token-dropdown" aria-haspopup="true">' +
				'<button type="button" class="fp-token-toggle" aria-label="' + escHtml( flowpressAdmin.strings.insertToken ) + '">' +
					'<span class="dashicons dashicons-shortcode" aria-hidden="true"></span>' +
					'<span class="fp-token-toggle-label">' + escHtml( flowpressAdmin.strings.insertToken ) + '</span>' +
				'</button>' +
				buildTokenDropdown( tokens ) +
				'</div>';

			var helpHtml = field.help ? '<p class="fp-field-help">' + escHtml( field.help ) + '</p>' : '';
			var errorId  = 'fp-field-error-' + fieldId;

			$container.append(
				'<div class="fp-field fp-action-field">' +
					'<div class="fp-field-label-row">' +
						'<label for="' + fieldId + '" class="fp-label">' +
							escHtml( field.label ) +
							( field.required ? '<span class="fp-required" aria-hidden="true">*</span>' : '' ) +
						'</label>' +
						tokenDropdownHtml +
					'</div>' +
					'<div class="fp-input-wrap">' + inputHtml + '</div>' +
					helpHtml +
					'<div class="fp-field-error" id="' + errorId + '" role="alert" aria-live="polite"></div>' +
				'</div>'
			);
		} );
	}

	function addAction( actionType, config ) {
		var index = state.actions.length;
		state.actions.push( { type: actionType || '', config: config || {} } );

		var $block = buildActionBlock( index, actionType || '', config || {} );
		$( '#fp-actions-list' ).append( $block );
		$block.find( '.fp-action-type-select' ).focus();

		updateSummary();
	}

	// Recollect state from DOM after any change.
	function syncActionsState() {
		state.actions = [];
		$( '#fp-actions-list .fp-action-block' ).each( function () {
			var type   = $( this ).find( '.fp-action-type-select' ).val() || '';
			var config = {};
			$( this ).find( '.fp-action-field-input' ).each( function () {
				var key = $( this ).data( 'field-key' );
				if ( key ) { config[ key ] = $( this ).val(); }
			} );
			state.actions.push( { type: type, config: config } );
		} );
	}

	// ── Action block events ────────────────────────────────────────────────────

	// Action type change: re-render fields.
	$( document ).on( 'change', '.fp-action-type-select', function () {
		var $block  = $( this ).closest( '.fp-action-block' );
		var type    = $( this ).val();
		var action  = getActionByType( type );
		var tokens  = state.selectedTrigger ? ( state.selectedTrigger.tokens || [] ) : [];

		$block.find( '.fp-action-number' ).attr( 'class', 'fp-action-number dashicons ' + ( action ? action.icon : 'dashicons-admin-generic' ) );
		renderActionFields( $block.find( '.fp-action-fields' ), action, {}, tokens );

		syncActionsState();
		updateSummary();
	} );

	// Remove action block.
	$( document ).on( 'click', '.fp-remove-action', function () {
		$( this ).closest( '.fp-action-block' ).remove();
		// Renumber remaining blocks.
		$( '#fp-actions-list .fp-action-block' ).each( function ( i ) {
			$( this ).attr( 'data-action-index', i );
		} );
		syncActionsState();
		updateSummary();
	} );

	// Field input: sync state + update summary.
	$( document ).on( 'input change', '.fp-action-field-input', function () {
		syncActionsState();
		updateSummary();
		// Clear field error on input.
		$( this ).closest( '.fp-action-field' ).find( '.fp-field-error' ).text( '' );
		$( this ).removeClass( 'fp-input-error' );
	} );

	// Add action button.
	$( '#fp-add-action' ).on( 'click', function () {
		addAction( '', {} );
	} );

	// ── Token insert ───────────────────────────────────────────────────────────

	// Toggle token dropdown.
	$( document ).on( 'click', '.fp-token-toggle', function ( e ) {
		e.stopPropagation();
		var $dropdown = $( this ).closest( '.fp-token-dropdown' );
		var isOpen = $dropdown.hasClass( 'fp-dropdown-open' );
		$( '.fp-token-dropdown' ).removeClass( 'fp-dropdown-open' );
		if ( ! isOpen ) {
			$dropdown.addClass( 'fp-dropdown-open' );
			$dropdown.find( '.fp-token-item' ).first().focus();
		}
	} );

	// Insert token on click.
	$( document ).on( 'click keydown', '.fp-token-item', function ( e ) {
		if ( e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ' ) { return; }
		e.preventDefault();
		var token    = $( this ).data( 'token' );
		var $field   = $( this ).closest( '.fp-action-field' ).find( '.fp-action-field-input' );
		var el       = $field[ 0 ];
		if ( el ) { insertAtCursor( el, token ); }
		$( this ).closest( '.fp-token-dropdown' ).removeClass( 'fp-dropdown-open' );
		syncActionsState();
		updateSummary();
	} );

	// Close dropdown on outside click.
	$( document ).on( 'click', function () {
		$( '.fp-token-dropdown' ).removeClass( 'fp-dropdown-open' );
	} );

	// Keyboard nav inside token dropdown.
	$( document ).on( 'keydown', '.fp-token-dropdown', function ( e ) {
		var $items = $( this ).find( '.fp-token-item' );
		var idx    = $items.index( document.activeElement );

		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			$items.eq( Math.min( idx + 1, $items.length - 1 ) ).focus();
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			$items.eq( Math.max( idx - 1, 0 ) ).focus();
		} else if ( e.key === 'Escape' ) {
			$( this ).removeClass( 'fp-dropdown-open' );
			$( this ).find( '.fp-token-toggle' ).focus();
		}
	} );

	// ── Live summary ───────────────────────────────────────────────────────────

	function updateSummary() {
		var $summary = $( '#fp-summary-text' );

		if ( ! state.selectedTrigger ) {
			$summary
				.text( flowpressAdmin.strings.summaryNoTrigger )
				.addClass( 'fp-summary-empty' );
			return;
		}

		var parts = [];

		state.actions.forEach( function ( act ) {
			if ( ! act.type ) { return; }
			var actionObj = getActionByType( act.type );
			if ( ! actionObj ) { return; }
			var to = act.config && act.config.to ? act.config.to : '';
			var label = to ? actionObj.label + ' to ' + to : actionObj.label;
			parts.push( label );
		} );

		var text = flowpressAdmin.strings.summaryPrefix + ' ' + state.selectedTrigger.label;
		if ( parts.length ) {
			text += ', ' + parts.join( ' ' + flowpressAdmin.strings.summaryConnector + ' ' );
		}
		text += '.';

		$summary.text( text ).removeClass( 'fp-summary-empty' );
	}

	// ── Form validation ────────────────────────────────────────────────────────

	function validateForm() {
		var valid = true;

		// Recipe name.
		var name = $( '#fp_title' ).val().trim();
		if ( ! name ) {
			$( '#fp-error-title' ).text( flowpressAdmin.strings.validationRequired );
			$( '#fp_title' ).addClass( 'fp-input-error' ).focus();
			valid = false;
		} else {
			$( '#fp-error-title' ).text( '' );
			$( '#fp_title' ).removeClass( 'fp-input-error' );
		}

		// Trigger.
		if ( ! state.selectedTrigger ) {
			$( '#fp-error-trigger' ).text( flowpressAdmin.strings.validationNoTrigger );
			valid = false;
		} else {
			$( '#fp-error-trigger' ).text( '' );
		}

		// Actions.
		if ( ! state.actions.length || ! state.actions.some( function ( a ) { return a.type; } ) ) {
			$( '#fp-error-actions' ).text( flowpressAdmin.strings.validationNoAction );
			valid = false;
		} else {
			$( '#fp-error-actions' ).text( '' );
		}

		// Required action fields.
		$( '#fp-actions-list .fp-action-field-input[required]' ).each( function () {
			var $input = $( this );
			if ( ! $input.val().trim() ) {
				$input.closest( '.fp-action-field' ).find( '.fp-field-error' ).text( flowpressAdmin.strings.validationRequired );
				$input.addClass( 'fp-input-error' );
				if ( valid ) { $input.focus(); }
				valid = false;
			}
		} );

		return valid;
	}

	// ── Hidden inputs serialisation ────────────────────────────────────────────

	function serializeActionsToHiddenInputs() {
		var $container = $( '#fp-actions-hidden-inputs' );
		$container.empty();

		state.actions.forEach( function ( action, i ) {
			if ( ! action.type ) { return; }
			$container.append(
				$( '<input>' ).attr( { type: 'hidden', name: 'fp_actions[' + i + '][type]', value: action.type } )
			);
			Object.keys( action.config || {} ).forEach( function ( key ) {
				$container.append(
					$( '<input>' ).attr( { type: 'hidden', name: 'fp_actions[' + i + '][config][' + key + ']', value: action.config[ key ] } )
				);
			} );
		} );
	}

	// ── Form submit ────────────────────────────────────────────────────────────

	$( '#fp-builder-form' ).on( 'submit', function ( e ) {
		syncActionsState();

		if ( ! validateForm() ) {
			e.preventDefault();
			$( '.fp-input-error' ).first().focus();
			return;
		}

		serializeActionsToHiddenInputs();
		$( '#fp-save-btn' ).prop( 'disabled', true ).text( flowpressAdmin.strings.saving );
	} );

	// Name field: clear error on input.
	$( '#fp_title' ).on( 'input', function () {
		if ( $( this ).val().trim() ) {
			$( '#fp-error-title' ).text( '' );
			$( this ).removeClass( 'fp-input-error' );
		}
	} );

	// ── Confirm delete ─────────────────────────────────────────────────────────

	$( document ).on( 'click', '.fp-delete-link', function ( e ) {
		if ( ! window.confirm( flowpressAdmin.strings.confirmDelete ) ) {
			e.preventDefault();
		}
	} );

	// ── Test recipe ────────────────────────────────────────────────────────────

	$( document ).on( 'click', '#fp-test-recipe', function () {
		var $btn     = $( this );
		var recipeId = $btn.data( 'recipe-id' );
		var $result  = $( '#fp-test-result' );
		var $content = $( '#fp-test-result-content' );

		$btn.prop( 'disabled', true ).text( flowpressAdmin.strings.testing );
		$result.hide().removeClass( 'notice-success notice-error' );

		$.post(
			flowpressAdmin.ajaxUrl,
			{ action: 'flowpress_test_recipe', nonce: flowpressAdmin.nonce, recipe_id: recipeId },
			function ( response ) {
				$btn.prop( 'disabled', false ).text( flowpressAdmin.strings.testRecipe );

				if ( response.success ) {
					var html = '<p>' + escHtml( response.data.message ) + '</p><ul>';
					( response.data.action_results || [] ).forEach( function ( r ) {
						html += '<li><strong>' + escHtml( r.type ) + '</strong>: ' + escHtml( r.status ) + ' — ' + escHtml( r.message || '' ) + '</li>';
					} );
					html += '</ul>';
					$result.addClass( 'notice-success' ).show();
					$content.html( html );
				} else {
					$result.addClass( 'notice-error' ).show();
					$content.html( '<p>' + escHtml( ( response.data && response.data.message ) || flowpressAdmin.strings.error ) + '</p>' );
				}

				$result[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		).fail( function () {
			$btn.prop( 'disabled', false ).text( flowpressAdmin.strings.testRecipe );
			$result.addClass( 'notice-error' ).show();
			$content.html( '<p>' + escHtml( flowpressAdmin.strings.error ) + '</p>' );
		} );
	} );

	// ── Conditions UI ──────────────────────────────────────────────────────────

	var $conditionsSection = $( '#fp-conditions-section' );

	function getActiveTokens() {
		// state.selectedTrigger is the full trigger object; access .tokens directly.
		return state.selectedTrigger ? ( state.selectedTrigger.tokens || [] ) : [];
	}

	function buildConditionFieldOptions( selectedField ) {
		var tokens = getActiveTokens();
		var html = '<option value="">' + escHtml( flowpressAdmin.strings.selectField || '— select field —' ) + '</option>';
		tokens.forEach( function ( tok ) {
			var sel = tok.token === selectedField ? ' selected' : '';
			html += '<option value="' + escHtml( tok.token ) + '"' + sel + '>' + escHtml( tok.label ) + '</option>';
		} );
		return html;
	}

	function buildOperatorOptions( selectedOp ) {
		var operators = ( window.flowpressBuilderData || {} ).operators || [];
		var html = '<option value="">' + escHtml( flowpressAdmin.strings.selectOperator || '— operator —' ) + '</option>';
		operators.forEach( function ( op ) {
			var sel = op.value === selectedOp ? ' selected' : '';
			html += '<option value="' + escHtml( op.value ) + '"' + sel + '>' + escHtml( op.label ) + '</option>';
		} );
		return html;
	}

	function isValuelessOp( op ) {
		var valueless = ( window.flowpressBuilderData || {} ).valuelessOps || [];
		return valueless.indexOf( op ) !== -1;
	}

	function addConditionRow( field, operator, value ) {
		field    = field    || '';
		operator = operator || '';
		value    = value    || '';

		var $row = $( '<div class="fp-condition-row"></div>' );

		var $field = $( '<select class="fp-cond-field">' + buildConditionFieldOptions( field ) + '</select>' );
		var $op    = $( '<select class="fp-cond-operator">' + buildOperatorOptions( operator ) + '</select>' );
		var $val   = $( '<input type="text" class="fp-cond-value regular-text" placeholder="value">' ).val( value );
		var $rm    = $( '<button type="button" class="button fp-remove-condition" aria-label="' + escHtml( flowpressAdmin.strings.removeCondition || 'Remove' ) + '">✕</button>' );

		if ( isValuelessOp( operator ) ) { $val.hide(); }

		$row.append( $field, $op, $val, $rm );
		$conditionsSection.find( '#fp-conditions-list' ).append( $row );
	}

	$conditionsSection.on( 'click', '#fp-add-condition', function () {
		addConditionRow( '', '', '' );
	} );

	$conditionsSection.on( 'click', '.fp-remove-condition', function () {
		$( this ).closest( '.fp-condition-row' ).remove();
	} );

	$conditionsSection.on( 'change', '.fp-cond-operator', function () {
		var $val = $( this ).closest( '.fp-condition-row' ).find( '.fp-cond-value' );
		if ( isValuelessOp( $( this ).val() ) ) {
			$val.hide().val( '' );
		} else {
			$val.show();
		}
	} );

	// Repopulate field dropdowns when the trigger changes.
	$( document ).on( 'flowpress:triggerSelected', function () {
		$conditionsSection.find( '.fp-cond-field' ).each( function () {
			var current = $( this ).val();
			$( this ).html( buildConditionFieldOptions( current ) );
		} );
	} );

	// Serialize conditions into hidden inputs on submit.
	$( '#fp-builder-form' ).on( 'submit', function () {
		// Remove stale condition inputs.
		$( this ).find( 'input[name^="fp_conditions"]' ).remove();

		var logic = $conditionsSection.find( '#fp-conditions-logic' ).val() || 'AND';
		$( '<input type="hidden" name="fp_conditions_logic">' ).val( logic ).appendTo( this );

		$conditionsSection.find( '.fp-condition-row' ).each( function ( i ) {
			var field = $( this ).find( '.fp-cond-field' ).val()    || '';
			var op    = $( this ).find( '.fp-cond-operator' ).val() || '';
			var val   = $( this ).find( '.fp-cond-value' ).val()    || '';
			if ( ! field || ! op ) { return; }
			$( '<input type="hidden">' ).attr( 'name', 'fp_conditions[' + i + '][field]' ).val( field ).appendTo( '#fp-builder-form' );
			$( '<input type="hidden">' ).attr( 'name', 'fp_conditions[' + i + '][operator]' ).val( op ).appendTo( '#fp-builder-form' );
			$( '<input type="hidden">' ).attr( 'name', 'fp_conditions[' + i + '][value]' ).val( val ).appendTo( '#fp-builder-form' );
		} );
	} );

	// ── Re-run button (Runs dashboard) ─────────────────────────────────────────

	$( document ).on( 'click', '.fp-rerun-btn', function () {
		var $btn   = $( this );
		var runId  = $btn.data( 'run-id' );
		var $row   = $btn.closest( 'tr' );
		var $badge = $row.find( '.fp-status-badge' );

		$btn.prop( 'disabled', true ).text( '…' );

		$.post(
			ajaxurl,
			{
				action:   'flowpress_rerun',
				run_id:   runId,
				_wpnonce: flowpressAdmin.nonce,
			},
			function ( response ) {
				if ( response && response.success ) {
					$badge.text( 'success' ).removeClass().addClass( 'fp-status-badge fp-status-success' );
					$btn.remove();
				} else {
					var msg = ( response && response.data && response.data.message ) ? response.data.message : flowpressAdmin.strings.error;
					$btn.prop( 'disabled', false ).text( flowpressAdmin.strings.rerun || 'Re-run' );
					alert( msg );
				}
			}
		).fail( function () {
			$btn.prop( 'disabled', false ).text( flowpressAdmin.strings.rerun || 'Re-run' );
		} );
	} );

	// ── Initialise builder on page load ────────────────────────────────────────

	function initBuilder() {
		if ( ! $( '#fp-builder-form' ).length ) { return; }

		renderTriggerCatalogue();

		var saved = window.flowpressBuilderData || {};

		// Restore saved trigger.
		if ( saved.savedTrigger ) {
			selectTrigger( saved.savedTrigger );
		}

		// Restore incoming webhook slug if present.
		if ( saved.savedTriggerConfig && saved.savedTriggerConfig.slug ) {
			$( '#fp_trigger_config_slug' ).val( saved.savedTriggerConfig.slug );
		}

		// Restore saved actions.
		if ( saved.savedActions && saved.savedActions.length ) {
			saved.savedActions.forEach( function ( act ) {
				addAction( act.type, act.config || {} );
			} );
		} else {
			// Start with one empty action slot.
			addAction( '', {} );
		}

		// Restore saved conditions.
		if ( saved.savedConditions ) {
			var conds = saved.savedConditions;
			if ( conds.logic ) {
				$conditionsSection.find( '#fp-conditions-logic' ).val( conds.logic );
			}
			if ( conds.items && conds.items.length ) {
				conds.items.forEach( function ( c ) {
					addConditionRow( c.field || '', c.operator || '', c.value || '' );
				} );
			}
		}

		updateSummary();
	}

	$( initBuilder );

} )( jQuery );
