<?php

class BPGE extends BP_Group_Extension {
	var $bpge = false;
	
	var $slug = 'extras';
	var $name = false;
	var $nav_item_name = false;

	/* By default - Is it visible to non-members of a group? Options: public/private */
	var $visibility = false;

	var $create_step_position = 5;
	var $nav_item_position = 15;

	var $enable_create_step = false; // will set to true in future version
	var $enable_nav_item = false;
	var $enable_edit_item = true;

	var $display_hook = 'groups_extras_group_boxes';
	var $template_file = 'groups/single/plugins';
	
	function BPGE(){
		global $bp;
		
		if (!empty($bp->groups->current_group)){
			// populate extras data in global var
			$bp->groups->current_group->extras = groups_get_groupmeta($bp->groups->current_group->id, 'bpge');
			add_action('bp_groups_adminbar_admin_menu', array($this, 'buddybar_admin_link'));
		}
		
		// Display or Hide top menu from group non-members
		$this->visibility = $bp->groups->current_group->extras['display_page'] ? $bp->groups->current_group->extras['display_page'] : 'public';
		$this->enable_nav_item = $bp->groups->current_group->extras['display_page'] == 'public' ? true : false;
	
		// In Admin
		$this->name = bpge_names('nav');
		// Public page
		$this->nav_item_name = bpge_names('name');
		
		add_action('groups_custom_group_fields_editable', array($this, 'edit_group_fields'));
		add_action('groups_group_details_edited', array($this, 'edit_group_fields_save'));
	}
	
	// Public page with already saved content
	function display() {
		global $bp;
		$fields = $this->get_all_items('fields', $bp->groups->current_group->id);
		if (empty($fields))
			return false;
		
		// Variant 1
		echo '<div class="extra-data">';
			foreach($fields as $field){
				if ( $field->display != 1)
					continue;
					
				echo '<h4 title="' . ( ! empty($field->desc)  ? esc_attr($field->desc) : '')  .'">' . $field->title .'</h4>';
				$data = groups_get_groupmeta($bp->groups->current_group->id, $field->slug);
				if ( is_array($data))
					$data = implode(', ', $data);
				echo '<p>' . $data . '</p>';
			}
		echo '</div>';
		
		echo '<hr />';
		
		// Variant 2
		echo '<table class="profile-fields zebra">';
			foreach($fields as $field){
				if ( $field->display != 1)
					continue;
				 
				echo '<tr><td class="label" title="' . ( ! empty($field->desc)  ? esc_attr($field->desc) : '')  .'">' . $field->title .'</td>';
				$data = groups_get_groupmeta($bp->groups->current_group->id, $field->slug);
				if ( is_array($data))
					$data = implode(', ', $data);
				echo '<td class="data">' . $data . '</td></tr>';
			}
		echo '</table>';
	}

