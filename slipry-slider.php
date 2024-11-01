<?php
/*
Plugin Name: Slipry Slider
Plugin URI: https://tishonator.com/plugins/slipry-slider
Description: Configure slideshows (titles, texts, and images) in minutes and display them as a Responsive Slider in your website by inserting a shortcode.
Author: tishonator
Version: 1.0.1
Author URI: http://tishonator.com/
Contributors: tishonator
Text Domain: slipry-slider
*/

if ( !class_exists('SliprySliderPlugin') ) :

    /**
     * Register the plugin.
     *
     * Display the administration panel, insert JavaScript etc.
     */
    class SliprySliderPlugin {

    	protected static $instance = NULL;

        private $settings = array();

        public function __construct() {}

        public function setup() {

            if ( class_exists('SliprySliderProPlugin') )
                return;

            register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

            if ( is_admin() ) { // admin actions

                add_action('admin_menu', array(&$this, 'add_admin_page'));
                add_action('admin_enqueue_scripts', array(&$this, 'admin_scripts'));
            }

            add_action( 'init', array(&$this, 'register_shortcode') );
        }

        public function register_shortcode() {

            add_shortcode( 'slipry-slider', array(&$this, 'display_shortcode') );
        }

        public function display_shortcode($atts) {

            $result = '';

            $options = get_option( 'slipry_slider_options' );
            
            if ( ! $options ) {

                return $result;
            }

            $result .= '';

            // JS
            wp_register_script('slipry_slider_js',
                plugins_url('js/slippry.js', __FILE__),
                array('jquery') );

            wp_enqueue_script('slipry_slider_js',
                    plugins_url('js/slippry.js', __FILE__),
                    array('jquery') );

             // CSS
            wp_register_style('slipryslider_css',
                plugins_url('css/slipry-slider.css', __FILE__), true);

            wp_enqueue_style( 'slipryslider_css',
                plugins_url('css/slipry-slider.css', __FILE__),
                array() );

            $result .= '<div id="slider-wrapper"><ul id="slider" class="slider">';

            $currentSlideIndex = 0;
            
            for ( $i = 1; $i <= 3; ++$i ) {

                $slideTitle = array_key_exists('slide_' . $i . '_title', $options)
                                ? $options[ 'slide_' . $i . '_title' ] : '';

                $slideText = array_key_exists('slide_' . $i . '_text', $options)
                                ? $options[ 'slide_' . $i . '_text' ] : '';

                $slideImage = array_key_exists('slide_' . $i . '_image', $options)
                                ? $options[ 'slide_' . $i . '_image' ] : '';

                if ( $slideTitle || $slideText || $slideImage ) :

                    ++$currentSlideIndex;

                    $sliderContent = '<h2>' . $slideTitle . '</h2>'
                                         . '<div class="slide-content">'
                                         .  $slideText
                                         . '</div>';

                    $result .= '<li><a href="#slide' . $currentSlideIndex . '">';

                    $result .= '<img src="' . esc_attr( $slideImage ) . '" width="100%" alt="'
                                    . str_replace('"', "'", $sliderContent) . '" class="slider-img" />';

                    $result .= '</a></li>'; // close slide li tag

                endif;
            }

            $result .= '</ul></div>';

            return $result;
        }

        public function admin_scripts($hook) {

            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');

            wp_register_script( 'slipry_upload_media',
                plugins_url('js/slipry-upload-media.js', __FILE__ ),
                array('jquery') );

            wp_enqueue_script('slipry_upload_media');

            wp_enqueue_style('thickbox');
        }

    	/**
    	 * Used to access the instance
         *
         * @return object - class instance
    	 */
    	public static function get_instance() {

    		if ( NULL === self::$instance ) {
                self::$instance = new self();
            }

    		return self::$instance;
    	}

        /**
         * Unregister plugin settings on deactivating the plugin
         */
        public function deactivate() {

            unregister_setting('slipry_slider', 'slipry_slider_options');
        }

        /** 
         * Print the Section text
         */
        public function print_section_info() {}

        public function admin_init_settings() {

            register_setting('slipry_slider', 'slipry_slider_options');

            // add separate sections for each of Sliders
            add_settings_section( 'slipry_slider_section',
                __( 'Slider Settings', 'slipry-slider' ),
                array(&$this, 'print_section_info'),
                'slipry_slider' );

            for ( $i = 1; $i <= 3; ++$i ) {

                // Slide Title
                add_settings_field(
                    'slide_' . $i . '_title',
                    sprintf( __( 'Slide %s Title', 'slipry-slider' ), $i ),
                    array(&$this, 'input_callback'),
                    'slipry_slider',
                    'slipry_slider_section',
                    [ 'id' => 'slide_' . $i . '_title',
                      'page' =>  'slipry_slider_options' ]
                );

                // Slide Text
                add_settings_field(
                    'slide_' . $i . '_text',
                    sprintf( __( 'Slide %s Text', 'slipry-slider' ), $i ),
                    array(&$this, 'textarea_callback'),
                    'slipry_slider',
                    'slipry_slider_section',
                    [ 'id' => 'slide_' . $i . '_text',
                      'page' =>  'slipry_slider_options' ]
                );

                // Slide Image
                add_settings_field(
                    'slide_' . $i . '_image',
                    sprintf( __( 'Slide %s Image', 'slipry-slider' ), $i ),
                    array(&$this, 'image_callback'),
                    'slipry_slider',
                    'slipry_slider_section',
                    [ 'id' => 'slide_' . $i . '_image',
                      'page' =>  'slipry_slider_options' ]
                );
            }
        }

        public function input_callback($args) {

            // get the value of the setting we've registered with register_setting()
            $options = get_option( $args['page'] );
 
            // output the field
            $fieldValue = ($options && $args['id'] && array_key_exists(esc_attr( $args['id'] ), $options))
                                ? $options[ esc_attr( $args['id'] ) ] : 
                                    (array_key_exists('default_val', $args) ? $args['default_val'] : '');
            ?>

            <input type="text" id="<?php echo $args['page'] . '[' . $args['id'] . ']'; ?>"
                name="<?php echo $args['page'] . '[' . $args['id'] . ']'; ?>" class="regular-text"
                value="<?php echo $fieldValue; ?>" />
<?php
        }

        public function image_callback($args) {

            // get the value of the setting we've registered with register_setting()
            $options = get_option( $args['page'] );
 
            // output the field

            $fieldValue = $options && $args['id'] && array_key_exists(esc_attr( $args['id'] ), $options)
                                ? $options[ esc_attr( $args['id'] ) ] : '';
            ?>

            <input type="text" id="<?php echo $args['page'] . '[' . $args['id'] . ']'; ?>"
                name="<?php echo $args['page'] . '[' . $args['id'] . ']'; ?>" class="regular-text"
                value="<?php echo $fieldValue; ?>" />
            <input class="upload_image_button button button-primary" type="button" value="Change Image" />

            <p><img class="slider-img-preview" <?php if ( $fieldValue ) : ?> src="<?php echo esc_attr($fieldValue); ?>" <?php endif; ?> style="max-width:300px;height:auto;" /><p>

<?php         
        }

        public function textarea_callback($args) {

            // get the value of the setting we've registered with register_setting()
            $options = get_option( $args['page'] );
 
            // output the field

            $fieldValue = $options && $args['id'] && array_key_exists(esc_attr( $args['id'] ), $options)
                                ? $options[ esc_attr( $args['id'] ) ] : '';
            ?>

            <textarea id="<?php echo $args['page'] . '[' . $args['id'] . ']'; ?>"
                name = "<?php echo $args['page'] . '[' . $args['id'] . ']'; ?>"
                rows="10" cols="39"><?php echo $fieldValue; ?></textarea>
<?php
        }

        public function add_admin_page() {

            add_menu_page( __('Slipry Slider Settings', 'slipry-slider'),
                __('Slipry Slider', 'slipry-slider'), 'manage_options',
                'slipry-slider.php', array(&$this, 'show_settings'),
                'dashicons-format-gallery', 6 );

            //call register settings function
            add_action( 'admin_init', array(&$this, 'admin_init_settings') );
        }

        /**
         * Display the settings page.
         */
        public function show_settings() { ?>

            <div class="wrap">
                <div id="icon-options-general" class="icon32"></div>

                <div class="notice notice-info"> 
                    <p><strong><?php _e('Upgrade to SliprySliderPro Plugin', 'slipry-slider'); ?>:</strong></p>
                    <ol>
                        <li><?php _e('Configure Up to 10 Different Sliders', 'slipry-slider'); ?></li>
                        <li><?php _e('Insert Up to 10 Slides per Slider', 'slipry-slider'); ?></li>
                        <li><?php _e('Color Options: Title and Text, Link and Link Hover.', 'slipry-slider'); ?></li>
                        <li><?php _e('Sliding Settings: Sliding Speed, Sliding Delay', 'slipry-slider'); ?></li>
                        <li><?php _e('Sliding Effects: fade, horizontal, vertical, or kenburns.', 'slipry-slider'); ?></li>
                    </ol>
                    <a href="https://tishonator.com/plugins/slipry-slider" class="button-primary">
                        <?php _e('Upgrade to SliprySliderPRO Plugin', 'slipry-slider'); ?>
                    </a>
                    <p></p>
                </div>


                <h2><?php _e('Slipry Slider Settings', 'slipry-slider'); ?></h2>

                <form action="options.php" method="post">
                <?php settings_fields('slipry_slider'); ?>
                <?php do_settings_sections('slipry_slider'); ?>

                <h3>
                  Usage
                </h3>
                <p>
                    <?php _e('Use the shortcode', 'slipry-slider'); ?> <code>[slipry-slider]</code> <?php echo _e( 'to display Slider to any page or post.', 'slipry-slider' ); ?>
                </p>

                <?php submit_button(); ?>
              </form>
            </div>
    <?php
        }
    }

endif; // SliprySliderPlugin

add_action('plugins_loaded', array( SliprySliderPlugin::get_instance(), 'setup' ), 10);
