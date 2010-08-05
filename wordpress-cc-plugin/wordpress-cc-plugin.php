<?php
/**
 * @package wordpress-cc-plugin
 * @author Nils Dagsson Moskopp // erlehmann
 * @version 0.6
 */
/*
Plugin Name: Wordpress CC Plugin
Plugin URI: http://labs.creativecommons.org/2010/05/24/gsoc-project-introduction-cc-wordpress-plugin/
Description: The Wordpress interface for managing media is extended to have an option to specify a CC license for uploaded content. When aforementioned content is inserted into an article, RDFa-enriched markup is generated.
Author: Nils Dagsson Moskopp // erlehmann
Version: 0.6
Author URI: http://dieweltistgarnichtso.net
*/

// CC REST API URL
$api_url = 'http://api.creativecommons.org/rest/dev';

/* install and uninstall */

// create database entry on install
function cc_wordpress_register_settings() {
    register_setting('cc_wordpress_options', 'cc_wordpress_css');
    register_setting('cc_wordpress_options', 'cc_wordpress_default_license');
    register_setting('cc_wordpress_options', 'cc_wordpress_default_rights_holder');
    register_setting('cc_wordpress_options', 'cc_wordpress_default_attribution_url');
    register_setting('cc_wordpress_options', 'cc_wordpress_default_jurisdiction');
    register_setting('cc_wordpress_options', 'cc_wordpress_post_thumbnail_filter');
    register_setting('cc_wordpress_options', 'cc_wordpress_locale');
}

// delete database entry on uninstall
function cc_wordpress_uninstall(){
    unregister_setting('cc_wordpress_options', 'cc_wordpress_css');
    unregister_setting('cc_wordpress_options', 'cc_wordpress_default_license');
    unregister_setting('cc_wordpress_options', 'cc_wordpress_default_rights_holder');
    unregister_setting('cc_wordpress_options', 'cc_wordpress_default_attribution_url');
    unregister_setting('cc_wordpress_options', 'cc_wordpress_default_jurisdiction');
    unregister_setting('cc_wordpress_options', 'cc_wordpress_post_thumbnail_filter');
    unregister_setting('cc_wordpress_options', 'cc_wordpress_locale');
}

// install hook
register_activation_hook(__FILE__,'cc_wordpress_install');

// uninstall hook
register_uninstall_hook(__FILE__, 'cc_wordpress_uninstall');

/* administration */

// output checked attribute if appropriate
function cc_wordpress_admin_checked($key, $value) {
    if (get_option($key) == $value) {
        return ' checked="checked"';
    }
}

// generate list of css files
function cc_wordpress_admin_css_list() {

    $path = WP_PLUGIN_DIR .'/wordpress-cc-plugin/css/';
    $directory = opendir($path);

    $html = '';

    if($directory){
        // TODO: use scandir()
        while (false !== ($file = readdir($directory))) {
            if(substr($file, -3) == 'css') {
                $html .= '
<li>
    <label><input type="radio" name="cc_wordpress_css" value="'. $file .'"';

                $html .= cc_wordpress_admin_checked('cc_wordpress_css', $file);

                $html .= '/><i>'. substr($file, 0, -4) .'</i></label>
    <img src="'. WP_PLUGIN_URL .'/wordpress-cc-plugin/css-preview/'. substr($file, 0, -3) .'png"/>
</li>';
    // maybe use real path editing facilities ? but then, this already works.
            }
        }
    }

    closedir($directory);

    return $html;
}

// generate license dropdown
function cc_wordpress_license_select($current_license, $name, $current_jurisdiction, $show_default) {
    $html  = '<select id="cc_license" name="'. $name .'"">';

    if ($show_default) {
        $selected = ('default' == $current_license) ? ' selected="selected"' : '';
        $html .= '<option value="default"'. $selected .'>Default</option>';
    }

    // TODO: Get this list via CC REST API
    $license_ids = array('by','by-nc','by-nd','by-sa','by-nc-nd','by-nc-sa');
    foreach ($license_ids as $license_id) {
        $selected = ($license_id == $current_license) ? ' selected="selected"' : '';
        $license_name = cc_wordpress_license_name($license_id, $current_jurisdiction);
        $html .= '<option value="'. $license_id .'"'. $selected .'>'. $license_name .'</option>';
    }

    $html .= '</select>';

    return $html;
}

