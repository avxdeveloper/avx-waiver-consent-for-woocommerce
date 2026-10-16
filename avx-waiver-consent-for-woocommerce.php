<?php
/**
 * Plugin Name: AVX Waiver & Consent for WooCommerce
 * Description: Required document signing and consent at WooCommerce checkout.
 * Version: 1.0.0
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 10.8
 * Author: AVX
 * Author URI: https://profiles.wordpress.org/avavictoria/
 * License: GPL-2.0-or-later
 * Text Domain: avx-waiver-consent-for-woocommerce
 *
 * @package avx_waiver
 */


defined( 'ABSPATH' ) || exit;

const AVX_WAIVER_VERSION = '1.0.0';
const AVX_WAIVER_FILE = __FILE__;

require_once __DIR__ . '/avx-waiver-admin.php';

add_action( 'before_woocommerce_init', 'avx_waiver__declare_hpos_compatibility' );
add_action( 'plugins_loaded', 'avx_waiver__init' );
function avx_waiver__init() {
  if ( ! class_exists( 'WooCommerce' ) ) {
    add_action( 'admin_notices', 'avx_waiver__woocommerce_notice' );
    return;
  }

  avx_waiver__register_admin_hooks();
  add_action( 'woocommerce_checkout_create_order', 'avx_waiver__add_custom_meta_data_to_order__avx_waiver', 20, 2 );
  add_filter( 'woocommerce_checkout_fields', 'avx_waiver__checkout__add__custom_field' );
  add_filter( 'woocommerce_form_field_checkbox', 'avx_waiver__checkout__add__popup_btn__n__msg', 10, 4 );
  add_action( 'woocommerce_after_checkout_validation', 'avx_waiver__checkout__validation__error_message', 9999, 2 );
  add_action( 'wp_ajax_avx_waiver_action', 'avx_waiver_action__callback' );
  add_action( 'wp_ajax_nopriv_avx_waiver_action', 'avx_waiver_action__callback' );
  add_action( 'wp_enqueue_scripts', 'avx_waiver__enqueue_assets' );
  add_action( 'wp_footer', 'avx_waiver__footer' );
}

function avx_waiver__declare_hpos_compatibility() {
  if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
      'custom_order_tables',
      AVX_WAIVER_FILE,
      true
    );
  }
}

function avx_waiver__woocommerce_notice() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'AVX Waiver & Consent for WooCommerce requires WooCommerce to be active.', 'avx-waiver-consent-for-woocommerce' ) . '</p></div>';
}


  const AVX_WAIVER_ICON_CROSS = '<svg class="icon icon--cross" width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 5L4.99998 19M5.00001 5L19 19" stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/></svg>';




function avx_waiver__read_scalar( $source, $key, $max_length = 200, $unslash = true ) {
  if (
       ( ! is_array( $source ) )
    || ( ! isset( $source[ $key ] ) )
    || ( ! is_string( $source[ $key ] ) )
    || ( strlen( $source[ $key ] ) > min( 50000, $max_length * 4 ) )
  ) {
    return null;
  }

  $value = ( $unslash ? wp_unslash( $source[ $key ] ) : $source[ $key ] );
  $length = preg_match_all( '/./us', $value );
  if ( ( $length === false ) || ( $length > $max_length ) ) {
    return null;
  }

  return sanitize_text_field( $value );
}

function avx_waiver__normalize_name( $value ) {
  return trim( preg_replace( '/[\s\p{Z}]+/u', ' ', $value ) );
}