	// Display exra fields on edit group details page
	function edit_group_fields(){
		global $bp;
		$fields = $this->get_all_items('fields', $bp->groups->current_group->id);
		if (empty($fields))
			return false;
		
		foreach( $fields as $field ){
			$field->value = groups_get_groupmeta($bp->groups->current_group->id, $field->slug);
			$req = false;
			if ( $field->required == 1 ) $req = '* ';
			echo '<label for="' . $field->slug . '">' . $req . $field->title . '</label>';
			switch($field->type){
				case 'text':
					echo '<input id="' . $field->slug . '" name="bpge-' . $field->slug . '" type="text" value="' . $field->value . '" />';
					break;
				case 'textarea':
					echo '<textarea id="' . $field->slug . '" name="bpge-' . $field->slug . '">' . $field->value . '</textarea>';
					break;
				case 'select':
					echo '<select id="' . $field->slug . '" name="bpge-' . $field->slug . '">';
						echo '<option ' . ($field->value == $option ? 'selected="selected"' : '') .' value="">-------</option>';
						foreach($field->options as $option){
							echo '<option ' . ($field->value == $option ? 'selected="selected"' : '') .' value="' . $option . '">' . $option . '</option>';
						}
					echo '</select>';
					break;
				case 'checkbox':
					foreach($field->options as $option){
						echo '<input ' . ( in_array($option, (array)$field->value) ? 'checked="checked"' : '') .' type="' . $field->type . '" name="bpge-' . $field->slug . '[]" value="' . $option . '"> ' . $option . '<br />';
					}
					break;
				case 'radio':
					echo '<span id="bpge-' . $field->slug . '">';
						foreach($field->options as $option){
							echo '<input ' . ($field->value == $option ? 'checked="checked"' : '') .' type="' . $field->type . '" name="bpge-' . $field->slug . '" value="' . $option . '"> ' . $option . '<br />';
						}
					echo '</span>';
					if ($req) 
						echo '<a class="clear-value" href="javascript:clear( \'bpge-' . $field->slug . '\' );">'. __( 'Clear', 'bpge' ) .'</a>';
					break;
				case 'datebox':
					echo '<input id="' . $field->slug . '" class="datebox" name="bpge-' . $field->slug . '" type="text" value="' . $field->value . '" />';
					break;					
			}
			if ( ! empty($field->desc) ) echo '<p class="description">' . $field->desc . '</p>';
			$req = false;
		}
		
	}
	
	// Save extra fields in groupmeta
	function edit_group_fields_save($group_id){
		global $bp;
		
		if ( $bp->current_component == $bp->groups->slug && 'edit-details' == $bp->action_variables[0] ) {
			if ( $bp->is_item_admin || $bp->is_item_mod  ) {
				// If the edit form has been submitted, save the edited details
				if ( isset( $_POST['save'] ) ) {
					/* Check the nonce first. */
					if ( !check_admin_referer( 'groups_edit_group_details' ) )
						return false;
					foreach($_POST as $data => $value){
						if ( substr($data, 0, 5) === 'bpge-' )
							$to_save[$data] =  $value;
					}

					foreach($to_save as $key => $value){
						$key = substr($key, 5);
						if ( ! is_array($value) ) {
							$value = wp_kses_data($value);
							$value = force_balance_tags($value);
						}
						groups_update_groupmeta($group_id, $key, $value);
					}
				}
			}
		}
	}
	
	function widget_display() {
		echo '';
		//echo 'BP_Group_Extension::widget_display()';
	}

	// Admin area - Main
	function edit_screen() {
		global $bp;
		//print_var($bp->groups->current_group);

		if ( 'admin' == $bp->current_action && $bp->action_variables[1] == 'fields' ) {
			$this->edit_screen_fields($bp);
		}elseif ( 'admin' == $bp->current_action && $bp->action_variables[1] == 'pages' ) {
			$this->edit_screen_pages($bp);
		}elseif ( 'admin' == $bp->current_action && $bp->action_variables[1] == 'fields-manage' ) {
			$this->edit_screen_fields_manage($bp);
		}elseif ( 'admin' == $bp->current_action && $bp->action_variables[1] == 'pages-manage' ) {
			$this->edit_screen_pages_manage($bp);
		}else{
			$this->edit_screen_general($bp);
		}

	}
	
