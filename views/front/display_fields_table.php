<table class="profile-fields zebra">
    <?php
    foreach($fields as $field){
        $field->desc    = get_post_meta($field->ID, 'bpge_field_desc', true);
        $field->options = json_decode($field->post_content);

        echo '<tr><td class="label" title="' . ( ! empty($field->desc)  ? esc_attr($field->desc) : '')  .'">' . stripslashes($field->post_title) .'</td>';

        if ( is_array($field->options) )
            $data = implode(', ', $field->options);
        else
            $data = $field->post_content;
        echo '<td class="data">' . $data . '</td></tr>';
    } ?>
</table>