function cc_wordpress_license_name($license_id, $jurisdiction_id) {
    // grab license information
    // FIXME: should come from cache
    global $api_url;
    $locale = get_option('cc_wordpress_locale');
    $rest = file_get_contents($api_url .'/license/standard/jurisdiction/'. $jurisdiction_id .'?locale='. $locale);

    $dom = new DOMDocument();
    $dom->loadXML($rest);

    $licenses = $dom->getElementsByTagName('license');

    foreach ($licenses as $license) {
        $license_url = $license->getAttribute('url');
        if (strpos($license_url, '/'. $license_id .'/') !== false) {
            return $license->getAttribute('name');;
        }
    }

    return 'All rights reserved.';
}

function cc_wordpress_license_url($license_id, $jurisdiction_id) {
    // grab license information
    // FIXME: should come from cache
    global $api_url;
    $locale = get_option('cc_wordpress_locale');
    $rest = file_get_contents($api_url .'/license/standard/jurisdiction/'. $jurisdiction_id .'?locale='. $locale);

    $dom = new DOMDocument();
    $dom->loadXML($rest);

    $licenses = $dom->getElementsByTagName('license');

    foreach ($licenses as $license) {
        $license_url = $license->getAttribute('url');
        if (strpos($license_url, '/'. $license_id .'/') !== false) {
            return $license_url;
        }
    }
}

// generate jurisdiction select
function cc_wordpress_jurisdiction_select($current_jurisdiction, $name, $show_default) {
    // grab list of supported jurisdictions
    // FIXME: should come from cache
    global $api_url;
    $locale = get_option('cc_wordpress_locale');
    $rest = file_get_contents($api_url .'/support/jurisdictions?locale='. $locale);

    $dom = new DOMDocument();
    // ugly hack because a root element is needed
    $dom->loadXML('<select>'. $rest .'</select>');

    // TODO: sort jurisdictions alphabetically
    $jurisdictions = $dom->getElementsByTagName('option');

    $html  = '<select id="cc_jurisdiction" name="'. $name .'"">';

    if ($show_default) {
        $selected = ('' == $current_jurisdiction) ? ' selected="selected"' : '';
        $html .= '<option value=""'. $selected .'>Default</option>';
    }

    foreach ($jurisdictions as $jurisdiction) {
        $jurisdiction_url = $jurisdiction->getAttribute('value');
        $jurisdiction_id = str_replace('http://creativecommons.org/international/', '', substr($jurisdiction_url, 0, -1));
        $jurisdiction_name = $jurisdiction->textContent;
        $selected = ($jurisdiction_id == $current_jurisdiction) ? ' selected="selected"' : '';
        $html .= '<option value="'. $jurisdiction_id .'"'. $selected .'>'. $jurisdiction_name .'</option>';
    }

    $html .= '</select>';

    return $html;
}

function cc_wordpress_jurisdiction_name($jurisdiction_id) {
    // grab list of supported jurisdiction
    // FIXME: should come from cache
    global $api_url;
    $locale = get_option('cc_wordpress_locale');
    $rest = file_get_contents($api_url .'/support/jurisdictions?locale='. $locale);

    $dom = new DOMDocument();
    // ugly hack because a root element is needed
    $dom->loadXML('<select>'. $rest .'</select>');

    // TODO: sort jurisdictions alphabetically
    $jurisdictions = $dom->getElementsByTagName('option');

    foreach ($jurisdictions as $jurisdiction) {
        $jurisdiction_url = $jurisdiction->getAttribute('value');
        if ('http://creativecommons.org/international/' .$jurisdiction_id. '/' == $jurisdiction_url) {
            return $jurisdiction->textContent;
        }
    }

    // if all else fails
    return $jurisdiction_id;
}

