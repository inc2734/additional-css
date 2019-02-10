<?php
/*
Plugin name: Additional CSS
*/

/**
 * Additional CSS to the block editor
 */
add_action( 'enqueue_block_editor_assets', 'ac_enqueue_block_editor_assets_admin_head' );

function ac_enqueue_block_editor_assets_admin_head() {
	add_action( 'admin_head', 'ac_additional_css_to_block_editor' );
}

function ac_additional_css_to_block_editor() {
	$css = wp_get_custom_css();
	$css_to_array = new AC_CSS_To_Array( $css );
	$css = $css_to_array->get();

	foreach ( $css as $key => $css_block ) {
		$selectors = $css_block->get_selectors();
		foreach ( $selectors as $i => $selector ) {
			$selectors[ $i ] = '.editor-styles-wrapper ' . $selector;
		}
		$css_block->set_selectors( $selectors );
		$css[ $key ] = $css_block;
	}

	$new_css = '';
	foreach ( $css as $key => $css_block ) {
		$new_css .= $css_block->get_inline_css();
	}
	?>
	<style id="wp-additional-css">
	<?php echo strip_tags( $new_css ); // WPCS XSS ok. ?>
	</style>
	<?php
}

/**
 * Additional CSS to the classic editor
 */
add_filter( 'tiny_mce_before_init', 'ac_additional_css_to_classic_editor' );

function ac_additional_css_to_classic_editor( $mce_init ) {
	$css = wp_get_custom_css();
	$css_to_array = new AC_CSS_To_Array( $css );
	$css = $css_to_array->get();

	foreach ( $css as $key => $css_block ) {
		$selectors = $css_block->get_selectors();
		foreach ( $selectors as $i => $selector ) {
			$selectors[ $i ] = $selector;
		}
		$css_block->set_selectors( $selectors );
		$css[ $key ] = $css_block;
	}

	$new_css = '';
	foreach ( $css as $key => $css_block ) {
		$new_css .= $css_block->get_inline_css();
	}

	if ( ! isset( $mce_init['content_style'] ) ) {
		$mce_init['content_style'] = '';
	}

	$mce_init['content_style'] .= $new_css;
	return $mce_init;
}

class AC_CSS_To_Array {

	/**
	 * CSS
	 *
	 * @var string
	 */
	protected $css;

	/**
	 * @param string $css
	 */
	public function __construct( $css ) {
		$this->css = $this->_convert( $css );
	}

	/**
	 * @return string
	 */
	public function get() {
		return $this->css;
	}

	/**
	 * Convert inline css to PHP array
	 *
	 * @param string $css
	 * @return array
	 */
	protected function _convert( $css ) {
		$css = $this->_clean( $css );
		$css = explode( '}', $css );
		$css = array_filter( $css, 'strlen' );

		foreach ( $css as $key => $val ) {
			$css[ $key ] = explode( '{', $val );
		}

		foreach ( $css as $key => $css_block ) {
			$css[ $key ] = $this->_create_css_block( $css_block );
		}

		return $css;
	}

	/**
	 * Create CSS_Block
	 *
	 * @param array $css_block
	 * @return AC_CSS_Block
	 */
	protected function _create_css_block( $css_block ) {
		if ( $this->_has_pre_selector( $css_block ) ) {
			$css_block     = array_reverse( $css_block );
			$selectors     = explode( ',', $css_block[1] );
			$properties    = $css_block[0];
			$pre_selectors = array_slice( $css_block, 2 );
		} else {
			$selectors     = explode( ',', $css_block[0] );
			$properties    = $css_block[1];
			$pre_selectors = [];
		}
		return new AC_CSS_Block( $selectors, $properties, $pre_selectors );
	}

	/**
	 * Return true when has pre selector
	 *
	 * @param array $css_block
	 * @return boolean
	 */
	protected function _has_pre_selector( $css_block ) {
		return 2 < count( $css_block );
	}

	/**
	 * Remove tab and line breaks
	 *
	 * @param string $value
	 * @return string
	 */
	protected function _clean( $value ) {
		return str_replace( [ "\t", "\n", "\r" ], '', $value );
	}
}

class AC_CSS_Block {

	/**
	 * Selectors
	 *
	 * @var array
	 */
	protected $selectors = [];

	/**
	 * CSS properties
	 *
	 * @var string
	 */
	protected $properties;

	/**
	 * Pre selectors
	 *
	 * @var array
	 */
	protected $pre_selectors = [];

	/**
	 * @param array $selectors
	 * @param string $properties
	 * @param array $pre_selectors
	 */
	public function __construct( array $selectors, $properties, array $pre_selectors = [] ) {
		$this->selectors     = $selectors;
		$this->properties    = $properties;
		$this->pre_selectors = $pre_selectors;
	}

	/**
	 * Return inline CSS
	 *
	 * @return string
	 */
	public function get_inline_css() {
		if ( $this->get_pre_selectors() ) {
			$inline_css = sprintf(
				'%1$s { %2$s }',
				implode( ',', $this->get_selectors() ),
				$this->get_properties()
			);
			foreach ( $this->get_pre_selectors() as $pre_selector ) {
				$inline_css = sprintf(
					'%1$s { %2$s }',
					$pre_selector,
					$inline_css
				);
			}
			return $inline_css;
		}

		return sprintf(
			'%1$s { %2$s }',
			implode( ',', $this->get_selectors() ),
			$this->get_properties()
		);
	}

	/**
	 * Return selectors
	 *
	 * @return array
	 */
	public function get_selectors() {
		return $this->selectors;
	}

	/**
	 * Return CSS properties
	 *
	 * @return string
	 */
	public function get_properties() {
		return $this->properties;
	}

	/**
	 * Return pre selectors
	 *
	 * @return array
	 */
	public function get_pre_selectors() {
		return $this->pre_selectors;
	}

	/**
	 * Set selectors
	 *
	 * @param array
	 */
	public function set_selectors( array $selectors ) {
		$this->selectors = $selectors;
	}
}