// AJAX fields have already been unslashed before parse_str(); checkout fields have not.
function avx_waiver__validate_fields( $source, $checkout = false ) {
  $prefix = ( $checkout ? 'avx_waiver-' : '' );
  $name = avx_waiver__read_scalar( $source, $prefix . 'user_name', 200, $checkout );
  $signature = avx_waiver__read_scalar( $source, $prefix . 'user_sign', 200, $checkout );

  if ( ( $name === null ) || ( $signature === null ) ) {
    return new WP_Error( 'avx_waiver_invalid', __( 'Participant name and signature must be text of no more than 200 characters each.', 'avx-waiver-consent-for-woocommerce' ) );
  }

  $name = avx_waiver__normalize_name( $name );
  $signature = avx_waiver__normalize_name( $signature );
  if ( ( $name === '' ) || ( $signature === '' ) ) {
    return new WP_Error( 'avx_waiver_required', __( 'Please enter your participant name and signature.', 'avx-waiver-consent-for-woocommerce' ) );
  }
  if ( $name !== $signature ) {
    return new WP_Error( 'avx_waiver_mismatch', __( 'Participant name and signature must match.', 'avx-waiver-consent-for-woocommerce' ) );
  }
  if ( avx_waiver__read_scalar( $source, $prefix . 'agree', 1, $checkout ) !== '1' ) {
    return new WP_Error( 'avx_waiver_agreement', __( 'Please agree to electronically sign the document.', 'avx-waiver-consent-for-woocommerce' ) );
  }

  // A submitted date is never used as evidence or saved; reject malformed scalar input.
  if (
       ( isset( $source[ $prefix . 'sign_date' ] ) )
    && ( avx_waiver__read_scalar( $source, $prefix . 'sign_date', 200, $checkout ) === null )
  ) {
    return new WP_Error( 'avx_waiver_invalid_date', __( 'Invalid document date field.', 'avx-waiver-consent-for-woocommerce' ) );
  }

  $values = array( 'user_name' => $name, 'user_sign' => $signature, 'agree' => '1' );
  if ( array_key_exists( $prefix . 'point', $source ) ) {
    $points = $source[ $prefix . 'point' ];
    if ( $checkout ) {
      if ( ( ! is_string( $points ) ) || ( strlen( $points ) > 100000 ) ) {
        return new WP_Error( 'avx_waiver_invalid_points', __( 'Invalid document options.', 'avx-waiver-consent-for-woocommerce' ) );
      }
      $points = wp_unslash( $points );
      if ( strlen( $points ) > 50000 ) {
        return new WP_Error( 'avx_waiver_invalid_points', __( 'Invalid document options.', 'avx-waiver-consent-for-woocommerce' ) );
      }
      $points = json_decode( $points );
    }
    if ( ( ! is_array( $points ) ) || ( count( $points ) > 5 ) ) {
      return new WP_Error( 'avx_waiver_invalid_points', __( 'Submit no more than five document options, as text values.', 'avx-waiver-consent-for-woocommerce' ) );
    }

    $allowed_points = array();
    $settings = avx_waiver__get_settings();
    foreach ( $settings['clauses'] as $clause ) {
      if ( ( !empty( $clause['optional'] ) ) && ( $clause['text'] !== '' ) ) {
        $allowed_value = $clause['title'] . ' - ' . $clause['text'];
        $allowed_points[] = avx_waiver__read_scalar( array( 'point' => $allowed_value ), 'point', 600, false );
      }
    }

    $clean_points = array();
    foreach ( $points as $key => $point ) {
      $clean_point = avx_waiver__read_scalar( $points, $key, 600, false );
      if ( ( $clean_point === null ) || ( ! in_array( $clean_point, $allowed_points, true ) ) ) {
        return new WP_Error( 'avx_waiver_invalid_points', __( 'Invalid document options.', 'avx-waiver-consent-for-woocommerce' ) );
      }
      $clean_points[] = $clean_point;
    }
    $values['point'] = wp_json_encode( $clean_points );
    if ( strlen( $values['point'] ) > 50000 ) {
      return new WP_Error( 'avx_waiver_invalid_points', __( 'Invalid document options.', 'avx-waiver-consent-for-woocommerce' ) );
    }
  }

  return $values;
}

