"use strict";
var MDWCPTjQ = jQuery.noConflict();

MDWCPTjQ.on_ready = function() {
	//maybe colorize base64 icon
	var img = MDWCPTjQ('.mdwcpt-track-icon');
	if ( img.length > 0 && img.css('background-image').substr( 0, 31 ) === 'url("data:image/svg+xml;base64,' ) {
		var encoded = img.css('background-image').substr( 0, img.css('background-image').length - 2 ).substr( 31 );
		var wrapper = MDWCPTjQ('<div></div>');
		wrapper.append( atob(encoded) ).find('path').css('fill', img.parent().css( 'color' ) );
		img.css( 'background-image', 'url("data:image/svg+xml;base64,' + btoa( wrapper.html() ) + '")' );
	}
	MDWCPTjQ('.mdwcpt-trigger > .mdwcpt-track-icon').css('opacity', '0.7');
	
	//colorize popup header
	MDWCPTjQ( '.mdwcpt-form-head' ).css({
		'background-color' : MDWCPTjQ('.mdwcpt-form-head').closest('.mdwcpt-form').find('button[name="mdwcpt-submit"]').css('background-color'),
		'color'            : MDWCPTjQ('.mdwcpt-form-head').closest('.mdwcpt-form').find('button[name="mdwcpt-submit"]').css('color'),
	});
	MDWCPTjQ( '.mdwcpt-close' ).css({
		'color'            : MDWCPTjQ('.mdwcpt-form-head').closest('.mdwcpt-form').find('button[name="mdwcpt-submit"]').css('color'),
	});
}

MDWCPTjQ(document).on('click', '.mdwcpt-close', function(){
	MDWCPTjQ(this).closest( '.mdwcpt-bg' ).hide();
	MDWCPTjQ('#mdwcpt-recaptcha').hide();
});

MDWCPTjQ(document).on('mousedown', '.mdwcpt-bg', function( e ){
	if ( e.which === 1 && e.target === this ) {
        MDWCPTjQ(this).hide();
		MDWCPTjQ('#mdwcpt-recaptcha').hide();
	}
});

MDWCPTjQ(document).on('click', '.mdwcpt-trigger', function(){
	//support ajaxified plugins like load more pagination or ajaxified filters like facetwp
	if ( MDWCPTjQ( '.mdwcpt-bg[id="mdwcpt-form-' + MDWCPTjQ(this).attr('data-mdwcpt-form') + '"]' ).length < 1 ) {
		var data = {
			'action' : 'mdwcpt_get_form',
			'pid'    : MDWCPTjQ(this).attr('data-mdwcpt-form')
		};
		MDWCPTjQ.get( mdwcpt_l10n.ajaxurl, data, function( resp ) {
			if ( '' !== resp.data ) {
				//console.log( resp.data );
				MDWCPTjQ( 'body' ).append( resp.data );
				MDWCPTjQ.on_ready();
				MDWCPTjQ('.mdwcpt-bg[id="mdwcpt-form-' + data.pid + '"]').show();
				MDWCPTjQ('#mdwcpt-recaptcha').show();
			} else {
				MDWCPTjQ( 'body' ).append( '<div class="mdwcpt-bg" id="mdwcpt-form-' + data.pid + '"></div>' );
			}
		});		
	} else if ( MDWCPTjQ( '.mdwcpt-bg[id="mdwcpt-form-' + MDWCPTjQ(this).attr('data-mdwcpt-form') + '"] > .mdwcpt-form' ).length > 0 ) {
		MDWCPTjQ('.mdwcpt-bg[id="mdwcpt-form-' + MDWCPTjQ(this).attr('data-mdwcpt-form') + '"]').show();
		MDWCPTjQ('#mdwcpt-recaptcha').show();
	}
});

MDWCPTjQ(document).on('focusin', '.mdwcpt-form :input', function(){
	MDWCPTjQ(this).prev('.mdwcpt-msg').hide('slow', function() { MDWCPTjQ(this).remove(); });
});

