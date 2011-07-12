<?php
/*
Plugin Name: BuddyPress Groups Extras
Plugin URI: http://ovirium.com/
Description: Adding extra group fields and some other missing functionality to groups
Version: 1.2
Author: slaFFik
Author URI: http://cosydale.com/
*/
define ('BPGE_VERSION', '1.0');

register_activation_hook( __FILE__, 'bpge_activation');
//register_deactivation_hook( __FILE__, 'bpge_deactivation');
function bpge_activation() {
    $bpge['groups'] = 'all';
    add_option('bpge', $bpge, '', 'yes');
}
function bpge_deactivation() { delete_option('bpge'); }

/* LOAD LANGUAGES */
add_action ('plugins_loaded', 'bpge_load_textdomain', 7 );
function bpge_load_textdomain() {
    $locale = apply_filters('buddypress_locale', get_locale() );
    $mofile = dirname( __File__ )   . "/langs/bpge-$locale.mo";

    if ( file_exists( $mofile ) )
        load_textdomain('bpge', $mofile);
}


add_action( 'bp_loaded', 'bpge_load' );
function bpge_load(){
	global $bp;
	require ( dirname(__File__) . '/bpge-cssjs.php');
	if ( is_admin()){
		require ( dirname(__File__) . '/bpge-admin.php');
	}
	$bpge = get_option('bpge');
	if ( (is_string($bpge['groups']) && $bpge['groups'] == 'all' ) || (is_array($bpge['groups']) && in_array($bp->groups->current_group->id, $bpge['groups'])) ){
		require ( dirname(__File__) . '/bpge-loader.php');
	}
}

// Helper for generating some titles
function bpge_names($name = 'name'){
	switch ($name){
		case 'title_general':
			return __('Group Extras &rarr; General Settings', 'bpge');
			break;
		case 'title_fields':
			return __('Group Extras &rarr; Fields Management', 'bpge');
			break;
		case 'title_pages':
			return __('Group Extras &rarr; Pages Management', 'bpge');
			break;
		case 'title_fields_add':
			return __('Group Extras &rarr; Add Field', 'bpge');
			break;
		case 'title_fields_edit':
			return __('Group Extras &rarr; Edit Field', 'bpge');
			break;
		case 'title_pages_add':
			return __('Group Extras &rarr; Add Page', 'bpge');
			break;
		case 'title_pages_edit':
			return __('Group Extras &rarr; Edit Page', 'bpge');
			break;
		case 'name':
			return __('Description', 'bpge');
			break;
		case 'nav':
			return __('Extras', 'bpge');
			break;
	}
}

/*
 * Personal debug functions
 */
if(!function_exists('print_var')){
	function print_var($var, $die = false){
		echo '<pre>';
		if (empty($var))
			var_dump($var);
		else
			print_r($var);
		echo '</pre>';
		if ($die)
			die;
	}
}

add_action('bp_adminbar_menus', 'bpge_queries');
function bpge_queries(){
    echo '<li class="no-arrow"><a>'.get_num_queries() . ' queries | ';
    echo round(memory_get_usage() / 1024 / 1024, 2) . 'Mb</a></li>';
}