//generate locale select
function cc_wordpress_locale_select($current_locale, $name) {
    // grab list of supported locales
    // FIXME: should come from cache
    global $api_url;
    $rest = file_get_contents($api_url . '/locales');

    $dom = new DOMDocument();
    $dom->loadXML($rest);

    // TODO: sort locales alphabetically
    $locales = $dom->getElementsByTagName('locale');

    $html  = '<select id="cc_locale" name="'. $name .'"">';

    foreach ($locales as $locale) {
        $locale_id = $locale->getAttribute('id');
        $selected = ($locale_id == $current_locale) ? ' selected="selected"' : '';
        $html .= '<option value="'. $locale_id .'"'. $selected .'>'. $locale_id .'</option>';
    }

    $html .= '</select>';

    return $html;
}

// admin page
function cc_wordpress_admin_page() {
    ?>

<style scoped="scoped">

img {
    vertical-align: middle;
}

label {
    display: inline-block;
    min-width: 160px;
    position: relative;
    line-height: 3em;
}

label input,
label select {
    margin: 0.5em;
}

label input[type=text],
label input[type=url],
label select {
    position: absolute;
    left: 128px;
}
</style>

<div class="wrap">
    <form method="post" action="options.php">

        <?php
        settings_fields('cc_wordpress_options');
        ?>

        <h2>License Defaults</h2>
        <p>
            Setting default license, rights holder, attribution URL and jurisdiction pre-fills the license chooser form field with the chosen option.
        </p>

        <p>
            <label>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAQAAABuvaSwAAAAAnNCSVQICFXsRgQAAAAJcEhZcwAABJ0AAASdAXw0a6EAAAAZdEVYdFNvZnR3YXJlAHd3dy5pbmtzY2FwZS5vcmeb7jwaAAABmklEQVQoz5XTPWiTURTG8d8b/GjEii2VKoqKi2DFwU9wUkTdFIeKIEWcpIOTiA4OLgVdXFJwEZHoIII0TiJipZJFrIgGKXQQCRg6RKREjEjMcQnmTVPB3jNc7j1/7nk49zlJ+P+1rPsqydqFD1HvSkUq9MkpaQihoWRcfzqftGUkx9y10Yy33vlttz2GzBmNQtfLrmqqGu6odNKccOvvubXt1/Da+tAZBkwKx1OwHjNqti1EQ7DBN2Vr2vBl4cJiaAjOCdfbcMF3mWC7O6qmDFntms9KzgYZNU/bcFkxBM+UjXjiilFNl4yZsCIoqrRgA0IuGNRws1W66H1KSE5YFzKoa+pFTV0/ydYk66s+kt5kE1ilqd7qs49KIcj75bEfxp0RJn0yKxtMm21rzmtYG6x0Wt5Fy4ODbhuzJejx06M2PCzc+2frbgjn0z9YEE4tih7Q8FyShgdVzRvpQk+omLe5wxvBIV+ECTtkQpCx00Oh4ugCI7XcfF8INa9MqQnhQdrRSedYJYcdsc9eTHvjRbzsyC5lBjNLYP0B5PQk1O2dJT8AAAAASUVORK5CYII=" alt="Creative Commons"/> License

                <?php
                $current_license = get_option('cc_wordpress_default_license');
                $default_jurisdiction = get_option('cc_wordpress_default_jurisdiction');
                echo cc_wordpress_license_select($current_license, 'cc_wordpress_default_license', $default_jurisdiction, false);
                ?>
            </label>
        </p>

       <p>
            <label>
                Rights Holder

                <input type="text" name="cc_wordpress_default_rights_holder" value="<?php echo get_option('cc_wordpress_default_rights_holder'); ?>"/>
            </label>
        </p>

       <p>
            <label>
                Attribution URL

                <input type="url" name="cc_wordpress_default_attribution_url" value="<?php echo get_option('cc_wordpress_default_attribution_url'); ?>"/>
            </label>
        </p>

       <p>
            <label>
                Jurisdiction

                <?php
                $current_jurisdiction = get_option('cc_wordpress_default_jurisdiction');
                echo cc_wordpress_jurisdiction_select($current_jurisdiction, 'cc_wordpress_default_jurisdiction', false);
                ?>
            </label>
        </p>

        <h2>Locale</h2>
        <p>
            Select the locale for this plugin:
        </p>
        <p>
            <label>
                Locale

                <?php
                $current_locale = get_option('cc_wordpress_locale');
                echo cc_wordpress_locale_select($current_locale, 'cc_wordpress_locale');
                ?>
            </label>
        </p>

        <h2>Post Thumbnail Filter</h2>
        <p>
            Specify if post thumbnail images should be replaced with <i>&lt;figure&gt;</i> elements.
        </p>
        <p>
            <label>
                <input type="checkbox" name="cc_wordpress_post_thumbnail_filter"<?php
                    echo cc_wordpress_admin_checked('cc_wordpress_post_thumbnail_filter','on');
                ?>/>
                Filter post thumbnails
            </label>
        </p>

        <h2>Stylesheet</h2>
        <p>
            A stylesheet changes the look of the license attribution.
        </p>
        <ul>

            <?php
                echo cc_wordpress_admin_css_list();
            ?>

            <li>
                <label><input type="radio" name="cc_wordpress_css" value=""<?php
                    echo cc_wordpress_admin_checked('cc_wordpress_css','');
                ?>/><?php
                    echo __('no stylesheet');
                ?></label>
            </li>
        </ul>

        <div class="submit">
            <input type="submit" class="button-primary" value="<?php
                echo __('Save Changes');
            ?>" />
        </div>
    </form>
</div>

    <?php
}