MDWCPTjQ(document).on('change', 'select[name="mdwcpt-variation"]', function(){
	MDWCPTjQ(this).next('.mdwcpt-gal').find('img:visible').hide('slow');	
	var prc_input = MDWCPTjQ(this).closest('.mdwcpt-form').find('input[name="mdwcpt-price"]');
	prc_input.attr( 'max', '' );
	if ( MDWCPTjQ(this).val() !== '' ) {
		var img_id = MDWCPTjQ(this).find('option:selected').data('mdwcpt-img');
		MDWCPTjQ(this).next('.mdwcpt-gal').find('img[data-mdwcpt-img="'+img_id+'"]').show('slow');
		
		var max_prc = MDWCPTjQ(this).find('option:selected').data('mdwcpt-maxprice');	
		prc_input.attr( 'max', max_prc );
		if ( prc_input.val() > max_prc ) {
			prc_input.val( max_prc ).attr( 'value', max_prc );
		}
	}		
});

MDWCPTjQ(document).on('change', 'input[name="mdwcpt-email"]', function(){
	//console.log( 'change' );
	if ( this.checkValidity() && MDWCPTjQ(this).val().split('@')[1].indexOf('.') > 0 ) {	
		var form = MDWCPTjQ(this).closest('.mdwcpt-form');
		form.find('button[name="mdwcpt-submit"]').prop('disabled', true);
		var data = {
			'action' : 'mdwcpt_email_exists',
			'key'    : form.find('input[name="mdwcpt-key"]').val(),
			'pid'    : form.find('input[name="mdwcpt-pid"]').val(),
			'email'  : MDWCPTjQ(this).val()
		};
		MDWCPTjQ.post( mdwcpt_l10n.ajaxurl , data, function( resp ) {
			//console.log( resp );
			if ( 'success' in resp && 'data' in resp && resp.success ) {
				if ( resp.data === 'exists' ) {
					form.find('input[name="mdwcpt-pass"]').prop('disabled', false).closest('.mdwcpt-field').show('slow');
					form.find('input[name="mdwcpt-terms"]').prop('disabled', true).closest('.mdwcpt-field').hide('slow');
				} else {
					form.find('input[name="mdwcpt-pass"]').val('').prop('disabled', true).closest('.mdwcpt-field').hide('slow');
					form.find('input[name="mdwcpt-terms"]').prop('disabled', false).closest('.mdwcpt-field').show('slow');
				}
			} else {
				form.find('input[name="mdwcpt-pass"]').val('').prop('disabled', true).closest('.mdwcpt-field').hide('slow');
				form.find('input[name="mdwcpt-terms"]').prop('disabled', false).closest('.mdwcpt-field').show('slow');
			}
			form.find('button[name="mdwcpt-submit"]').prop('disabled', false);
		});
	} else {
		MDWCPTjQ(this).closest('.mdwcpt-form').find('input[name="mdwcpt-pass"]').val('').prop('disabled', true).closest('.mdwcpt-field').hide('slow');		
		MDWCPTjQ(this).closest('.mdwcpt-form').find('input[name="mdwcpt-terms"]').prop('disabled', false).closest('.mdwcpt-field').show('slow');
	}
});

/*MDWCPTjQ(document).on('focusin', 'input[name="mdwcpt-email"]', function(){
	MDWCPTjQ(this).closest('.mdwcpt-form').find('button[name="mdwcpt-submit"]').prop('disabled', true);		
}).on('blur', 'input[name="mdwcpt-email"]', function(){
	if ( false === mdwcpt['doing_email_check'] )
		MDWCPTjQ(this).closest('.mdwcpt-form').find('button[name="mdwcpt-submit"]').prop('disabled', false);		
});*/

