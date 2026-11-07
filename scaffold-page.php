<?php
/**
 * ONE-TIME SCAFFOLD — builds the Home page in Elementor from the Penpot "Portfolio" mockup.
 * After this, the Elementor editor is the source of truth: edit the page there, not here.
 *
 * Run:   docker compose run --rm -T -v "$PWD:/build:ro" wpcli wp eval-file /build/scaffold-page.php
 * Guard: refuses to run if the page was edited in Elementor since the last scaffold
 *        (that would wipe the edits). Add `force` at the end of the command to overwrite anyway.
 */

wp_set_current_user(1);
$home = (int) get_option('page_on_front');
if (!$home) { WP_CLI::error('No front page set — run setup.sh first'); }
$last = get_post_meta($home, '_scaffold_hash', true);
if ($last && $last !== md5(get_post_meta($home, '_elementor_data', true)) && !in_array('force', $args ?? [], true)) {
  WP_CLI::error('The Home page was edited in Elementor since the last scaffold. Re-running would wipe those edits. Add `force` to overwrite.');
}

// ---------- helpers ----------
function eid() { return substr(bin2hex(random_bytes(4)), 0, 7); }
function dim($t, $r = null, $b = null, $l = null) {
  $r ??= $t; $b ??= $t; $l ??= $r;
  return ['unit' => 'px', 'top' => "$t", 'right' => "$r", 'bottom' => "$b", 'left' => "$l", 'isLinked' => false];
}
function gap($n) { return ['unit' => 'px', 'size' => $n, 'column' => "$n", 'row' => "$n", 'isLinked' => true]; }
function px($n) { return ['unit' => 'px', 'size' => $n]; }
// global color / typography references
function gc($id) { return "globals/colors?id=$id"; }
function gt($id) { return "globals/typography?id=$id"; }