	// Admin area - General
	function edit_screen_general($bp){
		
		$public_checked = $bp->groups->current_group->extras['display_page'] == 'public' ? 'checked="checked"' : '';
		$private_checked = $bp->groups->current_group->extras['display_page'] == 'private' ? 'checked="checked"' : '';

		$this->edit_screen_head('general');
		
		echo '<p>';
			echo '<label for="group_extras_display">'.sprintf(__('Do you want to make <strong>"%s"</strong> page public (extra group information will be displayed on this page)?','bpge'), $this->nav_item_name).'</label>';
			echo '<input type="radio" value="public" '.$public_checked.' name="group-extras-display"> '.__('Show it', 'bpge').'<br />';
			echo '<input type="radio" value="private" '.$private_checked.' name="group-extras-display"> '. __('Hide it', 'bpge');
		echo '</p>';
		
		echo '<hr />';
		
		/*
		echo '<p>';
			$fields = $this->get_all_items('fields', $bp->groups->current_group->id);
			if (!empty($fields)){
				echo '<label for="group_extras_display">'.sprintf(__('Please choose below the fields you want to show on <strong>"%s"</strong> page:','bpge'), $this->nav_item_name).'</label>';
				foreach ((array)$fields as $field){
					echo '<input type="checkbox" value="1" ' . (($field->display == 1) ? 'checked="chekced"' : '' ) . ' name="extra-field-display['.$field->slug.']"> ' . $field->title . '<br />';
				}
			}
		echo '</p>';
		*/
		
		echo '<p><input type="submit" name="save_general" id="save" value="'.__('Save Changes &rarr;','bpge').'"></p>';
		wp_nonce_field('groups_edit_group_extras');
	}
	
	// Admin area - Fields
	function edit_screen_fields($bp){
		$this->edit_screen_head('fields');

		$fields = $this->get_all_items('fields', $bp->groups->current_group->id);

		if(empty($fields)){
			$this->notices('no_fields');
			return false;
		}

		echo '<ul id="fields-sortable">';
			foreach($fields as $field){
				echo '<li id="position_'.str_replace('_', '', $field->slug).'" class="default">
								<strong title="' . $field->desc . '">' . $field->title .'</strong> &rarr; ' . $field->type . ' &rarr; ' . (($field->display == 1)?__('displayed','bpge'):__('not displayed','bpge')) . '
								<span class="items-link">
									<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . 'admin/'.$this->slug . '/fields-manage/?edit=' . $field->slug . '" class="button" title="'.__('Change its title, description etc','bpge').'">'.__('Edit field', 'bpge').'</a>&nbsp;
									<a href="#" class="button delete_field" title="'.__('Delete this item and all its content', 'bpge').'">'.__('Delete', 'bpge').'</a>
								</span>
							</li>';
			}
		echo '</ul>';
	}
	
	// Admin area - Pages
	function edit_screen_pages($bp){
		$this->edit_screen_head('pages');
		
		$pages = $this->get_all_items('pages', $bp->groups->current_group->id);

		if(empty($pages)){
			$this->notices('no_pages');
			return false;
		}
		
		echo '<ul id="pages-sortable">';
			foreach($pages as $page){
				echo '<li id="position_'.str_replace('_', '', $page->slug).'" class="default">
								<strong>' . $page->title .'</strong> &rarr; ' . (($page->display == 1)?__('displayed','bpge'):__('not displayed','bpge')) . '
								<span class="items-link">
									<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . 'admin/'.$this->slug . '/pages-manage/?edit=' . $page->slug . '" class="button" title="'.__('Change its title, parent page etc','bpge').'">'.__('Edit field', 'bpge').'</a>&nbsp;
									<a href="#" class="button delete_page" title="'.__('Delete this item and all its content', 'bpge').'">'.__('Delete', 'bpge').'</a>
								</span>
							</li>';
			}
		echo '</ul>';
		
	}
	
