<?php

/**
 * Main class for WordPress admin area
 */
class BPGE_ADMIN {
    // page slug, used on URL
    var $slug = BPGE_ADMIN_SLUG;
    // where all options are stored
    var $bpge = false;
    // where to save in options table
    var $bpge_options_key = 'bpge';
    // default tab that will be opened in nothing specified
    // will be redefined after all tabs are loaded
    var $default_tab = null;
    // the list of tabs in admin area, will be extended by child classes
    var $bpge_tabs = array();
    // path the folder where all tabs are situated
    var $tabs_path = null;

    /**
     * Do some important initial routine
     */
    function __construct(){
        global $bpge;
        $this->bpge      = $bpge;
        $this->tabs_path = dirname(__FILE__) . DS . 'admin_tabs';

        add_action('admin_head', 'bpge_js_localize', 5);

        // create tabs
        $this->get_tabs();
    }

    /**
     * Get all tabs from individual files (include)
     */
    function get_tabs(){
        if ($handle = opendir($this->tabs_path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == "." || $file == "..") continue;

                $this->bpge_tabs[] = include($this->tabs_path . DS . $file);
            }
            closedir($handle);
        }
        // print_var($this->bpge_tabs);
        $this->reorder_tabs();
    }

    /**
     * Used for sorting tabs according to their position
     */
    function reorder_tabs(){
        if(empty($this->bpge_tabs) || !is_array($this->bpge_tabs))
            return;

        foreach($this->bpge_tabs as $tab){
            $tmp[$tab->position] = $tab;
        }

        // make smaller position at the top of an array
        ksort($tmp);
        $this->bpge_tabs = $tmp;
        unset($tmp);

        // set the first tab as default
        if(empty($this->default_tab)){
            $first = reset($this->bpge_tabs);
            $this->default_tab = $first->slug;
        }
    }

    /**
     * Load all required styles and scripts
     */
    function load_assets($pagehook){
        $this->pagehook = $pagehook;

        // Accordion for Tuts page
        wp_enqueue_script('bpge_admin_js_acc', BPGE_URL . '/jquery.accordion.js', array('jquery'));
        wp_enqueue_style('bpge_admin_css_acc', BPGE_URL . '/jquery.accordion.css');

        add_action('admin_footer', array($this, 'load_pointers'));

        // All other admin area js
        add_action('admin_print_scripts', array($this, 'load_js'));
        add_action('admin_print_styles', array($this, 'load_css'));
    }

    function load_pointers(){
        $page = is_multisite()?'network/settings.php':'options-general.php';

        $vote_content  = '<h3>'. __('BP Groups Extras: Features List', 'bpge'). '</h3>';
        $vote_content .= '<p>'. sprintf(__('Go to plugin admin area and <a href="%s">vote</a> for new features!', 'bpge'), admin_url('/'.$page.'?page='.BPGE_ADMIN_SLUG.'&tab=more')) . '</p>';
        $vote_content .= '<p>'. __('Based on voting results I will implement them in new plugin version (either in core or as modules to extend the initial functionality).', 'bpge'). '</p>';

        $tuts_content  = '<h3>'. __('BP Groups Extras: Tutorials') .'</h3>';
        $tuts_content .= '<p>'. sprintf(__('Now you can get the basic help from the plugin admin area - several <a href="%s">very detailed tutorials</a> are bundled right into the plugin.', 'bpge'),  admin_url('/'.$page.'?page='.BPGE_ADMIN_SLUG.'&tab=tuts')) .'</p>';

        // get all pointer that we dismissed
        $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) ) ;

        // Check whether my pointer has been dismissed
        if ( in_array( 'bpge_vote', $dismissed ) ) {
            $vote_content = '';
        }
        if ( in_array( 'bpge_tuts', $dismissed ) ) {
            $tuts_content = '';
        }

        if(!empty($vote_content)){ ?>
            <script type="text/javascript">// <![CDATA[
            jQuery(document).ready(function($) {
                $('#menu-settings').pointer({
                    content: '<?php echo $vote_content; ?>',
                    position: {
                        edge: 'left',
                        align: 'center'
                    },
                    close: function() {
                        $.post( ajaxurl, {
                            action: 'dismiss-wp-pointer',
                            pointer: 'bpge_vote'
                        });
                    }
                }).pointer('open');
            });
            // ]]></script>
            <?php
        }

        if(!empty($tuts_content)){ ?>
            <script type="text/javascript">// <![CDATA[
            jQuery(document).ready(function($) {
                $('#bpge_tab_tuts').pointer({
                    content: '<?php echo $tuts_content; ?>',
                    position: {
                        edge: 'top',
                        align: 'left'
                    },
                    close: function() {
                        $.post( ajaxurl, {
                            action: 'dismiss-wp-pointer',
                            pointer: 'bpge_tuts'
                        });
                    }
                }).pointer('open');
            });
            // ]]></script>
            <?php
        }

    }

    function load_js(){
        wp_enqueue_script('wp-pointer');
        if(isset($_GET['page']) && $_GET['page'] == BPGE_ADMIN_SLUG){
            wp_enqueue_script('bpge_admin_js_popup', BPGE_URL . '/messi.js', array('jquery') );
        }
        wp_enqueue_script('bpge_admin_js', BPGE_URL . '/admin-scripts.js', array('wp-pointer') );
    }

    function load_css(){
        global $post_type;

        wp_enqueue_style('wp-pointer');

        if (
            (isset($_GET['post_type']) && $_GET['post_type'] == 'gpages')
         || (isset($post_type) && $post_type == 'gpages')
         || (isset($_GET['page']) && $_GET['page'] == $this->slug)
        ) {
            wp_enqueue_style('bpge_admin_css', BPGE_URL . '/admin-styles.css');
            wp_enqueue_style('bpge_admin_css_messi', BPGE_URL . '/messi.css');
        }
    }

    /**
     * Actual html of a page (its core)
     */
    function admin_page() {
        //define some data that can be given to each metabox during rendering
        $tab = $this->get_cur_tab(); ?>

        <div id="bpge-admin" class="wrap">
            <?php $this->bpge_header(); ?>

            <form action="" class="tab_<?php echo $tab; ?>" id="bpge-form" method="post">
                <?php
                wp_nonce_field( 'bpge-options' );
                settings_fields( $tab );
                do_settings_sections( $tab );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * We need to know the current tab at any time
     * If not specified - get the default one
     */
    function get_cur_tab(){
        if(isset($_GET['tab']) && !empty($_GET['tab'])) {
            return $_GET['tab'];
        }else{
            return $this->default_tab;
        }
    }

    /**
     * Content part with header section.
     * HTML
     */
    function bpge_header(){
        $current_tab = $this->get_cur_tab();
        screen_icon('options-general');
        echo '<h2>';
            _e('BuddyPress Groups Extras','bpge');
            echo '<sup>v' . BPGE_VERSION . '</sup> &rarr; ';
            _e('Extend Your Groups', 'bpge');
            do_action('bpge_admin_header_title');
        echo '</h2>';

        if ( isset($_GET['saved']) ) {
            echo '<div id="message" class="updated fade"><p>'. __('All changes were saved. Go and check results!', 'bpge'). '</p></div>';
        }

        echo '<h3 class="nav-tab-wrapper">';
        foreach ( $this->bpge_tabs as $tab ) {
            $active = $current_tab == $tab->slug ? 'nav-tab-active' : '';
            echo '<a class="nav-tab ' . $active . '" id="bpge_tab_'. $tab->slug .'" href="?page='. $this->slug .'&tab='. $tab->slug .'">'. $tab->title .'</a>';
        }
        echo '</h3>';
    }
}



