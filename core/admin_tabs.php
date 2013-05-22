<?php
/**
 * Admin area made in BuddyPress way - tabs
 */

$bpge_admin = new BPGE_ADMIN();

class BPGE_ADMIN{

    private $slug = 'bpge-admin';
    private $bpge = false;

    // Declare Tabs
    private $general_settings_key = 'general';
    private $sets_settings_key    = 'sets';
    private $pages_settings_key   = 'pages';
    private $groups_settings_key  = 'groups';
    private $bpge_tabs            = array();

    // where to save in options table
    private $bpge_options_key     = 'bpge';

    function __construct() {
        global $bpge;
        $this->bpge = $bpge;

        // resister admin page
        if (is_multisite()){
            add_action('network_admin_menu', array( &$this, 'add_admin_menu') );
        }else{
            add_action('admin_menu', array( &$this, 'add_admin_menu') );
        }

        // register tabs
        if(isset($_GET['page']) && $_GET['page'] == $this->slug){
            add_action( 'admin_init', array( &$this, 'register_general_settings' ) );
            add_action( 'admin_init', array( &$this, 'register_sets_settings' ) );
            // add_action( 'admin_init', array( &$this, 'register_pages_settings' ) );
            add_action( 'admin_init', array( &$this, 'register_groups_settings' ) );
        }

        // translate js string
        add_action('admin_head', 'bpge_js_localize', 5);

        // process save process
        if(is_admin() && !empty($_POST) && isset($_GET['page']) && $_GET['page'] == $this->slug){
            $this->on_save();
        }
    }

    /**
     * Register 1st tab with lots of general options
     */
    function register_general_settings(){
        $this->bpge_tabs[$this->general_settings_key] = '<span></span>'.__('General Options', 'bpge');

        register_setting( $this->general_settings_key, $this->general_settings_key );
        add_settings_section( 'general_settings', '', array( &$this, 'display_general' ), $this->general_settings_key );

        add_settings_field('re',
            __('Rich Editor', 'bpge'),
            array($this, 'general_re'),
            $this->general_settings_key,
            'general_settings');
        add_settings_field('access',
            __('User Access', 'bpge'),
            array($this, 'general_access'),
            $this->general_settings_key,
            'general_settings');
        add_settings_field('import',
            __('Data Import', 'bpge'),
            array($this, 'general_import'),
            $this->general_settings_key,
            'general_settings');
        add_settings_field('uninstall',
            __('Uninstall Options', 'bpge'),
            array($this, 'general_uninstall'),
            $this->general_settings_key,
            'general_settings');
    }

    function display_general(){
        echo '<p class="description">'.__('Here are some general settings.', 'bpge').'</p>';
    }

    /**
     * Change accessibility of Extras group admin tab
     */
    function general_access(){?>
        <p>
            <?php _e('Sometimes we want to change the access level to different parts of a site. Options below will help you to do this.','bpge');?>
        </p>

        <p><?php _e('Who can open group admin tab Extras?', 'bpge'); ?></p>
        <?php
        if(!isset($this->bpge['access_extras']) || empty($this->bpge['access_extras']))
            $this->bpge['access_extras'] = 'g_s_admin';
        ?>
        <ul>
            <li><label>
                <input name="bpge_access_extras" type="radio" value="s_admin" <?php checked('s_admin', $this->bpge['access_extras']); ?> />&nbsp;
                <?php _e('Site admins only', 'bpge'); ?>
            </label></li>
            <li><label>
                <input name="bpge_access_extras" type="radio" value="g_s_admin" <?php checked('g_s_admin', $this->bpge['access_extras']); ?> />&nbsp;
                <?php _e('Group administrators and site admins', 'bpge'); ?>
            </label></li>
        </ul>

        <?php
    }

    /**
     * Data import from versions before BPGE v3.4
     */
    function general_import(){ ?>
        <p>
            <?php _e('If you upgraded from any version of BuddyPress Groups Extras, which had the version number less than 3.4, and if you want to preserve all previously generated content (like default and groups fields etc) please do the import using controls below.','bpge');?>
        </p>

        <p class="description"><?php _e('<strong>Important</strong>: Do not import data twice - as this will create lots of duplicated fields.', 'bpge'); ?></p>

        <p>
            <input type="submit" name="bpge-import-data" value="<?php _e('Import Data', 'bpge'); ?>" class="button-secondary" /> &nbsp;
            <input type="submit" name="bpge-clear-data" value="<?php _e('Clear Data', 'bpge'); ?>" class="button" />
        </p>

        <p class="description"><?php _e('Note: Clearing data will delete everything except options on this page.', 'bpge'); ?></p>
        <?php
    }

