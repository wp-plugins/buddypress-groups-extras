<?php
/**
 * @todo
 *     Access management
 *     Apply set of field immediately
 */

$bpge_admin = new BPGE_ADMIN();

class BPGE_ADMIN{

    private $slug = 'bpge-admin';

    function __construct() {
        add_filter('screen_layout_columns', array( &$this, 'on_screen_layout_columns'), 10, 2 );
        add_action('admin_head', 'bpge_js_localize', 5);

        if (is_multisite()){
            add_action('network_admin_menu', array( &$this, 'on_admin_menu') );
        }else{
            add_action('admin_menu', array( &$this, 'on_admin_menu') );
        }

        if(is_admin() && !empty($_POST) && isset($_GET['page']) && $_GET['page'] == $this->slug){
            $this->on_save();
        }
    }

    /**
     * Columns managements for admin area
     */
    function on_screen_layout_columns( $columns, $screen ) {
        if ( $screen == $this->pagehook ) {
            if (is_multisite()){
                $columns[ $this->pagehook ] = 1;
            }else{
                $columns[ $this->pagehook ] = 2;
            }
            //$columns[ $this->pagehook ] = 1;
        }else{
            $columns[ $this->pagehook ] = 2;
        }
        return $columns;
    }

    /**
     * Add admin area page with options
     */
    function on_admin_menu() {
        $this->pagehook = add_submenu_page(
                            is_multisite()?'settings.php':'options-general.php',
                            __('BP Groups Extras', 'bpge'),
                            __('BP Groups Extras', 'bpge'),
                            'manage_options',
                            $this->slug,
                            array( &$this, 'on_show_page') );
        add_action('load-'.$this->pagehook, array( &$this, 'on_load_page') );
    }

    /**
     * Register all metaboxes
     */
    function on_load_page() {
        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');
        wp_enqueue_script('postbox');

        // sidebar
        add_meta_box('bpge-admin-re', __('Rich Editor for Groups Pages', 'bpge'), array(&$this, 'on_bpge_admin_re'), $this->pagehook, 'side', 'low' );
        add_meta_box('bpge-admin-uninstall', __('Plugin Uninstall Options', 'bpge'), array(&$this, 'on_bpge_admin_uninstall'), $this->pagehook, 'side', 'low' );
        add_meta_box('bpge-admin-import', __('Data Import From Pre-v3.4', 'bpge'), array(&$this, 'on_bpge_admin_import'), $this->pagehook, 'side', 'low' );
        add_meta_box('bpge-admin-promo', __('Need Help / Custom Work?', 'bpge'), array(&$this, 'on_bpge_admin_promo'), $this->pagehook, 'side', 'low' );
        // main content - normal
        add_meta_box('bpge-admin-groups', __('Groups Management', 'bpge'), array( &$this, 'on_bpge_admin_groups'), $this->pagehook, 'normal', 'core');
        add_meta_box('bpge-admin-fields', __('Set of Fields', 'bpge'), array( &$this, 'on_bpge_admin_fields'), $this->pagehook, 'normal', 'core');
    }

