<?php
/**
 * @package wordpress-cc-plugin
 * @author Nils Dagsson Moskopp // erlehmann
 * @version 0.5
 */
/*
Plugin Name: Wordpress CC Plugin
Plugin URI: http://labs.creativecommons.org/2010/05/24/gsoc-project-introduction-cc-wordpress-plugin/
Description: The Wordpress interface for managing media is extended to have an option to specify a CC license for uploaded content. When aforementioned content is inserted into an article, RDFa-enriched markup is generated.
Author: Nils Dagsson Moskopp // erlehmann
Version: 0.5
Author URI: http://dieweltistgarnichtso.net
*/

/* install and uninstall */

// create database entry on install
function cc_wordpress_register_settings() {
    register_setting('cc_wordpress_options', 'cc_wordpress_css');
    register_setting('cc_wordpress_options', 'cc_wordpress_default_license');
}

// delete database entry on uninstall
function cc_wordpress_uninstall(){
    delete_option('cc_wordpress_css');
    delete_option('cc_wordpress_options', 'cc_wordpress_default_license');
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

    // FIXME: get path the proper way
    $path = '../wp-content/plugins/wordpress-cc-plugin/css/';
    $directory = opendir($path);

    $html = '';

    if($directory){
        while (false !== ($file = readdir($directory))) {
            if($file !== '.' && $file !== '..'){
                $html .= '
<li>
    <label><input type="radio" name="cc_wordpress_css" value="'. $file .'"';

                $html .= cc_wordpress_admin_checked('cc_wordpress_css', $file);

                $html .= '/><i>'. substr($file, 0, -4) .'</i></label>
    <img src="'. substr($path, 0, -4) .'css-preview/'. substr($file, 0, -3) .'png"/>
</li>';
    // maybe use real path editing facilities ? but then, this already works.
            }
        }
    }

    closedir($directory);

    return $html;
}

// generate license dropdown
function cc_wordpress_license_select($current_license, $name, $mark_default) {
    $html  = '<select id="cc_license" name="'. $name .'"">';

    $licenses = array('reserved', 'by', 'by-nc', 'by-nc-nd', 'by-nc-sa', 'by-nd', 'by-sa');
    foreach ($licenses as $license) {
        $selected = ($license == $current_license) ? ' selected="selected"' : '';
        $license_name = cc_wordpress_license_name($license, $mark_default);
        $html .= '<option value="'. $license .'"'. $selected .'>'. $license_name .'</option>';
    }

    $html .= '</select>';

    return $html;
}

//generate license name
function cc_wordpress_license_name($license, $mark_default) {
    $default_license = get_option('cc_wordpress_default_license');

    if ($license == 'reserved') {
        $license_name = __('All rights reserved.');
    } else {
        $license_name = strtoupper($license);

        if ($license == $default_license and $mark_default == true) {
            $license_name .= ' ' .__('(Default)');
        }
    }

    return $license_name;
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
}

label input {
    margin: 1em;
}
</style>

<div class="wrap">
    <form method="post" action="options.php">

        <?php
        settings_fields('cc_wordpress_options');
        ?>

        <h2>Default License</h2>
        <p>
            Setting a default license pre-fills the license chooser form field with the chosen license.
        </p>

        <p>
            <label>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAQAAABuvaSwAAAAAnNCSVQICFXsRgQAAAAJcEhZcwAABJ0AAASdAXw0a6EAAAAZdEVYdFNvZnR3YXJlAHd3dy5pbmtzY2FwZS5vcmeb7jwaAAABmklEQVQoz5XTPWiTURTG8d8b/GjEii2VKoqKi2DFwU9wUkTdFIeKIEWcpIOTiA4OLgVdXFJwEZHoIII0TiJipZJFrIgGKXQQCRg6RKREjEjMcQnmTVPB3jNc7j1/7nk49zlJ+P+1rPsqydqFD1HvSkUq9MkpaQihoWRcfzqftGUkx9y10Yy33vlttz2GzBmNQtfLrmqqGu6odNKccOvvubXt1/Da+tAZBkwKx1OwHjNqti1EQ7DBN2Vr2vBl4cJiaAjOCdfbcMF3mWC7O6qmDFntms9KzgYZNU/bcFkxBM+UjXjiilFNl4yZsCIoqrRgA0IuGNRws1W66H1KSE5YFzKoa+pFTV0/ydYk66s+kt5kE1ilqd7qs49KIcj75bEfxp0RJn0yKxtMm21rzmtYG6x0Wt5Fy4ODbhuzJejx06M2PCzc+2frbgjn0z9YEE4tih7Q8FyShgdVzRvpQk+omLe5wxvBIV+ECTtkQpCx00Oh4ugCI7XcfF8INa9MqQnhQdrRSedYJYcdsc9eTHvjRbzsyC5lBjNLYP0B5PQk1O2dJT8AAAAASUVORK5CYII=" alt="Creative Commons"/>

                <?php
                $current_license = get_option('cc_wordpress_default_license');
                echo cc_wordpress_license_select($current_license, 'cc_wordpress_default_license', false);
                ?>
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
        echo '<link rel="stylesheet" href="'. get_bloginfo('url') .'/wp-content/plugins/wordpress-cc-plugin/css/'. get_option('cc_wordpress_css') .'" type="text/css"/>';
    }
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
.cc_license > td {
    border-top: 1px solid #c0c0c0;
    border-style: solid;
    padding-top: 7px !important;
}