// add admin menu entry
function cc_wordpress_plugin_menu() {
    add_options_page('CC Wordpress', 'CC Wordpress', 8, __FILE__, 'cc_wordpress_admin_page');
}

// add admin pages
if(is_admin()) {
    add_action( 'admin_init', 'cc_wordpress_register_settings');
    add_action('admin_menu', 'cc_wordpress_plugin_menu');
}

/* actual plugin functionality */

// output link to chosen CSS
function cc_wordpress_add_css() {
    if (get_option('cc_wordpress_css')) {
        echo '<link rel="stylesheet" href="'. WP_PLUGIN_URL .'/wordpress-cc-plugin/css/'. get_option('cc_wordpress_css') .'" type="text/css"/>';
    }

    // show IE a little of the HTML5 goodness
    echo '<!--[if lte IE 8]><script>document.createElement("figure");document.createElement("figcaption");</script><![endif]-->';
}

// add CSS to all pages
add_action('wp_head', 'cc_wordpress_add_css');

// this function adds attachment fields to the media manager
function cc_wordpress_fields_to_edit($form_fields, $post) {
?>

<style scoped="scoped">

abbr {
    border-bottom: 1px dotted black;
}

label {
    display: inline-block !important;
    font-size: 13px;
    font-weight: bold;
    width: 130px;
}

    label img {
        position: relative;
        top: 7px;
        margin-top: -7px;
    }

table {
    border-collapse: collapse;
}

.cc_license > th,
.cc_license > td,
.cc_jurisdiction > th,
.cc_jurisdiction > td {
    border-top: 1px solid #c0c0c0;
    border-style: solid;
    padding-top: 7px !important;
}

#cc_license {
    margin-right: 1em;
}

#cc_license,
#cc_license + p,
#cc_jurisdiction,
#cc_jurisdiction + p {
    display: inline-block;
}

#cc_attribution_url,
#cc_rights_holder {
    -moz-border-radius-bottomleft: 4px;
    -moz-border-radius-bottomright: 4px;
    -moz-border-radius-topleft: 4px;
    -moz-border-radius-topright: 4px;
    -moz-box-sizing: border-box;
    background-color: #ffffff;
    border: 1px solid #dfdfdf;
    border-radius: 4px;
    width: 460px;
}

</style>

