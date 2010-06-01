<?php
/**
 * @package wordpress-cc-plugin
 * @author Nils Dagsson Moskopp // erlehmann
 * @version 0.1
 */
/*
Plugin Name: Wordpress CC Plugin
Plugin URI: http://labs.creativecommons.org/2010/05/24/gsoc-project-introduction-cc-wordpress-plugin/
Description: The Wordpress interface for managing media will be extended to have an option to specify a CC license for uploaded content. The Wordpress interface for writing blog posts will be extended so that when aforementioned content is inserted into an article, the correct markup will be generated.
Author: Nils Dagsson Moskopp // erlehmann
Version: 0.1
Author URI: http://dieweltistgarnichtso.net
*/

function cc_interface() {
?>

<style>

abbr {
    border-bottom: 1px dotted black;
}

fieldset {
    border: 1px solid #dfdfdf !important;
    width: 623px !important;
}

input[type=text],
input[type=url] {
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

<fieldset>
    <h4 class="media-sub-title">Specify a Creative Commons license</h4>

    <ul>
        <li>
            <label for="author">
                Author
            </label>
            <input type="text" id="author" name="author" value="Nils Dagsson Moskopp"/>
        </li>
        <li>
            <label for="attribution">
                Attribution <abbr title="Uniform Resource Locator">URL</abbr>
            </label>
            <input type="url" id="attribution" name="attribution" value="http://dieweltistgarnichtso.net"/>

        </li>
        <li>
            <label for="cc-license">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAQAAABuvaSwAAAAAnNCSVQICFXsRgQAAAAJcEhZcwAABJ0AAASdAXw0a6EAAAAZdEVYdFNvZnR3YXJlAHd3dy5pbmtzY2FwZS5vcmeb7jwaAAABmklEQVQoz5XTPWiTURTG8d8b/GjEii2VKoqKi2DFwU9wUkTdFIeKIEWcpIOTiA4OLgVdXFJwEZHoIII0TiJipZJFrIgGKXQQCRg6RKREjEjMcQnmTVPB3jNc7j1/7nk49zlJ+P+1rPsqydqFD1HvSkUq9MkpaQihoWRcfzqftGUkx9y10Yy33vlttz2GzBmNQtfLrmqqGu6odNKccOvvubXt1/Da+tAZBkwKx1OwHjNqti1EQ7DBN2Vr2vBl4cJiaAjOCdfbcMF3mWC7O6qmDFntms9KzgYZNU/bcFkxBM+UjXjiilFNl4yZsCIoqrRgA0IuGNRws1W66H1KSE5YFzKoa+pFTV0/ydYk66s+kt5kE1ilqd7qs49KIcj75bEfxp0RJn0yKxtMm21rzmtYG6x0Wt5Fy4ODbhuzJejx06M2PCzc+2frbgjn0z9YEE4tih7Q8FyShgdVzRvpQk+omLe5wxvBIV+ECTtkQpCx00Oh4ugCI7XcfF8INa9MqQnhQdrRSedYJYcdsc9eTHvjRbzsyC5lBjNLYP0B5PQk1O2dJT8AAAAASUVORK5CYII="/ alt="Creative Commons">
                License
            </label>
            <select id="cc-license" name="cc-license">
                <option value="by" selected="selected">BY</option>
                <option value="by-nc">BY-NC</option>
                <option value="by-nd">BY-ND</option>
                <option value="by-sa">BY-SA</option>
                <option value="by-nc-nd">BY-NC-ND</option>
                <option value="by-nc-sa">BY-NC-SA</option>
            </select>
        </li>
    </ul>
</fieldset>

<?php
}

// Now we set that function up to execute when the admin_footer action is called
add_action('post-html-upload-ui', 'cc_interface');

?>