	// Add / Edit fields form
	function edit_screen_fields_manage($bp){

		if (isset($_GET['edit']) && !empty($_GET['edit'])){
			$field = $this->get_item_by_slug('field', $_GET['edit']);
		}
		
		$this->edit_screen_head('fields-manage');
		
		echo '<p>';
			echo '<label>' . __('Field Title', 'bpge') . '</label>';
			echo '<input type="text" value="'.$field->title.'" name="extra-field-title">';
			
			if (empty($field)){
				echo '<label>' . __('Field Type', 'bpge') . '</label>';
				echo '<select name="extra-field-type" id="extra-field-type">';
					echo '<option value="text">' . __('Text Box', 'bpge') . '</option>';
					echo '<option value="textarea">' . __('Multi-line Text Box', 'bpge') . '</option>';
					echo '<option value="checkbox">' . __('Checkboxes', 'bpge') . '</option>';
					echo '<option value="radio">' . __('Radio Buttons', 'bpge') . '</option>';
					//echo '<option value="datebox">' . __('Date Selector', 'bpge') . '</option>';
					echo '<option value="select">' . __('Drop Down Select Box', 'bpge') . '</option>';
				echo '</select>';
				
				echo '<div id="extra-field-vars">';
					echo '<div class="content"></div>';
					echo '<div class="links">
									<a class="button" href="#" id="add_new">' . __('Add New', 'bpge') . '</a>
							</div>';
				echo '</div>';
			}
			echo '<label>' . __('Field Description', 'bpge') . '</label>';
				echo '<textarea name="extra-field-desc">'.$field->title.'</textarea>';
			
			echo '<label for="extra-field-required">' . __('Is this field required (will be marked as required on group Edit Details page)?','bpge') . '</label>';
				$req = '';
				$not_req = 'checked="checked"';
				if ( $field->required == 1 ) {
					$req = 'checked="checked"';
					$not_req = '';
				}
				echo '<input type="radio" value="1" '.$req.' name="extra-field-required"> '.__('Required', 'bpge').'<br />';
				echo '<input type="radio" value="0" '.$not_req.' name="extra-field-required"> '. __('Not Required', 'bpge');
				
			echo '<label for="extra-field-display">' . sprintf(__('Should this field be displayed for public on "<u>%s</u>" page?','bpge'), $this->nav_item_name) . '</label>';
				$disp = 'checked="checked"';
				$not_disp = '';
				if ( $field->display != 1 ) {
					$not_disp = 'checked="checked"';
					$disp = '';
				}
				echo '<input type="radio" value="1" '.$disp.' name="extra-field-display"> '.__('Display it', 'bpge').'<br />';
				echo '<input type="radio" value="0" '.$not_disp.' name="extra-field-display"> '. __('Do NOT display it', 'bpge');
		echo '</p>';
		
		if (empty($field)){
			echo '<p><input type="submit" name="save_fields_add" id="save" value="'.__('Create New &rarr;','bpge').'"></p>';
		}else{
			echo '<input type="hidden" name="extra-field-slug" value="' . $field->slug . '">';
			echo '<p><input type="submit" name="save_fields_edit" id="save" value="'.__('Save Changes &rarr;','bpge').'"></p>';
		}
		wp_nonce_field('groups_edit_group_extras');
	}
	
	// Add / Edit pages form
	function edit_screen_pages_manage($bp){
		if (isset($_GET['edit']) && !empty($_GET['edit'])){
			$page = $this->get_item_by_slug('page', $_GET['edit']);
		}
		$pages = $this->get_all_items('pages', $bp->groups->current_group->id);
		
		$this->edit_screen_head('pages-manage');
		
		echo '<p>';
			echo '<label>' . __('Page Title', 'bpge') . '</label>';
			echo '<input type="text" value="'.$page->title.'" name="extra-page-title">';
		echo '</p>';
		
		echo '<label>' . __('Page Parent', 'bpge') . '</label>';
			echo '<select name="extra-page-parent">';
				echo '<option value="0">'.__('no parent','').'</option>';
				foreach ($pages as $parent){
					if(!empty($parent->parent)) continue;
					$selected = '';
					if ($parent->slug == $page->parent) $selected = 'selected="selected"';
					echo '<option '. $selected .' value="'.$parent->slug.'">'.$parent->title.'</option>';
				}
			echo '</select>';
		echo '<p class="description">'.__('Only 1 level is allowed', 'bpge') .'</p>';
		
		
		echo '<p>';
			echo '<label for="extra-page-display">' . __('Should this page be displayed for public in group navigation?','bpge') . '</label>';
				$disp = 'checked="checked"';
				$not_disp = '';
				if ( $page->display != 1 ) {
					$not_disp = 'checked="checked"';
					$disp = '';
				}
				echo '<input type="radio" value="1" '.$disp.' name="extra-page-display"> '.__('Display it', 'bpge').'<br />';
				echo '<input type="radio" value="0" '.$not_disp.' name="extra-page-display"> '. __('Do NOT display it', 'bpge');
		echo '</p>';
		
		if (empty($page)){
			echo '<p><input type="submit" name="save_pages_add" id="save" value="'.__('Create New &rarr;','bpge').'"></p>';
		}else{
			echo '<input type="hidden" name="extra-page-slug" value="' . $page->slug . '">';
			echo '<p><input type="submit" name="save_pages_edit" id="save" value="'.__('Save Changes &rarr;','bpge').'"></p>';
		}
		wp_nonce_field('groups_edit_group_extras');
	}
	
