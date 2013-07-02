<?php

/**
 * Get the template file and output its content
 * @param  string   $view       template file name
 * @param  mixed    $params     variables that should be passed to the view
 * @return string               HTML of a page or view
 */
function bpge_view($view, $params = false){
    global $bp, $bpge;

    do_action('bpge_view_pre', $view, $params);

    $params = apply_filters('bpge_view_params', $params);

    if(!empty($params))
        extract($params);

    $theme_parent_file =   get_template_directory() . DS . BPGE . DS . $view .'.php';
    $theme_child_file  = get_stylesheet_directory() . DS . BPGE . DS . $view .'.php';

    // admin area templates should not be overridable via theme files
    // check that file exists in theme folder
    if(!is_admin() && file_exists($theme_child_file)){
        // from child theme
        include $theme_child_file;
    }elseif(!is_admin() && file_exists($theme_parent_file)){
        // from parent theme if no in child
        include $theme_parent_file;
    }else{
        // from plugin folder
        $plugin_file = BPGE_PATH . 'views'. DS . $view . '.php';
        if(file_exists($plugin_file)){
            include $plugin_file;
        }
    }

    do_action('bpge_view_post', $view, $params);
}

/***************************
 * GPages template functions
 ***************************/

/**
 * Edit page link
 */
function bpge_the_gpage_edit_link($page_id){
    global $bp, $bpge;
    if (bpge_user_can('group_extras_admin')){
        echo '<div class="edit_link">
                <a target="_blank" title="'.__('Edit link for group admins only', 'bpge').'" href="'.bp_get_group_permalink( $bp->groups->current_group ).'admin/extras/pages-manage/?edit='.$page_id.'">'
                    .__('[Edit this page]', 'bpge') .
                '</a>
            </div>';
    }
}