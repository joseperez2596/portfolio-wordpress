<?php
/**
 * Turns Fluent Forms' demo form (id 1) into the portfolio contact form.
 * Run: docker compose run --rm -T -v "$PWD:/build:ro" wpcli wp eval-file /build/form.php
 */
global $wpdb;
$t = $wpdb->prefix . 'fluentform_forms';
$form = $wpdb->get_row("SELECT * FROM $t WHERE id = 1");
if (!$form) { WP_CLI::error('Form 1 not found'); }
$old = json_decode($form->form_fields, true);

$req = fn($msg) => ['required' => ['value' => true, 'message' => $msg]];
$text = fn($i, $name, $label, $ph, $rules) => [
  'index' => $i, 'element' => 'input_text',
  'attributes' => ['type' => 'text', 'name' => $name, 'value' => '', 'class' => '', 'placeholder' => $ph],
  'settings' => ['container_class' => '', 'label' => $label, 'label_placement' => '', 'admin_field_label' => $label, 'help_message' => '',
    'validation_rules' => $rules, 'conditional_logics' => []],
  'editor_options' => ['title' => 'Simple Text', 'icon_class' => 'ff-edit-text', 'template' => 'inputText'],
  'uniqElKey' => "el_$name",
];
$fields = [
  $text(0, 'name', 'Name', 'Your name', $req('Please tell me your name')),
  [
    'index' => 1, 'element' => 'input_email',
    'attributes' => ['type' => 'email', 'name' => 'email', 'value' => '', 'id' => '', 'class' => '', 'placeholder' => 'you@company.com'],
    'settings' => ['container_class' => '', 'label' => 'Email', 'label_placement' => '', 'help_message' => '', 'admin_field_label' => 'Email',
      'validation_rules' => $req('I need an email to reply to') + ['email' => ['value' => true, 'message' => 'That email doesn’t look right']],
      'conditional_logics' => []],
    'editor_options' => ['title' => 'Email Address', 'icon_class' => 'ff-edit-email', 'template' => 'inputText'],
    'uniqElKey' => 'el_email',
  ],
  [
    'index' => 2, 'element' => 'select',
    'attributes' => ['name' => 'need', 'value' => '', 'id' => '', 'class' => ''],
    'settings' => ['dynamic_default_value' => '', 'label' => 'What do you need?', 'admin_field_label' => 'Need', 'help_message' => '',
      'container_class' => '', 'label_placement' => '', 'placeholder' => 'Website / Web app / Automation / Other',
      'advanced_options' => array_map(fn($o) => ['label' => $o, 'value' => $o, 'calc_value' => ''], ['Website', 'Web app', 'Automation', 'Other']),
      'calc_value_status' => false, 'enable_image_input' => false, 'enable_select_2' => 'no',
      'validation_rules' => ['required' => ['value' => false, 'message' => 'This field is required']], 'conditional_logics' => []],
    'editor_options' => ['title' => 'Dropdown', 'icon_class' => 'ff-edit-dropdown', 'element' => 'select', 'template' => 'select'],
    'uniqElKey' => 'el_need',
  ],
  [
    'index' => 3, 'element' => 'textarea',
    'attributes' => ['name' => 'message', 'value' => '', 'id' => '', 'class' => '', 'placeholder' => 'Tell me about your project…', 'rows' => 4, 'cols' => 2],
    'settings' => ['container_class' => '', 'label' => 'Message', 'admin_field_label' => 'Message', 'label_placement' => '', 'help_message' => '',
      'validation_rules' => $req('A short message helps me reply properly'), 'conditional_logics' => []],
    'editor_options' => ['title' => 'Text Area', 'icon_class' => 'ff-edit-textarea', 'template' => 'inputTextarea'],
    'uniqElKey' => 'el_message',
  ],
];
$submit = $old['submitButton'];
$submit['settings']['button_ui']['text'] = 'Send message';
$submit['settings']['align'] = 'left';

$wpdb->update($t, ['title' => 'Portfolio contact', 'form_fields' => wp_json_encode(['fields' => $fields, 'submitButton' => $submit])], ['id' => 1]);

// Success message
$mt = $wpdb->prefix . 'fluentform_form_meta';
$raw = $wpdb->get_var("SELECT value FROM $mt WHERE form_id = 1 AND meta_key = 'formSettings'");
$settings = json_decode($raw, true);
$settings['confirmation']['messageToShow'] = 'Thanks! I’ll reply within 24 hours.';
$settings['confirmation']['redirectTo'] = 'samePage';
$wpdb->update($mt, ['value' => wp_json_encode($settings)], ['form_id' => 1, 'meta_key' => 'formSettings']);

// Drop the unused demo subscription form
$wpdb->delete($t, ['id' => 2]);
$wpdb->delete($mt, ['form_id' => 2]);
WP_CLI::success('Contact form configured');
