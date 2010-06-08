<?php
/**
 * @package wordpress-cc-plugin
 * @author Nils Dagsson Moskopp // erlehmann
 * @version 0.2
 */
/*
Plugin Name: Wordpress CC Plugin
Plugin URI: http://labs.creativecommons.org/2010/05/24/gsoc-project-introduction-cc-wordpress-plugin/
Description: The Wordpress interface for managing media will be extended to have an option to specify a CC license for uploaded content. The Wordpress interface for writing blog posts will be extended so that when aforementioned content is inserted into an article, the correct markup will be generated.
Author: Nils Dagsson Moskopp // erlehmann
Version: 0.1
Author URI: http://dieweltistgarnichtso.net
*/

// this function adds attachment fields to the media manager
function cc_wordpress_fields_to_edit($form_fields, $post) {
?>

<style>

abbr {
    border-bottom: 1px dotted black;
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

label {
    display: inline-block;
    font-size: 13px;
    font-weight: bold;
    margin: 0.5em;
    width: 130px;
}

    label img {
        position: relative;
        top: 7px;
        margin-top: -7px;
    }

</style>

<?php
    print_r($post);

    $id = $post->ID;
    echo 'license: ';
    print_r(get_post_meta($id, 'cc_license'));
    // echo 'rights holder: '. get_post_meta('cc_rights_holder', $id, true);
    // echo 'attribution url: '. get_post_meta('cc_attribution_url', $id, true);

    $html = '
        <select id="cc_license" name="attachments['. $post->ID .'][cc_license]">
            <option value="">(none)</option>
            <option value="by">BY</option>
            <option value="by-nc">BY-NC</option>
            <option value="by-nd">BY-ND</option>
            <option value="by-sa">BY-SA</option>
            <option value="by-nc-nd">BY-NC-ND</option>
            <option value="by-nc-sa">BY-NC-SA</option>
        </select>';
    
    $form_fields['cc_license'] = array(
        'label' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAQAAABuvaSwAAAAAnNCSVQICFXsRgQAAAAJcEhZcwAABJ0AAASdAXw0a6EAAAAZdEVYdFNvZnR3YXJlAHd3dy5pbmtzY2FwZS5vcmeb7jwaAAABmklEQVQoz5XTPWiTURTG8d8b/GjEii2VKoqKi2DFwU9wUkTdFIeKIEWcpIOTiA4OLgVdXFJwEZHoIII0TiJipZJFrIgGKXQQCRg6RKREjEjMcQnmTVPB3jNc7j1/7nk49zlJ+P+1rPsqydqFD1HvSkUq9MkpaQihoWRcfzqftGUkx9y10Yy33vlttz2GzBmNQtfLrmqqGu6odNKccOvvubXt1/Da+tAZBkwKx1OwHjNqti1EQ7DBN2Vr2vBl4cJiaAjOCdfbcMF3mWC7O6qmDFntms9KzgYZNU/bcFkxBM+UjXjiilFNl4yZsCIoqrRgA0IuGNRws1W66H1KSE5YFzKoa+pFTV0/ydYk66s+kt5kE1ilqd7qs49KIcj75bEfxp0RJn0yKxtMm21rzmtYG6x0Wt5Fy4ODbhuzJejx06M2PCzc+2frbgjn0z9YEE4tih7Q8FyShgdVzRvpQk+omLe5wxvBIV+ECTtkQpCx00Oh4ugCI7XcfF8INa9MqQnhQdrRSedYJYcdsc9eTHvjRbzsyC5lBjNLYP0B5PQk1O2dJT8AAAAASUVORK5CYII="/ alt="Creative Commons"> '. __('License'),
        'input' => 'html',
        'html'  => $html,
        'helps' => __('Choose a Creative Commons License.')
        );

    $html = '<input type="text" id="cc_rights_holder" name="attachments['. $post->ID .'][cc_rights_holder]" value=""/>';

    $form_fields['cc_rights_holder'] = array(
        'label' => __('Rights holder'),
        'input' => 'html',
        'html' => $html
        );

    $html = '<input type="url" id="cc_attribution_url" name="attachments['. $post->ID .'][cc_attribution_url]" value=""/>';

    $form_fields['cc_attribution_url'] = array(
        'label' => __('Attribution') .'<abbr title="Uniform Resource Locator">URL</abbr>',
        'input' => 'html',
        'html' => $html
        );

    return $form_fields;
}

function cc_wordpress_update_or_add($id, $key, $value) {
    if(!update_post_meta($id, $key, $value)) {
        add_post_meta($id, $key, $val);
    };
}

function cc_wordpress_attachment_fields_to_save($post, $attachment) {

    $id = $post->ID;
    if ($attachment['cc_license']!='') {
        cc_wordpress_update_or_add($id, 'cc_license', $attachment['cc_license']);
    } else {
        delete_post_meta($id, 'cc_license');
    }
    
//    $post['cc_license'] = $attachment['cc_license'];
//    $post['cc_rights_holder'] = $attachment['cc_rights_holder'];
//    $post['cc_attribution_url'] = $attachment['cc_attribution_url'];

//    wp_update_post($post);

    return $post;
}

function cc_wordpress_media_send_to_editor($html, $attachment_id, $attachment) {
    $post =& get_post($attachment_id);

    $type = substr($post->post_mime_type, 0, 5);

    // TODO: get proper URL
    $url = $attachment['url'];
    $alt = $attachment['image_alt'];

    if ($type == 'image') {
        $media_html  = '<img src="'. $url .'" alt="'. $alt .'"/>';
    } elseif ($type == 'audio') {
        $media_html = '<audio src="'. $url . '"/>';
    } elseif ($type == 'video') {
        $media_html = '<video src="'. $url .'"/>';
    } else {
        $media_html = '<object src="'. $url .'"/>';
    }

    $attribution_name = $attachment['cc_rights_holder'];
    $attribution_url = $attachment['cc_attribution_url'];

    // TODO: license version and jurisdiction
    $license = $attachment['cc_license'];
    $license_url = 'http://creativecommons.org/licenses/'. $license .'/3.0/';

    switch ($license) {
        case "":
            // no license, just return standard markup
            return $html;
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
    }

    $title = $attachment->post_excerpt;

    // TODO: RDFa / Microdata switch (currently only RDFa supported)

    // produce caption
    $caption_html = '<span href="http://purl.org/dc/dcmitype/Text" property="dc:title" rel="dc:type">'. $title .'</span> <a href="'. $attribution_url .'" property="cc:attributionName" rel="cc:attributionURL">'. $attribution_name .'</a> <small> <a href="'. $license_url .'" rel="license"> <abbr title="'. $license_full .'">'. $license_abbr .'</abbr> </a> </small>';

    // add figure element
    $html = '<figure xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/"> '. $media_html .' <figcaption> '. $caption_html .'</figcaption> </figure>';

    return $html;
}

// add attachment fields
add_filter('attachment_fields_to_edit', 'cc_wordpress_fields_to_edit', 11, 2);

// save attachment fields
// TODO: this is not working at the moment
add_filter('attachment_fields_to_save', 'cc_wordpress_attachment_fields_to_save', 11, 2);

//
add_filter('media_send_to_editor', 'cc_wordpress_media_send_to_editor', 11, 3);
?>
