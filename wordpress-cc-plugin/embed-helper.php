<?php
// use Wordpress functions
require '../../../wp-blog-header.php';

$id = $_GET["id"];

if (is_numeric($id) === false) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Non-numeric ID not allowed.';
    return;
}

$post =& get_post($id);
$url = wp_get_attachment_url($id);

if ($url === false) {
    header('HTTP/1.1 404 Not Found');
    echo 'There is no attachment with the specified ID.';
    return;
}

@ob_end_clean();
header('Access-Control-Allow-Origin:*');
header('Content-type: '. $post->post_mime_type);

if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
    header('HTTP/1.1 204 No Content');
    return;
}

readfile($url);
?>
