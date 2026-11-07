<?php
/**
 * Plugin Name: Site guide
 * Description: "How to edit your site" box on the WordPress dashboard, for the site owner.
 */
add_action('wp_dashboard_setup', function () {
  wp_add_dashboard_widget('site_guide', 'How to edit your site', function () {
    $edit = admin_url('post.php?post=' . (int) get_option('page_on_front') . '&action=elementor');
    $entries = admin_url('admin.php?page=fluent_forms_all_entries');
    ?>
    <style>
      #site_guide h4 { margin: 16px 0 4px; font-size: 14px; }
      #site_guide h4:first-of-type { margin-top: 4px; }
      #site_guide ol, #site_guide ul { margin: 4px 0 0 18px; }
      #site_guide li { margin-bottom: 2px; }
      #site_guide .warn { background: #fef3c7; border-left: 3px solid #d97706; padding: 8px 10px; margin-top: 16px; }
    </style>
    <p><a class="button button-primary" href="<?= esc_url($edit) ?>">Edit the home page</a></p>

    <h4>Change any text</h4>
    <ol>
      <li>Click <strong>Edit the home page</strong> above.</li>
      <li>Click the text on the page and type.</li>
      <li>Click <strong>Publish</strong> (top right) to save.</li>
    </ol>

    <h4>Find a part of the page</h4>
    <p>Open the <strong>Structure</strong> panel (the layers icon in the top bar, or <kbd>Ctrl/Cmd</kbd>+<kbd>I</kbd>).
      Every block has a name, like <em>Section · Work</em> or <em>Project · AI Job Runner</em>. Click a name to jump to it.</p>

    <h4>Swap an image</h4>
    <ol>
      <li>Click the image, then <strong>Choose Image</strong> in the left panel.</li>
      <li>Upload the new one and click <strong>Insert Media</strong>.</li>
    </ol>
    <p>Best sizes: project images 1080 × 560 px; your photo portrait, at least 700 px wide.</p>

    <h4>Add a project</h4>
    <ol>
      <li>In <strong>Structure</strong>, right-click any <em>Project · …</em> card → <strong>Duplicate</strong>.</li>
      <li>Change the title, texts, tags and image of the copy.</li>
    </ol>
    <p>Cards sit two per row (<em>Row 1</em>, <em>Row 2</em>). For a fifth project, duplicate a whole row and delete the extra card.
      Clean copies of a <em>Project card</em>, <em>Service card</em> and <em>Process step</em> are saved as templates: at the bottom of the page in the editor, click the folder icon → <strong>My Templates</strong> → <strong>Insert</strong>, then drag the block where you want it in <strong>Structure</strong>.</p>

    <h4>Change colors or fonts everywhere</h4>
    <p>In the editor: menu icon (top left) → <strong>Site Settings</strong> → <strong>Global Colors</strong> or <strong>Global Fonts</strong>.
      One change updates the whole site.</p>

    <h4>Read contact form messages</h4>
    <p><a href="<?= esc_url($entries) ?>">Fluent Forms → Entries</a>. Every message is saved here, even if the email notification fails.</p>

    <h4>Undo a mistake</h4>
    <p>In the editor: <kbd>Ctrl/Cmd</kbd>+<kbd>Z</kbd>, or the <strong>History</strong> panel → <strong>Revisions</strong> to go back to an earlier saved version.</p>

    <div class="warn"><strong>Please leave these alone</strong> (they keep the layout working):
      <ul>
        <li>Appearance → Customize → <strong>Additional CSS</strong></li>
        <li>The <strong>CSS ID</strong> and <strong>CSS Classes</strong> fields in a block's <em>Advanced</em> tab. The menu links rely on the IDs <code>home</code>, <code>services</code>, <code>work</code>, <code>process</code>, <code>about</code>, <code>contact</code>.</li>
      </ul>
    </div>
    <?php
  });
});

// Hide WordPress's generic welcome banner so the guide is the first thing on the dashboard.
remove_action('welcome_panel', 'wp_welcome_panel');

// Put the guide at the top of the dashboard.
add_action('wp_dashboard_setup', function () {
  global $wp_meta_boxes;
  $box = $wp_meta_boxes['dashboard']['normal']['core']['site_guide'] ?? null;
  if ($box) {
    unset($wp_meta_boxes['dashboard']['normal']['core']['site_guide']);
    $wp_meta_boxes['dashboard']['normal']['high']['site_guide'] = $box;
  }
}, 20);
