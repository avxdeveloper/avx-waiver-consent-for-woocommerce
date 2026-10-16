<?php
defined( 'ABSPATH' ) || exit;

function avx_waiver__default_settings() {
  return array(
    'enabled'                => 0,
    'waiver_heading'         => __( 'Waiver and Consent', 'avx-waiver-consent-for-woocommerce' ),
    'participant_name_label' => __( 'Participant Name', 'avx-waiver-consent-for-woocommerce' ),
    'introductory_text'      => '',
    'clauses'                => array_fill( 0, 5, array( 'title' => '', 'text' => '', 'optional' => 0 ) ),
    'agreement_text'         => __( 'I agree to electronically sign and be legally bound by this document.', 'avx-waiver-consent-for-woocommerce' ),
    'signature_label'        => __( 'Participant Signature', 'avx-waiver-consent-for-woocommerce' ),
    'button_label'           => __( 'Sign', 'avx-waiver-consent-for-woocommerce' ),
  );
}

function avx_waiver__sanitize_setting_text( $value, $limit, $multiline = false ) {
  if ( ! is_string( $value ) ) {
    return '';
  }

  $value = ( $multiline ? sanitize_textarea_field( $value ) : sanitize_text_field( $value ) );
  if ( preg_match_all( '/./us', $value, $characters ) === false ) {
    return '';
  }

  return implode( '', array_slice( $characters[0], 0, $limit ) );
}

function avx_waiver__sanitize_settings( $input ) {
  $defaults = avx_waiver__default_settings();
  $input = ( is_array( $input ) ? $input : array() );
  $settings = $defaults;

  $settings['enabled'] = ( isset( $input['enabled'] ) && ( ( $input['enabled'] === '1' ) || ( $input['enabled'] === 1 ) ) ) ? 1 : 0;
  $limits = array(
    'waiver_heading'         => 100,
    'participant_name_label' => 80,
    'introductory_text'      => 300,
    'agreement_text'         => 250,
    'signature_label'        => 80,
    'button_label'           => 40,
  );
  foreach ( $limits as $key => $limit ) {
    if ( array_key_exists( $key, $input ) ) {
      $multiline = in_array( $key, array( 'introductory_text', 'agreement_text' ), true );
      $settings[ $key ] = avx_waiver__sanitize_setting_text( $input[ $key ], $limit, $multiline );
    }
  }

  $submitted_clauses = ( isset( $input['clauses'] ) && is_array( $input['clauses'] ) ) ? $input['clauses'] : array();
  for ( $index = 0; $index < 5; $index++ ) {
    $row = ( isset( $submitted_clauses[ $index ] ) && is_array( $submitted_clauses[ $index ] ) ) ? $submitted_clauses[ $index ] : array();
    $settings['clauses'][ $index ] = array(
      'title'    => avx_waiver__sanitize_setting_text( $row['title'] ?? '', 50 ),
      'text'     => avx_waiver__sanitize_setting_text( $row['text'] ?? '', 500, true ),
      'optional' => ( isset( $row['optional'] ) && ( ( $row['optional'] === '1' ) || ( $row['optional'] === 1 ) ) ) ? 1 : 0,
    );
  }

  $has_clause = false;
  foreach ( $settings['clauses'] as $clause ) {
    if ( ( $clause['title'] !== '' ) || ( $clause['text'] !== '' ) ) {
      $has_clause = true;
      break;
    }
  }
  if ( ( $settings['enabled'] === 1 ) && ! $has_clause ) {
    $settings['enabled'] = 0;
  }

  return $settings;
}

function avx_waiver__sanitize_settings_for_save( $input ) {
  $settings = avx_waiver__sanitize_settings( $input );
  if (
       is_array( $input )
    && isset( $input['enabled'] )
    && ( ( $input['enabled'] === '1' ) || ( $input['enabled'] === 1 ) )
    && ( $settings['enabled'] === 0 )
  ) {
    add_settings_error(
      'avx_waiver_settings',
      'avx_waiver_clause_required',
      __( 'Enter a title or text in at least one clause before enabling the waiver. The other settings were saved, but the waiver remains disabled.', 'avx-waiver-consent-for-woocommerce' ),
      'error'
    );
  }

  return $settings;
}

