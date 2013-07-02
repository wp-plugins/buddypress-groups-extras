<div class="extra-data">
    <?php
    foreach($fields as $field){
        $field->desc    = get_post_meta($field->ID, 'bpge_field_desc', true);
        $field->options = json_decode($field->post_content);

        echo '<h4 title="' . ( ! empty($field->desc)  ? esc_attr($field->desc) : '')  .'">' . stripslashes($field->post_title) .'</h4>';

        if ( is_array($field->options) )
            $data = implode(', ', $field->options);
        else
            $data = $field->post_content;
        echo '<p>' . $data . '</p>';
    }
    ?>
</div>