<?php defined( 'ABSPATH' ) or die; ?>
<div class="wrap wrap-pdflio">
	<h1><?php _e( 'pdfl.io - Options', 'pdflio' ); ?></h1>
	<form method="post" action="options.php">
		<?php settings_errors(); ?>
		<?php settings_fields( 'pdflio_optsgroup' ); ?>
		<?php do_settings_sections( 'pdflio_optsgroup' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'API Key', 'pdflio' ); ?></th>
				<td class="sub-row">
					<input type="text" class="regular-text" name="pdflio_options[api_key]" value="<?php esc_attr_e( $this->get_option( 'api_key' ) );; ?>" required />
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<a href="<?php esc_attr_e( menu_page_url( 'pdflio-help', false ) ); ?>"><?php _e( 'How to use it' ); ?></a>
</div>