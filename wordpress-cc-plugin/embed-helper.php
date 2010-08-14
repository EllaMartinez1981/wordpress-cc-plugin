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
$filename = get_post_meta($id, '_wp_attached_file', true);

if ($filename == '') {
    header('HTTP/1.1 404 Not Found');
    echo 'There is no attachment with the specified ID.';
    return;
}

$upload_dir = wp_upload_dir();
$abspath = $upload_dir['basedir'] .'/'. $filename;

$mimetype = $post->post_mime_type;

@ob_end_clean();
header('Access-Control-Allow-Origin:*');
header('Content-Size: '. filesize($abspath));
header('Content-type: '. $mimetype);

if (strpos($mimetype, '/ogg')) {
    require_once 'lib/ogg.class/ogg.class.php';
    $oggfile = new Ogg($abspath);
    header('X-Content-Duration: '. $oggfile->Streams[duration]);
}

if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
    header('HTTP/1.1 204 No Content');
    return;
}

// loads the whole file into memory - NOT GOOD
readfile($abspath);
?>