function avx_waiver__get_settings() {
  return avx_waiver__sanitize_settings( get_option( 'avx_waiver_settings', array() ) );
}

function avx_waiver__register_admin_hooks() {
  add_action( 'admin_menu', 'avx_waiver__add_settings_page', 20 );
  add_action( 'admin_init', 'avx_waiver__register_settings' );
  add_action( 'add_meta_boxes', 'avx_waiver__add_order_metabox', 10, 2 );
  add_filter( 'plugin_action_links_' . plugin_basename( AVX_WAIVER_FILE ), 'avx_waiver__add_settings_link' );
}

function avx_waiver__add_settings_link( $links ) {
  if ( ! current_user_can( 'manage_options' ) ) {
    return $links;
  }

  $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=avx-waiver-settings' ) ) . '">' . esc_html__( 'Settings', 'avx-waiver-consent-for-woocommerce' ) . '</a>';
  return array( 'avx_waiver_settings' => $settings_link ) + $links;
}

function avx_waiver__add_settings_page() {
  add_submenu_page(
    'woocommerce',
    __( 'AVX Waiver', 'avx-waiver-consent-for-woocommerce' ),
    __( 'AVX Waiver', 'avx-waiver-consent-for-woocommerce' ),
    'manage_options',
    'avx-waiver-settings',
    'avx_waiver__render_settings_page'
  );
}

function avx_waiver__register_settings() {
  register_setting( 'avx_waiver_settings_group', 'avx_waiver_settings', array(
    'sanitize_callback' => 'avx_waiver__sanitize_settings_for_save',
  ) );
}

