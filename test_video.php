<?php
$file = 'uploads/file_687d0a331c1c62.86994794.mp4';
header('Content-Type: video/mp4');
header('Content-Disposition: inline; filename="video.mp4"');
readfile($file);