	// save all changes into DB
	function edit_screen_save() {
		global $bp;
		if ( $bp->current_component == $bp->groups->slug && 'extras' == $bp->action_variables[0] ) {
			if ( !$bp->is_item_admin )
				return false;
			// Save general settings
			if ( isset($_POST['save_general'])){
				/* Check the nonce first. */
				if ( !check_admin_referer( 'groups_edit_group_extras' ) )
					return false;
				
				$meta = array();
				$meta['display_page'] = $_POST['group-extras-display'];
				
				// Save into groupmeta table
				groups_update_groupmeta( $bp->groups->current_group->id, 'bpge', $meta );
				
				$this->notices('updated');
				
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/'.$this->slug .'/' );
			}
			
			// Save new field
			if ( isset($_POST['save_fields_add'])){
				/* Check the nonce first. */
				if ( !check_admin_referer( 'groups_edit_group_extras' ) )
					return false;

				// get current fields if any
				$fields = $this->get_all_items('fields', $bp->groups->current_group->id);
				if (!$fields)	
					$fields = array();
				
				$new = new Stdclass;
				$new->title = htmlspecialchars(strip_tags($_POST['extra-field-title']));
				$new->slug = str_replace('-', '_', sanitize_title($new->title)); // will be used as unique identifier
				$new->desc = htmlspecialchars(strip_tags($_POST['extra-field-desc']));
				$new->type = $_POST['extra-field-type'];
				$new->required = $_POST['extra-field-required'];
				$new->display = $_POST['extra-field-display'];
				if(!empty($_POST['options'])){
					foreach($_POST['options'] as $option){
						$new->options[] = htmlspecialchars(strip_tags($option));
					}
				}
				
				// To the end of an array of current fields
				array_push($fields, $new);

				// Save into groupmeta table
				$fields = json_encode($fields);
				groups_update_groupmeta( $bp->groups->current_group->id, 'bpge_fields', $fields );

				$this->notices('added_field');
				
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/'.$this->slug .'/fields/' );
			}
			
			// Save new page
			if ( isset($_POST['save_pages_add'])){
				/* Check the nonce first. */
				if ( !check_admin_referer( 'groups_edit_group_extras' ) )
					return false;

				// get current pages if any
				$pages = $this->get_all_items('pages', $bp->groups->current_group->id);
				if (!$pages)	
					$pages = array();
				
				$new = new Stdclass;
				$new->title = htmlspecialchars(strip_tags($_POST['extra-page-title']));
				$new->slug = str_replace('-', '_', sanitize_title($new->title)); // will be used as unique identifier
				$new->parent = ($_POST['extra-page-parent'] == '0') ? false : $_POST['extra-page-parent'];
				$new->display = $_POST['extra-page-display'];
				
				// To the end of an array of current pages
				array_push($pages, $new);

				// Save into groupmeta table
				$pages = json_encode($pages);
				groups_update_groupmeta( $bp->groups->current_group->id, 'bpge_pages', $pages );

				$this->notices('added_page');
				
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/'.$this->slug .'/pages/' );
			}
			
			// Edit existing field
			if ( isset($_POST['save_fields_edit'])){
				/* Check the nonce first. */
				if ( !check_admin_referer( 'groups_edit_group_extras' ) )
					return false;
					
				// get current fields
				$fields = $this->get_all_items('fields', $bp->groups->current_group->id);
				foreach( $fields as $field ){
					if ( $_POST['extra-field-slug'] == $field->slug ){
						$field->title = htmlspecialchars(strip_tags($_POST['extra-field-title']));
						$field->desc = htmlspecialchars(strip_tags($_POST['extra-field-desc']));
						$field->required = $_POST['extra-field-required'];
						$field->display = $_POST['extra-field-display'];
					}
					$updated[] = $field;
				}
				// Save into groupmeta table
				$updated = json_encode($updated);
				groups_update_groupmeta( $bp->groups->current_group->id, 'bpge_fields', $updated );
				
				$this->notices('edited_field');
				
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/'.$this->slug .'/fields/' );
				
			}
			
			// Edit existing page
			if ( isset($_POST['save_pages_edit'])){
				/* Check the nonce first. */
				if ( !check_admin_referer( 'groups_edit_group_extras' ) )
					return false;
					
				// get current fields
				$pages = $this->get_all_items('pages', $bp->groups->current_group->id);
				foreach( $pages as $page ){
					if ( $_POST['extra-page-slug'] == $page->slug ){
						$page->title = htmlspecialchars(strip_tags($_POST['extra-page-title']));
						$page->parent = ($_POST['extra-page-parent'] == '0') ? false : $_POST['extra-page-parent'];
						$page->display = $_POST['extra-page-display'];
					}
					$updated[] = $page;
				}
				// Save into groupmeta table
				$updated = json_encode($updated);
				groups_update_groupmeta( $bp->groups->current_group->id, 'bpge_pages', $updated );
				
				$this->notices('edited_page');
				
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/'.$this->slug .'/pages/' );
				
			}
		}
	}