    /**
     * Rich Editor
     */
    function general_re(){
        echo '<p>';
            _e('Would you like to enable Rich Editor for easy use of html tags for groups pages?','bpge');
        echo '</p>';

        echo '<p>';
            echo '<label><input type="radio" name="bpge_re" '.($this->bpge['re'] == 1?'checked="checked"':'').' value="1">&nbsp'.__('Enable','bpge').'</label><br />';
            echo '<label><input type="radio" name="bpge_re" '.($this->bpge['re'] != 1?'checked="checked"':'').' value="0">&nbsp'.__('Disable','bpge').'</label>';
        echo '</p>';
    }

    /**
     * Plugin Deactivation options
     */
    function general_uninstall(){
        echo '<p>';
            _e('On BPGE deactivation you can delete or preserve all its settings and created content (like groups pages and fields). What do you want to do?','bpge');
        echo '</p>';

        if(!isset($this->bpge['uninstall']))
            $this->bpge['uninstall'] = 'no';

        echo '<p>';
            echo '<label><input type="radio" name="bpge_uninstall" '.($this->bpge['uninstall'] == 'no'?'checked="checked"':'').' value="no">&nbsp'.__('Preserve all data','bpge').'</label><br />';
            echo '<label><input type="radio" name="bpge_uninstall" '.($this->bpge['uninstall'] == 'yes'?'checked="checked"':'').' value="yes">&nbsp'.__('Delete everything','bpge') . '</label>';
        echo '</p>';
    }

    /**
     * Promo (contact slaFFik)
     */
    function promo(){
        echo '<p>If you:</p>
                <ul style="list-style:disc;margin-left:15px;">
                    <li>have a site/plugin idea and want to implement it</li>
                    <li>want to modify this plugin to your needs and ready to sponsor this</li>
                </ul>
                <p>feel free to contact slaFFik via <a href="skype:slaffik_ua?chat">skype:slaFFik_ua</a></p>';
    }

    /**
     * Set of fields
     */
    function register_sets_settings(){
        $this->bpge_tabs[$this->sets_settings_key] = '<span></span>'.__('Default Sets of Fields', 'bpge');

        register_setting( $this->sets_settings_key, $this->sets_settings_key );
        add_settings_section( 'fields_settings', '', array( &$this, 'display_sets' ), $this->sets_settings_key );
    }

    function display_sets(){
        echo '<p class="description">';
            _e('Please create/edit here fields you want to be available as standard blocks of data.<br />This will be helpful for group admins - no need for them to create lots of fields from scratch.','bpge');
        echo '</p>';

        $set_args = array(
            'posts_per_page' => 50,
            'numberposts'    => 50,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'post_type'      => BPGE_FIELDS_SET
        );
        $set_of_fields = get_posts($set_args);

        echo '<ul class="sets">';
        if(!empty($set_of_fields)){
            $fields_args = array(
                'posts_per_page' => 50,
                'numberposts'    => 50,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'post_type'      => BPGE_FIELDS
            );
            foreach($set_of_fields as $set){
                // get some extra options
                $set->options = get_post_meta($set->ID, 'bpge_set_options', true);
                // get all fields in that set
                $fields_args['post_parent'] = $set->ID;
                $fields = get_posts($fields_args);
                // display the html
                bpge_view('admin_set_list', array('fields' => $fields, 'set' => $set));
            }
        }else{
            echo '<li>';
                echo '<span class="no_fields">'.__('Currently there are no predefined fields. Groups admins should create all fields by themselves.', 'bpge') . '</span>';
            echo '</li>';
        }
        echo '</ul>';

        echo '<div class="clear"></div>';

        // Adding set of fields
        bpge_view('admin_set_add');

        // Editing for set of fields
        bpge_view('admin_set_edit');

        // Form to add fields to a set
        bpge_view('admin_set_field_add');
    }

    /**
     * Default Pages for all groups
     */
    function register_pages_settings(){
        $this->bpge_tabs[$this->pages_settings_key] = '<span></span>'.__('Default Pages', 'bpge');

        register_setting( $this->pages_settings_key, $this->pages_settings_key );
        add_settings_section( 'pages_settings', '', array( &$this, 'display_pages' ), $this->pages_settings_key );
    }