#cc_license {
    margin-right: 1em;
}

#cc_license,
#cc_license + p {
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
        $current_license = $default_license;
    }

    $name = 'attachments['. $id .'][cc_license]';
    $html = cc_wordpress_license_select($current_license, $name, true);
    
    $form_fields['cc_license'] = array(
        'label' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAQAAABuvaSwAAAAAnNCSVQICFXsRgQAAAAJcEhZcwAABJ0AAASdAXw0a6EAAAAZdEVYdFNvZnR3YXJlAHd3dy5pbmtzY2FwZS5vcmeb7jwaAAABmklEQVQoz5XTPWiTURTG8d8b/GjEii2VKoqKi2DFwU9wUkTdFIeKIEWcpIOTiA4OLgVdXFJwEZHoIII0TiJipZJFrIgGKXQQCRg6RKREjEjMcQnmTVPB3jNc7j1/7nk49zlJ+P+1rPsqydqFD1HvSkUq9MkpaQihoWRcfzqftGUkx9y10Yy33vlttz2GzBmNQtfLrmqqGu6odNKccOvvubXt1/Da+tAZBkwKx1OwHjNqti1EQ7DBN2Vr2vBl4cJiaAjOCdfbcMF3mWC7O6qmDFntms9KzgYZNU/bcFkxBM+UjXjiilFNl4yZsCIoqrRgA0IuGNRws1W66H1KSE5YFzKoa+pFTV0/ydYk66s+kt5kE1ilqd7qs49KIcj75bEfxp0RJn0yKxtMm21rzmtYG6x0Wt5Fy4ODbhuzJejx06M2PCzc+2frbgjn0z9YEE4tih7Q8FyShgdVzRvpQk+omLe5wxvBIV+ECTtkQpCx00Oh4ugCI7XcfF8INa9MqQnhQdrRSedYJYcdsc9eTHvjRbzsyC5lBjNLYP0B5PQk1O2dJT8AAAAASUVORK5CYII=" alt="Creative Commons"> '. __('License'),
        'input' => 'html',
        'html'  => $html,
        'helps' => __('Choose a Creative Commons License.')
        );

    $html = '<input type="text" id="cc_rights_holder" name="attachments['. $post->ID .'][cc_rights_holder]" value="'. get_post_meta($id, 'cc_rights_holder', true) .'"/>';

    $form_fields['cc_rights_holder'] = array(
        'label' => __('Rights holder'),
        'input' => 'html',
        'html' => $html
        );

    $html = '<input type="url" id="cc_attribution_url" name="attachments['. $post->ID .'][cc_attribution_url]" value="'. get_post_meta($id, 'cc_attribution_url', true) .'"/>';

    $form_fields['cc_attribution_url'] = array(
        'label' => __('Attribution') .' <abbr title="Uniform Resource Locator">URL</abbr>',
        'input' => 'html',
        'html' => $html
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

function cc_wordpress_attachment_fields_to_save($post, $attachment) {

    $id = $post['ID'];

    cc_wordpress_update_or_add_or_delete($id, 'cc_license', $attachment['cc_license']);
    cc_wordpress_update_or_add_or_delete($id, 'cc_rights_holder', $attachment['cc_rights_holder']);
    cc_wordpress_update_or_add_or_delete($id, 'cc_attribution_url', $attachment['cc_attribution_url']);

    // grab license information through CC API
    $rest = file_get_contents('http://api.creativecommons.org/rest/1.5/license/standard/get');
    $license = get_post_meta($id, 'cc_license', true);

    if ($rest) {
        preg_match('/<license-uri>(.*?)<\/license-uri>/', $rest, $matches);
        $license_url = str_replace('/by/', '/'. $license .'/' , $matches[1]);
    } else {
        $license_url = 'http://creativecommons.org/licenses/'. $license .'/3.0/';
    }

    cc_wordpress_update_or_add_or_delete($id, 'cc_license_url', $license_url);

    return $post;
}

function cc_wordpress_media_send_to_editor($html, $attachment_id, $attachment) {
    $post =& get_post($attachment_id, ARRAY_A);
    $id = $post['ID'];

    // save licensing information before sending to editor
    cc_wordpress_attachment_fields_to_save($post, $attachment);

    $title = $attachment['post_excerpt'];

    # example output: "[[cc:18|some caption]]"
    $embed_code = '[[cc:' . $attachment_id .'|' .$title. ']]';
    return $embed_code;
}

function cc_wordpress_create_figure($attachment_id, $title, $size, $is_post_thumbnail) {
    $post =& get_post($attachment_id);
    $id = $post->ID;

    if ($title == '') {
        $title = $post->post_excerpt;
    }

    $type = substr($post->post_mime_type, 0, 5);

    $url = wp_get_attachment_url($id);
    $alt = get_post_meta($id, '_wp_attachment_image_alt', true);

    if ($type == 'image') {
        $dmci_type_url = 'http://purl.org/dc/dcmitype/Image';
        if (isset($size)) {
            $image = wp_get_attachment_image_src($id);
            $media_html  = '<img src="'. $image[0] .'" alt="'. $alt .'"/>';
        } else {
            $media_html  = '<img src="'. $url .'" alt="'. $alt .'"/>';
        }
    } elseif ($type == 'audio') {
        $dmci_type_url = 'http://purl.org/dc/dcmitype/Sound';
        $media_html = '<audio src="'. $url . '"/>';
    } elseif ($type == 'video') {
        $dmci_type_url = 'http://purl.org/dc/dcmitype/MovingImage';
        $media_html = '<video src="'. $url .'"/>';
    } else {
        $media_html = '<object src="'. $url .'"/>';
    }

    $attribution_name = get_post_meta($id, 'cc_rights_holder', true);
    $attribution_url = get_post_meta($id, 'cc_attribution_url', true);

    // TODO: license version and jurisdiction
    $license = get_post_meta($id, 'cc_license', true);
    $license_url = get_post_meta($id, 'cc_license_url', true);

    switch ($license) {
        case "by":
            $license_abbr = 'CC BY';
            $license_full = 'Creative Commons'. __('Attribution');
            break;

        case "by-nc":
            $license_abbr = 'CC BY-NC';
            $license_full = 'Creative Commons'. __('Attribution-Noncommercial');
            break;

        case "by-nd":
            $license_abbr = 'CC BY-ND';
            $license_full = 'Creative Commons'. __('Attribution-No Derivative Works');
            break;

        case "by-sa":
            $license_abbr = 'CC BY-SA';
            $license_full = 'Creative Commons'. __('Attribution-ShareAlike');
            break;

        case "by-nc-nd":
            $license_abbr = 'CC BY-NC-ND';
            $license_full = 'Creative Commons'. __('Attribution-Noncommercial-No Derivative Works');
            break;

        case "by-nc-sa":
            $license_abbr = 'CC BY-NC-SA';
            $license_full = 'Creative Commons'. __('Attribution-Noncommercial-Share Alike');
            break;

        default:
            // no license, just return standard markup
            return wp_get_attachment_image($id);
    }

    // produce caption
    $caption_html = '<span href="'. $dmci_type_url .'" property="dc:title" rel="dc:type">'. $title .'</span> <a href="'. $attribution_url .'" property="cc:attributionName" rel="cc:attributionURL">'. $attribution_name .'</a> <small> <a href="'. $license_url .'" rel="license"> <abbr title="'. $license_full .'">'. $license_abbr .'</abbr> </a> </small>';

    // add specific thumbnail class
    if ($is_post_thumbnail == true) {
        $post_thumbnail_class = 'class="post-thumbnail" ';
    }

    // add figure element
    $html = '<figure '. $post_thumbnail_class .'about="'. $url .'" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/terms/"> '. $media_html .' <figcaption> '. $caption_html .'</figcaption> </figure>';

    return $html;
}

function cc_wordpress_article_filter($article) {
    # example match: "[[cc:18|some caption]]"
    preg_match_all('/\[\[cc:([0-9].*?)\|(.*?)\]\]/', $article, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $figure = cc_wordpress_create_figure($match[1], $match[2]);
        $article = str_replace($match[0], $figure, $article);
    }

    return $article;
}

function cc_wordpress_post_thumbnail_filter($html, $post_id, $post_thumbnail_id, $size, $attr) {
    $html = cc_wordpress_create_figure($post_thumbnail_id, '', $size, true);

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
add_filter('attachment_fields_to_save', 'cc_wordpress_attachment_fields_to_save', 11, 2);

// send to wordpress editor
add_filter('media_send_to_editor', 'cc_wordpress_media_send_to_editor', 11, 3);
?>