function avx_waiver__is_required() {
  $settings = avx_waiver__get_settings();
  if ( empty( $settings['enabled'] ) ) {
    return false;
  }
  if (
       ( class_exists( 'WC_Subscriptions_Cart' ) )
    && ( WC_Subscriptions_Cart::cart_contains_subscription() )
  ) {
    return false;
  }
  if (
       ( function_exists( 'wcs_cart_contains_renewal' ) )
    && ( wcs_cart_contains_renewal() )
  ) {
    return false;
  }

  return true;
}

/**
 *   START - fields
*/
  /**
   *   START - ORDER
  */
    function avx_waiver__add_custom_meta_data_to_order__avx_waiver( $order, $data ) {
      if ( ! avx_waiver__is_required() ) {
        return;
      }

      $values = avx_waiver__validate_fields( $_POST, true ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before this hook executes.
      if ( $values instanceof WP_Error ) {
        throw new Exception( esc_html( $values->get_error_message() ) );
      }

      $order->update_meta_data( '_avx_waiver-user_name', $values['user_name'] );
      $order->update_meta_data( '_avx_waiver-user_sign', $values['user_sign'] );
      $order->update_meta_data( '_avx_waiver-agree', $values['agree'] );
      if ( isset( $values['point'] ) ) {
        $order->update_meta_data( '_avx_waiver-point', $values['point'] );
      }
      $order->update_meta_data( '_avx_waiver-sign_date', date_i18n( 'd M Y H:i:s' ) );
    }

  // *  END  - ORDER

/* *  END  - fields





/**
 *   START - CHECKOUT
*/
  function avx_waiver__checkout__add__custom_field( $fields ) {

    if ( avx_waiver__is_required() ) {
      $fields['billing']['avx_waiver_checkout'] = array(
        'type'        => 'checkbox', // Or 'textarea', 'select', etc.
        'label'       => esc_html__( 'Sign Document', 'avx-waiver-consent-for-woocommerce' ),
        'description' => '',
        'required'    => true, // This makes the field required
        'class'       => array( 'form-row-wide' ), // Adjust styling as needed
        'clear'       => true,
      );
    }

    return $fields;
  }



  function avx_waiver__checkout__add__popup_btn__n__msg( $field, $key, $args, $value ) {

    if ( ( $key === 'avx_waiver_checkout' ) && avx_waiver__is_required() ) {
      $field = str_replace(
        '</p>',
          '<span class="textNote">'
            . esc_html__( 'Please read and sign the document before proceeding to place your order', 'avx-waiver-consent-for-woocommerce' )
            . ' '
            . '<span class="required" aria-hidden="true">*</span>'
        . '</span>'
        . '<span class="avx_waiverBtnRow">' // exactly span
          . '<button class="btn avx_waiverCheckoutBtn" type="button" data-popup=".avx_waiverPopup" data-text_sign="' . esc_attr( __( 'Sign Document', 'avx-waiver-consent-for-woocommerce' ) ) . '" data-text_signed="' . esc_attr( __( 'Document signed', 'avx-waiver-consent-for-woocommerce' ) ) . '">'
            . esc_html( __( 'Sign Document', 'avx-waiver-consent-for-woocommerce' ) )
          . '</button>'
        . '</span><!-- / avx_waiverBtnRow -->'
        . '</p>',
        $field
      );
    }

    return $field;

  } // function avx_waiver__checkout__add__popup_btn__n__msg( $field, $key, $args, $value ) { ... }



  function avx_waiver__checkout__validation__error_message( $fields, $errors ) {

    if ( ! avx_waiver__is_required() ) {
      return;
    }

    $values = avx_waiver__validate_fields( $_POST, true ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before this hook executes.
    if ( $values instanceof WP_Error ) {
      $errors->add( $values->get_error_code(), esc_html( $values->get_error_message() ) );
    }

  } // function avx_waiver__checkout__validation__error_message( $fields, $errors ) { ... }

// *  END  - CHECKOUT






/**
 *   START - ajax avx_waiver action
*/

  function avx_waiver_action__callback() {
    $nonce = avx_waiver__read_scalar( $_POST, 'avx_waiver_ajax_nonce' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This POST-only scalar check precedes check_ajax_referer() below.
    if ( $nonce !== null ) {
      // Make check_ajax_referer() verify the POST value even if another request source uses the same key.
      $_REQUEST['avx_waiver_ajax_nonce'] = $nonce;
    }
    if (
         ( $nonce === null )
      || ( false === check_ajax_referer( 'avx_waiver_ajax_action', 'avx_waiver_ajax_nonce', false ) )
    ) {
      wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'avx-waiver-consent-for-woocommerce' ) ), 403 );
    }

    if (
         ( ! isset( $_POST['avx_waiverData'] ) )
      || ( ! is_string( $_POST['avx_waiverData'] ) )
      || ( strlen( $_POST['avx_waiverData'] ) > 100000 ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Limit the raw string before unslashing and parsing; accepted fields are sanitized by avx_waiver__validate_fields().
    ) {
      wp_send_json_error( array( 'message' => __( 'Invalid document submission.', 'avx-waiver-consent-for-woocommerce' ) ), 400 );
    }

    $raw = wp_unslash( $_POST['avx_waiverData'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The query string is parsed below; every accepted field is sanitized by avx_waiver__validate_fields().
    if ( ( $raw === '' ) || ( strlen( $raw ) > 50000 ) ) {
      wp_send_json_error( array( 'message' => __( 'Invalid document submission.', 'avx-waiver-consent-for-woocommerce' ) ), 400 );
    }
    parse_str( $raw, $submitted );
    if (
         ( ! isset( $submitted['avx_waiver'] ) )
      || ( ! is_array( $submitted['avx_waiver'] ) )
    ) {
      wp_send_json_error( array( 'message' => __( 'Invalid document submission.', 'avx-waiver-consent-for-woocommerce' ) ), 400 );
    }

    $values = avx_waiver__validate_fields( $submitted['avx_waiver'] );
    if ( $values instanceof WP_Error ) {
      wp_send_json_error( array( 'message' => $values->get_error_message() ), 400 );
    }

    wp_send_json_success( $values );

  } // function avx_waiver_action__callback() { ... }

// *  END  - ajax avx_waiver action








function avx_waiver__should_display() {
  if ( ( ! class_exists( 'WooCommerce' ) ) || ( is_admin() ) ) {
    return false;
  }

  if (
       ( ! is_checkout() )
    || ( is_cart() )
    || ( is_product() )
    || ( is_order_received_page() )
    || ( is_checkout_pay_page() )
    || ( has_block( 'woocommerce/checkout' ) )
  ) {
    return false;
  }

  // Also exclude Checkout blocks supplied by a saved block-theme checkout template.
  if (
       ( class_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) )
    && ( \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default() )
  ) {
    return false;
  }

  return avx_waiver__is_required();
}

function avx_waiver__enqueue_assets() {
  if ( ! avx_waiver__should_display() ) {
    return;
  }

  $min_suffix = '.min';
  $plugin_directory_uri = plugin_dir_url(AVX_WAIVER_FILE);

  $css__plugin__avx_waiver = 'assets/css/avx_waiver' . $min_suffix . '.css';
  wp_enqueue_style( 'avx_waiver', $plugin_directory_uri . $css__plugin__avx_waiver, array(), AVX_WAIVER_VERSION, 'all');

  $js__plugin__avx_waiver = 'assets/js/avx_waiver' . $min_suffix . '.js';
  wp_enqueue_script( 'avx_waiver', $plugin_directory_uri . $js__plugin__avx_waiver, array( 'jquery' ), AVX_WAIVER_VERSION, true );
  wp_localize_script( 'avx_waiver', 'AVXWaiver', array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'avx_waiver_ajax_action' ),
    'messages' => array(
      'required'                => __( 'Required', 'avx-waiver-consent-for-woocommerce' ),
      'namesMismatch'           => __( 'Names mismatch', 'avx-waiver-consent-for-woocommerce' ),
      'invalidDocumentResponse' => __( 'Invalid document response', 'avx-waiver-consent-for-woocommerce' ),
      'error'                   => __( 'Error', 'avx-waiver-consent-for-woocommerce' ),
    ),
  ) );
}

