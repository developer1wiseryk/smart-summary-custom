# Smart Summary Customized Plugin Documentation

## About

We customized the [Smart Summary WordPress Plugin](https://wordpress.org/plugins/smart-summary/) to generate short summaries for posts and custom post types.

When a supported post is opened on the front end, Smart Summary generates the summary and saves it in the WordPress post meta using:

```text
_smart_summary
```

Once the summary is saved, the plugin displays the saved summary instead of generating it again. An admin settings page was also added so we can select which post types should use Smart Summary.

### Front-End Summary

![Smart Summary Front-End Example](https://github.com/developer1wiseryk/screenshots/blob/main/front-end-summary-example.png?raw=true)

### Summary Saved in Post Meta

The generated summary is stored in the WordPress post meta table with the `_smart_summary` meta key.

![Smart Summary Post Meta](https://github.com/developer1wiseryk/screenshots/blob/main/postmeta-smart-summary.png?raw=true)

### Settings Options Page

Go to **Settings > Smart Summary** and select the post types where the summary should appear.

![Smart Summary Settings](https://github.com/developer1wiseryk/screenshots/blob/main/settings-post-types.png?raw=true)

---

## Custom Code Added to Smart Summary Plugin

We modified the Smart Summary plugin in a few places to achieve the required functionality:

1. Translated the Smart Summary instructions from Chinese to English and updated the system instructions.
2. Updated the plugin so the generated summary is saved in WordPress post meta.
3. Modified the Vue.js summary prompt according to our requirements.
4. Allowed HTML tags in the generated summary so headings and lists can be formatted correctly.
5. Added a condition in `index.js` so the Vue summary is not generated again when a saved summary already exists.
6. Updated `Smart_Summary.php` to save and display the summary.
7. Added custom CSS to properly align and style the saved summary on the front end.

---

## Important Notes

- No separate API key is required. Smart Summary uses its own API service.
- The summary is automatically generated when the post is viewed on the front end.
- After activating the plugin, select the required post types from **Settings > Smart Summary**.
- Caching plugins may conflict with Smart Summary, so caching should be disabled when testing or troubleshooting the plugin.

---

## Main Code Modifications

### 1. Modified Vue.js Summary Instructions

File:

```text
smart-summary/vites/dist/index.js
```

The original Chinese instructions were replaced with English instructions. The updated prompt generates:

- A short English summary under approximately 100 words.
- A list of parts/components used in the project.
- The parts list in HTML format.
- Three short related questions.

Example structure used for the parts list:

```html
<h3 style="margin-top:20px;">Parts used in the [Project Name]:</h3>
<ul style="list-style-type:disc;">
  <li>First part</li>
  <li>Second part</li>
  <li>Third part</li>
</ul>
```

---

### 2. Allow HTML in the Generated Summary

The original Smart Summary code removed HTML formatting from the generated summary.

**Original line:**

```javascript
:(Z(), se("div", __, Me(v(t).summary), 1)),
```

**Changed to:**

```javascript
:U("div", {innerHTML: v(t).summary}),
```

This allows HTML headings, lists, and other formatting to display correctly.

![Vue HTML Modification](https://github.com/developer1wiseryk/screenshots/blob/main/vue-preserve-html.png?raw=true)

---

### 3. Stop Dynamic Summary When a Saved Summary Exists

The original Vue app was mounted every time:

```javascript
pf.mount("#vuespa");
```

It was replaced with:

```javascript
if (window.dataLocal.summary_exists) {
    console.log('Summary already exists. Skipping Vue app render.');
} else {
    pf.mount("#vuespa");
}
```

This prevents a new dynamic summary from displaying when the post already has a saved summary.

![Skip Vue When Summary Exists](https://github.com/developer1wiseryk/screenshots/blob/main/vue-skip-mount-existing-summary.png?raw=true)

---

### 4. Modified `Smart_Summary.php`

File:

```text
smart-summary/Smart_Summary.php
```

The main plugin file was updated to:

- Add the **Settings > Smart Summary** options page.
- Allow selection of public post types.
- Add the post ID to the Vue data.
- Save summaries in `_smart_summary` post meta.
- Display the saved summary above the post content.
- Load the Vue files only when a saved summary does not already exist.
- Add front-end styling for the summary box.

The REST endpoint used to save the generated summary is:

```text
/wp-json/smart-summary/v1/save-summary
```

---

## Dependencies / Requirements

1. The customized Smart Summary plugin must be activated.
2. The required post type must be selected in **Settings > Smart Summary**.
3. Caching plugins should be disabled if they cause a conflict with Smart Summary.

### Plugin Version

The Smart Summary plugin version was changed from:

```text
1.0.1
```

to:

```text
99.99.99
```

This was done to reduce the chance of the customized plugin being automatically updated. Updating the original plugin may overwrite the custom code.

---

## Smart Summary API Blocked Issue

**Date documented:** 02-08-2025

The website/domain IP was blocked by the Smart Summary API service. To work around this, the API requests were changed so they are sent through the WordPress server instead of directly from the browser.

### Steps

1. Install the updated customized Smart Summary plugin.
2. Add the following code to the active theme's `functions.php` file.

```php
// WordPress proxy instead of direct ai.imgkits.com calls
add_action('rest_api_init', function() {
    register_rest_route('smart-summary/v1', '/proxy/create', array(
        'methods'  => 'POST',
        'callback' => 'smart_summary_proxy_create',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('smart-summary/v1', '/proxy/result', array(
        'methods'  => 'POST',
        'callback' => 'smart_summary_proxy_result',
        'permission_callback' => '__return_true'
    ));
});

function smart_summary_proxy_create($request) {
    $params = $request->get_json_params();
    $original_data = $params['original_data'] ?? '';

    if (empty($original_data)) {
        return new WP_Error('missing_data', 'Original data is required', array('status' => 400));
    }

    $api_url = 'https://ai.imgkits.com/api/chat/create';

    $response = wp_remote_post($api_url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => $original_data
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $body = wp_remote_retrieve_body($response);
    return rest_ensure_response(json_decode($body));
}

function smart_summary_proxy_result($request) {
    $params = $request->get_json_params();
    $original_data = $params['original_data'] ?? '';

    if (empty($original_data)) {
        return new WP_Error('missing_data', 'Original data is required', array('status' => 400));
    }

    $api_url = 'https://api.imgkits.com/api/chat/result';

    $response = wp_remote_post($api_url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => json_encode($original_data)
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $body = wp_remote_retrieve_body($response);
    return rest_ensure_response(json_decode($body));
}
```

---

## Modified Files

```text
smart-summary/
├── Smart_Summary.php
└── vites/
    └── dist/
        └── index.js
```

These customized files should be backed up before making changes or updating the original Smart Summary plugin.
