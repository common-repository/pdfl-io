<?php defined( 'ABSPATH' ) or die; ?>
<div class="wrap wrap-pdflio-help">
	<h1><?php _e( 'pdfl.io - Help', 'pdflio' ); ?></h1>
	<div>
		<h2><?php _e( 'Plugin usage', 'pdflio' ); ?></h2>
		<p><?php echo sprintf( __( 'Use the shortcode %s[pdflio]%s, it admits the following parameters:', 'pdflio' ), '<strong>', '</strong>' ); ?><br>
			<?php echo sprintf( __( '%sfilename%s: The PDF filename (default is file.pdf)', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%stext%s: the link anchor (default is "Download")', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%surl%s: the URL to be converted to PDF (default is current URL)', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%sformat%s: the PDF page size (default is Letter)', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%sno_background%s: may be \'y\'', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%sgreyscale%s: may be \'y\'', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%sgreyscale%s: may be \'y\'', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%stop_view_only%s: may be \'y\'', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%sdisable_javascript%s: may be \'y\'', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%sdisable_images%s: may be \'y\'', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%sjust_wait%s: may be \'y\'', 'pdflio' ), '<em>', '</em>' ); ?><br>
			<?php echo sprintf( __( '%sdelay%s: may be \'y\'', 'pdflio' ), '<em>', '</em>' ); ?><br>
		</p>
	</div>
	<a class="button button-secondary" href="<?php esc_attr_e( menu_page_url( 'pdflio', false ) ); ?>"><?php _e( 'Go back' ); ?></a>
</div>