<?php

    $id = $post->ID;

    $default_license = get_option('cc_wordpress_default_license');
    $current_license = get_post_meta($id, 'cc_license', true);
    if ($current_license == '') {
        $current_license = 'default';
    }

    $current_jurisdiction = get_post_meta($id, 'cc_jurisdiction', true);

    $name = 'attachments['. $id .'][cc_license]';
    $html = cc_wordpress_license_select($current_license, $name, $current_jurisdiction, true);
    
    $form_fields['cc_license'] = array(
        'label' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAQAAABuvaSwAAAAAnNCSVQICFXsRgQAAAAJcEhZcwAABJ0AAASdAXw0a6EAAAAZdEVYdFNvZnR3YXJlAHd3dy5pbmtzY2FwZS5vcmeb7jwaAAABmklEQVQoz5XTPWiTURTG8d8b/GjEii2VKoqKi2DFwU9wUkTdFIeKIEWcpIOTiA4OLgVdXFJwEZHoIII0TiJipZJFrIgGKXQQCRg6RKREjEjMcQnmTVPB3jNc7j1/7nk49zlJ+P+1rPsqydqFD1HvSkUq9MkpaQihoWRcfzqftGUkx9y10Yy33vlttz2GzBmNQtfLrmqqGu6odNKccOvvubXt1/Da+tAZBkwKx1OwHjNqti1EQ7DBN2Vr2vBl4cJiaAjOCdfbcMF3mWC7O6qmDFntms9KzgYZNU/bcFkxBM+UjXjiilFNl4yZsCIoqrRgA0IuGNRws1W66H1KSE5YFzKoa+pFTV0/ydYk66s+kt5kE1ilqd7qs49KIcj75bEfxp0RJn0yKxtMm21rzmtYG6x0Wt5Fy4ODbhuzJejx06M2PCzc+2frbgjn0z9YEE4tih7Q8FyShgdVzRvpQk+omLe5wxvBIV+ECTtkQpCx00Oh4ugCI7XcfF8INa9MqQnhQdrRSedYJYcdsc9eTHvjRbzsyC5lBjNLYP0B5PQk1O2dJT8AAAAASUVORK5CYII=" alt="Creative Commons"> '. __('License'),
        'input' => 'html',
        'html'  => $html,
        'helps' => __('Choose a Creative Commons License.') .' '. __('Default is') .' '. cc_wordpress_license_name($default_license, $current_jurisdiction) .'.'
        );

    $html = '<input type="text" id="cc_rights_holder" name="attachments['. $post->ID .'][cc_rights_holder]" value="'. get_post_meta($id, 'cc_rights_holder', true) .'"/>';

    $form_fields['cc_rights_holder'] = array(
        'label' => __('Rights holder'),
        'input' => 'html',
        'html' => $html,
        'helps' => __('If you leave this field empty, the default ') . ' (' . get_option('cc_wordpress_default_rights_holder') . ') will be displayed.'
        );

    $html = '<input type="url" id="cc_attribution_url" name="attachments['. $post->ID .'][cc_attribution_url]" value="'. get_post_meta($id, 'cc_attribution_url', true) .'"/>';

    $form_fields['cc_attribution_url'] = array(
        'label' => __('Attribution') .' <abbr title="Uniform Resource Locator">URL</abbr>',
        'input' => 'html',
        'html' => $html,
        'helps' => __('If you leave this field empty, the default ') . ' &lt;' . get_option('cc_wordpress_default_attribution_url') .'&gt; '. __('will be displayed.')
        );

    $default_jurisdiction = get_option('cc_wordpress_default_jurisdiction');
    $current_jurisdiction = get_post_meta($id, 'cc_jurisdiction', true);
    if ($current_jurisdiction == '') {
        $current_jurisdiction = 'default';
    }

    $name = 'attachments['. $id .'][cc_jurisdiction]';
    $html = cc_wordpress_jurisdiction_select($current_jurisdiction, $name, true);

    $form_fields['cc_jurisdiction'] = array(
        'label' => __('Jurisdiction'),
        'input' => 'html',
        'html' => $html,
        'helps' => __('Choose a jurisdiction.') .' '. __('Default is') .' '. cc_wordpress_jurisdiction_name($default_jurisdiction) .'.'
        );

    return $form_fields;
}

function cc_wordpress_update_or_add_or_delete($id, $key, $value) {
    if ($value != '') {
        if(!update_post_meta($id, $key, $value)) {
            add_post_meta($id, $key, $value);
        };
    } else {
        delete_post_meta($id, $key);
    }
}