    function display_pages(){
        echo '<p class="description">';
            _e('Please create/edit here global pages you want to be available in all groups. ','bpge');
        echo '</p>';
    }

    /**
     * Groups list
     */
    function register_groups_settings(){
        $this->bpge_tabs[$this->groups_settings_key] = '<span></span>'.__('Allowed Groups', 'bpge');

        register_setting( $this->groups_settings_key, $this->groups_settings_key );
        add_settings_section( 'groups_settings', '', array( &$this, 'display_groups' ), $this->groups_settings_key );
    }

    /**
     * Display list of groups to enable BPGE for them
     */
    function display_groups(){
        $arg['type']     = 'alphabetical';
        $arg['per_page'] = '1000';
        bpge_view('admin_groups_list', array('arg' => $arg));
    }

    /**
     * Add admin area page with options
     */
    function add_admin_menu() {
        $this->pagehook = add_submenu_page(
                            is_multisite()?'settings.php':'options-general.php',
                            __('BP Groups Extras', 'bpge'),
                            __('BP Groups Extras', 'bpge'),
                            'manage_options',
                            $this->slug,
                            array( &$this, 'admin_page') );
        // here I can load all styles and scripts
    }

    function on_save(){
        global $wpdb, $bp;

        if ( !isset($_POST['submit']) ) {
            wp_redirect(str_replace('&saved', '', site_url($_POST['_wp_http_referer'])));
        }

        // Save new Set of fields
        if(!empty($_POST['add_set_fields_name'])){
            $set = array(
                    'post_type'    => BPGE_FIELDS_SET,
                    'post_status'  => 'publish',
                    'post_title'   => $_POST['add_set_fields_name'],
                    'post_content' => $_POST['add_set_field_description']
                );
            wp_insert_post( $set );
        }

        // Edit Set of fields
        if(!empty($_POST['edit_set_fields_name']) && !empty($_POST['edit_set_fields_id'])){
            $set = array(
                    'ID'           => $_POST['edit_set_fields_id'],
                    'post_title'   => $_POST['edit_set_fields_name'],
                    'post_content' => $_POST['edit_set_field_description']
                );
            wp_update_post( $set );
        }

        // Save field for a set
        if(!empty($_POST['extra-field-title']) && !empty($_POST['sf_id_for_field'])){
            // save field
            $field_id = wp_insert_post(array(
                            'post_type'    => BPGE_FIELDS,
                            'post_parent'  => $_POST['sf_id_for_field'], // assign to a set of fields
                            'post_title'   => $_POST['extra-field-title'],
                            'post_content' => $_POST['extra-field-desc'],
                            'post_excerpt' => $_POST['extra-field-type'],
                            'post_status'  => 'publish'
                        ));

            if(!empty($_POST['options'])){
                $options = array();
                foreach($_POST['options'] as $option){
                    $options[] = htmlspecialchars(strip_tags($option));
                }
                update_post_meta( $field_id, 'bpge_field_options', $options );
            }
        }

        // Save other options
        if ( isset($_POST['groups']) ) {
            $this->bpge['groups'] = $_POST['bpge_groups'] ? $_POST['bpge_groups'] : array();
            bp_update_option('bpge', $this->bpge);
        }

        if ( isset($_POST['bpge_re']) ) {
            // print_var($_POST);
            $this->bpge['re']            = $_POST['bpge_re'];
            $this->bpge['uninstall']     = $_POST['bpge_uninstall'];
            $this->bpge['access_extras'] = $_POST['bpge_access_extras'];

            bp_update_option('bpge', $this->bpge);
        }

        if ( isset($_POST['bpge-import-data']) ) {
            /**
             * Default fields
             */
            // get list of set of fields
            $set_fields = $wpdb->get_results(
                            "SELECT option_name AS `slug`, option_value AS `set`
                            FROM {$wpdb->options}
                            WHERE option_name LIKE 'bpge-set-%%'");

            // reformat data
            if(!empty($set_fields)){
                foreach($set_fields as &$set){
                    $set->set = maybe_unserialize($set->set);
                }
            }

            // process the import part 1
            foreach ((array)$set_fields as $set){
                // save the set
                $set_data = array(
                        'post_type'    => BPGE_FIELDS_SET,
                        'post_status'  => 'publish',
                        'post_title'   => $set->set['name'],
                        'post_content' => $set->set['desc']
                    );
                $set_id = wp_insert_post($set_data);

                // now we need to save fields in that set
                if(is_int($set_id)){}
                    foreach((array)$set->set['fields'] as $field){
                        $field_id = wp_insert_post(array(
                                        'post_type'    => BPGE_FIELDS,
                                        'post_parent'  => $set_id, // assign to a set of fields
                                        'post_title'   => $field['name'],
                                        'post_content' => $field['desc'],
                                        'post_excerpt' => $field['type'],
                                        'post_status'  => 'publish'
                                    ));
                        // and save options if any
                        if(isset($field['options']) && !empty($field['options'])){
                            $options = array();
                            foreach($field['options'] as $option){
                                $options[] = htmlspecialchars(strip_tags($option['name']));
                            }
                            update_post_meta( $field_id, 'bpge_field_options', $options );
                        }
                    }
                }

            /**
             * Groups fields
             */
            // get list of groups that have gFields from  groupmeta
            $gFields = $wpdb->get_row($wpdb->prepare(
                            "SELECT group_id, meta_value AS `fields`
                            FROM {$bp->table_prefix}bp_groups_groupmeta
                            WHERE meta_key = 'bpge_fields'", __return_false()
                        ));

            // reformat data
            if(!empty($gFields) && !empty($gFields->fields)){
                $gFields->fields = json_decode($gFields->fields);
            }

            $i = 100;
            if(!empty($gFields->fields) && is_array($gFields->fields) ){
                foreach ($gFields->fields as $field){
                    $new               = new Stdclass;
                    $new->post_title   = apply_filters('bpge_new_field_title',    $field->title);
                    $new->post_excerpt = apply_filters('bpge_new_field_type',     $field->type);
                    $new->pinged       = apply_filters('bpge_new_field_required', $field->required);
                    $new->post_status  = apply_filters('bpge_new_field_display',  $field->display=='1'?'publish':'draft');
                    $new->post_parent  = apply_filters('bpge_new_field_group',    $gFields->group_id);
                    $new->post_type    = apply_filters('bpge_new_field_type',     BPGE_GFIELDS);
                    $new->menu_order   = $i;

                    if(isset($field->options) && !empty($field->options)){
                        $options = array();
                        foreach($field->options as $option){
                            $options[] = htmlspecialchars(strip_tags($option));
                        }
                    }

                    // Save Field
                    $field_id = wp_insert_post($new);

                    if(is_integer($field_id)){
                        // Save field options
                        update_post_meta($field_id, 'bpge_field_options', $options );

                        $field_desc = apply_filters('bpge_new_field_desc', $field->desc);
                        update_post_meta($field_id, 'bpge_field_desc', $field_desc);
                    }
                    $i++;
                }
            }
        }

        // Remove everything plugin-related except options
        if ( isset($_POST['bpge-clear-data']) ) {
            bpge_clear(false);
        }

        // now redirect to the same page to clear POST
        if(isset($_POST['_wp_http_referer'])){
            wp_redirect(str_replace('&saved', '', site_url($_POST['_wp_http_referer'])).'&saved');
        }
    }

    /**
     * Actual html of a page (its core)
     */
    function admin_page() {
        //define some data that can be given to each metabox during rendering
        $tab  = $this->get_cur_tab();
        ?>

        <div id="bpge-admin" class="wrap">
            <?php $this->bpge_header(); ?>

            <form action="" id="bpge-form" method="post">
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

    function get_cur_tab(){
        return (isset($_GET['tab']) && !empty($_GET['tab'])) ? $_GET['tab'] : $this->general_settings_key;
    }

    function bpge_header(){
        $current_tab = $this->get_cur_tab();
        screen_icon('options-general');
        echo '<h2>';
            _e('BuddyPress Groups Extras','bpge');
            echo '<sup>v' . BPGE_VERSION . '</sup> &rarr; ';
            _e('Extend Your Groups', 'bpge');
        echo '</h2>';

        if ( isset($_GET['saved']) ) {
            echo '<div id="message" class="updated fade"><p>'. __('All changes were saved. Go and check results!', 'bpge'). '</p></div>';
        }

        echo '<h3 class="nav-tab-wrapper">';
        foreach ( $this->bpge_tabs as $tab_key => $tab_caption ) {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
            echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->slug . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
        }
        echo '</h3>';
    }
}
