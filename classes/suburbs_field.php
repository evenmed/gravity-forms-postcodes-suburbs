<?php
class GF_Suburbs_Field extends GF_Field {

    public $type = 'suburbs_field';

    public function get_form_editor_button(){
        return array(
            'group' => 'advanced_fields',
            'text'  => __('Suburbs', 'wordpress')
        );
    }

    public function get_form_editor_field_title() {
        return __('Suburbs List', 'gravityforms');
    }

    public function get_form_editor_field_settings() {
        return array(
            'label_setting'
        );
    }

    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen') {
        if (is_array($value)) {
            return print_r($value, true);
            $items = '';
            foreach ($value as $key => $item) {
                if (!empty($item)) {
                    switch ($format) {
                        case 'text' :
                            $items .= \GFCommon::selection_display($item, $this, $currency, $use_text) . ', ';
                            break;
                        default:
                            $items .= '<li>' . \GFCommon::selection_display( $item, $this, $currency, $use_text ) . '</li>';
                            break;
                    }
                }
            }
            if (empty($items)) {
                return '';
            } elseif ($format == 'text') {
                return substr($items, 0, strlen( $items ) - 2);
            } else {
                return "<ul class='bulleted'>$items</ul>";
            }
        } else {
            return $value;
        }
    }

    public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {

        return $value;
    }

    public function is_conditional_logic_supported() {
        return true;
    }

    public function get_field_input( $form, $value = '', $entry = null ) {
        $form_id = $form['id'];
        $is_multipage = $form['pagination'];
        $id = (int) $this->id;
        $input_id = "input_$id";

        $input = '<div id="gf-postcode-wrap_'. $form_id .'" class="ginput_complex ginput_container gf-postcode-wrap';
        if ($is_multipage)
            $input .= ' multipage_form ';
        $input .= '">';
        $input .= '<input data-input_id="'. $input_id .'" id="gf-postcode_'. $form_id .'" class="gf-postcode" name="postcode_'. $form_id .'" placeholder="Postcode" />';
        $input .= '<div class="gf-suburbs-wrap" data-formid=' . $form_id . '></div>';
        if ($value) {
            $input .= "<input type='hidden' id='$input_id' name='$input_id' value='$value' />";
        }
        $input .= '</div>';

        return $input;
    }

    public function get_form_inline_script_on_page_render( $form ) {
        $script = "var ajaxurl = '" . admin_url( 'admin-ajax.php' ) . "';";
        $script .= file_get_contents( plugin_dir_path( __FILE__ ) . '../js/frontend.js');
        return $script;
    }

}