function cc_wordpress_fields_to_save($post, $attachment) {

    $id = $post['ID'];

    cc_wordpress_update_or_add_or_delete($id, 'cc_license', $attachment['cc_license']);
    cc_wordpress_update_or_add_or_delete($id, 'cc_rights_holder', $attachment['cc_rights_holder']);
    cc_wordpress_update_or_add_or_delete($id, 'cc_attribution_url', $attachment['cc_attribution_url']);
    cc_wordpress_update_or_add_or_delete($id, 'cc_jurisdiction', $attachment['cc_jurisdiction']);

    return $post;
}

function cc_wordpress_media_send_to_editor($html, $attachment_id, $attachment) {
    $post =& get_post($attachment_id, ARRAY_A);
    $id = $post['ID'];

    // save licensing information before sending to editor
    cc_wordpress_fields_to_save($post, $attachment);

    $title = $attachment['post_title'];
    $type = substr($post['post_mime_type'], 0, 5);
    $url = wp_get_attachment_url($id);

    if ($type == 'image') {
        $size = $attachment['image-size'];
        $image = wp_get_attachment_image_src($id, $size);
        $alt = $attachment['image_alt'];

        $html = <<<HTML
<img class="wp-image-$id size-$size" src="$image[0]" title="$title" alt="$alt"/>
HTML;

    } elseif ($type == 'audio') {
        $download = __('Audio');
        $html = <<<HTML
<audio class="wp-audio-$id" src="$url" title="$title" controls="controls">$download: <a href="$url">$title</a></audio>
HTML;
    } elseif ($type == 'video') {
        $download = __('Video');
        $html = <<<HTML
<video class="wp-video-$id" src="$url" title="$title" controls="controls">$download: <a href="$url">$title</a></video>
HTML;
        //$html = $id . $url . $title . $download;
    } else {
        $download = __('Download:');
        $html = <<<HTML
<object class="wp-object-$id" src="$url" title="$title">$download: <a href="$url">$title</a></object>';
HTML;
    }

    return $html;
}

function cc_wordpress_figure($attachment_id, $title, $size = '', $is_post_thumbnail = false) {

    global $license_list;

    $post =& get_post($attachment_id);
    $id = $post->ID;

    if ($title == '') {
        $title = $post->post_excerpt;
        if ($title == '') {
            $title = $post->post_title;
            if ($title == '') {
                $title = 'untitled';
            }
        }
    }

    $type = substr($post->post_mime_type, 0, 5);

    $url = wp_get_attachment_url($id);
    $alt = get_post_meta($id, '_wp_attachment_image_alt', true);

    if ($type == 'image') {
        $dmci_type_url = 'http://purl.org/dc/dcmitype/Image';
        if ($size == '') {
            $media_html  = '<img src="'. $url .'" alt="'. $alt .'"/>';
        } else {
            $image = wp_get_attachment_image_src($id, $size);
            $media_html  = '<img src="'. $image[0] .'" alt="'. $alt .'"/>';
        }
    } elseif ($type == 'audio') {
        $dmci_type_url = 'http://purl.org/dc/dcmitype/Sound';
        $media_html = '<audio src="'. $url . '" controls="controls"><a href="'. $url .'">audio download</a></audio>';
    } elseif ($type == 'video') {
        $dmci_type_url = 'http://purl.org/dc/dcmitype/MovingImage';
        $media_html = '<video src="'. $url .'" controls="controls"><a href="'. $url .'">video download</a></video>';
    } else {
        $media_html = '<object src="'. $url .'"><a href="'. $url .'">download</a></object>';
    }

    $attribution_name = get_post_meta($id, 'cc_rights_holder', true);
    if ($attribution_name == '') {
        $attribution_name = get_option('cc_wordpress_default_rights_holder');
    }

    $attribution_url = get_post_meta($id, 'cc_attribution_url', true);
    if ($attribution_url == '') {
        $attribution_url = get_option('cc_wordpress_default_attribution_url');
    }

    $license = get_post_meta($id, 'cc_license', true);
    if ($license == 'default') {
        $license = get_option('cc_wordpress_default_license');
    }

    $jurisdiction = get_post_meta($id, 'cc_jurisdiction', true);
    if ($jurisdiction == '') {
        $jurisdiction = get_option('cc_wordpress_default_jurisdiction');
    }

    $license_url = cc_wordpress_license_url($license, $jurisdiction);

    if ($license != '') {
        $license_abbr = 'CC' .' '. strtoupper($license);
        $license_full = cc_wordpress_license_name($license, $jurisdiction);
    } else {
        // no license, just return standard markup
        return wp_get_attachment_image($id);
    }

    // produce caption
    $caption_html = '<span href="'. $dmci_type_url .'" property="dc:title" rel="dc:type">'. $title .'</span><a href="'. $attribution_url .'" property="cc:attributionName" rel="cc:attributionURL">'. $attribution_name .'</a><small><a href="'. $license_url .'" rel="license"><abbr title="'. $license_full .'">'. $license_abbr .'</abbr></a></small>';

    // add re-embed html and script
    $embed_script = <<<SCRIPT
<script> function showEmbed(element) {
var figureNode = element.parentNode.parentNode;
var helperNode = document.createElement('html');
helperNode.appendChild(figureNode.cloneNode(true));

embedNode = document.createElement('input');
embedNode.value = helperNode.innerHTML;
embedNode.readOnly = true;

element.parentNode.replaceChild(embedNode,element);
embedNode.select();
} </script>
SCRIPT;
    $embed_html = '<button onclick="showEmbed(this)">embed</button>';

    // add specific thumbnail class
    if ($is_post_thumbnail == true) {
        $post_thumbnail_class = 'class="post-thumbnail" ';
    }

    // add figure element
    $html = '<figure '. $post_thumbnail_class .'about="'. $url .'" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/terms/">'. $media_html .' <figcaption>'. $caption_html . $embed_script . $embed_html .'</figcaption></figure>';

    return $html;
}