function avx_waiver__render_settings_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  $settings = avx_waiver__get_settings();
  ?>
  <div class="wrap">
    <h1><?php echo esc_html__( 'AVX Waiver', 'avx-waiver-consent-for-woocommerce' ); ?></h1>
    <?php settings_errors(); ?>
    <form method="post" action="options.php">
      <?php settings_fields( 'avx_waiver_settings_group' ); ?>
      <table class="form-table" role="presentation">
        <tbody>
          <tr>
            <th scope="row"><?php echo esc_html__( 'Enable waiver', 'avx-waiver-consent-for-woocommerce' ); ?></th>
            <td>
              <label><input type="checkbox" name="avx_waiver_settings[enabled]" value="1" <?php checked( $settings['enabled'], 1 ); ?>> <?php echo esc_html__( 'Yes', 'avx-waiver-consent-for-woocommerce' ); ?></label>
              <p class="description"><?php echo esc_html__( 'Show and require the waiver on Classic Checkout.', 'avx-waiver-consent-for-woocommerce' ); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="avx_waiver_heading"><?php echo esc_html__( 'Waiver heading', 'avx-waiver-consent-for-woocommerce' ); ?></label></th>
            <td><input id="avx_waiver_heading" class="regular-text" type="text" name="avx_waiver_settings[waiver_heading]" value="<?php echo esc_attr( $settings['waiver_heading'] ); ?>" maxlength="100"><p class="description"><?php echo esc_html__( 'Maximum 100 characters.', 'avx-waiver-consent-for-woocommerce' ); ?></p></td>
          </tr>
          <tr>
            <th scope="row"><label for="avx_waiver_name_label"><?php echo esc_html__( 'Participant name label', 'avx-waiver-consent-for-woocommerce' ); ?></label></th>
            <td><input id="avx_waiver_name_label" class="regular-text" type="text" name="avx_waiver_settings[participant_name_label]" value="<?php echo esc_attr( $settings['participant_name_label'] ); ?>" maxlength="80"><p class="description"><?php echo esc_html__( 'Maximum 80 characters.', 'avx-waiver-consent-for-woocommerce' ); ?></p></td>
          </tr>
          <tr>
            <th scope="row"><label for="avx_waiver_intro"><?php echo esc_html__( 'Introductory text', 'avx-waiver-consent-for-woocommerce' ); ?></label></th>
            <td><textarea id="avx_waiver_intro" class="large-text" rows="3" name="avx_waiver_settings[introductory_text]" maxlength="300"><?php echo esc_textarea( $settings['introductory_text'] ); ?></textarea><p class="description"><?php echo esc_html__( 'Maximum 300 characters.', 'avx-waiver-consent-for-woocommerce' ); ?></p></td>
          </tr>
        </tbody>
      </table>

      <h2><?php echo esc_html__( 'Clauses', 'avx-waiver-consent-for-woocommerce' ); ?></h2>
      <p class="description"><?php echo esc_html__( 'To enable the waiver, enter a title or text in at least one clause.', 'avx-waiver-consent-for-woocommerce' ); ?></p>
      <?php for ( $index = 0; $index < 5; $index++ ) : ?>
        <?php $clause = $settings['clauses'][ $index ]; ?>
        <h3><?php
        /* translators: %d: waiver clause number. */
        echo esc_html( sprintf( __( 'Clause %d', 'avx-waiver-consent-for-woocommerce' ), $index + 1 ) );
        ?></h3>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row"><label for="avx_waiver_clause_title_<?php echo esc_attr( $index ); ?>"><?php echo esc_html__( 'Title', 'avx-waiver-consent-for-woocommerce' ); ?></label></th>
              <td><input id="avx_waiver_clause_title_<?php echo esc_attr( $index ); ?>" class="regular-text" type="text" name="avx_waiver_settings[clauses][<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $clause['title'] ); ?>" maxlength="50"><p class="description"><?php echo esc_html__( 'Maximum 50 characters.', 'avx-waiver-consent-for-woocommerce' ); ?></p></td>
            </tr>
            <tr>
              <th scope="row"><label for="avx_waiver_clause_text_<?php echo esc_attr( $index ); ?>"><?php echo esc_html__( 'Text', 'avx-waiver-consent-for-woocommerce' ); ?></label></th>
              <td><textarea id="avx_waiver_clause_text_<?php echo esc_attr( $index ); ?>" class="large-text" rows="4" name="avx_waiver_settings[clauses][<?php echo esc_attr( $index ); ?>][text]" maxlength="500"><?php echo esc_textarea( $clause['text'] ); ?></textarea><p class="description"><?php echo esc_html__( 'Maximum 500 characters.', 'avx-waiver-consent-for-woocommerce' ); ?></p></td>
            </tr>
            <tr>
              <th scope="row"><?php echo esc_html__( 'Separate optional consent', 'avx-waiver-consent-for-woocommerce' ); ?></th>
              <td><label><input type="checkbox" name="avx_waiver_settings[clauses][<?php echo esc_attr( $index ); ?>][optional]" value="1" <?php checked( $clause['optional'], 1 ); ?>> <?php echo esc_html__( 'Allow the customer to choose this clause separately.', 'avx-waiver-consent-for-woocommerce' ); ?></label><p class="description"><?php echo esc_html__( 'Leave unchecked to include this clause in the mandatory document accepted by the final signature.', 'avx-waiver-consent-for-woocommerce' ); ?></p></td>
            </tr>
          </tbody>
        </table>
      <?php endfor; ?>

      <table class="form-table" role="presentation">
        <tbody>
          <tr>
            <th scope="row"><label for="avx_waiver_agreement"><?php echo esc_html__( 'Agreement text', 'avx-waiver-consent-for-woocommerce' ); ?></label></th>
            <td><textarea id="avx_waiver_agreement" class="large-text" rows="3" name="avx_waiver_settings[agreement_text]" maxlength="250"><?php echo esc_textarea( $settings['agreement_text'] ); ?></textarea><p class="description"><?php echo esc_html__( 'Maximum 250 characters.', 'avx-waiver-consent-for-woocommerce' ); ?></p></td>
          </tr>
          <tr>
            <th scope="row"><label for="avx_waiver_signature_label"><?php echo esc_html__( 'Signature label', 'avx-waiver-consent-for-woocommerce' ); ?></label></th>
            <td><input id="avx_waiver_signature_label" class="regular-text" type="text" name="avx_waiver_settings[signature_label]" value="<?php echo esc_attr( $settings['signature_label'] ); ?>" maxlength="80"><p class="description"><?php echo esc_html__( 'Maximum 80 characters.', 'avx-waiver-consent-for-woocommerce' ); ?></p></td>
          </tr>
          <tr>
            <th scope="row"><label for="avx_waiver_button_label"><?php echo esc_html__( 'Popup button label', 'avx-waiver-consent-for-woocommerce' ); ?></label></th>
            <td><input id="avx_waiver_button_label" class="regular-text" type="text" name="avx_waiver_settings[button_label]" value="<?php echo esc_attr( $settings['button_label'] ); ?>" maxlength="40"><p class="description"><?php echo esc_html__( 'Maximum 40 characters.', 'avx-waiver-consent-for-woocommerce' ); ?></p></td>
          </tr>
        </tbody>
      </table>
      <?php submit_button(); ?>
    </form>

    <hr>
    <h2><?php echo esc_html__( 'Need additional functionality?', 'avx-waiver-consent-for-woocommerce' ); ?></h2>
    <p><?php echo esc_html__( 'Product- or category-specific waiver rules, document evidence extensions, email/account output, Checkout Block support, and other WooCommerce integrations are available as custom development.', 'avx-waiver-consent-for-woocommerce' ); ?></p>
    <p><a href="<?php echo esc_url( 'mailto:avxplugins@gmail.com' ); ?>"><?php echo esc_html__( 'Contact the developer.', 'avx-waiver-consent-for-woocommerce' ); ?></a></p>
    <p class="description"><?php echo esc_html__( 'This plugin helps collect and record consent. It does not provide legal advice or guarantee that a waiver is legally enforceable.', 'avx-waiver-consent-for-woocommerce' ); ?></p>
  </div>
  <?php
}

