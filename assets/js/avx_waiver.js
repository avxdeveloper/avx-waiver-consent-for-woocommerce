








/**
 *
 *
 *
 *     avx_waiver
 *
 *
 *
*/
( function() {

  var pr_list = 'body';

  var w_list = '.avx_waiverPopup .containerWrapper';

  jQuery(window).on('load', function() {

    var to_w_sb = document.createElement('div');
    to_w_sb.style.overflowY = 'scroll';
    to_w_sb.style.width = '50px';
    to_w_sb.style.height = '50px';
    to_w_sb.style.visibility = 'hidden';

    document.body.appendChild(to_w_sb);
    var scrollBarWidth = to_w_sb.offsetWidth - to_w_sb.clientWidth;
    document.body.removeChild(to_w_sb);

    jQuery('body').on('click', '.avx_waiverCheckoutBtn, .avx_waiverPopup__closeBtnOverlay, .avx_waiverPopup__closeBtn', function(e) {
      var this__popup_toggler = jQuery(this);
      var this__popup         = this__popup_toggler.attr('data-popup');

      if (
           jQuery('body').hasClass('avx_waiverPopupShow')
        && (
             jQuery( e.target ).hasClass('avx_waiverPopup__closeBtnOverlay')
          || jQuery( e.target ).hasClass('avx_waiverPopup__closeBtn')
        )
      ) {

        jQuery( this__popup ).removeClass('avx_waiverPopupShow');
        if ( jQuery('.avx_waiverPopupShow').length == 0 ) {
          jQuery('body').removeClass('avx_waiverPopupShow');
        }

        jQuery('body').css({
          'overflow': '',
        });

        jQuery( pr_list ).css({
          'padding-right': '',
        });

        jQuery( w_list ).css({
          'width': '',
        });

        jQuery('body').trigger('avx_waiverPopupShow');

      } else {

        jQuery('body').addClass('avx_waiverPopupShow');
        jQuery( this__popup ).addClass('avx_waiverPopupShow');

        jQuery('body').css({
          'overflow': 'hidden',
        });

        jQuery( pr_list ).css({
          'padding-right': scrollBarWidth,
        });

        jQuery( w_list ).css({
          'width': 'calc(100% - ' + scrollBarWidth + 'px)',
        });

        jQuery('body').trigger('avx_waiverPopupHide');

      }

    }); // END  jQuery('.').on('click', function() { ... });

  }); // END  jQuery(window).on('load', function() { ... });




}() );



