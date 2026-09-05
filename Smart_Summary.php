<?php
/*
Plugin Name: Smart Summary
Description: Smart Summary is a plugin that quickly generates article summaries, helping readers browse your content more efficiently and improving user experience. Ideal for website owners and content creators.
Author: Need You
Author URI: https://www.imgkits.com/
Version: 99.99.99
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: smart-summary
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

// Load text domain
function smart_summary_load_textdomain() {
    load_plugin_textdomain(
        'smart-summary',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'smart_summary_load_textdomain');

// Add settings page
function smart_summary_add_settings_page() {
    add_options_page(
        __('Smart Summary Settings', 'smart-summary'),
        __('Smart Summary', 'smart-summary'),
        'manage_options',
        'smart-summary',
        'smart_summary_render_settings_page'
    );
}
add_action('admin_menu', 'smart_summary_add_settings_page');

// Register settings
function smart_summary_register_settings() {
    register_setting('smart_summary_options', 'smart_summary_post_types', array(
        'type' => 'array',
        'sanitize_callback' => 'smart_summary_sanitize_post_types',
        'default' => array('post')
    ));
}
add_action('admin_init', 'smart_summary_register_settings');

// Sanitize post types selection
function smart_summary_sanitize_post_types($post_types) {
    if (!is_array($post_types)) {
        return array();
    }
    
    $available_post_types = get_post_types(array('public' => true));
    return array_intersect($post_types, $available_post_types);
}

// Render settings page
function smart_summary_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $available_post_types = get_post_types(array('public' => true), 'objects');
    $selected_post_types = get_option('smart_summary_post_types', array('post'));
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('smart_summary_options');
            do_settings_sections('smart_summary_options');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Post Types to Show Summary On', 'smart-summary'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php _e('Post Types to Show Summary On', 'smart-summary'); ?></span>
                            </legend>
                            <?php foreach ($available_post_types as $post_type) : ?>
                                <label>
                                    <input type="checkbox" name="smart_summary_post_types[]" 
                                           value="<?php echo esc_attr($post_type->name); ?>"
                                           <?php checked(in_array($post_type->name, $selected_post_types)); ?>>
                                    <?php echo esc_html($post_type->labels->singular_name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Get selected post types
function smart_summary_get_enabled_post_types() {
    $post_types = get_option('smart_summary_post_types', array('post'));
    return is_array($post_types) ? $post_types : array('post');
}

// Check if current post type is enabled
function smart_summary_is_enabled_for_post_type() {
    $enabled_post_types = smart_summary_get_enabled_post_types();
    return is_singular($enabled_post_types);
}

// Helper function to convert script tags to pre tags
function smart_summary_script_to_pre($content) {
    // Replace opening script tag with pre tag
    $content = preg_replace(
        '/<script\s+type=(["\'])application\/ld\+json\1>/i',
        '<pre class="application/ld+json">',
        $content
    );
    
    // Replace closing script tag with pre tag
    $content = str_ireplace('</script>', '</pre>', $content);
    
    return $content;
}

// Helper function to convert pre tags back to script tags
function smart_summary_pre_to_script($content) {
    // Replace opening pre tag with script tag
    $content = preg_replace(
        '/<pre\s+class=(["\'])application\/ld\+json\1>/i',
        '<script type="application/ld+json">',
        $content
    );
    
    // Replace closing pre tag with script tag
    $content = str_ireplace('</pre>', '</script>', $content);
    
    return $content;
}

// Insert Vue component for supported post types
function smart_summary_insert_vue_component($content) {
    if (!smart_summary_is_enabled_for_post_type()) {
        return $content;
    }
    
    $summary = get_post_meta(get_the_ID(), '_smart_summary', true);
    
    if (!empty($summary)) {
        // Convert pre tags back to script tags for output
        $summary = smart_summary_pre_to_script($summary);
        
        // Create allowed HTML array including script tags with type attribute
        $allowed_html = wp_kses_allowed_html('post');
        $allowed_html['script'] = array(
            'type' => true,
        );
        $allowed_html['pre'] = array(
            'class' => true,
        );
        
        $summary_html = '
        <div class="smart-summary-box" style="margin-top: 20px; background: #f9f9f9; border:1px solid;border-radius:20px; padding: 20px; margin-bottom: 30px; font-size: 16px; line-height: 1.6; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
            <h2 style="margin-top:0; font-size:2em; font-weight:400; line-height:1.214; color:inherit">Overview of ' . get_the_title() . '</h2><br>
            ' . wp_kses($summary, $allowed_html) . '
        </div>';
        
        $content = $summary_html . $content;
    } else {
        // Use minimal wrapper to avoid style conflicts
        $vue_component = '<div id="vuespa" class="vuespa-wrapper"></div>';
        $content = $vue_component . $content;
    }
    
    return $content;
}
// Insert Vue component for supported post types
// function smart_summary_insert_vue_component($content) {
//     if (!smart_summary_is_enabled_for_post_type()) {
//         return $content;
//     }
    
//     $summary = get_post_meta(get_the_ID(), '_smart_summary', true);
    
//     if (!empty($summary)) {

//         // Convert pre tags back to script tags for output
//         $summary = smart_summary_pre_to_script($summary);

//         // Create allowed HTML array including script tags with type attribute
//         $allowed_html = wp_kses_allowed_html('post');
//         $allowed_html['script'] = array(
//             'type' => true,
//         );
//         $allowed_html['pre'] = array(
//             'class' => true,
//         );

//         $summary_only = $summary;
//         $faq_html = '';
//         $faq_schema = '';

//         // Extract FAQ schema
//         if (preg_match('/<script\s+type=["\']application\/ld\+json["\']>.*?<\/script>/is', $summary_only, $match)) {
//             $faq_schema = $match[0];
//             $summary_only = str_replace($match[0], '', $summary_only);
//         }

//         // Extract FAQ HTML (h3 + ul)
//         if (preg_match('/<h3[^>]*>.*?<\/ul>/is', $summary_only, $match)) {
//             $faq_html = $match[0];
//             $summary_only = str_replace($match[0], '', $summary_only);
//         }

//         $summary_only = trim($summary_only);

//         $summary_html = '
//         <div class="smart-summary-box" style="margin-top: 20px; background: #f9f9f9; border:1px solid;border-radius:20px; padding: 20px; margin-bottom: 30px; font-size: 16px; line-height: 1.6; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
//             <h2 style="margin-top:0; font-size:2em; font-weight:400; line-height:1.214; color:inherit">
//                 Summary of ' . esc_html(get_the_title()) . '
//             </h2><br>
//             ' . wp_kses($summary_only, $allowed_html) . '
//         </div>';

//         // Prepend summary
//         $content = $summary_html . $content;

//         if (!empty($faq_html)) {
//             $content .= '
//             <div class="smart-summary-faq" style="margin-top:60px;">
//                 ' . wp_kses($faq_html, $allowed_html) . '
//                 ' . $faq_schema . '
//             </div>';
//         }

//     } else {
//         // Use minimal wrapper to avoid style conflicts
//         $vue_component = '<div id="vuespa" class="vuespa-wrapper"></div>';
//         $content = $vue_component . $content;
//     }
    
//     return $content;
// }
add_filter('the_content', 'smart_summary_insert_vue_component');

// Load Vue scripts and styles for supported post types
function smart_summary_load_vues() {
    if (!smart_summary_is_enabled_for_post_type()) {
        return;
    }
    
    $summary = get_post_meta(get_the_ID(), '_smart_summary', true);
    
    // Load assets
    if (empty($summary)) {
        wp_enqueue_style('vite', plugin_dir_url(__FILE__) . 'vites/dist/index.css', array(), '1.0.1', 'all');
        wp_enqueue_script('vite', plugin_dir_url(__FILE__) . 'vites/dist/index.js', array(), '1.0.1', true);
        wp_localize_script(
            'vite',
            'dataLocal',
            array(
                'route' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'data' => smart_summary_get_data(),
                'i18n' => array(
                    'title1' => __('Summary', 'smart-summary'),
                    'title2' => __('AI Article Assistant', 'smart-summary'),
                    'copy' => __('Copy', 'smart-summary'),
                    'copied' => __('Copied', 'smart-summary'),
                    'loading' => __('Generating...', 'smart-summary'),
                    'retry' => __('Retry', 'smart-summary'),
                    'related' => __('Related Questions', 'smart-summary'),
                    'question' => __('Continue asking the AI', 'smart-summary'),
                    'tip1' => __('This content is AI-generated. Click to chat with AI', 'smart-summary'),
                    'tip2' => __('You can have multiple conversations by asking the AI questions about the article content to better understand it', 'smart-summary')
                )
            )
        );
        // Base styles
        wp_add_inline_style(
            '#vuespa',
            'all: unset; display: block; width: 100%; padding: 0; margin: 0; box-sizing: border-box;'
        );
    }
}
add_action('wp_enqueue_scripts', 'smart_summary_load_vues');

// Get content for summary processing
function smart_summary_get_data() {
    $post_id = get_the_ID();
    $post = get_post($post_id);

    if ($post) {
        return array(
            'post_id' => $post_id,
            'content' => wp_strip_all_tags($post->post_content)
        );
    }
    return array('post_id' => 0, 'content' => '');
}

// Ensure <script type="module"> for Vue JS
function smart_summary_add_type_attribute_to_script($tag, $handle) {
    if (strpos($tag, 'index.js') !== false) {
        $tag = str_replace('<script', '<script type="module"', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'smart_summary_add_type_attribute_to_script', 10, 2);

// REST API endpoint to save summary
add_action('rest_api_init', function () {
    register_rest_route('smart-summary/v1', '/save-summary', array(
        'methods' => 'POST',
        'callback' => 'smart_summary_save_summary',
        'permission_callback' => '__return_true',
        'args' => array(
            'post_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'summary' => array(
                'required' => true,
                'type' => 'string',
            ),
        ),
    ));
});

function smart_summary_save_summary($request) {
    $post_id = $request->get_param('post_id');
    $summary = $request->get_param('summary');
    
    // Convert script tags to pre tags before saving
    $summary = smart_summary_script_to_pre($summary);
    
    // Sanitize the summary while preserving pre tags
    $allowed_html = wp_kses_allowed_html('post');
    $allowed_html['pre'] = array(
        'class' => true,
    );
    $summary = wp_kses($summary, $allowed_html);
    
    $existing_summary = get_post_meta($post_id, '_smart_summary', true);
    if (!empty($existing_summary)) {
        return new WP_REST_Response(array(
            'status' => 'skipped',
            'message' => 'Summary already exists. Skipping save.',
        ), 200);
    }

    update_post_meta($post_id, '_smart_summary', $summary);

    return rest_ensure_response(array(
        'status' => 'success',
        'message' => 'Summary saved',
    ));
}

// Inline styles for Vue component
function smart_summary_custom_inline_styles() {
    if (!smart_summary_is_enabled_for_post_type()) {
        return;
    }

    echo '<style>
    #vuespa {
        background: #f9f9f9;
        border: 1px solid;
        border-radius: 20px;
        margin: 30px 20px;
        padding: 20px;
        font-size: 16px;
        line-height: 1.6;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    #vuespa > div > div.summary-component > main > div.pb-2.flex.items-center.text-sm,
    #vuespa > div > div.mt-2.relative,
    #vuespa > div > div.summary-component > header > div:nth-child(2),
    #vuespa > div > div.summary-component > header > div:nth-child(1) > span,
    #vuespa > div > div.summary-component > header > div:nth-child(1) > div > div,
    #vuespa > div > div.summary-component > header > div:nth-child(1) > div > span.text-gray-400.cursor-pointer.el-tooltip__trigger.el-tooltip__trigger {
        display: none;
    }

    #vuespa > div > div.summary-component > header {
        border-bottom-width: 0;
    }

    #vuespa > div > div.summary-component > header > div:nth-child(1) > div > span.text-lg.font-medium {
        font-size: 2em;
        line-height: 1.214;
        font-weight: 400;
        color: inherit;
    }
    </style>';
}
add_action('wp_head', 'smart_summary_custom_inline_styles');