function avx_waiver__order_meta_value( $order, $key ) {
  $value = $order->get_meta( '_' . $key );
  if ( $value === '' ) {
    $value = $order->get_meta( $key );
  }

  return ( is_scalar( $value ) ? (string) $value : '' );
}

function avx_waiver__add_order_metabox( $screen_id, $post_or_order ) {
  if (
       ( $screen_id !== 'shop_order' )
    && ( $screen_id !== wc_get_page_screen_id( 'shop-order' ) )
  ) {
    return;
  }

  $order = ( $post_or_order instanceof WC_Order ) ? $post_or_order : wc_get_order( $post_or_order instanceof WP_Post ? $post_or_order->ID : $post_or_order );
  if ( ! $order ) {
    return;
  }

  $keys = array( 'avx_waiver-user_name', 'avx_waiver-user_sign', 'avx_waiver-sign_date', 'avx_waiver-agree' );
  foreach ( $keys as $key ) {
    if ( avx_waiver__order_meta_value( $order, $key ) !== '' ) {
      add_meta_box( 'avx-waiver-order', __( 'AVX Waiver', 'avx-waiver-consent-for-woocommerce' ), 'avx_waiver__render_order_metabox', $screen_id, 'side', 'default' );
      return;
    }
  }
}

function avx_waiver__render_order_metabox( $post_or_order ) {
  $order = ( $post_or_order instanceof WC_Order ) ? $post_or_order : wc_get_order( $post_or_order instanceof WP_Post ? $post_or_order->ID : $post_or_order );
  if ( ! $order ) {
    return;
  }

  $labels = array(
    'avx_waiver-user_name' => __( 'Participant name', 'avx-waiver-consent-for-woocommerce' ),
    'avx_waiver-user_sign' => __( 'Typed signature', 'avx-waiver-consent-for-woocommerce' ),
    'avx_waiver-sign_date' => __( 'Signature date', 'avx-waiver-consent-for-woocommerce' ),
  );
  foreach ( $labels as $key => $label ) {
    $value = avx_waiver__order_meta_value( $order, $key );
    if ( $value !== '' ) {
      echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
    }
  }
  if ( avx_waiver__order_meta_value( $order, 'avx_waiver-agree' ) === '1' ) {
    echo '<p><strong>' . esc_html__( 'Agreement', 'avx-waiver-consent-for-woocommerce' ) . ':</strong> ' . esc_html__( 'Yes', 'avx-waiver-consent-for-woocommerce' ) . '</p>';
  }
}