    function on_save(){
        global $wpdb, $bp;
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

        // Save fields for a set
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
        if ( isset($_POST['saveData']) ) {
            $bpge['groups']    = $_POST['bpge_groups'] ? $_POST['bpge_groups'] : array();
            $bpge['re']        = $_POST['bpge_re'];
            $bpge['uninstall'] = $_POST['bpge_uninstall'];

            bp_update_option('bpge', $bpge);
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
        if(isset($_POST['_wp_http_referer']))
            wp_redirect(site_url($_POST['_wp_http_referer']));
    }

    /**
     * Data import from versions before BPGE v3.4
     */
    function on_bpge_admin_import($bpge){ ?>
        <p>
            <?php _e('If you upgraded from any version of BuddyPress Groups Extras, which had the version number less than 3.4, and if you want to preserve all previously generated content (like default and groups fields etc) please do the import using controls below.','bpge');?>
        </p>

        <p class="description"><?php _e('<strong>Important</strong>: Do not import data twice - as this will create lots of duplicated fields.', 'bpge'); ?></p>

        <p>
            <input type="submit" name="bpge-import-data" value="<?php _e('Import Data', 'bpge'); ?>" class="button-primary" /> &nbsp;
            <input type="submit" name="bpge-clear-data" value="<?php _e('Clear Data', 'bpge'); ?>" class="button" />
        </p>

        <p class="description"><?php _e('Note:Clearing data will delete everything except options on this page.', 'bpge'); ?></p>
        <?php
    }

    /**
     * Rich Editor
     */
    function on_bpge_admin_re($bpge){
        echo '<p>';
            _e('Would you like to enable Rich Editor for easy use of html tags for groups pages?','bpge');
        echo '</p>';

        echo '<p>';
            echo '<label><input type="radio" name="bpge_re" '.($bpge['re'] == 1?'checked="checked"':'').' value="1">&nbsp'.__('Enable','bpge').'</label><br />';
            echo '<label><input type="radio" name="bpge_re" '.($bpge['re'] != 1?'checked="checked"':'').' value="0">&nbsp'.__('Disable','bpge').'</label>';
        echo '</p>';
    }

    /**
     * Plugin Deactivation options
     */
    function on_bpge_admin_uninstall($bpge){
        echo '<p>';
            _e('On BPGE deactivation you can delete or preserve all its settings and created content (like groups pages and fields). What do you want to do?','bpge');
        echo '</p>';

        if(!isset($bpge['uninstall']))
            $bpge['uninstall'] = 'no';

        echo '<p>';
            echo '<label><input type="radio" name="bpge_uninstall" '.($bpge['uninstall'] == 'no'?'checked="checked"':'').' value="no">&nbsp'.__('Preserve all data','bpge').'</label><br />';
            echo '<label><input type="radio" name="bpge_uninstall" '.($bpge['uninstall'] == 'yes'?'checked="checked"':'').' value="yes">&nbsp'.__('Delete everything','bpge') . '</label>';
        echo '</p>';
    }

    /**
     * Promo (contact slaFFik)
     */
    function on_bpge_admin_promo($bpge){
        echo '<p>If you:</p>
                <ul style="list-style:disc;margin-left:15px;">
                    <li>have a site/plugin idea and want to implement it</li>
                    <li>want to modify this plugin to your needs and ready to sponsor this</li>
                </ul>
                <p>feel free to contact slaFFik via <a href="skype:slaffik_ua?chat">skype:slaFFik_ua</a></p>';
    }

    /**
     * Set of Fields Management
     */
    function on_bpge_admin_fields($bpge){
        echo '<p>';
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
     * Display list of groups to enable BPGE for them
     */
    function on_bpge_admin_groups($bpge){
        $arg['type']     = 'alphabetical';
        $arg['per_page'] = '1000';
        bpge_view('admin_groups_list', array('arg' => $arg));
    }

    /**
     * Actual html of a page (its core)
     */
    function on_show_page() {
        global $bp, $wpdb, $screen_layout_columns;

        //define some data that can be given to each metabox during rendering
        $bpge = bp_get_option('bpge');
        ?>

        <div id="bpge-admin-general" class="wrap">
            <?php screen_icon('options-general'); ?>
            <style>table.link-group li{margin:0 0 0 25px}</style>
            <h2><?php _e('BuddyPress Groups Extras','bpge') ?> <sup><?php echo 'v' . BPGE_VERSION; ?></sup> &rarr; <?php _e('Extend Your Groups', 'bpge') ?></h2>

            <?php if ( isset($_POST['saveData']) ) { ?>
                <div id='message' class='updated fade'><p><?php _e('All changes were saved. Go and check results!', 'bpge');?></p></div>
            <?php } ?>

            <form action="" id="bpge-form" method="post">
                <?php
                wp_nonce_field('bpge-admin-general');
                wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false );
                wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>

                <div id="poststuff" class="metabox-holder<?php echo (2 == $screen_layout_columns) ? ' has-right-sidebar' : ''; ?>">
                    <?php if( !is_multisite() ) { ?>
                        <div id="side-info-column" class="inner-sidebar">
                            <p style="text-align:center">
                                <input type="submit" value="<?php _e('Save Changes', 'bpge') ?>" class="button-primary" name="saveData"/>
                                <a class="button" href="" title="<?php _e('Refresh current page', 'bpge') ?>"><?php _e('Refresh', 'bpge') ?></a>
                            </p>
                            <?php do_meta_boxes($this->pagehook, 'side', $bpge); ?>
                        </div>
                    <?php } ?>
                    <div id="post-body" class="<?php !is_multisite()?' has-sidebar':''; ?>">
                        <div id="post-body-content" class="<?php !is_multisite()?' has-sidebar-content':''; ?>">
                            <?php
                            do_meta_boxes($this->pagehook, 'normal', $bpge);
                            if( is_multisite() ) {
                                do_meta_boxes($this->pagehook, 'side', $bpge);
                            }
                            ?>
                            <p>
                                <input type="submit" value="<?php _e('Save Changes', 'bpge') ?>" class="button-primary" name="saveData"/>
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <script type="text/javascript">
            jQuery(document).ready( function() {
                jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
                postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
            });
        </script>
    <?php
    }
}