	// Display Header and Extra-Nav
	function edit_screen_head($cur = 'general'){
		$group_link = bp_get_group_permalink( $bp->groups->current_group ) . 'admin/'.$this->slug;
		switch($cur){
			case 'general':
				echo '<span class="extra-title">'.bpge_names('title_general').'</span>';
				echo '<span class="extra-subnav">
							<a href="'. $group_link .'/" class="button active">'. __('General', 'bpge') .'</a>
							<a href="'. $group_link .'/fields/" class="button">'. __('All Fields', 'bpge') .'</a>
							<a href="'. $group_link .'/pages/" class="button">'. __('All Pages', 'bpge') .'</a>
							<a href="'. $group_link .'/fields-manage/'.'" class="button">'. __('Add Field', 'bpge') .'</a>
							<a href="'. $group_link .'/pages-manage/'.'" class="button">'. __('Add Page', 'bpge') .'</a>
						</span>';
				break;
			
			case 'fields':
				echo '<span class="extra-title">'.bpge_names('title_fields').'</span>';
				echo '<span class="extra-subnav">
							<a href="'. $group_link .'/" class="button">'. __('General', 'bpge') .'</a>
							<a href="'. $group_link .'/fields/" class="button active">'. __('All Fields', 'bpge') .'</a>
							<a href="'. $group_link .'/pages/" class="button">'. __('All Pages', 'bpge') .'</a>
							<a href="'. $group_link .'/fields-manage/'.'" class="button">'. __('Add Field', 'bpge') .'</a>
							<a href="'. $group_link .'/pages-manage/'.'" class="button">'. __('Add Page', 'bpge') .'</a>
						</span>';
				break;
			
			case 'fields-manage':
				if ( isset($_GET['edit']) && !empty($_GET['edit']) ){
					echo '<span class="extra-title">'.bpge_names('title_fields_edit').'</span>';
					$active = '';
				}else{
					echo '<span class="extra-title">'.bpge_names('title_fields_add').'</span>';
					$active = 'active';
				}
				echo '<span class="extra-subnav">
							<a href="'. $group_link . '/" class="button">'. __('General', 'bpge') .'</a>
							<a href="'. $group_link . '/fields/" class="button">'. __('All Fields', 'bpge') .'</a>
							<a href="'. $group_link . '/pages/" class="button">'. __('All Pages', 'bpge') .'</a>
							<a href="'. $group_link . '/fields-manage/" class="button ' . $active . '">'. __('Add Field', 'bpge') .'</a>
							<a href="'. $group_link .'/pages-manage/'.'" class="button">'. __('Add Page', 'bpge') .'</a>
						</span>';
				break;
				
			case 'pages':
				echo '<span class="extra-title">'.bpge_names('title_pages').'</span>';
				echo '<span class="extra-subnav">
							<a href="'. $group_link .'/" class="button">'. __('General', 'bpge') .'</a>
							<a href="'. $group_link .'/fields/" class="button">'. __('All Fields', 'bpge') .'</a>
							<a href="'. $group_link .'/pages/" class="button active">'. __('All Pages', 'bpge') .'</a>
							<a href="'. $group_link .'/fields-manage/'.'" class="button">'. __('Add Field', 'bpge') .'</a>
							<a href="'. $group_link .'/pages-manage/'.'" class="button">'. __('Add Page', 'bpge') .'</a>
						</span>';
				break;
			
			case 'pages-manage':
				if ( isset($_GET['edit']) && !empty($_GET['edit']) ){
					echo '<span class="extra-title">'.bpge_names('title_pages_edit').'</span>';
					$active = '';
				}else{
					echo '<span class="extra-title">'.bpge_names('title_pages_add').'</span>';
					$active = 'active';
				}
				echo '<span class="extra-subnav">
							<a href="'. $group_link . '/" class="button">'. __('General', 'bpge') .'</a>
							<a href="'. $group_link . '/fields/" class="button">'. __('All Fields', 'bpge') .'</a>
							<a href="'. $group_link . '/pages/" class="button">'. __('All Pages', 'bpge') .'</a>
							<a href="'. $group_link . '/fields-manage/" class="button">'. __('Add Field', 'bpge') .'</a>
							<a href="'. $group_link .'/pages-manage/'.'" class="button ' . $active . '">'. __('Add Page', 'bpge') .'</a>
						</span>';
				break;
		}
		do_action('bpge_extra_menus', $cur);
	}
	
