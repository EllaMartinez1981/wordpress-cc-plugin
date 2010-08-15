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

header('Access-Control-Allow-Origin:*');
header('Content-Type: '. $mimetype);
header('Date: ' . gmstrftime("%A %d-%b-%y %T %Z", time()));

// cache media for two weeks
header('Cache-Control: max-age='. 14*24*60*60);

$etag = '"'. md5_file($abspath) .'"';
header('ETag: '. $etag);

if (stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
    header('HTTP/1.1 304 Not Modified');
    return;
}

$last_modified =  filemtime($abspath);
header('Last-Modified: '. gmstrftime("%A %d-%b-%y %T %Z", $last_modified));

$if_modified = stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']);
if ((strtotime($if_modified) !== False) && (($last_modified - strtotime($if_modified)) >= 0)) {
    header('HTTP/1.1 304 Not Modified');
    return;
}

header('Content-Length: '. filesize($abspath));

if (strpos($mimetype, '/ogg')) {
    require_once 'lib/ogg.class/ogg.class.php';
    $oggfile = new Ogg($abspath);
    header('X-Content-Duration: '. $oggfile->Streams[duration]);
}

if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
    header('HTTP/1.1 204 No Content');
    return;
}

$chunksize = 1*(1024*1024);
$buffer = '';
$file = fopen($abspath, 'rb');

if(!$file) {
    header ("HTTP/1.0 500 Internal server error");
    echo 'Attachment file could not be opened for reading.';
    return;
}

@ob_start();
while (!feof($file)) {
    $buffer = fread($file, $chunksize);
    print $buffer;
    @ob_flush();
    @flush();
}
@ob_end_flush();

fclose($file);
?>
