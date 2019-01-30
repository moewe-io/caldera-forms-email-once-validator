<?php
/*
 * Plugin Name: Caldera Forms Email Once Validator
 * Description: Validator for only one email address per form. Except to default "Email only once per form" validator submitting works with multiple forms and the field of email slug can be configured
 * Author: MOEWE
 * Author URI: https://www.moewe.io
 * Text Domain: caldera-forms-email-once-validator
*/

add_filter('caldera_forms_get_form_processors', 'caldera_forms_email_once_validator_processor');
function  caldera_forms_email_once_validator_processor($processors)
{
    $processors['caldera_forms_email_once_validator'] = array(
        'name' => __('E-Mail Once Validator', 'caldera-forms-email-once-validator'),
        'description' => __('Processor to validate E-Mail field.'),
        'pre_processor' => 'caldera_forms_email_once_validator_pre_processor',
        'template' => __DIR__ . '/templates/caldera_forms_email_once_validator_processor.config.php'
    );
    return $processors;
}

function caldera_forms_email_once_validator_pre_processor($config, $form)
{
    global $wpdb;

    $cf_email_once_slug = Caldera_Forms::do_magic_tags($config['cf_email_once_slug']);

    foreach ($form['fields'] as $field) {
        if ($field['slug'] == $cf_email_once_slug) {
            $cf_email_once_field_id = $field['ID'];
            break;
        }
    }

    if (!$cf_email_once_field_id) {
        return array(
            'note' => __('Es wurde keine Feld ID für den konfigurierten E-Mail Slug gefunden.','caldera-forms-email-once-validator'),
            'type' => 'error'
        );
    }

    $raw_data = Caldera_Forms::get_submission_data($form);

    $email_once_field_value = $raw_data[$cf_email_once_field_id];

    $email_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}cf_form_entry_values WHERE field_id='{$cf_email_once_field_id}' AND value = '" . esc_sql($email_once_field_value) . "'" );

    if($email_count > 0) {
        return array(
            'note' => __('Die eingebene E-Mail-Adresse ist bereits vorhanden.','caldera-forms-email-once-validator'),
            'type' => 'error'
        );
    }

    return;
}

function caldera_forms_email_once_validator_fields()
{
    return array(
        array(
            'id' => 'cf_email_once_slug',
            'label' => 'E-Mail Slug',
            'type' => 'text',
            'required' => true,
            'magic' => false,
            'desc' => 'Define custom email slug.'
        ),
    );
}