( function() {

  jQuery('body').on('click', '.avx_waiverCheckoutBtn', function() {
    jQuery('.avx_waiverPopup .avx_waiverForm').removeAttr('novalidate');
    jQuery('.avx_waiverPopup .avx_waiverForm .woocommerce-NoticeGroup').remove();
    jQuery('.avx_waiverPopup .avx_waiverForm .woocommerce-NoticeGroup-checkout').remove();
  });


  jQuery('body').on('click', '.avx_waiverSubmitBtn', function(e) {
    var this_btn = jQuery( this );
    var this_form = this_btn.closest('form');
    var avx_waiver_name = this_form.find('.avx_waiverData__unameInput--name').val();
    var avx_waiver_signature = this_form.find('.avx_waiverData__unameInput--sign').val();
    avx_waiver_name = ( typeof avx_waiver_name === 'string' ) ? avx_waiver_name.replace(/[\s\u0085]+/g, ' ').trim() : '';
    avx_waiver_signature = ( typeof avx_waiver_signature === 'string' ) ? avx_waiver_signature.replace(/[\s\u0085]+/g, ' ').trim() : '';

    if ( avx_waiver_name == '' ) {
      this_form.find('.avx_waiverData__unameInput--name + .avx_waiverData__unameInputError').text(AVXWaiver.messages.required);
    }
    else {
      this_form.find('.avx_waiverData__unameInput--name + .avx_waiverData__unameInputError').html('');
    }

    if ( avx_waiver_signature == '' ) {
      this_form.find('.avx_waiverData__unameInput--sign + .avx_waiverData__unameInputError').text(AVXWaiver.messages.required);
    }
    else {
      this_form.find('.avx_waiverData__unameInput--sign + .avx_waiverData__unameInputError').html('');
    }

    if (
         ( avx_waiver_name != '' )
      && ( avx_waiver_signature != '' )
      && ( avx_waiver_name != avx_waiver_signature )
    ) {
      this_form.find('.avx_waiverData__unameInputError').text(AVXWaiver.messages.namesMismatch);
    }

    if ( ! this_form.find('#avx_waiverAgreeCheckbox').is(':checked') ) {
      this_form.find('.avx_waiverData__checkboxError').text(AVXWaiver.messages.required);
    }
    else {
      this_form.find('.avx_waiverData__checkboxError').html('');
    }

    if (
         ( avx_waiver_name != '' )
      && ( avx_waiver_signature != '' )
      && ( avx_waiver_name == avx_waiver_signature )
      && this_form.find('#avx_waiverAgreeCheckbox').is(':checked')
    ) {

      this_form.find('.avx_waiverData__unameInputError').html('');

      var waiver_request_success = false;

      jQuery.ajax({
        url : AVXWaiver.ajaxUrl,
        type : 'POST',
        dataType : 'json',

        data: {
          avx_waiverData : this_form.serialize(),
          action     : 'avx_waiver_action',
          avx_waiver_ajax_nonce : AVXWaiver.nonce,
        },
        beforeSend: function( qXHR, PlainObject ) {

          jQuery('.avx_waiverForm').addClass('loading');

        },
        success: function( data, textStatus ) {

          if (
               ( ! data )
            || ( data.success !== true )
            || ( ! data.data )
            || ( typeof data.data.user_name !== 'string' )
            || ( typeof data.data.user_sign !== 'string' )
            || ( data.data.agree !== '1' )
            || ( ( data.data.point !== undefined ) && ( typeof data.data.point !== 'string' ) )
          ) {
            jQuery('.avx_waiverErrorNote').text(AVXWaiver.messages.invalidDocumentResponse);
            return;
          }

          var avx_waiver_fields = jQuery();
          jQuery.each(['user_name', 'user_sign', 'agree', 'point'], function(index, key) {
            if ( typeof data.data[key] === 'string' ) {
              avx_waiver_fields = avx_waiver_fields.add(
                jQuery('<input>', {
                  type: 'hidden',
                  name: 'avx_waiver-' + key,
                  'class': 'avx_waiverField avx_waiverField--' + key
                }).val(data.data[key])
              );
            }
          });
          jQuery('#avx_waiver_checkout_field .avx_waiverField').remove();
          jQuery('#avx_waiver_checkout_field label.checkbox').after(avx_waiver_fields);
          jQuery('input[name="avx_waiver_checkout"]').prop( 'checked', true );
          jQuery('.avx_waiverErrorNote').text('');

          jQuery('#avx_waiver_checkout_field.woocommerce-invalid').removeClass('woocommerce-invalid');
          jQuery('#avx_waiver_checkout_field.woocommerce-invalid-required-field').removeClass('woocommerce-invalid-required-field');
          jQuery('#avx_waiver_checkout_field .checkout-inline-error-message').remove();

          waiver_request_success = true;

        },
        error: function( jqXHR ) {
          var avx_waiver_message = AVXWaiver.messages.error;
          if (
               ( jqXHR.responseJSON )
            && ( jqXHR.responseJSON.data )
            && ( typeof jqXHR.responseJSON.data.message === 'string' )
          ) {
            avx_waiver_message = jqXHR.responseJSON.data.message;
          }
          jQuery('.avx_waiverErrorNote').text(avx_waiver_message);
        },
        complete: function( jqXHR, textStatus ) {
          if ( ! waiver_request_success ) {
            jQuery('.avx_waiverForm').removeClass('loading');
            return;
          }

          jQuery('.avx_waiverForm').removeClass('loading');
          jQuery('.avx_waiverPopup__closeBtn[data-popup=".avx_waiverPopup"]').click();

          jQuery('.avx_waiverCheckoutBtn').addClass('signed');
          jQuery('.avx_waiverCheckoutBtn').text( jQuery('.avx_waiverCheckoutBtn').attr( 'data-text_signed' )  );
        }
      });
    }

  }); // jQuery('body').on('click', '.avx_waiverSubmitBtn', function(e) { ... }



}() );