MDWCPTjQ(document).on('click', 'button[name="mdwcpt-submit"]', function( e ){
	e.preventDefault();
	var data = getFormData( MDWCPTjQ( this ) );
	
	if ( ! data )
		return;
	
	if ( MDWCPTjQ('#mdwcpt-recaptcha [name="g-recaptcha-response"]').length > 0 ) {
		MDWCPTjQ(this).prop('disabled', true);
		grecaptcha.execute();			
	} else {
		mdwcptSubmit( MDWCPTjQ(this), data );
	}
});

function mdwcptCaptchaSubmit( token ) {
	MDWCPTjQ('.mdwcpt-form input[name="mdwcpt-captcha"]').val( token );
	var button = MDWCPTjQ('.mdwcpt-bg:visible').find( 'button[name="mdwcpt-submit"]' );
	if ( button.length > 0 ) {
		button.prop('disabled', false);
		mdwcptSubmit( button, getFormData( button ) );
	}		
}

function getFormData( button ) {
	var valid = true, data = { 'action' : 'mdwcpt_subscribe' }, valSupport = 'reportValidity' in HTMLFormElement.prototype;

	button.closest('.mdwcpt-form').find(':input').not('[name="mdwcpt-submit"]').each( function() {
		if ( valSupport && ! this.checkValidity() ) {
			this.reportValidity();
			valid = false;
			return false;
		} else {
			data[ MDWCPTjQ(this).attr('name').replace('mdwcpt-', '') ] = MDWCPTjQ(this).val();
		}				
	});
	
	return ( valid ) ? data : false;
}

function mdwcptSubmit( button, data ) {	
	if ( data ) {
		//console.log( data );
		var form = button.closest('.mdwcpt-form');
		var pass_state = form.find('input[name="mdwcpt-pass"]').prop('disabled');
		form.find(':input').prop('disabled', true);
		form.find('.mdwcpt-msg').remove();
		MDWCPTjQ.post( mdwcpt_l10n.ajaxurl , data, function( resp ) {
			//console.log( resp );
			if ( 'success' in resp && 'data' in resp ) {
				if ( resp.success ) {
					form.find('.mdwcpt-form-head').after(
						'<div class="mdwcpt-field mdwcpt-msg woocommerce-message">' + resp.data + '</div>'
					);
					form.find(':input[type="password"]').val('').attr('value','');
				} else {
					var top_error = '';
					MDWCPTjQ.each( resp.data, function( k, v ) {
						if ( form.find(':input[name="'+k+'"]').length > 0 ) {
							form.find( ':input[name="'+k+'"]').before( '<div class="mdwcpt-msg woocommerce-error">' + v + '</div>' );
						} else {
							top_error += v + '<br />';
						}
					});
					if ( '' !== top_error ) {
						form.find('.mdwcpt-form-head').after(
							'<div class="mdwcpt-field mdwcpt-msg woocommerce-error">' + top_error.substring(0,(top_error.length-6)) + '</div>'
						);
					}
				}
			}
			form.find(':input').prop('disabled', false);
			form.find('input[name="mdwcpt-pass"]').prop('disabled', pass_state);
			
			if ( MDWCPTjQ('#mdwcpt-recaptcha [name="g-recaptcha-response"]').length > 0 )	
				grecaptcha.reset();				
		});
	}	
}

MDWCPTjQ(document).ready( function() {
	
	if ( '0' !== mdwcpt_l10n.captcha_key ) {	
		MDWCPTjQ( 'body' ).append( '<div id="mdwcpt-recaptcha" class="g-recaptcha" data-sitekey="'+mdwcpt_l10n.captcha_key+'" data-callback="mdwcptCaptchaSubmit" data-size="invisible"></div>' );
		MDWCPTjQ.getScript( "//www.google.com/recaptcha/api.js?ver=5.5.3" );
	}
	
	MDWCPTjQ.on_ready();
	
	//open form by hash
	if ( location.hash.indexOf( '#mdwcpt-form-' ) === 0 && MDWCPTjQ( location.hash ).length > 0 ) {
		MDWCPTjQ( location.hash ).show();
	}
});

MDWCPTjQ( document ).ajaxStop(function() {
	MDWCPTjQ.on_ready();
});