function cc_wordpress_article_filter($article) {
    require_once 'lib/html5lib/Parser.php';

    // sorry, but parseFragment() returns a DomNodeList, which is as inflexible as it gets
    $dom = HTML5_Parser::parse($article);

    $tagnames = array('img', 'audio', 'video', 'object');
    foreach($tagnames as $tagname) {
        foreach($dom->getElementsByTagName($tagname) as $element) {
            $class = $element->getAttribute('class');

            // relevant class name example: wp-image-18
            preg_match('/wp-(image|audio|video|object)-([0-9]*)/', $class, $matches);
            $id = $matches[2];

            // relevant class name example: size-medium
            preg_match('/size-(.*)/', $class, $matches);
            $size = $matches[1];

            // TODO: make cc_wordpress_figure() take and return a DOM fragment
            $figure_html = cc_wordpress_figure($id, '', $size, false);
            $figure = HTML5_Parser::parseFragment($figure_html)->item(0);

            // a document context change is needed before appending the node
            $figure = $dom->importNode($figure, True);
            $element->parentNode->replaceChild($figure, $element);
        }
    }    

    // hackish but reliable way to serialize the DOM
    // TODO: fix this mess
    $XML = $dom->saveXML($dom->getElementsByTagName('body')->item(0));
    $XML = str_replace('<body>', '', $XML);
    $XML = str_replace('</body>', '', $XML);
    return $XML;
}

function cc_wordpress_post_thumbnail_filter($html, $post_id, $post_thumbnail_id, $size, $attr) {
    if (get_option('cc_wordpress_post_thumbnail_filter') == 'on') {
        $html = cc_wordpress_figure($post_thumbnail_id, '', $size, true);
    }

    return $html;
}

// apply filter to articles before they are displayed
add_action('the_content', 'cc_wordpress_article_filter');

// apply filter to post thumbnail html
add_filter( 'post_thumbnail_html', 'cc_wordpress_post_thumbnail_filter', 11, 5);

// add attachment fields
add_filter('attachment_fields_to_edit', 'cc_wordpress_fields_to_edit', 11, 2);

// save attachment fields
// TODO: this is not working at the moment
add_filter('attachment_fields_to_save', 'cc_wordpress_fields_to_save', 11, 2);

// send to wordpress editor
add_filter('media_send_to_editor', 'cc_wordpress_media_send_to_editor', 11, 3);
?>
