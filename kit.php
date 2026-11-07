<?php
// Seeds Elementor's global kit with the Penpot "Portfolio" tokens. Run: wp eval-file kit.php
$kit_id = (int) get_option('elementor_active_kit');
if (!$kit_id) { WP_CLI::error('No active Elementor kit'); }
$s = get_post_meta($kit_id, '_elementor_page_settings', true) ?: [];
$c = fn($id, $title, $hex) => ['_id' => $id, 'title' => $title, 'color' => $hex];
$s['system_colors'] = [
  $c('primary', 'Foreground', '#09090B'), $c('secondary', 'Muted', '#52525B'),
  $c('text', 'Body text', '#52525B'), $c('accent', 'Accent', '#10B981'),
];
$s['custom_colors'] = [
  $c('bg', 'Background', '#FFFFFF'), $c('surface', 'Surface', '#F4F4F5'), $c('border', 'Border', '#E4E4E7'),
  $c('accstrong', 'Accent Strong', '#047857'), $c('invbg', 'Inverse Background', '#09090B'),
  $c('invfg', 'Inverse Foreground', '#FAFAFA'), $c('invmuted', 'Inverse Muted', '#A1A1AA'),
];
// [id, title, family, weight, desktop px, mobile px, line-height em, letter-spacing px, transform]
$t = function ($id, $title, $fam, $w, $d, $m, $lh, $ls, $tt = '') {
  $x = ['_id' => $id, 'title' => $title, 'typography_typography' => 'custom',
    'typography_font_family' => $fam, 'typography_font_weight' => (string) $w,
    'typography_font_size' => ['unit' => 'px', 'size' => $d],
    'typography_font_size_mobile' => ['unit' => 'px', 'size' => $m],
    'typography_line_height' => ['unit' => 'em', 'size' => $lh],
    'typography_letter_spacing' => ['unit' => 'px', 'size' => $ls]];
  if ($tt) { $x['typography_text_transform'] = $tt; }
  return $x;
};
$SG = 'Space Grotesk'; $JB = 'JetBrains Mono';
$s['system_typography'] = [
  $t('primary', 'Heading (H2)', $SG, 700, 40, 30, 1.15, -0.5),
  $t('secondary', 'Subheading (H3)', $SG, 600, 22, 20, 1.3, 0),
  $t('text', 'Body', $SG, 400, 16, 16, 1.6, 0),
  $t('accent', 'Label', $JB, 500, 13, 13, 1.4, 1, 'uppercase'),
];
$s['custom_typography'] = [
  $t('display', 'Display', $SG, 700, 64, 40, 1.05, -1.5),
  $t('bodylg', 'Body Large', $SG, 400, 20, 18, 1.6, 0),
  $t('button', 'Button', $SG, 600, 16, 16, 1.2, 0, 'none'),
  $t('wordmark', 'Wordmark', $SG, 700, 22, 22, 1.2, -0.5),
];
$s['container_width'] = ['unit' => 'px', 'size' => 1200];
$s['body_background_background'] = 'classic';
$s['body_background_color'] = '#FFFFFF';
$s['body_color'] = '#52525B';
$s['body_typography_typography'] = 'custom';
$s['body_typography_font_family'] = $SG;
$s['viewport_mobile'] = 767;
update_post_meta($kit_id, '_elementor_page_settings', $s);
\Elementor\Plugin::$instance->files_manager->clear_cache();
WP_CLI::success("Kit $kit_id: " . count($s['system_colors']) + count($s['custom_colors']) . ' colors, ' . (count($s['system_typography']) + count($s['custom_typography'])) . ' fonts');