/** Container. $o: name (label in Elementor's Navigator), dir, gap, pad, bg (global id), class, id, align, justify, wrap, w (px), boxed, + raw Elementor keys */
function box(array $kids, array $o = []) {
  $s = [
    'content_width' => !empty($o['boxed']) ? 'boxed' : 'full',
    'flex_direction' => $o['dir'] ?? 'column',
    'flex_gap' => gap($o['gap'] ?? 0),
    'padding' => $o['pad'] ?? dim(0),
  ];
  if (!empty($o['boxed'])) { $s['boxed_width'] = px(1200); }
  if (isset($o['w'])) { $s['width'] = px($o['w']); }
  if (isset($o['align'])) { $s['flex_align_items'] = $o['align']; }
  if (isset($o['justify'])) { $s['flex_justify_content'] = $o['justify']; }
  if (!empty($o['wrap'])) { $s['flex_wrap'] = 'wrap'; }
  if (isset($o['class'])) { $s['css_classes'] = $o['class']; }
  if (isset($o['id'])) { $s['_element_id'] = $o['id']; }
  if (isset($o['name'])) { $s['_title'] = $o['name']; }
  if (isset($o['bg'])) {
    $s['background_background'] = 'classic';
    $s['__globals__']['background_color'] = gc($o['bg']);
  }
  if (isset($o['radius'])) { $s['border_radius'] = dim($o['radius']); }
  if (isset($o['border'])) { // [global color id, width dims]
    $s['border_border'] = 'solid';
    $s['border_width'] = $o['border'][1] ?? dim(1);
    $s['__globals__']['border_color'] = gc($o['border'][0]);
  }
  foreach ($o as $k => $v) { if (str_contains($k, '_')) { $s[$k] = $v; } } // raw responsive keys (flex_direction_mobile, padding_tablet…)
  return ['id' => eid(), 'elType' => 'container', 'isInner' => false, 'settings' => $s, 'elements' => $kids];
}
function widget($type, array $s) { return ['id' => eid(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $s, 'elements' => []]; }

/** Single-line / short text → Heading widget (no paragraph margins). */
function txt($text, $typo, $color, $tag = 'p', $extra = []) {
  $s = ['title' => $text, 'header_size' => $tag, '__globals__' => ['title_color' => gc($color)]];
  if (is_array($typo)) { $s += $typo; } else { $s['__globals__']['typography_typography'] = gt($typo); }
  if (isset($extra['link'])) { $s['link'] = ['url' => $extra['link']]; unset($extra['link']); }
  return widget('heading', array_merge($s, $extra));
}
/** Paragraph → Text Editor widget (easy for a client to edit). */
function para($html, $typo, $color, $extra = []) {
  return widget('text-editor', array_merge([
    'editor' => "<p>$html</p>",
    '__globals__' => ['text_color' => gc($color), 'typography_typography' => gt($typo)],
  ], $extra));
}
function button($text, $url, $kind, $small = false) {
  $s = [
    'text' => $text, 'link' => ['url' => $url],
    'border_radius' => dim(8), 'background_background' => 'classic', 'button_background_hover_background' => 'classic',
    'text_padding' => $small ? dim(10, 18) : dim(14, 24),
    '__globals__' => ['typography_typography' => gt('button')],
  ];
  if ($kind === 'primary') {
    $s['__globals__'] += ['background_color' => gc('primary'), 'button_text_color' => gc('invfg'), 'button_background_hover_color' => gc('accstrong'), 'hover_color' => gc('invfg')];
  } else {
    $s['border_border'] = 'solid'; $s['border_width'] = dim(1);
    $s['__globals__'] += ['background_color' => gc('bg'), 'button_text_color' => gc('primary'), 'border_color' => gc('border'), 'button_background_hover_color' => gc('surface'), 'hover_color' => gc('primary')];
  }
  return widget('button', $s);
}
function wordmark($color) { return txt('jose perez<span class="dot">.</span>', 'wordmark', $color, 'div', ['link' => '#home']); }
function chips(array $items, $dark = false) {
  return box(array_map(fn($i) => box([txt($i, 'accent', $dark ? 'invmuted' : 'secondary')],
    ['name' => "Tag · $i", 'class' => 'auto', 'pad' => dim(6, 10), 'radius' => 6, 'bg' => $dark ? 'invbg' : 'surface']), $items),
    ['name' => 'Tags', 'dir' => 'row', 'gap' => 8, 'wrap' => true, 'margin' => dim(8, 0, 0, 0)]);
}
function header_block($label, $title, $intro = null, $dark = false) {
  $k = [txt($label, 'accent', $dark ? 'accent' : 'accstrong'), txt($title, 'primary', $dark ? 'invfg' : 'primary', 'h2')];
  if ($intro) { $k[] = para($intro, 'bodylg', $dark ? 'invmuted' : 'secondary'); }
  return box($k, ['name' => 'Section title', 'gap' => 12]);
}
/** Page section: boxed 1200, responsive paddings. */
function section($id, $bg, array $kids, array $o = []) {
  return box($kids, $o + [
    'name' => 'Section · ' . ucfirst($id), 'boxed' => true, 'id' => $id, 'bg' => $bg, 'gap' => 48, 'flex_gap_mobile' => gap(32),
    'pad' => dim(112, 24), 'padding_tablet' => dim(88, 40), 'padding_mobile' => dim(64, 20),
  ]);
}
function placeholder($label, $h, $hm) {
  return box([txt($label, 'accent', 'secondary')], ['name' => 'Image placeholder', 'bg' => 'surface', 'radius' => 12, 'align' => 'center', 'justify' => 'center',
    'min_height' => px($h), 'min_height_mobile' => px($hm)]);
}
// mono text for the release card: same as Label but no uppercase
$mono = ['typography_typography' => 'custom', 'typography_font_family' => 'JetBrains Mono', 'typography_font_weight' => '500',
  'typography_font_size' => px(13), 'typography_line_height' => ['unit' => 'em', 'size' => 1.4], 'typography_letter_spacing' => px(0.5)];

// ---------- sections ----------
$nav = box([
  wordmark('primary'),
  box(array_merge(
    array_map(fn($l) => txt($l, 'text', 'secondary', 'div', ['link' => '#' . strtolower($l), 'hide_mobile' => 'hidden-mobile']), ['Work', 'Services', 'About', 'Contact']),
    [button('Let’s talk', '#contact', 'primary', true)]
  ), ['name' => 'Menu links', 'dir' => 'row', 'gap' => 32, 'align' => 'center', 'class' => 'auto']),
], ['name' => 'Header · Navigation', 'boxed' => true, 'dir' => 'row', 'justify' => 'space-between', 'align' => 'center', 'class' => 'site-nav', 'bg' => 'bg',
  'pad' => dim(20, 24), 'padding_tablet' => dim(20, 40), 'padding_mobile' => dim(14, 20), 'border' => ['border', dim(0, 0, 1, 0)]]);

$checks = [];
foreach ([['lint', 'passed'], ['unit tests', 'passed'], ['e2e tests', 'passed'], ['accessibility', 'passed'], ['deploy', 'live']] as [$k, $v]) {
  $checks[] = box([
    txt('<span class="dot">✓</span>&nbsp;&nbsp;' . $k, $mono, 'invfg'),
    txt($v, $mono, $v === 'live' ? 'accent' : 'invmuted'),
  ], ['name' => "Check · $k", 'dir' => 'row', 'justify' => 'space-between', 'align' => 'center']);
}
$hero = section('home', 'bg', [
  box([
    txt('<span class="dot">●</span>&nbsp; Software Product Engineer', 'accent', 'accstrong'),
    txt('I build software products that work — and prove it.', 'display', 'primary', 'h1'),
    para('From first idea to tested release: web apps, dashboards, automations and WordPress sites, built with a quality engineer’s eye for detail.', 'bodylg', 'secondary'),
    box([button('See my work →', '#work', 'primary'), button('Let’s talk', '#contact', 'secondary')], ['name' => 'Buttons', 'dir' => 'row', 'gap' => 12, 'wrap' => true]),
    txt('Next.js · React · PostgreSQL · WordPress · n8n · Playwright · AI automation', 'accent', 'secondary', 'p', ['_margin' => dim(16, 0, 0, 0)]),
  ], ['name' => 'Hero · Text', 'gap' => 24, 'flex_gap_mobile' => gap(20), 'class' => 'grow']),
  box(array_merge(
    [txt('●●●', $mono + ['typography_letter_spacing' => px(4)], 'invmuted', 'div', ['_css_classes' => 'dots']), txt('$ npm run release', $mono, 'invmuted')],
    $checks,
    [widget('divider', ['color' => '#27272A', 'weight' => px(1), 'gap' => px(2)]), txt('Shipped. Works on every screen.', 'text', 'invfg')]
  ), ['name' => 'Hero · Release card', 'w' => 460, 'width_tablet' => ['unit' => '%', 'size' => 100], 'class' => 'fixed', 'bg' => 'invbg', 'radius' => 16, 'gap' => 14,
    'pad' => dim(28), 'padding_mobile' => dim(20)]),
], ['dir' => 'row', 'flex_direction_tablet' => 'column', 'align' => 'center', 'flex_align_items_tablet' => 'stretch', 'gap' => 64, 'flex_gap_mobile' => gap(40),
  'pad' => dim(120, 24), 'padding_tablet' => dim(88, 40), 'padding_mobile' => dim(56, 20)]);

$services = section('services', 'surface', [
  header_block('Services', 'What I can build for you'),
  box(array_map(fn($s) => box([txt($s[0], 'accent', 'accstrong'), txt($s[1], 'secondary', 'primary', 'h3'), para($s[2], 'text', 'secondary')],
    ['name' => "Service · {$s[1]}", 'bg' => 'bg', 'border' => ['border'], 'radius' => 12, 'pad' => dim(28), 'padding_mobile' => dim(24), 'gap' => 12]), [
    ['01', 'Websites & WordPress', 'Presentation pages, portfolios and business sites that you can edit yourself. Responsive, fast and ready to launch.'],
    ['02', 'Web apps & dashboards', 'Custom tools that turn your data into decisions — from internal apps to analytics dashboards with clear, live charts.'],
    ['03', 'Automation & integrations', 'Connect the tools you already use. I automate the repetitive work — data syncs, reports, notifications — so it runs without you.'],
    ['04', 'Quality & testing', 'Automated tests, release checks and QA processes, so what goes live keeps working after launch.'],
  ]), ['name' => 'Service cards', 'dir' => 'row', 'flex_direction_tablet' => 'column', 'gap' => 24, 'flex_gap_mobile' => gap(16), 'class' => 'eq-d']),
]);

// Image widget from a media-library file (found by file name, so re-runs don't depend on attachment IDs).
function image($slug, $extra = []) {
  $a = get_posts(['post_type' => 'attachment', 'numberposts' => 1, 'post_status' => 'inherit',
    'meta_query' => [['key' => '_wp_attached_file', 'value' => "/$slug.", 'compare' => 'LIKE']]]);
  if (!$a) { WP_CLI::error("Missing media: $slug (run: wp media import assets/$slug.*)"); }
  return widget('image', array_merge(['image' => ['id' => $a[0]->ID, 'url' => wp_get_attachment_url($a[0]->ID)], 'image_size' => 'full',
    'width' => ['unit' => '%', 'size' => 100], 'image_border_radius' => dim(12)], $extra));
}

$project = function ($t, $tag, $prob, $built, $res, $stack, $img = null) {
  $blocks = [];
  foreach ([['Problem', $prob], ['What I built', $built], ['Result', $res]] as [$k, $v]) {
    if ($v) { $blocks[] = box([txt($k, 'accent', 'secondary'), para($v, 'text', 'primary')], ['name' => $k, 'gap' => 4]); }
  }
  return box([
    $img ? image($img) : placeholder('Project screenshot', 280, 200),
    box(array_merge([txt($t, 'secondary', 'primary', 'h3'), txt($tag, 'text', 'accstrong')], $blocks, [chips($stack)]),
      ['name' => 'Project text', 'gap' => 12, 'pad' => dim(0, 8), 'padding_mobile' => dim(0, 4)]),
  ], ['name' => "Project · $t", 'border' => ['border'], 'radius' => 16, 'bg' => 'bg', 'pad' => dim(24), 'padding_mobile' => dim(16), 'gap' => 20]);
};
$P = [
  $project('Social Media Analytics Platform', 'One dashboard for TikTok, Instagram, Facebook and YouTube.',
    'A content creator was checking four platforms by hand, with no way to compare performance or see what content actually worked.',
    'Automated workflows that pull data from each platform’s API every day into a central database, plus a dashboard with growth trends, a content performance matrix and strategy reports.',
    'Zero manual data collection; content decisions based on numbers instead of guesses.', ['n8n', 'PostgreSQL', 'Next.js', 'React', 'Recharts', 'Docker'], 'analytics-dashboard'),
  $project('Personal Finance App', 'Household expenses, organized and tax-ready.',
    'Expenses spread across banks and cards made monthly budgeting and yearly tax filing slow and error-prone.',
    'A web app to record and categorize transactions, track monthly spending and investments, and flag tax-deductible expenses automatically.',
    'Monthly reports in one click and a ready-made summary at tax time.', ['Next.js', 'React', 'PostgreSQL', 'Tailwind', 'Recharts', 'Jest'], 'personal-finance'),
  $project('AI Job Runner', 'Let your apps hand work to an AI agent.', null,
    'A lightweight local service that queues tasks from any app, runs them with an AI agent in the background and sends the result back when done.',
    'Apps gain AI features without each one re-implementing the plumbing.', ['Node.js', 'Claude AI', 'REST'], 'ai-job-runner-diagram'),
  $project('This website', 'Built with WordPress + Elementor.', null,
    'This one-page portfolio — custom layout, global styles, responsive on every device, with a contact form ready to go.', null, ['WordPress', 'Elementor'], 'this-website'),
];
$rowOpts = ['dir' => 'row', 'flex_direction_mobile' => 'column', 'gap' => 24, 'flex_gap_mobile' => gap(16), 'class' => 'eq'];
$work = section('work', 'bg', [
  header_block('Work', 'Selected work', 'Real products I designed, built and maintain.'),
  box([box([$P[0], $P[1]], $rowOpts + ['name' => 'Row 1']), box([$P[2], $P[3]], $rowOpts + ['name' => 'Row 2'])], ['name' => 'Project cards', 'gap' => 24, 'flex_gap_mobile' => gap(16)]),
]);

$steps = [['Understand', 'A short call to learn your goals, audience and must-haves.'], ['Plan', 'A clear scope, timeline and price. No surprises.'],
  ['Build', 'Regular previews so you see progress and give feedback early.'], ['Test & launch', 'Checked on desktop, tablet and phone before anything goes live.'],
  ['Hand over', 'You get a site you can edit yourself, plus a short guide.']];
$process = section('process', 'surface', [
  header_block('Process', 'How we’ll work together'),
  box(array_map(fn($i) => box([txt('0' . ($i + 1), 'accent', 'accstrong'), txt($steps[$i][0], 'secondary', 'primary', 'h3'), para($steps[$i][1], 'text', 'secondary')],
    ['name' => "Step · {$steps[$i][0]}", 'gap' => 10, 'pad' => dim(28, 0, 0, 0), 'border' => [$i % 2 ? 'accent' : 'primary', dim(2, 0, 0, 0)]]), array_keys($steps)),
    ['name' => 'Steps', 'dir' => 'row', 'flex_direction_tablet' => 'column', 'gap' => 24, 'class' => 'eq-d']),
]);

$about = section('about', 'bg', [
  box([image('headshot', ['image_border_radius' => dim(16), 'height' => px(520), 'height_mobile' => px(360), 'object-fit' => 'cover'])],
    ['name' => 'Photo', 'w' => 440, 'width_tablet' => ['unit' => '%', 'size' => 100], 'class' => 'fixed']),
  box([
    header_block('About', 'Hi, I’m Jose'),
    para('I’m a software product engineer based in Portugal. My background is in product quality — years of making sure software does what it promises — and today I build the products myself.', 'bodylg', 'secondary'),
    para('That mix is what you get when you work with me: I think like a builder <em>and</em> like a tester. I ask the questions early, I keep you in the loop, and I don’t call something done until it works on every screen.', 'bodylg', 'secondary'),
    para('When I’m not building for clients, I’m building tools for myself — finance trackers, analytics pipelines, AI automations — because I like solving real problems with software.', 'bodylg', 'secondary'),
    chips(['Portugal · works worldwide', 'English · Spanish · Portuguese', 'Replies within 24h']),
  ], ['name' => 'About · Text', 'gap' => 20, 'class' => 'grow']),
], ['dir' => 'row', 'flex_direction_tablet' => 'column', 'align' => 'center', 'flex_align_items_tablet' => 'stretch', 'gap' => 80, 'flex_gap_mobile' => gap(32)]);

$links = [['Upwork ↗', 'https://www.upwork.com/freelancers/~01ae7e884d90b97371'], ['LinkedIn ↗', 'https://www.linkedin.com/in/jose-manuel-perez-castillo-67377b123'],
  ['GitHub ↗', 'https://github.com/joseperez2596'], ['joseperez2596@gmail.com', 'mailto:joseperez2596@gmail.com']];
$contact = section('contact', 'invbg', [
  box([
    header_block('Contact', 'Let’s build something', 'Have a project in mind, or just a question? Send me a message and I’ll get back to you within 24 hours.', true),
    box(array_map(fn($l) => txt($l[0], 'text', 'invfg', 'div', ['link' => $l[1]]), $links), ['name' => 'Links', 'gap' => 12]),
  ], ['name' => 'Contact · Text', 'gap' => 24, 'class' => 'grow']),
  box([widget('shortcode', ['shortcode' => '[fluentform id="1"]'])], ['name' => 'Contact form', 'class' => 'grow form-card', 'bg' => 'bg', 'radius' => 16, 'pad' => dim(32), 'padding_mobile' => dim(20)]),
], ['dir' => 'row', 'flex_direction_mobile' => 'column', 'gap' => 80, 'flex_gap_tablet' => gap(40), 'flex_gap_mobile' => gap(40)]);

$footer = box([wordmark('invfg'), txt('© 2026 Jose Perez · Software Product Engineer', 'text', 'invmuted')], [
  'name' => 'Footer', 'boxed' => true, 'dir' => 'row', 'flex_direction_mobile' => 'column', 'justify' => 'space-between', 'align' => 'center', 'flex_align_items_mobile' => 'flex-start',
  'gap' => 8, 'bg' => 'invbg', 'pad' => dim(28, 24), 'padding_tablet' => dim(28, 40), 'padding_mobile' => dim(28, 20),
  'border_border' => 'solid', 'border_width' => dim(1, 0, 0, 0), 'border_color' => '#27272A',
]);

$data = [$nav, $hero, $services, $work, $process, $about, $contact, $footer];

// ---------- save ----------
$doc = \Elementor\Plugin::$instance->documents->get($home, false);
$doc->set_is_built_with_elementor(true);
$doc->save(['elements' => $data, 'settings' => ['template' => 'elementor_canvas']]);
update_post_meta($home, '_wp_page_template', 'elementor_canvas');

// Site-wide CSS (Appearance → Customize → Additional CSS): things Elementor Free has no setting for.
$css = <<<CSS
/* =====================================================================
   SITE LAYOUT HELPERS — please don't edit unless you know CSS.
   Colors and fonts are NOT set here: change them in Elementor →
   Site Settings → Global Colors / Global Fonts, and this file follows.
   ===================================================================== */

/* Menu links scroll smoothly to each section, stopping below the sticky menu */
html { scroll-behavior: smooth; }
.e-con[id] { scroll-margin-top: 72px; }
/* Keeps the top menu visible while scrolling (block with CSS class "site-nav") */
.site-nav { position: sticky; top: 0; z-index: 100; }
/* The green dot in "jose perez." and the hero label */
.dot { color: var(--e-global-color-accent); }
.elementor-heading-title a { color: inherit; }
.elementor-heading-title a:hover { color: var(--e-global-color-accent); }
/* Removes the extra space under the last paragraph of a text block */
.elementor-widget-text-editor p:last-child { margin-bottom: 0; }
/* Layout classes used in Advanced → CSS Classes:
   auto  = block is only as wide as its content (tags, menu)
   fixed = block keeps its set width (photo, release card)
   eq    = blocks inside share the width equally from tablet up (project cards)
   eq-d  = same, desktop only (service cards, process steps)
   grow  = block takes the remaining space on desktop (hero text, about text) */
.e-con.auto { --width: auto; flex: 0 0 auto; }
.e-con.fixed { flex-shrink: 0; }
@media (min-width: 768px) { .e-con.eq > .e-con { flex: 1 1 0; min-width: 0; } }
@media (min-width: 1025px) {
  .e-con.eq-d > .e-con { flex: 1 1 0; min-width: 0; }
  .e-con > .e-con-inner > .e-con.grow, .e-con > .e-con.grow { flex: 1 1 0; min-width: 0; }
}
/* Smaller "Let's talk" button in the phone menu */
@media (max-width: 767px) { .site-nav .elementor-widget-button .elementor-button { padding: 8px 14px; } }
/* Contact form: makes Fluent Forms use the site's colors and fonts */
body .fluentform .ff-el-group { margin-bottom: 18px; }
body .fluentform .ff-el-input--label label { font-family: var(--e-global-typography-text-font-family); font-size: 16px; font-weight: 400; color: var(--e-global-color-primary); }
body .fluentform .ff-el-input--label.asterisk-right label:after { color: var(--e-global-color-accent); }
body .fluentform .ff-el-form-control { font-family: var(--e-global-typography-text-font-family); font-size: 16px; color: var(--e-global-color-primary);
  border: 1px solid var(--e-global-color-border); border-radius: 8px; padding: 12px 14px; background: #fff; }
body .fluentform .ff-el-form-control::placeholder { color: var(--e-global-color-invmuted); }
body .fluentform .ff-el-form-control:focus { border-color: var(--e-global-color-accent); box-shadow: 0 0 0 3px rgba(16,185,129,.18); outline: none; }
body .fluentform textarea.ff-el-form-control { min-height: 128px; }
body .fluentform .ff_submit_btn_wrapper { margin-bottom: 0; }
body .fluentform .ff-btn-submit { width: 100%; background: var(--e-global-color-primary) !important; color: var(--e-global-color-invfg) !important;
  border-radius: 8px; padding: 14px 24px; font-family: var(--e-global-typography-button-font-family); font-weight: 600; font-size: 16px; border: 0; }
body .fluentform .ff-btn-submit:hover { background: var(--e-global-color-accstrong) !important; opacity: 1; }
body .ff-message-success { border: 1px solid var(--e-global-color-accent); border-radius: 8px; color: var(--e-global-color-primary); box-shadow: none; }
CSS;
wp_update_custom_css_post($css, ['stylesheet' => get_stylesheet()]);

// Kit: no default container padding (every container sets its own).
$kit_id = (int) get_option('elementor_active_kit');
$ks = get_post_meta($kit_id, '_elementor_page_settings', true);
$ks['container_padding'] = dim(0);
update_post_meta($kit_id, '_elementor_page_settings', $ks);

// Reusable blocks in Templates → Saved Templates (insert via the folder icon in the editor).
$src = \Elementor\Plugin::$instance->templates_manager->get_source('local');
$tpl = ['Project card' => $P[0], 'Service card' => $services['elements'][1]['elements'][0], 'Process step' => $process['elements'][1]['elements'][0]];
foreach ($tpl as $title => $el) {
  foreach (get_posts(['post_type' => 'elementor_library', 'title' => $title, 'numberposts' => -1, 'post_status' => 'any']) as $old) { wp_delete_post($old->ID, true); }
  $id = $src->save_item(['type' => 'container', 'title' => $title, 'content' => [$el]]);
  if (is_wp_error($id)) { WP_CLI::warning("Template $title: " . $id->get_error_message()); }
}

update_post_meta($home, '_scaffold_hash', md5(get_post_meta($home, '_elementor_data', true)));
\Elementor\Plugin::$instance->files_manager->clear_cache();
WP_CLI::success("Home page ($home) built: " . count($data) . ' sections');