function avx_waiver__footer() {

  if ( avx_waiver__should_display() ) {

    $settings = avx_waiver__get_settings();
    $avx_waiver_content = array(
      'hl'          => $settings['waiver_heading'] !== '' ? $settings['waiver_heading'] : __( 'Waiver and Consent', 'avx-waiver-consent-for-woocommerce' ),
      'text_before' => $settings['introductory_text'],
      'options'     => array(),
      'agree_text'  => $settings['agreement_text'],
    );
    foreach ( $settings['clauses'] as $clause ) {
      if ( ( $clause['title'] === '' ) && ( $clause['text'] === '' ) ) {
        continue;
      }
      $avx_waiver_content['options'][] = array(
        'point'       => $clause['title'],
        'sub_options' => $clause['text'] === '' ? array() : array( array(
          'optional' => $clause['optional'],
          'option'   => $clause['text'],
        ) ),
      );
    }
    $participant_name_label = $settings['participant_name_label'] !== '' ? $settings['participant_name_label'] : __( 'Participant Name', 'avx-waiver-consent-for-woocommerce' );
    $signature_label = $settings['signature_label'] !== '' ? $settings['signature_label'] : __( 'Participant Signature', 'avx-waiver-consent-for-woocommerce' );
    $button_label = $settings['button_label'] !== '' ? $settings['button_label'] : __( 'Sign', 'avx-waiver-consent-for-woocommerce' );
    $svg_allowed_html = array(
      'svg'  => array( 'class' => true, 'width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'xmlns' => true ),
      'path' => array( 'd' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
    );

    if ( !empty( $settings['enabled'] ) ) {

      echo '<div class="avx_waiverPopup'
        // . ' popupShow'
      . '">';
        echo '<div class="containerWrapper">';
          echo '<div class="container">';

            echo '<div class="content">';

              echo '<button class="avx_waiverPopup__closeBtn" type="button" aria-label="' . esc_attr__( 'Close waiver popup', 'avx-waiver-consent-for-woocommerce' ) . '" data-popup=".avx_waiverPopup">' . wp_kses( AVX_WAIVER_ICON_CROSS, $svg_allowed_html ) . '</button>';

              echo '<form class="avx_waiverForm">';

                echo '<div class="secHlWrapper">';

                  if ( $avx_waiver_content['hl'] !== '' ) {
                    echo '<h2 class="secHl">';
                      echo esc_html( $avx_waiver_content['hl'] );
                    echo '</h2>';
                  } // if ( $avx_waiver_content['hl'] !== '' ) { ... }

                  echo '<div class="avx_waiverData">';

                    // Submitted fields are validated by avx_waiver__validate_fields().
                    // The saved signature date is generated on the server.

                    echo '<div class="avx_waiverData__item avx_waiverData__uname">';
                      echo '<b>';
                        echo esc_html( $participant_name_label );
                        echo ' ';
                        echo '<span class="asterisk">*</span>';
                        echo  ': ';
                      echo '</b>';
                      echo '<div class="avx_waiverData__unameInputWrapper">';
                        echo '<input class="avx_waiverData__unameInput avx_waiverData__unameInput--name" type="text" name="avx_waiver[user_name]" value="" placeholder="' . esc_attr( __( 'Full name', 'avx-waiver-consent-for-woocommerce' ) ) . '" maxlength="200" required />';
                        echo '<div class="avx_waiverData__unameInputError"></div>';
                      echo '</div>';
                    echo '</div><!-- / avx_waiverData__item avx_waiverData__uname -->';

                  echo '</div><!-- / avx_waiverData -->';

                  if ( $avx_waiver_content['text_before'] !== '' ) {
                    echo '<div class="avx_waiverCommonText">';
                      echo '<div class="wysiwyg">' . nl2br( esc_html( $avx_waiver_content['text_before'] ) ) . '</div>';
                    echo '</div><!-- / avx_waiverCommonText -->';
                  }

                echo '</div><!-- secHlWrapper -->';


                  if ( !empty( $avx_waiver_content['options'] ) ) {
                  echo '<ol class="avx_waiverList">';
                    foreach ( $avx_waiver_content['options'] as $avx_waiver_content_ind => $w_option_li ) {

                      echo '<li class="avx_waiverList__item">';

                        if ( $w_option_li['point'] !== '' ) {
                          echo '<div class="avx_waiverList__itemPoint">';
                            echo esc_html( $w_option_li['point'] );
                          echo '</div>';
                        } // if ( $w_option_li['point'] !== '' ) { ... }

                        if ( !empty( $w_option_li['sub_options'] ) ) {
                          echo '<ul class="avx_waiverListOptions">';
                            foreach ( $w_option_li['sub_options'] as $w_option_li_ind => $sub_option ) {
                              $sub_option_optional = ( (!empty( $sub_option['optional'] )) ? $sub_option['optional'] : '' );

                              if ( $sub_option['option'] !== '' ) {
                                echo '<li class="avx_waiverListOptions__item">';
                                  if ( !empty( $sub_option_optional ) ) {
                                    echo '<input id="avx_waiver_point_' . esc_attr( ($avx_waiver_content_ind+1) . '_' . ($w_option_li_ind+1) ) . '" class="avx_waiverCheckboxInput" type="checkbox" name="avx_waiver[point][]" value="'  . esc_attr( $w_option_li['point'] . ' - ' . $sub_option['option'] ) . '">';

                                    echo '<label for="avx_waiver_point_' . esc_attr( ($avx_waiver_content_ind+1) . '_' . ($w_option_li_ind+1) ) . '" class="avx_waiverCheckbox">';
                                      echo wp_kses( AVX_WAIVER_ICON_CROSS, $svg_allowed_html );
                                    echo '</label>';

                                    echo '<label for="avx_waiver_point_' . esc_attr( ($avx_waiver_content_ind+1) . '_' . ($w_option_li_ind+1) ) . '" class="avx_waiverCheckboxLabel">';
                                      echo nl2br( esc_html( $sub_option['option'] ) );
                                    echo '</label>';
                                  }
                                  else {
                                    echo '<span class="avx_waiverCheckbox">' . wp_kses( AVX_WAIVER_ICON_CROSS, $svg_allowed_html ) . '</span>';
                                    echo nl2br( esc_html( $sub_option['option'] ) );
                                  }
                                echo '</li>';
                              } // if ( $sub_option['option'] !== '' ) { ... }

                            } // foreach ( $w_option_li['sub_options'] as $sub_option ) { ... }
                          echo '</ul><!-- / avx_waiverList__itemOptions -->';
                        } // if ( !empty( $w_option_li['sub_options'] ) ) { ... }
                      echo '</li><!-- / avx_waiverList__item -->';

                    } // foreach ( $avx_waiver_content['options'] as $avx_waiver_content_ind => $w_option_li ) { ... }
                  echo '</ol><!-- / avx_waiverList -->';
                } // if ( !empty( $avx_waiver_content['options'] ) ) { ... }


                echo '<div class="avx_waiverData">';
                  echo '<div class="avx_waiverData__item avx_waiverData__item--signature">';
                    echo '<div class="avx_waiverData__itemInner">';
                      echo '<b>';
                        echo esc_html( $signature_label );
                          echo ' ';
                          echo '<span class="asterisk">*</span>';
                          echo  ': ';
                      echo '</b>';
                      echo '<div class="avx_waiverData__unameInputWrapper">';
                        echo '<input class="avx_waiverData__unameInput avx_waiverData__unameInput--sign" type="text" name="avx_waiver[user_sign]" value="" placeholder="' . esc_attr( __( 'Full name', 'avx-waiver-consent-for-woocommerce' ) ) . '" maxlength="200" required />';
                        echo '<div class="avx_waiverData__unameInputError"></div>';
                      echo '</div>';
                    echo '</div><!-- / avx_waiverData__itemInner -->';
                  echo '</div>';

                  echo '<div class="avx_waiverData__item avx_waiverData__item--date">';
                    echo '<div class="avx_waiverData__itemInner">';
                      echo '<b>';
                        echo esc_html( __( 'Date', 'avx-waiver-consent-for-woocommerce' ) ) . ': ';
                      echo '</b>';
                      echo esc_html( date_i18n( 'd M Y' ) );
                      echo '<input class="avx_waiverData__unameInput avx_waiverData__unameInput--date" type="hidden" name="avx_waiver[sign_date]" value="' . esc_attr( date_i18n( 'd M Y H:i:s' ) ) . '" />';
                    echo '</div><!-- / avx_waiverData__itemInner -->';
                  echo '</div>';

                  echo '<div class="avx_waiverData__item avx_waiverData__item--agree">';
                    echo '<div class="avx_waiverData__itemInner">';
                      echo '<input id="avx_waiverAgreeCheckbox" class="avx_waiverCheckboxInput avx_waiverCheckboxInput--agree" type="checkbox" name="avx_waiver[agree]" value="1" required />';

                      echo '<label class="avx_waiverCheckbox avx_waiverCheckbox--agree" for="avx_waiverAgreeCheckbox">';
                        echo wp_kses( AVX_WAIVER_ICON_CROSS, $svg_allowed_html );
                      echo '</label>';

                      echo '<label class="avx_waiverCheckboxLabel avx_waiverCheckboxLabel--agree" for="avx_waiverAgreeCheckbox">';
                        echo '<span class="asterisk">*</span>';
                        echo ' ';
                        if ( $avx_waiver_content['agree_text'] !== '' ) {
                          echo nl2br( esc_html( $avx_waiver_content['agree_text'] ) );
                        }
                        else {
                          echo esc_html__( 'I agree to electronically sign and be legally bound by this document.', 'avx-waiver-consent-for-woocommerce' );
                        }
                      echo '</label>';
                    echo '</div><!-- / avx_waiverData__itemInner -->';
                    echo '<div class="avx_waiverData__checkboxError"></div>';
                  echo '</div>';
                echo '</div><!-- / avx_waiverData -->';


                echo '<div class="avx_waiverErrorNote"></div>';

                echo '<div class="avx_waiverBtnRow">';
                  echo '<button class="btn avx_waiverSubmitBtn" type="button">';
                    echo esc_html( $button_label );
                  echo '</button>';
                echo '</div>';

              echo '</form><!-- / avx_waiverForm -->';

            echo '</div><!-- END content -->';


            echo '<button class="avx_waiverPopup__closeBtnOverlay" type="button" aria-label="' . esc_attr__( 'Close waiver popup', 'avx-waiver-consent-for-woocommerce' ) . '" data-popup=".avx_waiverPopup"></button>';

          echo '</div><!-- / container -->';
        echo '</div><!-- / containerWrapper -->';
      echo '</div><!-- END avx_waiverPopup -->';


    } // if ( !empty( $settings['enabled'] ) ) { ... }

  } // if ( avx_waiver__should_display() ) { ... }

} // function avx_waiver__footer() { ... }