	// Getting all extra items (fields or pages) for defined group
	function get_all_items($type, $id){
		// get all fields
		$items = array();
		
		if ( $type == 'fields' ){
			$items = groups_get_groupmeta($id, 'bpge_fields');
		}elseif ( $type == 'pages' ){
			$items = groups_get_groupmeta($id, 'bpge_pages');
		}

		if (empty($items)) {
			$items = false;
		}else{
			$items = json_decode($items);
		}
		
		return $items;
	}
	
	// Get item (field or page) by slug - reusable
	function get_item_by_slug($type, $slug){
		global $bp;
		// just in case...
		if (!is_string($type) || !is_string($slug))
			return false;
			
		$items = array();
		$searched = array();
		
		if ( $type == 'field'){
			$items = $this->get_all_items('fields', $bp->groups->current_group->id);
		}elseif ( $type == 'page'){
			$items = $this->get_all_items('pages', $bp->groups->current_group->id);
		}
		
		foreach( $items as $item ){
			if ( $slug == $item->slug )
				$searched = $item;
		}
		
		return $searched;
	}
	
	// Notices about user actions
	function notices($type){
		switch($type){
			case 'updated';
				bp_core_add_message(__('Group Extras settings were succefully updated.','bpge'));
				break;
			case 'added_field';
				bp_core_add_message(__('New field was successfully added.','bpge'));
				break;
			case 'edited_field';
				bp_core_add_message(__('The field was successfully updated.','bpge'));
				break;
			case 'added_page';
				bp_core_add_message(__('New page was successfully added.','bpge'));
				break;
			case 'edited_page';
				bp_core_add_message(__('The page was successfully updated.','bpge'));
				break;
			case 'no_fields':
				echo '<div class="" id="message"><p>' . __('Please create at least 1 extra field to show it in a list.', 'bpge') . '</p></div>';
				break;
			case 'no_pages':
				echo '<div class="" id="message"><p>' . __('Please create at least 1 extra page to show it in a list.', 'bpge') . '</p></div>';
				break;
		}
	}
	