/**
* Class that will be a skeleton for all other pages
*/
class BPGE_ADMIN_TAB {
    // all theme options
    var $bpge = null;

    // all these vars are required and should be overwritten
    var $position = 0;
    var $title    = null;
    var $slug     = null;

    /**
    * Create the actual page object
    */
    function __construct(){
        if(!(isset($_GET['page']) && $_GET['page'] == BPGE_ADMIN_SLUG)){
            return;
        }

        global $bpge;
        $this->bpge = $bpge;

        register_setting( $this->slug, $this->slug );
        add_settings_section(
            $this->slug . '_settings', // section id
            '', // title
            array(&$this, 'display'), // method handler
            $this->slug // slug
        );

        $this->register_sections();

        $tab = 'general';
        if(isset($_GET['tab'])){
            $tab = $_GET['tab'];
        }

        // process save process
        if(is_admin() && !empty($_POST)
            && isset($_GET['page']) && $_GET['page'] == BPGE_ADMIN_SLUG
            && $this->slug == $tab
        ){
            $this->save();
            // now redirect to the same page to clear POST
            wp_redirect(str_replace('&saved', '', site_url($_POST['_wp_http_referer'])).'&saved');
        }
    }

    /**
     * Here we need to register all settings if needed
     * Those sections will be used to display fields/options
     * @override
     */
    function register_sections(){}

    /**
    * HTML should be here
    * @override
    */
    function display(){}

    /**
    * All security and data checks should be here
    * @override
    */
    function save(){}
}