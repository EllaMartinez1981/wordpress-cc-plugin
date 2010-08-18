<?php
// use Wordpress functions
require '../../../wp-blog-header.php';

function embed_helper_readfile($abspath, $start=False, $end=False) {
    $chunksize = 1*(1024*1024);
    $buffer = '';

    $file = fopen($abspath, 'rb');

    if ($file === False) {
        header('HTTP/1.1 500 Internal server error');
        echo 'Attachment file could not be opened for reading.';
        return;
    }

    $filesize = filesize($abspath);

    if (($start !== False) and ($end !== False)) {
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: bytes'. $start .'-'. $end .'/'. $filesize);
        fseek($file, $start, 0);
        $current = $start;
    } else {
        header('HTTP/1.1 200 OK');
        $end = $filesize;
    }

    ob_end_clean ();
    while (!feof($file) ) {
        if ($current + $chunksize >= $end) {
            $buffer = fread($file, ($end - $current + 1));
            echo $buffer;

            @ob_flush();
            break;
        } else {
            $buffer = fread($file, $chunksize);
            echo $buffer;
            $current += $chunksize;

            @ob_flush();
            }
    }

    fclose($file);
}

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

$filesize = filesize($abspath);
header('Content-Length: '. $filesize);

if (strpos($mimetype, '/ogg')) {
    require_once 'lib/ogg.class/ogg.class.php';
    $oggfile = new Ogg($abspath);
    header('X-Content-Duration: '. $oggfile->Streams[duration]);
}

header('Accept-Ranges: bytes');

if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
    header('HTTP/1.1 204 No Content');
    return;
}

header('Content-Disposition: inline, filename='. basename($filename));

if (!isset($_SERVER['HTTP_RANGE']) or !preg_match('/bytes=\d*-\d*(,\d*-\d*)*$/', $_SERVER['HTTP_RANGE'])) {
    // return full content for malformed range
    embed_helper_readfile($abspath);
} else {
    // Use regex only for testing if format looks valid, explode() results
    // see <http://stackoverflow.com/questions/2209204/parsing-http-range-header-in-php#answer-2211880>
    $ranges = explode(',', substr($_SERVER['HTTP_RANGE'], 6));
    foreach($ranges as $range) {
        $parts = explode('-', $range);

        $start = $parts[0];
        if ($start == '') {
            $start = 0;
        }
        if ($start > $filesize-1) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header('Content-Range: bytes */'. $filesize);
            return;
        }

        $end = $parts[1];
        if ($end == '') {
            $end = $filesize-1;
        }
        if ($end > $filesize-1) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header('Content-Range: bytes */' . $filesize);
            return;
        }

        if ($end < $start) {
            // "In the case that the second integer is smaller than the first one,
            // that particular range is tagged as invalid, and ignored.  If it was
            // the only requested byte range, the entire document is returned."
            // see <http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt>
            if (count($ranges) == 1) {
                embed_helper_readfile($abspath);
            }
        } else {
            // everything set up for range retrieval
            embed_helper_readfile($abspath, $start, $end);
        }
    }
}

?>