	// Handle all ajax requests
	function ajax(){
		global $bp;
		$method = isset($_POST['method']) ? $_POST['method'] : '';
		
		switch($method){
			case 'reorder_fields':
				parse_str($_POST['field_order'], $field_order );
				$fields = $this->get_all_items('fields', $bp->groups->current_group->id);

				// reorder all fields accordig to new positions
				foreach($field_order['position'] as $u_slug){
					foreach($fields as $field){
						if ( $u_slug == str_replace('_', '', $field->slug) ){
							$new_order[] = $field;
							//break;
						}
					}
				}

				// Save new order into groupmeta table
				$new_order = json_encode($new_order);
				groups_update_groupmeta( $bp->groups->current_group->id, 'bpge_fields', $new_order );
				die('saved');
				break;
				
			case 'delete_field':
				$fields = $this->get_all_items('fields', $bp->groups->current_group->id);
				$left = array();
				// Delete all corresponding data
				foreach( $fields as $field ) {
					if ( str_replace('_', '', $field->slug) == $_POST['field'] ){
						groups_delete_groupmeta($bp->groups->current_group->id, $field->slug);
						continue;
					}
					array_push($left, $field);
				}
				// Save fields that are left
				$left = json_encode($left);
				groups_update_groupmeta($bp->groups->current_group->id, 'bpge_fields', $left);
				die('deleted');
				break;
				
			case 'reorder_pages':
				parse_str($_POST['page_order'], $page_order );
				$pages = $this->get_all_items('pages', $bp->groups->current_group->id);

				// reorder all fields accordig to new positions
				foreach($page_order['position'] as $u_slug){
					foreach($pages as $page){
						if ( $u_slug == str_replace('_', '', $page->slug) ){
							$new_order[] = $page;
							//break;
						}
					}
				}

				// Save new order into groupmeta table
				$new_order = json_encode($new_order);
				groups_update_groupmeta( $bp->groups->current_group->id, 'bpge_pages', $new_order );
				die('saved');
				break;
				
			case 'delete_page':
				$pages = $this->get_all_items('pages', $bp->groups->current_group->id);
				$left = array();
				// Delete all corresponding data
				foreach( $pages as $page ) {
					if ( str_replace('_', '', $page->slug) == $_POST['page'] ){
						groups_delete_groupmeta($bp->groups->current_group->id, $page->slug);
						continue;
					}
					array_push($left, $page);
				}
				// Save fields that are left
				$left = json_encode($left);
				groups_update_groupmeta($bp->groups->current_group->id, 'bpge_pages', $left);
				die('deleted');
				break;
				
			default:
				die(1);
		}	
	}
	
	// Creation step - enter the data
	function create_screen() {
		echo 'BP_Group_Extension::create_screen()';
	}

	// Creation step - save the data
	function create_screen_save() {
		echo 'BP_Group_Extension::create_screen_save()';
	}

	// Display a link for group/site admins in BuddyBar when on group page
	function buddybar_admin_link(){
		global $bp;
		echo '<li><a href="'. bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug . '">'. __( "Manage Extras", 'bpge' ) .'</a></li>';
	}
	
	// Load if was not already loaded
	private static $instance = false;
	static function getInstance(){
		if(!self::$instance)
			self::$instance = new BPGE;
		
		return self::$instance;
	}
}

bp_register_group_extension('BPGE');

add_action('wp_ajax_bpge', 'bpge_ajax');
function bpge_ajax(){
	$load = BPGE::getInstance();
	$load->ajax();
}
