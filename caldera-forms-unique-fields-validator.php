<?php
/*
 * Plugin Name: Caldera Forms Unique Fields Validator
 * Description: Validator for unique fields per form. For example only one email address user can be submitted per form. Except to "Email only once per form" validator submitting works with multiple forms and the field of email slug can be configured. Furthermore all given field slugs define a unique constraint.
 * Author: MOEWE
 * Author URI: https://www.moewe.io
 * Text Domain: caldera-forms-unique-fields-validator
*/

add_filter('caldera_forms_get_form_processors', 'caldera_forms_unique_fields_validator_processor');
function caldera_forms_unique_fields_validator_processor($processors)
{
    $processors['caldera_forms_unique_fields_validator'] = array(
        'name' => __('Unique Fields Validator', 'caldera-forms-unique-fields-validator'),
        'description' => __('Processor to define fields for unique constraint.'),
        'pre_processor' => 'caldera_forms_unique_fields_validator_pre_processor',
        'template' => __DIR__ . '/templates/caldera_forms_unique_fields_validator_processor.config.php'
    );
    return $processors;
}

function caldera_forms_unique_fields_validator_pre_processor($config, $form)
{
    global $wpdb;

    $cf_unique_fields_validator_slugs = Caldera_Forms::do_magic_tags($config['cf_unique_fields_validator_slugs']);
	
	$cf_unique_fields_validator_slugs = array_map('trim',explode(",", $cf_unique_fields_validator_slugs));
	$cf_unique_fields_validator_slug_ids = array();
	
    foreach ($form['fields'] as $field) {
        if( in_array($field['slug'], $cf_unique_fields_validator_slugs) ) {
			$cf_unique_fields_validator_slug_ids[] = $field['ID'];
		}
    }

	if( sizeof($cf_unique_fields_validator_slug_ids) > 0 ) {	
	
		$raw_data = Caldera_Forms::get_submission_data($form);

		$unique_count_sql = "
			SELECT 
			  COUNT(*) 
			FROM 
				{$wpdb->prefix}cf_form_entries AS cffe
			WHERE 
				cffe.form_id = '{$form['ID']}'
		";
		
		foreach( $cf_unique_fields_validator_slug_ids as $cf_unique_fields_validator_slug_id ) {
			$unique_count_sql .= " AND EXISTS (SELECT 1 FROM {$wpdb->prefix}cf_form_entry_values AS cffev WHERE cffev.entry_id = cffe.id  AND cffev.field_id = '{$cf_unique_fields_validator_slug_id}' AND cffev.value = '" . esc_sql($raw_data[$cf_unique_fields_validator_slug_id]) . "') ";
		}
			
		$unique_count = $wpdb->get_var($unique_count_sql);
		
		if ($unique_count > 0) {
			return array(
				'note' => __(Caldera_Forms::do_magic_tags($config['cf_unique_fields_validator_error_message']), 'caldera-forms-unique-fields-validator'),
				'type' => 'error'
			);
		}

	}
    return;
}

function caldera_forms_unique_fields_validator_fields()
{
    return array(
		array(
            'id' => 'cf_unique_fields_validator_slugs',
            'label' => 'Unique Field Slugs',
            'type' => 'text',
            'required' => true,
            'magic' => false,
            'desc' => 'Define comma separated field slugs which should be used for unique constraint.'
        ),
		array(
            'id' => 'cf_unique_fields_validator_error_message',
            'label' => 'Error Message',
            'type' => 'text',
            'required' => true,
            'magic' => false,
            'desc' => 'Error message to display when unique constraint fails.'
        ),
    );
}
