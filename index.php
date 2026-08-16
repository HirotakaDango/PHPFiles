<?php
session_start();

$config = [
  'root_dir'           => __DIR__,
  'cache_dir'          => __DIR__ . '/.gallery_cache',
  'app_title'          => 'PHPFiles',
  'auth_enabled'       => false,
  'password'           => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: admin
  'thumb_size'         => 480,
  'thumb_quality'      => 85,
  'memory_limit'       => '512M',
  'max_upload_size'    => 100 * 1024 * 1024,
  'allow_upload'       => true,
  'allow_delete'       => true,
  'allow_rename'       => true,
  'allow_edit'         => true,
  'allow_zip'          => true,
  'allow_download'     => true,
  'image_extensions'   => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'],
  'video_extensions'   => ['mp4', 'webm', 'mov', 'm4v', 'ogv', 'mkv'],
  'audio_extensions'   => ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac'],
  'text_extensions'    => ['txt', 'md', 'json', 'js', 'css', 'html', 'php', 'py', 'c', 'cpp', 'sh', 'log', 'xml', 'yaml', 'yml', 'ini', 'env', 'sql', 'csv'],
  'archive_extensions' => ['zip', 'tar', 'gz', '7z', 'rar'],
];

ini_set('memory_limit', $config['memory_limit']);

if (!is_dir($config['cache_dir'])) {
  @mkdir($config['cache_dir'], 0777, true);
  @file_put_contents($config['cache_dir'] . '/.htaccess', "Order Deny,Allow\nDeny from all");
}

function formatBytes($bytes, $precision = 2) {
  if ($bytes >= 1073741824) return number_format($bytes / 1073741824, $precision) . ' GB';
  if ($bytes >= 1048576) return number_format($bytes / 1048576, $precision) . ' MB';
  if ($bytes >= 1024) return number_format($bytes / 1024, $precision) . ' KB';
  return $bytes . ' B';
}

function safePath($base, $requestPath) {
  $realBase = realpath($base);
  if (!$realBase) return false;
  $targetPath = $realBase . DIRECTORY_SEPARATOR . ltrim(urldecode($requestPath ?? ''), '/\\');
  $realTarget = realpath($targetPath);
  if ($realTarget === false) {
    $check = normalizePath($targetPath);
    if (strpos($check, $realBase) === 0) return $check;
    return false;
  }
  if (strpos($realTarget, $realBase) !== 0) return $realBase;
  return $realTarget;
}

function normalizePath($path) {
  $parts = explode(DIRECTORY_SEPARATOR, str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
  $safe = [];
  foreach ($parts as $p) {
    if ($p === '' || $p === '.') continue;
    if ($p === '..') array_pop($safe);
    else $safe[] = $p;
  }
  return (DIRECTORY_SEPARATOR === '\\' ? '' : DIRECTORY_SEPARATOR) . implode(DIRECTORY_SEPARATOR, $safe);
}

function jsonResponse($data, $status = 200) {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

function getFileType($ext, $config) {
  $ext = strtolower($ext);
  if (in_array($ext, $config['image_extensions'])) return 'image';
  if (in_array($ext, $config['video_extensions'])) return 'video';
  if (in_array($ext, $config['audio_extensions'])) return 'audio';
  if (in_array($ext, $config['text_extensions'])) return 'text';
  if (in_array($ext, $config['archive_extensions'])) return 'archive';
  if ($ext === 'pdf') return 'pdf';
  return 'file';
}

function getFolderStats($dirPath, $cacheDir) {
  $size = 0;
  $files = 0;
  $folders = 0;
  $cacheReal = realpath($cacheDir);

  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
  );

  foreach ($it as $item) {
    $p = $item->getPathname();
    if ($cacheReal && strpos($p, $cacheReal) === 0) continue;
    if ($item->isDir()) {
      $folders++;
    } else {
      $files++;
      $size += $item->getSize();
    }
  }
  return ['size' => $size, 'files' => $files, 'folders' => $folders];
}

function getExifMetadata($filePath) {
  $meta = ['exif' => [], 'iptc' => []];
  $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

  if (in_array($ext, ['jpg', 'jpeg', 'tiff']) && function_exists('exif_read_data')) {
    $exif = @exif_read_data($filePath, 'ANY_TAG', true);
    if ($exif) {
      if (isset($exif['IFD0']['Make'])) $meta['exif']['Camera Make'] = trim($exif['IFD0']['Make']);
      if (isset($exif['IFD0']['Model'])) $meta['exif']['Camera Model'] = trim($exif['IFD0']['Model']);
      if (isset($exif['EXIF']['ExposureTime'])) $meta['exif']['Shutter Speed'] = $exif['EXIF']['ExposureTime'] . 's';
      if (isset($exif['EXIF']['FNumber'])) {
        $f = explode('/', $exif['EXIF']['FNumber']);
        $meta['exif']['Aperture'] = 'f/' . (count($f) === 2 && $f[1] > 0 ? round($f[0] / $f[1], 1) : $exif['EXIF']['FNumber']);
      }
      if (isset($exif['EXIF']['ISOSpeedRatings'])) $meta['exif']['ISO'] = is_array($exif['EXIF']['ISOSpeedRatings']) ? $exif['EXIF']['ISOSpeedRatings'][0] : $exif['EXIF']['ISOSpeedRatings'];
      if (isset($exif['EXIF']['FocalLength'])) {
        $fl = explode('/', $exif['EXIF']['FocalLength']);
        $meta['exif']['Focal Length'] = (count($fl) === 2 && $fl[1] > 0 ? round($fl[0] / $fl[1], 1) : $exif['EXIF']['FocalLength']) . 'mm';
      }
      if (isset($exif['EXIF']['DateTimeOriginal'])) $meta['exif']['Date Taken'] = $exif['EXIF']['DateTimeOriginal'];

      if (isset($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef'], $exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef'])) {
        $lat = parseGPSCoordinate($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
        $lon = parseGPSCoordinate($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);
        if ($lat !== null && $lon !== null) {
          $meta['exif']['GPS Coordinates'] = sprintf('%.5f, %.5f', $lat, $lon);
          $meta['exif']['Maps'] = "https://www.google.com/maps?q={$lat},{$lon}";
        }
      }
    }
  }

  if (in_array($ext, ['jpg', 'jpeg']) && function_exists('iptcparse')) {
    @getimagesize($filePath, $info);
    if (!empty($info['APP13'])) {
      $iptc = @iptcparse($info['APP13']);
      if ($iptc) {
        if (!empty($iptc['2#005'][0])) $meta['iptc']['Title'] = $iptc['2#005'][0];
        if (!empty($iptc['2#120'][0])) $meta['iptc']['Caption'] = $iptc['2#120'][0];
        if (!empty($iptc['2#080'][0])) $meta['iptc']['Author'] = $iptc['2#080'][0];
        if (!empty($iptc['2#025'])) $meta['iptc']['Keywords'] = implode(', ', $iptc['2#025']);
      }
    }
  }
  return $meta;
}

function parseGPSCoordinate($coord, $ref) {
  if (!is_array($coord) || count($coord) < 3) return null;
  $parts = [];
  for ($i = 0; $i < 3; $i++) {
    $p = explode('/', $coord[$i]);
    $parts[$i] = (count($p) === 2 && $p[1] > 0) ? ($p[0] / $p[1]) : floatval($coord[$i]);
  }
  $degrees = $parts[0] + ($parts[1] / 60) + ($parts[2] / 3600);
  return ($ref === 'S' || $ref === 'W') ? -$degrees : $degrees;
}

function createThumbnail($src, $dest, $size, $quality) {
  if (!file_exists($src)) return false;
  $info = @getimagesize($src);
  if (!$info) return false;

  list($origW, $origH) = $info;
  $mime = $info['mime'];

  $ratio = min($size / $origW, $size / $origH);
  $newW = max(1, round($origW * $ratio));
  $newH = max(1, round($origH * $ratio));

  $srcImg = false;
  switch ($mime) {
    case 'image/jpeg': $srcImg = @imagecreatefromjpeg($src); break;
    case 'image/png':  $srcImg = @imagecreatefrompng($src); break;
    case 'image/gif':  $srcImg = @imagecreatefromgif($src); break;
    case 'image/webp': $srcImg = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false; break;
    case 'image/avif': $srcImg = function_exists('imagecreatefromavif') ? @imagecreatefromavif($src) : false; break;
    case 'image/bmp':  $srcImg = function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($src) : false; break;
  }
  if (!$srcImg) return false;

  if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
    $exif = @exif_read_data($src);
    if (!empty($exif['Orientation'])) {
      switch ($exif['Orientation']) {
        case 3: $srcImg = imagerotate($srcImg, 180, 0); break;
        case 6:
          $srcImg = imagerotate($srcImg, -90, 0);
          list($origW, $origH) = [$origH, $origW];
          break;
        case 8:
          $srcImg = imagerotate($srcImg, 90, 0);
          list($origW, $origH) = [$origH, $origW];
          break;
      }
      $ratio = min($size / $origW, $size / $origH);
      $newW = max(1, round($origW * $ratio));
      $newH = max(1, round($origH * $ratio));
    }
  }

  $destImg = imagecreatetruecolor($newW, $newH);
  if ($mime === 'image/png' || $mime === 'image/webp') {
    imagealphablending($destImg, false);
    imagesavealpha($destImg, true);
    $transparent = imagecolorallocatealpha($destImg, 255, 255, 255, 127);
    imagefilledrectangle($destImg, 0, 0, $newW, $newH, $transparent);
  }
  imagecopyresampled($destImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

  $ok = imagejpeg($destImg, $dest, $quality);
  imagedestroy($srcImg);
  imagedestroy($destImg);
  return $ok;
}

function streamRangeFile($path, $mime) {
  $filesize = filesize($path);
  $offset = 0;
  $length = $filesize;

  if (isset($_SERVER['HTTP_RANGE'])) {
    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (strpos($range, ',') !== false) {
      header('HTTP/1.1 416 Requested Range Not Satisfiable');
      header("Content-Range: bytes */$filesize");
      exit;
    }
    if ($range === '-') {
      $offset = $filesize - substr($range, 1);
    } else {
      $range = explode('-', $range);
      $offset = intval($range[0]);
      $end = (isset($range[1]) && is_numeric($range[1])) ? intval($range[1]) : $filesize - 1;
      $length = $end - $offset + 1;
    }
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $offset-" . ($offset + $length - 1) . "/$filesize");
  }

  header('Content-Type: ' . $mime);
  header('Accept-Ranges: bytes');
  header('Content-Length: ' . $length);
  header('Cache-Control: public, max-age=86400');

  $fp = fopen($path, 'rb');
  fseek($fp, $offset);
  while (!feof($fp) && ($length > 0)) {
    $read = min(8192, $length);
    echo fread($fp, $read);
    $length -= $read;
    flush();
  }
  fclose($fp);
  exit;
}

function deleteRecursive($path) {
  if (is_dir($path) && !is_link($path)) {
    $files = array_diff(scandir($path), ['.', '..']);
    foreach ($files as $file) {
      deleteRecursive($path . DIRECTORY_SEPARATOR . $file);
    }
    return @rmdir($path);
  }
  return @unlink($path);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($config['auth_enabled']) {
  if ($action === 'login') {
    $pass = $_POST['password'] ?? '';
    if (password_verify($pass, $config['password']) || $pass === $config['password']) {
      $_SESSION['authenticated'] = true;
      jsonResponse(['success' => true]);
    }
    jsonResponse(['error' => 'Invalid password'], 401);
  }

  if ($action === 'logout') {
    unset($_SESSION['authenticated']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
  }

  if (empty($_SESSION['authenticated'])) {
    if ($action && $action !== 'login') {
      jsonResponse(['error' => 'Unauthorized'], 401);
    }
  }
}

if ($action && (!$config['auth_enabled'] || !empty($_SESSION['authenticated']))) {
  if ($action === 'list') {
    $dir = $_GET['dir'] ?? '';
    $fullPath = safePath($config['root_dir'], $dir);
    if (!$fullPath || !is_dir($fullPath)) jsonResponse(['error' => 'Directory not found'], 404);

    $relDir = ltrim(str_replace(['\\', '//'], '/', substr($fullPath, strlen(realpath($config['root_dir'])))), '/');
    $scanned = @scandir($fullPath) ?: [];
    $folders = [];
    $files = [];
    $totalSize = 0;

    foreach ($scanned as $item) {
      if ($item === '.' || $item === '..' || substr($item, 0, 1) === '.') continue;
      $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
      if ($itemPath === realpath($config['cache_dir'])) continue;

      $itemRel = $relDir ? ($relDir . '/' . $item) : $item;
      $mtime = @filemtime($itemPath);

      if (is_dir($itemPath)) {
        $subCount = count(array_diff(@scandir($itemPath) ?: [], ['.', '..', '.gallery_cache']));
        $folders[] = [
          'name'        => $item,
          'path'        => $itemRel,
          'mtime'       => $mtime,
          'items_count' => $subCount,
        ];
      } else {
        $size = @filesize($itemPath);
        $totalSize += $size;
        $ext = strtolower(pathinfo($itemPath, PATHINFO_EXTENSION));
        $type = getFileType($ext, $config);

        $width = 0; $height = 0;
        if ($type === 'image') {
          $imgSize = @getimagesize($itemPath);
          if ($imgSize) {
            $width = $imgSize[0];
            $height = $imgSize[1];
          }
        }

        $files[] = [
          'name'     => $item,
          'path'     => $itemRel,
          'size'     => $size,
          'size_fmt' => formatBytes($size),
          'mtime'    => $mtime,
          'ext'      => $ext,
          'type'     => $type,
          'width'    => $width,
          'height'   => $height,
        ];
      }
    }

    usort($folders, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
    usort($files, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

    jsonResponse([
      'path'       => $relDir,
      'folders'    => $folders,
      'files'      => $files,
      'stats'      => [
        'folders'    => count($folders),
        'files'      => count($files),
        'total_size' => formatBytes($totalSize),
      ]
    ]);
  }

  if ($action === 'search') {
    $dir = $_GET['dir'] ?? '';
    $query = trim($_GET['q'] ?? '');
    $fullPath = safePath($config['root_dir'], $dir);
    if (!$fullPath || !is_dir($fullPath) || $query === '') {
      jsonResponse(['folders' => [], 'files' => [], 'query' => $query, 'count' => 0]);
    }

    $maxResults = 200;
    $foundFolders = [];
    $foundFiles = [];
    $rootLen = strlen(realpath($config['root_dir']));
    $cacheReal = realpath($config['cache_dir']);

    $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS;
    $dirIterator = new RecursiveDirectoryIterator($fullPath, $flags);
    $filterIterator = new RecursiveCallbackFilterIterator($dirIterator, function ($current) use ($cacheReal) {
      $path = $current->getPathname();
      $filename = $current->getFilename();
      if ($filename[0] === '.' || ($cacheReal && strpos($path, $cacheReal) === 0)) {
        return false;
      }
      return true;
    });

    $iterator = new RecursiveIteratorIterator($filterIterator, RecursiveIteratorIterator::SELF_FIRST);
    $iterator->setMaxDepth(10);

    $count = 0;
    foreach ($iterator as $item) {
      if ($count >= $maxResults) break;
      $name = $item->getFilename();
      if (stripos($name, $query) !== false) {
        $itemPath = $item->getPathname();
        $rel = ltrim(str_replace(['\\', '//'], '/', substr($itemPath, $rootLen)), '/');
        $mtime = $item->getMTime();

        if ($item->isDir()) {
          $foundFolders[] = [
            'name'        => $name,
            'path'        => $rel,
            'mtime'       => $mtime,
            'items_count' => 0
          ];
        } else {
          $size = $item->getSize();
          $ext = strtolower($item->getExtension());
          $type = getFileType($ext, $config);
          $foundFiles[] = [
            'name'     => $name,
            'path'     => $rel,
            'size'     => $size,
            'size_fmt' => formatBytes($size),
            'mtime'    => $mtime,
            'ext'      => $ext,
            'type'     => $type,
            'width'    => 0,
            'height'   => 0
          ];
        }
        $count++;
      }
    }

    usort($foundFolders, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
    usort($foundFiles, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

    jsonResponse([
      'folders' => $foundFolders,
      'files'   => $foundFiles,
      'query'   => $query,
      'count'   => $count
    ]);
  }

  if ($action === 'tree') {
    function buildTree($base, $currentRel = '') {
      $realBase = safePath($base, $currentRel);
      if (!$realBase || !is_dir($realBase)) return [];
      $items = @scandir($realBase) ?: [];
      $nodes = [];
      foreach ($items as $item) {
        if ($item === '.' || $item === '..' || substr($item, 0, 1) === '.') continue;
        $full = $realBase . DIRECTORY_SEPARATOR . $item;
        if (!is_dir($full)) continue;
        $rel = $currentRel ? ($currentRel . '/' . $item) : $item;
        $nodes[] = [
          'name'     => $item,
          'path'     => $rel,
          'children' => buildTree($base, $rel)
        ];
      }
      usort($nodes, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
      return $nodes;
    }
    jsonResponse(buildTree($config['root_dir']));
  }

  if ($action === 'thumb') {
    $file = $_GET['f'] ?? '';
    $fullPath = safePath($config['root_dir'], $file);
    if (!$fullPath || !is_file($fullPath)) {
      header('HTTP/1.0 404 Not Found');
      exit;
    }

    $hash = md5($fullPath . filemtime($fullPath) . filesize($fullPath));
    $cachePath = $config['cache_dir'] . DIRECTORY_SEPARATOR . $hash . '.jpg';

    if (!file_exists($cachePath)) {
      createThumbnail($fullPath, $cachePath, $config['thumb_size'], $config['thumb_quality']);
    }

    if (file_exists($cachePath)) {
      header('Content-Type: image/jpeg');
      header('Cache-Control: public, max-age=31536000, immutable');
      header('Content-Length: ' . filesize($cachePath));
      readfile($cachePath);
    } else {
      $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
      header('Content-Type: ' . $mime);
      readfile($fullPath);
    }
    exit;
  }

  if ($action === 'raw' || $action === 'file') {
    $file = $_GET['f'] ?? '';
    $fullPath = safePath($config['root_dir'], $file);
    if (!$fullPath || !is_file($fullPath)) {
      header('HTTP/1.0 404 Not Found');
      exit;
    }
    $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    if ($ext === 'svg') $mime = 'image/svg+xml';
    if ($ext === 'js') $mime = 'application/javascript';
    if ($ext === 'css') $mime = 'text/css';

    streamRangeFile($fullPath, $mime);
  }

  if ($action === 'download') {
    $file = $_GET['f'] ?? '';
    $fullPath = safePath($config['root_dir'], $file);
    if (!$fullPath || !is_file($fullPath)) jsonResponse(['error' => 'File not found'], 404);

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
  }

  if ($action === 'details') {
    $items = $_POST['items'] ?? ($_GET['items'] ?? null);
    $singleFile = $_GET['f'] ?? ($_POST['f'] ?? null);

    if ($items && is_array($items)) {
      $totalBytes = 0;
      $fileCount = 0;
      $folderCount = 0;
      $types = [];

      foreach ($items as $rel) {
        $full = safePath($config['root_dir'], $rel);
        if (!$full || !file_exists($full)) continue;
        if (is_dir($full)) {
          $st = getFolderStats($full, $config['cache_dir']);
          $folderCount += 1 + $st['folders'];
          $fileCount += $st['files'];
          $totalBytes += $st['size'];
        } else {
          $fileCount++;
          $sz = filesize($full);
          $totalBytes += $sz;
          $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION)) ?: 'other';
          $types[$ext] = ($types[$ext] ?? 0) + 1;
        }
      }

      jsonResponse([
        'type'    => 'batch',
        'title'   => count($items) . ' Selected Items',
        'general' => [
          'Total Elements' => count($items),
          'Total Files'    => $fileCount,
          'Total Folders'  => $folderCount,
          'Combined Size'  => formatBytes($totalBytes),
          'File Breakdown' => empty($types) ? 'None' : implode(', ', array_map(fn($k, $v) => "$k ($v)", array_keys($types), $types))
        ]
      ]);
    }

    $fullPath = safePath($config['root_dir'], $singleFile);
    if (!$fullPath || !file_exists($fullPath)) jsonResponse(['error' => 'Target not found'], 404);

    $isDir = is_dir($fullPath);
    $relPath = ltrim(str_replace(['\\', '//'], '/', substr($fullPath, strlen(realpath($config['root_dir'])))), '/');

    if ($isDir) {
      $stats = getFolderStats($fullPath, $config['cache_dir']);
      jsonResponse([
        'type'    => 'folder',
        'title'   => basename($fullPath) ?: 'Root Directory',
        'general' => [
          'Folder Name'   => basename($fullPath) ?: 'Root',
          'Relative Path' => $relPath ?: '/',
          'Total Files'   => $stats['files'],
          'Subfolders'    => $stats['folders'],
          'Total Size'    => formatBytes($stats['size']),
          'Last Modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
          'Permissions'   => substr(sprintf('%o', fileperms($fullPath)), -4)
        ]
      ]);
    }

    $meta = getExifMetadata($fullPath);
    $meta['type'] = 'file';
    $meta['title'] = basename($fullPath);
    $meta['general'] = [
      'File Name'     => basename($fullPath),
      'Relative Path' => $relPath,
      'File Size'     => formatBytes(filesize($fullPath)),
      'Last Modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
      'MIME Type'     => mime_content_type($fullPath) ?: 'application/octet-stream',
      'Permissions'   => substr(sprintf('%o', fileperms($fullPath)), -4)
    ];

    $img = @getimagesize($fullPath);
    if ($img) {
      $meta['general']['Dimensions'] = "{$img[0]} × {$img[1]} px";
    }
    jsonResponse($meta);
  }

  if ($action === 'upload_chunk') {
    if (!$config['allow_upload']) jsonResponse(['error' => 'Upload disabled'], 403);
    $targetDir = safePath($config['root_dir'], $_POST['dir'] ?? '');
    if (!$targetDir || !is_dir($targetDir) || !is_writable($targetDir)) jsonResponse(['error' => 'Invalid upload directory'], 400);

    $uploadId = preg_replace('/[^\w\-]/', '', $_POST['upload_id'] ?? '');
    $chunkIndex = intval($_POST['chunk_index'] ?? 0);
    $totalChunks = intval($_POST['total_chunks'] ?? 1);
    $fileName = preg_replace('/[^\w\s\d\.\-_~()[\]]/', '', basename($_POST['file_name'] ?? ''));
    $relPath = trim(str_replace(['\\', '..'], ['/', ''], $_POST['relative_path'] ?? ''), '/');

    if (!$uploadId || !$fileName || empty($_FILES['chunk']['tmp_name'])) {
      jsonResponse(['error' => 'Missing chunk data'], 400);
    }

    $tempChunkDir = $config['cache_dir'] . DIRECTORY_SEPARATOR . 'chunks' . DIRECTORY_SEPARATOR . $uploadId;
    if (!is_dir($tempChunkDir)) {
      @mkdir($tempChunkDir, 0777, true);
    }

    $chunkFile = $tempChunkDir . DIRECTORY_SEPARATOR . "chunk_{$chunkIndex}";
    if (!@move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkFile)) {
      jsonResponse(['error' => 'Failed to save chunk'], 500);
    }

    $allReady = true;
    for ($i = 0; $i < $totalChunks; $i++) {
      if (!file_exists($tempChunkDir . DIRECTORY_SEPARATOR . "chunk_{$i}")) {
        $allReady = false;
        break;
      }
    }

    if ($allReady) {
      $destDir = $targetDir;
      if ($relPath) {
        $subPath = dirname($relPath);
        if ($subPath && $subPath !== '.') {
          $destDir = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);
          if (!is_dir($destDir)) {
            @mkdir($destDir, 0777, true);
          }
        }
      }

      $finalDest = $destDir . DIRECTORY_SEPARATOR . $fileName;
      $out = fopen($finalDest, 'wb');
      if (!$out) {
        jsonResponse(['error' => 'Cannot create destination file'], 500);
      }

      for ($i = 0; $i < $totalChunks; $i++) {
        $cPath = $tempChunkDir . DIRECTORY_SEPARATOR . "chunk_{$i}";
        $in = fopen($cPath, 'rb');
        if ($in) {
          while ($buff = fread($in, 65536)) {
            fwrite($out, $buff);
          }
          fclose($in);
          @unlink($cPath);
        }
      }
      fclose($out);
      @rmdir($tempChunkDir);

      jsonResponse(['success' => true, 'completed' => true, 'file' => $fileName]);
    }

    jsonResponse(['success' => true, 'completed' => false, 'chunk' => $chunkIndex]);
  }

  if ($action === 'create') {
    $parent = safePath($config['root_dir'], $_POST['dir'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'folder';
    if (!$parent || !is_dir($parent) || !$name) jsonResponse(['error' => 'Invalid parameters'], 400);

    $name = preg_replace('/[^\w\s\d\.\-_~()[\]]/', '', $name);
    $dest = $parent . DIRECTORY_SEPARATOR . $name;
    if (file_exists($dest)) jsonResponse(['error' => 'Item already exists'], 400);

    if ($type === 'folder') {
      $ok = @mkdir($dest, 0777, true);
    } else {
      $ok = @file_put_contents($dest, "") !== false;
    }
    jsonResponse(['success' => $ok]);
  }

  if ($action === 'rename') {
    if (!$config['allow_rename']) jsonResponse(['error' => 'Rename disabled'], 403);
    $item = safePath($config['root_dir'], $_POST['path'] ?? '');
    $newName = trim($_POST['new_name'] ?? '');
    if (!$item || !file_exists($item) || !$newName) jsonResponse(['error' => 'Invalid parameters'], 400);

    $newName = preg_replace('/[^\w\s\d\.\-_~()[\]]/', '', $newName);
    $dest = dirname($item) . DIRECTORY_SEPARATOR . $newName;
    if (file_exists($dest)) jsonResponse(['error' => 'Destination already exists'], 400);

    jsonResponse(['success' => @rename($item, $dest)]);
  }

  if ($action === 'delete') {
    if (!$config['allow_delete']) jsonResponse(['error' => 'Delete disabled'], 403);
    $items = $_POST['items'] ?? [];
    if (!is_array($items) || empty($items)) jsonResponse(['error' => 'No items specified'], 400);

    $deleted = 0;
    foreach ($items as $rel) {
      $full = safePath($config['root_dir'], $rel);
      if ($full && $full !== realpath($config['root_dir']) && file_exists($full)) {
        if (deleteRecursive($full)) $deleted++;
      }
    }
    jsonResponse(['success' => true, 'deleted_count' => $deleted]);
  }

  if ($action === 'read_text') {
    $file = $_GET['f'] ?? '';
    $fullPath = safePath($config['root_dir'], $file);
    if (!$fullPath || !is_file($fullPath)) jsonResponse(['error' => 'File not found'], 404);
    jsonResponse(['content' => @file_get_contents($fullPath)]);
  }

  if ($action === 'save_text') {
    if (!$config['allow_edit']) jsonResponse(['error' => 'Edit disabled'], 403);
    $file = $_POST['f'] ?? '';
    $content = $_POST['content'] ?? '';
    $fullPath = safePath($config['root_dir'], $file);
    if (!$fullPath || !is_file($fullPath)) jsonResponse(['error' => 'File not found'], 404);

    jsonResponse(['success' => @file_put_contents($fullPath, $content) !== false]);
  }

  if ($action === 'zip') {
    if (!$config['allow_zip'] || !class_exists('ZipArchive')) jsonResponse(['error' => 'ZipArchive unavailable'], 500);
    $parent = safePath($config['root_dir'], $_POST['dir'] ?? '');
    $items = $_POST['items'] ?? [];
    $zipName = trim($_POST['zip_name'] ?? 'archive.zip');
    if (!$parent || empty($items)) jsonResponse(['error' => 'Invalid parameters'], 400);

    $zipPath = $parent . DIRECTORY_SEPARATOR . $zipName;
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      jsonResponse(['error' => 'Cannot create zip archive'], 500);
    }

    foreach ($items as $rel) {
      $full = safePath($config['root_dir'], $rel);
      if ($full && file_exists($full)) {
        if (is_dir($full)) {
          $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full, RecursiveDirectoryIterator::SKIP_DOTS));
          foreach ($files as $f) {
            $zip->addFile($f->getPathname(), substr($f->getPathname(), strlen($parent) + 1));
          }
        } else {
          $zip->addFile($full, basename($full));
        }
      }
    }
    $zip->close();
    jsonResponse(['success' => true, 'archive' => basename($zipPath)]);
  }

  if ($action === 'unzip') {
    if (!$config['allow_zip'] || !class_exists('ZipArchive')) jsonResponse(['error' => 'ZipArchive unavailable'], 500);
    $file = safePath($config['root_dir'], $_POST['f'] ?? '');
    if (!$file || !is_file($file)) jsonResponse(['error' => 'Archive not found'], 404);

    $destDir = dirname($file);
    $zip = new ZipArchive();
    if ($zip->open($file) === true) {
      $zip->extractTo($destDir);
      $zip->close();
      jsonResponse(['success' => true]);
    }
    jsonResponse(['error' => 'Failed to extract zip'], 500);
  }

  if ($action === 'download_zip') {
    $dir = safePath($config['root_dir'], $_GET['dir'] ?? '');
    $items = $_POST['items'] ?? null;

    if (!$dir || !is_dir($dir) || !class_exists('ZipArchive')) jsonResponse(['error' => 'Invalid directory'], 400);

    $zipName = (basename($dir) ?: 'gallery') . '.zip';
    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    $zip = new ZipArchive();
    $zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    if ($items && is_array($items)) {
      foreach ($items as $rel) {
        $full = safePath($config['root_dir'], $rel);
        if ($full && file_exists($full)) {
          if (is_dir($full)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $f) {
              if (strpos($f->getPathname(), realpath($config['cache_dir'])) === 0) continue;
              $zip->addFile($f->getPathname(), substr($f->getPathname(), strlen($dir) + 1));
            }
          } else {
            $zip->addFile($full, basename($full));
          }
        }
      }
    } else {
      $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
      foreach ($files as $f) {
        if (strpos($f->getPathname(), realpath($config['cache_dir'])) === 0) continue;
        $zip->addFile($f->getPathname(), substr($f->getPathname(), strlen($dir) + 1));
      }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($tempZip));
    readfile($tempZip);
    @unlink($tempZip);
    exit;
  }

  if ($action === 'manga_offline') {
    $dir = safePath($config['root_dir'], $_GET['dir'] ?? '');
    if (!$dir || !is_dir($dir)) jsonResponse(['error' => 'Directory not found'], 404);

    $title = htmlspecialchars(basename($dir) ?: 'Manga Reader');
    $scanned = @scandir($dir) ?: [];
    $imageFiles = [];

    foreach ($scanned as $item) {
      if ($item === '.' || $item === '..' || substr($item, 0, 1) === '.') continue;
      $full = $dir . DIRECTORY_SEPARATOR . $item;
      if (is_file($full)) {
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        if (in_array($ext, $config['image_extensions'])) {
          $imageFiles[] = $full;
        }
      }
    }

    usort($imageFiles, fn($a, $b) => strnatcasecmp(basename($a), basename($b)));

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . ($title ?: 'manga') . '_offline.html"');

    echo "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
    echo "  <meta charset=\"UTF-8\">\n";
    echo "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
    echo "  <title>{$title} - Offline Reader</title>\n";
    echo "  <style>\n";
    echo "    * { box-sizing: border-box; margin: 0; padding: 0; }\n";
    echo "    body { background: #050505; color: #fff; display: flex; flex-direction: column; align-items: center; min-height: 100dvh; overflow-x: hidden; font-family: sans-serif; }\n";
    echo "    .header { position: sticky; top: 0; width: 100%; background: rgba(10,10,10,0.85); backdrop-filter: blur(10px); padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; z-index: 100; font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.1); }\n";
    echo "    .wrap { width: 100%; max-width: 1000px; display: flex; flex-direction: column; align-items: center; gap: 0; margin: 0 auto; }\n";
    echo "    .wrap img { display: block; width: 100%; height: auto; margin: 0 auto; padding: 0; border: none; }\n";
    echo "  </style>\n</head>\n<body>\n";
    echo "  <div class=\"header\"><strong>{$title}</strong><span>" . count($imageFiles) . " Pages</span></div>\n";
    echo "  <div class=\"wrap\">\n";

    foreach ($imageFiles as $img) {
      $mime = mime_content_type($img) ?: 'image/jpeg';
      $base64 = base64_encode(file_get_contents($img));
      echo "    <img src=\"data:{$mime};base64,{$base64}\" alt=\"Page\" loading=\"lazy\">\n";
    }

    echo "  </div>\n</body>\n</html>";
    exit;
  }

  jsonResponse(['error' => 'Invalid action'], 400);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($config['app_title']) ?></title>
    <link rel="icon" type="image/svg+xml" href="https://icons.getbootstrap.com/assets/icons/folder2-open.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
      :root[data-theme="dark"] {
        --md-sys-color-surface: #141218;
        --md-sys-color-surface-container-lowest: #0f0d13;
        --md-sys-color-surface-container-low: #1d1b20;
        --md-sys-color-surface-container: #211f26;
        --md-sys-color-surface-container-high: #2b2930;
        --md-sys-color-surface-container-highest: #36343b;
        --md-sys-color-on-surface: #e6e0e9;
        --md-sys-color-on-surface-variant: #cac4d0;
        --md-sys-color-outline: #938f99;
        --md-sys-color-outline-variant: #49454f;
        --md-sys-color-primary: #d0bcff;
        --md-sys-color-on-primary: #381e72;
        --md-sys-color-primary-container: #4f378b;
        --md-sys-color-on-primary-container: #eaddff;
        --md-sys-color-secondary-container: #4a4458;
        --md-sys-color-on-secondary-container: #e8def8;
        --md-sys-color-error: #f2b8b5;
        --md-sys-color-error-container: #8c1d18;
        --md-sys-color-on-error-container: #f9dedc;
        --md-elevation-1: 0px 1px 3px 1px rgba(0, 0, 0, 0.15), 0px 1px 2px 0px rgba(0, 0, 0, 0.3);
        --md-elevation-2: 0px 2px 6px 2px rgba(0, 0, 0, 0.15), 0px 1px 2px 0px rgba(0, 0, 0, 0.3);
      }
  
      :root[data-theme="light"] {
        --md-sys-color-surface: #fef7ff;
        --md-sys-color-surface-container-lowest: #ffffff;
        --md-sys-color-surface-container-low: #f7f2fa;
        --md-sys-color-surface-container: #f3edf7;
        --md-sys-color-surface-container-high: #ece6f0;
        --md-sys-color-surface-container-highest: #e6e0e9;
        --md-sys-color-on-surface: #1d1b20;
        --md-sys-color-on-surface-variant: #49454f;
        --md-sys-color-outline: #79747e;
        --md-sys-color-outline-variant: #cac4d0;
        --md-sys-color-primary: #6750a4;
        --md-sys-color-on-primary: #ffffff;
        --md-sys-color-primary-container: #eaddff;
        --md-sys-color-on-primary-container: #21005d;
        --md-sys-color-secondary-container: #e8def8;
        --md-sys-color-on-secondary-container: #1d192b;
        --md-sys-color-error: #b3261e;
        --md-sys-color-error-container: #f9dedc;
        --md-sys-color-on-error-container: #410e0b;
        --md-elevation-1: 0px 1px 3px 1px rgba(0, 0, 0, 0.08), 0px 1px 2px 0px rgba(0, 0, 0, 0.15);
        --md-elevation-2: 0px 2px 6px 2px rgba(0, 0, 0, 0.08), 0px 1px 2px 0px rgba(0, 0, 0, 0.15);
      }
  
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    * {
      scrollbar-width: thin;
      scrollbar-color: var(--md-sys-color-outline-variant) transparent;
    }
    *::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    *::-webkit-scrollbar-track {
      background: transparent;
    }
    *::-webkit-scrollbar-thumb {
      background: var(--md-sys-color-outline-variant);
      border-radius: 4px;
    }
    *::-webkit-scrollbar-thumb:hover {
      background: var(--md-sys-color-outline);
    }

    body {
      font-family: 'Roboto', system-ui, -apple-system, sans-serif;
        background-color: var(--md-sys-color-surface);
        color: var(--md-sys-color-on-surface);
        height: 100dvh;
        min-height: 100dvh;
        max-height: 100dvh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        user-select: none;
        -webkit-font-smoothing: antialiased;
        padding-bottom: env(safe-area-inset-bottom, 0px);
      }
      a { color: inherit; text-decoration: none; }
      button, input, select, textarea { font-family: inherit; font-size: inherit; color: inherit; border: none; background: none; }
      button { cursor: pointer; display: flex; align-items: center; justify-content: center; }
      svg { width: 20px; height: 20px; fill: currentColor; flex-shrink: 0; }
  
      .app-topbar {
        height: 56px;
        background: var(--md-sys-color-surface);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 0.8rem;
        z-index: 100;
        gap: 0.6rem;
        flex-shrink: 0;
      }
      .topbar-left, .topbar-right { display: flex; align-items: center; gap: 0.4rem; }
      .topbar-center { flex: 1; display: flex; justify-content: center; max-width: 540px; }
  
      .brand {
        font-weight: 700;
        font-size: 1.05rem;
        color: var(--md-sys-color-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
        margin-left: 0.2rem;
      }
  
      .subbar-path {
        height: 36px;
        background: var(--md-sys-color-surface-container-low);
        display: flex;
        align-items: center;
        padding: 0 1rem;
        font-size: 0.8rem;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        flex-shrink: 0;
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
      }
      .breadcrumbs {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        overflow-x: auto;
        white-space: nowrap;
        scrollbar-width: none;
        width: 100%;
      }
      .breadcrumbs::-webkit-scrollbar { display: none; }
      .bc-item {
        padding: 0.2rem 0.5rem;
        border-radius: 8px;
        color: var(--md-sys-color-on-surface-variant);
        font-weight: 500;
        max-width: 140px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .bc-item:hover { background: var(--md-sys-color-surface-container-high); color: var(--md-sys-color-on-surface); }
      .bc-item.active { color: var(--md-sys-color-primary); font-weight: 700; background: var(--md-sys-color-surface-container); }
      .bc-sep { color: var(--md-sys-color-outline); font-size: 0.75rem; }
  
      .search-box {
        position: relative;
        display: flex;
        align-items: center;
        background: var(--md-sys-color-surface-container-high);
        border-radius: 28px;
        padding: 0 0.9rem;
        gap: 0.55rem;
        width: 100%;
        height: 40px;
        transition: all 0.2s ease;
        border: 1px solid transparent;
      }
      .search-box:focus-within {
        background: var(--md-sys-color-surface-container-highest);
        border-color: var(--md-sys-color-primary);
        box-shadow: 0 0 0 1px var(--md-sys-color-primary);
      }
      .search-box input {
        width: 100%;
        height: 100%;
        outline: none;
        font-size: 0.88rem;
        color: var(--md-sys-color-on-surface);
      }
      .search-box svg { color: var(--md-sys-color-on-surface-variant); width: 19px; height: 19px; }
  
      .btn-icon {
        width: 40px;
        height: 40px;
        border-radius: 20px;
        color: var(--md-sys-color-on-surface-variant);
        transition: all 0.15s ease;
        position: relative;
      }
      .btn-icon:hover { background: var(--md-sys-color-surface-container-high); color: var(--md-sys-color-on-surface); }
      .btn-icon.active { background: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); }
  
      .btn-primary {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        background: var(--md-sys-color-primary);
        color: var(--md-sys-color-on-primary);
        padding: 0.45rem 1rem;
        height: 40px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
        box-shadow: var(--md-elevation-1);
      }
      .btn-primary:hover { opacity: 0.9; }
  
      .app-body {
        display: flex;
        flex: 1;
        overflow: hidden;
        position: relative;
        min-height: 0;
      }
  
      .sidebar-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(2px);
        z-index: 140;
        display: none;
      }
      .sidebar-backdrop.active { display: block; }
  
      .sidebar {
        width: 280px;
        background: var(--md-sys-color-surface-container-low);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        overflow-y: auto;
        transition: margin-left 0.25s cubic-bezier(0.2, 0, 0, 1), transform 0.25s cubic-bezier(0.2, 0, 0, 1);
        z-index: 150;
        border-right: 1px solid var(--md-sys-color-outline-variant);
      }
      .sidebar.collapsed {
        margin-left: -280px;
      }
      .sidebar-section {
        padding: 1rem;
      }
      .sidebar-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--md-sys-color-outline);
        font-weight: 700;
        margin-bottom: 0.6rem;
        padding-left: 0.5rem;
      }
  
      .tree-node-row {
      display: flex;
      align-items: center;
      gap: 0.2rem;
      margin-bottom: 0.15rem;
      position: relative;
    }
    .tree-toggle {
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--md-sys-color-outline);
      border-radius: 6px;
      flex-shrink: 0;
      transition: transform 0.15s ease, color 0.15s ease;
    }
    .tree-toggle:hover {
      color: var(--md-sys-color-on-surface);
      background: var(--md-sys-color-surface-container-high);
    }
    .tree-toggle svg {
      width: 14px;
      height: 14px;
      transition: transform 0.2s ease;
    }
    .tree-branch.collapsed > .tree-node-row .tree-toggle svg {
      transform: rotate(-90deg);
    }
    .tree-branch.collapsed > .tree-children {
      display: none;
    }
    .tree-spacer {
      width: 24px;
      height: 24px;
      flex-shrink: 0;
    }
    .tree-node-row .tree-node {
      flex: 1;
      margin-bottom: 0;
      min-width: 0;
    }

    .tree-node {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 0.6rem;
      padding: 0.45rem 0.75rem;
      border-radius: 20px;
      font-size: 0.85rem;
      color: var(--md-sys-color-on-surface-variant);
      cursor: pointer;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .tree-node svg { margin: 0; flex-shrink: 0; width: 18px; height: 18px; }
    .tree-node:hover { background: var(--md-sys-color-surface-container-high); color: var(--md-sys-color-on-surface); }
    .tree-node.active { background: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); font-weight: 600; }
  
      .filter-group { display: flex; flex-direction: column; gap: 0.2rem; }
      .filter-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.8rem;
        border-radius: 28px;
        font-size: 0.85rem;
        color: var(--md-sys-color-on-surface-variant);
        cursor: pointer;
      }
      .filter-item:hover { background: var(--md-sys-color-surface-container-high); color: var(--md-sys-color-on-surface); }
      .filter-item.active { background: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); font-weight: 600; }
      .filter-badge {
        font-size: 0.75rem;
        background: var(--md-sys-color-surface-container-highest);
        padding: 0.1rem 0.5rem;
        border-radius: 12px;
        color: var(--md-sys-color-on-surface-variant);
      }
  
      .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        position: relative;
        padding: 1rem 1rem 0 1rem;
        -webkit-overflow-scrolling: touch;
        min-height: 0;
      }
  
      .content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
      }
      .dir-info h1 {
        font-size: 1.25rem;
        font-weight: 700;
      }
      .dir-stats {
        font-size: 0.8rem;
        color: var(--md-sys-color-on-surface-variant);
      }
      .toolbar-actions {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        background: var(--md-sys-color-surface-container);
        padding: 0.3rem 0.5rem;
        border-radius: 20px;
      }
  
      .gallery-container {
        width: 100%;
        flex: 1;
      }
  
      .layout-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
        grid-auto-rows: min-content;
        align-content: start;
        gap: 0.75rem;
      }
      .layout-grid[data-cols="1"] { grid-template-columns: repeat(1, minmax(0, 1fr)); }
      .layout-grid[data-cols="2"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .layout-grid[data-cols="3"] { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .layout-grid[data-cols="4"] { grid-template-columns: repeat(4, minmax(0, 1fr)); }
      .layout-grid[data-cols="5"] { grid-template-columns: repeat(5, minmax(0, 1fr)); }
      .layout-grid[data-cols="6"] { grid-template-columns: repeat(6, minmax(0, 1fr)); }
      .layout-grid[data-cols="8"] { grid-template-columns: repeat(8, minmax(0, 1fr)); }
  
      .layout-grid .file-card {
        aspect-ratio: 1 / 1;
        width: 100%;
        height: auto;
      }
      .layout-grid .file-thumb {
        aspect-ratio: 1 / 1;
        width: 100%;
        height: auto;
      }
  
      .layout-grid[data-cols="1"] .type-icon svg, .layout-columns[data-cols="1"] .type-icon svg { width: 72px; height: 72px; }
      .layout-grid[data-cols="2"] .type-icon svg, .layout-columns[data-cols="2"] .type-icon svg { width: 58px; height: 58px; }
      .layout-grid[data-cols="3"] .type-icon svg, .layout-columns[data-cols="3"] .type-icon svg { width: 48px; height: 48px; }
      .layout-grid[data-cols="4"] .type-icon svg, .layout-columns[data-cols="4"] .type-icon svg { width: 40px; height: 40px; }
      .layout-grid[data-cols="5"] .type-icon svg, .layout-columns[data-cols="5"] .type-icon svg { width: 34px; height: 34px; }
      .layout-grid[data-cols="6"] .type-icon svg, .layout-columns[data-cols="6"] .type-icon svg { width: 28px; height: 28px; }
      .layout-grid[data-cols="8"] .type-icon svg, .layout-columns[data-cols="8"] .type-icon svg { width: 22px; height: 22px; }
  
      .file-card {
        background: var(--md-sys-color-surface-container-low);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 14px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        cursor: pointer;
        position: relative;
        content-visibility: auto;
        contain-intrinsic-size: 140px;
        transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
      }
      .file-card:hover {
        background: var(--md-sys-color-surface-container);
        border-color: var(--md-sys-color-outline);
        box-shadow: var(--md-elevation-1);
      }
      .file-card:hover .file-info-overlay {
        background: linear-gradient(to top, rgba(0, 0, 0, 0.92) 0%, rgba(0, 0, 0, 0.6) 65%, transparent 100%);
      }
      .file-card.selected {
        border-color: var(--md-sys-color-primary);
        background: var(--md-sys-color-surface-container-high);
        box-shadow: 0 0 0 2px var(--md-sys-color-primary);
      }
  
      .file-thumb {
        width: 100%;
        min-height: 120px;
        background: var(--md-sys-color-surface-container-lowest);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
      }
      .file-thumb::before {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--md-sys-color-surface-container);
        pointer-events: none;
        z-index: 1;
      }
      .file-thumb img {
        position: relative;
        z-index: 2;
      }
      .file-thumb .type-icon {
        position: relative;
        z-index: 2;
      }
      .file-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }
  
      .file-info-overlay {
        position: absolute;
        inset: auto 0 0 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.45) 60%, transparent 100%);
        padding: 2rem 0.65rem 0.55rem 0.65rem;
        color: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        pointer-events: none;
        z-index: 4;
      }
      .file-info-overlay .file-name {
        font-size: 0.82rem;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
      }
      .file-info-overlay .file-meta {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.85);
        display: flex;
        justify-content: space-between;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
      }
  
      .layout-columns {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
        width: 100%;
      }
      .masonry-col {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        min-width: 0;
      }
      .layout-columns .file-card {
        width: 100%;
        height: auto;
        margin-bottom: 0;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
      }
      .layout-columns .file-thumb {
        width: 100%;
        height: auto;
        min-height: 140px;
        aspect-ratio: 1 / 1;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        flex-shrink: 0;
      }
      .layout-columns .file-thumb img {
        width: 100%;
        height: auto;
        display: block;
        object-fit: cover;
      }
  
      .layout-columns[data-cols="1"] .file-card {
        aspect-ratio: auto;
        height: auto;
      }
      .layout-columns[data-cols="1"] .file-thumb {
        aspect-ratio: auto;
        height: auto;
      }
      .layout-columns[data-cols="1"] .file-thumb img {
        width: 100%;
        height: auto;
        object-fit: contain;
      }
  
      .layout-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }
      .layout-list .file-card {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 0.5rem 0.85rem;
        gap: 0.85rem;
        aspect-ratio: auto;
        height: auto;
        min-height: 56px;
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 14px;
        background: var(--md-sys-color-surface-container-low);
        box-shadow: none;
        transition: background-color 0.15s ease, border-color 0.15s ease;
      }
      .layout-list .file-card:hover {
        background: var(--md-sys-color-surface-container);
        border-color: var(--md-sys-color-outline);
        box-shadow: var(--md-elevation-1);
      }
      .layout-list .file-card:hover .file-info-overlay {
        background: none !important;
      }
      .layout-list .file-card.selected {
        border-color: var(--md-sys-color-primary);
        background: var(--md-sys-color-surface-container-high);
        box-shadow: 0 0 0 2px var(--md-sys-color-primary);
      }
      .layout-list .file-checkbox {
        position: static;
        width: 20px;
        height: 20px;
        border-radius: 6px;
        background: transparent;
        border: 2px solid var(--md-sys-color-outline);
        flex-shrink: 0;
      }
      .layout-list .file-card.selected .file-checkbox {
        background: var(--md-sys-color-primary);
        border-color: var(--md-sys-color-primary);
      }
      .layout-list .file-thumb {
        width: 38px;
        height: 38px;
        min-height: 38px;
        aspect-ratio: 1 / 1;
        border-radius: 8px;
        flex-shrink: 0;
        background: var(--md-sys-color-surface-container);
      }
      .layout-list .file-thumb::before {
        display: none;
      }
      .layout-list .file-thumb .type-icon svg {
        width: 26px;
        height: 26px;
      }
      .layout-list .file-badge {
        display: none;
      }
      .layout-list .file-info-overlay {
        position: static;
        background: none !important;
        padding: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.15rem;
        min-width: 0;
      }
      .layout-list .file-info-overlay .file-name {
        font-size: 0.88rem;
        font-weight: 500;
        color: var(--md-sys-color-on-surface);
        text-shadow: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .layout-list .file-info-overlay .file-meta {
        font-size: 0.75rem;
        color: var(--md-sys-color-on-surface-variant);
        text-shadow: none;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 1.2rem;
      }
  
      .file-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(0, 0, 0, 0.65);
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 0.15rem 0.4rem;
        border-radius: 6px;
        text-transform: uppercase;
        z-index: 5;
      }
  
      .file-checkbox {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 22px;
        height: 22px;
        border-radius: 6px;
        border: 2px solid var(--md-sys-color-outline);
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        transition: all 0.15s;
      }
      .file-card.selected .file-checkbox {
        background: var(--md-sys-color-primary);
        border-color: var(--md-sys-color-primary);
      }
      .file-checkbox svg {
        width: 14px;
        height: 14px;
        fill: var(--md-sys-color-on-primary);
        display: none;
      }
      .file-card.selected .file-checkbox svg { display: block; }
  
      .batch-bar {
        position: fixed;
        bottom: calc(1.5rem + env(safe-area-inset-bottom, 0px));
        left: 50%;
        transform: translateX(-50%) translateY(160%);
        background: var(--md-sys-color-surface-container-highest);
        border: 1px solid var(--md-sys-color-outline-variant);
        padding: 0.5rem 1rem;
        border-radius: 32px;
        box-shadow: var(--md-elevation-2);
        display: flex;
        align-items: center;
        gap: 0.6rem;
        z-index: 500;
        transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1);
      }
      .batch-bar.active { transform: translateX(-50%) translateY(0); }
      .batch-count { font-size: 0.85rem; font-weight: 600; color: var(--md-sys-color-primary); }
  
      .type-icon {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
      }
      .type-icon svg { width: 38px; height: 38px; }
      .type-folder { color: #f59e0b; }
  
      .center-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 45dvh;
        grid-column: 1 / -1;
        color: var(--md-sys-color-on-surface-variant);
        text-align: center;
        gap: 0.75rem;
      }
  
      .m3-spinner {
        width: 40px;
        height: 40px;
        animation: m3-rotate 2s linear infinite;
        display: block;
        margin: auto;
      }
      .m3-spinner circle {
        stroke: var(--md-sys-color-primary);
        stroke-linecap: round;
        animation: m3-dash 1.5s ease-in-out infinite;
      }
      @keyframes m3-rotate {
        100% { transform: rotate(360deg); }
      }
      @keyframes m3-dash {
        0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
        50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
        100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
      }
  
      .manga-container {
        position: fixed;
        inset: 0;
        height: 100dvh;
        min-height: 100dvh;
        background: #050505;
        z-index: 2000;
        display: none;
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
        scroll-behavior: smooth;
      }
      .manga-container.active { display: flex; }
  
      .manga-topbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 52px;
        background: rgba(20, 18, 24, 0.88);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1rem;
        z-index: 2050;
        color: #ffffff;
        transition: transform 0.25s ease;
      }
      .manga-topbar.autohide { transform: translateY(-100%); }
  
      .manga-counter {
        font-weight: 600;
        font-size: 0.9rem;
        background: rgba(255, 255, 255, 0.14);
        padding: 0.25rem 0.75rem;
        border-radius: 16px;
        letter-spacing: 0.5px;
      }
      .manga-controls { display: flex; align-items: center; gap: 0.4rem; }
  
      .manga-pages-wrap {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding: 52px 0 calc(30px + env(safe-area-inset-bottom, 0px)) 0;
        gap: 0px;
        margin: 0 auto;
      }
      .manga-select {
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 12px;
        padding: 0.35rem 1.75rem 0.35rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 500;
        outline: none;
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='%23ffffff'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.55rem center;
        background-size: 16px 16px;
      }
      .manga-select option {
        background-color: #211f26;
        color: #ffffff;
        padding: 0.4rem;
      }
  
      .manga-page {
        width: 100%;
        max-width: var(--manga-width, 1000px);
        min-height: 250px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 0;
        font-size: 0;
        margin: 0 auto;
        padding: 0;
      }
      .manga-page img {
        width: 100%;
        height: auto;
        display: block;
        margin: 0 auto;
        padding: 0;
        border: none;
        outline: none;
        object-fit: contain;
      }
  
      .manga-pages-wrap.mode-fit-height .manga-page,
      .manga-pages-wrap.mode-fit-screen .manga-page {
        max-width: 100%;
        min-height: auto;
        padding: 0.25rem 0;
      }
      .manga-pages-wrap.mode-fit-height .manga-page img {
        width: auto;
        max-width: 100%;
        max-height: calc(100dvh - 64px);
        object-fit: contain;
      }
      .manga-pages-wrap.mode-fit-screen .manga-page img {
        width: auto;
        max-width: 95vw;
        max-height: calc(100dvh - 64px);
        object-fit: contain;
      }
  
      .lightbox {
        position: fixed;
        inset: 0;
        height: 100dvh;
        min-height: 100dvh;
        background: rgba(0, 0, 0, 0.94);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        z-index: 1500;
        display: none;
        flex-direction: column;
        touch-action: pan-y;
      }
      .lightbox.active { display: flex; }
  
      .lightbox-header {
        height: 54px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1rem;
        background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
        color: #fff;
        position: absolute;
        top: 0; left: 0; right: 0;
        z-index: 1550;
      }
      .lightbox-title {
        font-weight: 500;
        font-size: 0.92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 60vw;
      }
  
      .lightbox-body {
        flex: 1;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        user-select: none;
      }
      .lightbox-media {
        max-width: 95%;
        max-height: 86dvh;
        object-fit: contain;
        user-select: none;
        box-shadow: var(--md-elevation-2);
        border-radius: 0 !important;
        opacity: 1;
        transform: translateX(0) scale(1);
        transition: opacity 0.18s cubic-bezier(0.2, 0, 0, 1), transform 0.18s cubic-bezier(0.2, 0, 0, 1);
        will-change: transform, opacity;
      }
      .lb-spinner {
        position: absolute;
        inset: 0;
        margin: auto;
        width: 48px;
        height: 48px;
        display: none;
        z-index: 1510;
        pointer-events: none;
      }
      .lb-spinner.active {
        display: block;
      }
      .lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 48px;
        height: 80px;
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-radius: 12px;
        z-index: 1520;
        transition: background 0.2s ease;
      }
      .lightbox-nav:hover { background: rgba(255, 255, 255, 0.3); }
      .lightbox-nav.prev { left: 1rem; }
      .lightbox-nav.next { right: 1rem; }
  
      .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(4px);
        z-index: 3000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
      }
      .modal-backdrop.active { display: flex; }
  
      .modal-box {
        background: var(--md-sys-color-surface-container);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 28px;
        width: 100%;
        max-width: 480px;
        box-shadow: var(--md-elevation-2);
        overflow: hidden;
        display: flex;
        flex-direction: column;
      }
      .modal-box.large { max-width: 820px; height: 82dvh; }
      .modal-header {
        padding: 1.2rem 1.4rem 0.8rem 1.4rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 700;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
      }
      .modal-content {
        padding: 1rem 1.4rem 1.2rem 1.4rem;
        overflow-y: auto;
        flex: 1;
      }
      .modal-footer {
        padding: 0.8rem 1.4rem 1.2rem 1.4rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        border-top: 1px solid var(--md-sys-color-outline-variant);
      }
  
      .details-section {
        margin-bottom: 1.2rem;
      }
      .details-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--md-sys-color-primary);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 0.6rem;
      }
      .details-grid {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        background: var(--md-sys-color-surface-container-low);
        padding: 0.6rem 0.85rem;
        border-radius: 16px;
        border: 1px solid var(--md-sys-color-outline-variant);
      }
      .details-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
        padding: 0.3rem 0;
        border-bottom: 1px solid var(--md-sys-color-surface-container-highest);
      }
      .details-row:last-child { border-bottom: none; }
      .details-label { color: var(--md-sys-color-on-surface-variant); font-size: 0.8rem; }
      .details-value { font-weight: 500; color: var(--md-sys-color-on-surface); text-align: right; word-break: break-all; }
  
      .form-group { margin-bottom: 1rem; }
      .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--md-sys-color-primary); margin-bottom: 0.4rem; }
      .form-input {
        width: 100%;
        background: var(--md-sys-color-surface-container-high);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 12px;
        padding: 0.65rem 0.85rem;
        color: var(--md-sys-color-on-surface);
        outline: none;
        font-size: 0.9rem;
      }
      .form-input:focus { border-color: var(--md-sys-color-primary); }
      .editor-textarea {
        width: 100%;
        height: 100%;
        min-height: 380px;
        font-family: 'JetBrains Mono', monospace;
        background: var(--md-sys-color-surface-container-high);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 16px;
        padding: 1rem;
        color: var(--md-sys-color-on-surface);
        outline: none;
        resize: none;
        font-size: 0.85rem;
        line-height: 1.5;
      }
  
      .upload-dock {
        position: fixed;
        bottom: calc(1.5rem + env(safe-area-inset-bottom, 0px));
        right: 1.5rem;
        width: 360px;
        max-width: calc(100vw - 2rem);
        background: var(--md-sys-color-surface-container-high);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 20px;
        box-shadow: var(--md-elevation-2);
        z-index: 6000;
        display: none;
        flex-direction: column;
        overflow: hidden;
        transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1);
      }
      .upload-dock.active { display: flex; }
      .upload-dock-header {
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--md-sys-color-surface-container-highest);
        font-size: 0.85rem;
        font-weight: 600;
        gap: 0.5rem;
      }
      .upload-dock-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
      }
      .upload-dock-controls { display: flex; align-items: center; gap: 0.2rem; }
      .upload-dock-controls button { width: 28px; height: 28px; border-radius: 14px; }
      .upload-dock-controls button:hover { background: rgba(255, 255, 255, 0.1); }
      .upload-dock-progress {
        height: 4px;
        width: 100%;
        background: var(--md-sys-color-surface-container-low);
        overflow: hidden;
      }
      .upload-dock-progress-bar {
        height: 100%;
        width: 0%;
        background: var(--md-sys-color-primary);
        transition: width 0.15s linear;
      }
      .upload-dock-body {
        max-height: 230px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
      }
      .upload-dock.minimized .upload-dock-body { display: none; }
      .upload-item-row {
        padding: 0.6rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
        font-size: 0.8rem;
      }
      .upload-item-row:last-child { border-bottom: none; }
      .upload-item-info {
        flex: 1;
        overflow: hidden;
      }
      .upload-item-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 500;
      }
      .upload-item-sub {
        font-size: 0.7rem;
        color: var(--md-sys-color-on-surface-variant);
        margin-top: 0.15rem;
      }
      .upload-item-status {
        display: flex;
        align-items: center;
        font-weight: 600;
        font-size: 0.75rem;
        gap: 0.3rem;
        flex-shrink: 0;
      }
  
      .dropdown-menu {
        position: fixed;
        background: var(--md-sys-color-surface-container-high);
        border-radius: 16px;
        padding: 0.4rem;
        box-shadow: var(--md-elevation-2);
        z-index: 4000;
        display: none;
        flex-direction: column;
        min-width: 210px;
        border: 1px solid var(--md-sys-color-outline-variant);
      }
      .dropdown-menu.active { display: flex; }
      .dm-item {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 0.6rem;
        padding: 0.55rem 0.8rem;
        border-radius: 12px;
        font-size: 0.85rem;
        color: var(--md-sys-color-on-surface);
        cursor: pointer;
      }
      .dm-item svg { margin: 0; flex-shrink: 0; width: 18px; height: 18px; }
      .dm-item:hover { background: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); }
      .dm-sep { height: 1px; background: var(--md-sys-color-outline-variant); margin: 0.3rem 0; }
  
      #dropdown-sort {
        display: none;
        flex-direction: column;
        min-width: 230px;
        border-radius: 16px;
        background: var(--md-sys-color-surface-container-high);
        padding: 0.4rem;
        box-shadow: var(--md-elevation-2);
      }
      #dropdown-sort.active {
        display: flex;
      }
      #dropdown-sort .dm-item {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 0.6rem;
        padding: 0.55rem 0.8rem;
        border-radius: 12px;
        font-size: 0.85rem;
        color: var(--md-sys-color-on-surface);
        cursor: pointer;
        position: relative;
      }
      #dropdown-sort .dm-item:hover {
        background: var(--md-sys-color-secondary-container);
        color: var(--md-sys-color-on-secondary-container);
      }
      #dropdown-sort .dm-item.active {
        background: var(--md-sys-color-secondary-container);
        color: var(--md-sys-color-on-secondary-container);
        font-weight: 600;
      }
      #dropdown-sort .dm-item .sort-check {
        margin-left: auto;
        width: 18px;
        height: 18px;
        color: var(--md-sys-color-primary);
        display: none;
      }
      #dropdown-sort .dm-item.active .sort-check {
        display: block;
      }
  
      .tree-node {
        transition: background-color 0.15s ease, color 0.15s ease;
      }
      .dm-item.danger:hover { background: var(--md-sys-color-error-container); color: var(--md-sys-color-on-error-container); }
      .dm-sep { height: 1px; background: var(--md-sys-color-outline-variant); margin: 0.3rem 0; }
  
      .slider-container {
        padding: 0.6rem 0.8rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
      }
      .slider-header {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        color: var(--md-sys-color-on-surface-variant);
        font-weight: 600;
      }
      .slider-input {
        width: 100%;
        height: 6px;
        background: var(--md-sys-color-surface-container-highest);
        outline: none;
        -webkit-appearance: none;
        border-radius: 3px;
      }
      .slider-input::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 16px;
        height: 16px;
        background: var(--md-sys-color-primary);
        border-radius: 50%;
        cursor: pointer;
      }
  
      .toast-container {
        position: fixed;
        bottom: calc(1.5rem + env(safe-area-inset-bottom, 0px));
        left: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        z-index: 5000;
        pointer-events: none;
      }
      .toast {
        background: var(--md-sys-color-surface-container-highest);
        padding: 0.65rem 1rem;
        border-radius: 12px;
        box-shadow: var(--md-elevation-2);
        font-size: 0.85rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--md-sys-color-on-surface);
        pointer-events: auto;
      }
  
      .dropzone-overlay {
        position: fixed;
        inset: 0;
        background: rgba(103, 80, 164, 0.2);
        border: 3px dashed var(--md-sys-color-primary);
        backdrop-filter: blur(4px);
        z-index: 9000;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--md-sys-color-primary);
        pointer-events: none;
      }
      .dropzone-overlay.active { display: flex; }
  
      .bottom-pad {
        height: calc(5.5rem + env(safe-area-inset-bottom, 0px));
        width: 100%;
        flex-shrink: 0;
      }
  
      .login-container {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100dvh;
        width: 100%;
        padding: 1rem;
      }
      .login-card {
        background: var(--md-sys-color-surface-container);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 28px;
        padding: 2.2rem;
        width: 100%;
        max-width: 380px;
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
        box-shadow: var(--md-elevation-2);
        text-align: center;
      }
  
      .desktop-only { display: flex; }
      .mobile-only { display: none; }
  
      @media (max-width: 768px) {
        .desktop-only { display: none !important; }
        .mobile-only { display: flex !important; }
        .sidebar {
          position: fixed;
          inset: 0 auto 0 0;
          transform: translateX(-100%);
          box-shadow: var(--md-elevation-2);
          width: 280px;
          height: 100dvh;
        }
        .sidebar.open { transform: translateX(0); }
        .layout-columns { column-count: 2; }
        .main-content { padding: 0.6rem 0.6rem 0 0.6rem; }
      }
    </style>
  </head>
  <body>
  
    <?php if ($config['auth_enabled'] && empty($_SESSION['authenticated'])): ?>
    <div class="login-container">
      <form class="login-card" id="login-form">
        <svg viewBox="0 0 16 16" style="width:44px; height:44px; color:var(--md-sys-color-primary); margin:0 auto;"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg>
        <h2 style="font-size:1.35rem; font-weight:700;"><?= htmlspecialchars($config['app_title']) ?></h2>
        <input type="password" class="form-input" id="login-pass" placeholder="Enter Password" required autofocus>
        <button type="submit" class="btn-primary" style="justify-content:center; padding:0.6rem 1rem;">Unlock</button>
      </form>
    </div>
    <script>
      document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData();
        fd.append('action', 'login');
        fd.append('password', document.getElementById('login-pass').value);
        const res = await fetch('', { method: 'POST', body: fd });
        if (res.ok) window.location.reload();
        else alert('Invalid password');
      });
    </script>
    </body></html>
    <?php exit; endif; ?>
  
    <header class="app-topbar">
      <div class="topbar-left">
        <button class="btn-icon" id="btn-sidebar" title="Toggle Menu">
          <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        </button>
        <a href="#/" class="brand desktop-only">
          <svg viewBox="0 0 16 16" style="width:20px;height:20px;"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg>
          <?= htmlspecialchars($config['app_title']) ?>
        </a>
      </div>
  
      <div class="topbar-center">
        <div class="search-box">
          <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
          <input type="text" id="search-input" placeholder="Search files & subfolders...">
        </div>
      </div>
  
      <div class="topbar-right">
        <div class="desktop-only" style="display:flex; align-items:center; gap:0.5rem;">
          <div style="display:flex; align-items:center; gap:0.4rem; padding:0 0.6rem; background:var(--md-sys-color-surface-container-high); border-radius:20px; height:40px;">
            <span style="font-size:0.75rem; font-weight:600; color:var(--md-sys-color-on-surface-variant);">Cols:</span>
            <input type="range" id="slider-cols-desk" class="slider-input" min="0" max="8" value="0" style="width:70px;">
            <span id="slider-cols-desk-val" style="font-size:0.75rem; font-weight:600; min-width:28px;">Auto</span>
          </div>
          <button class="btn-icon" id="btn-clear-cache-desk" title="Clear OPFS Cache">
            <svg viewBox="0 0 24 24"><path d="M19.36 2.72l1.42 1.42-2.12 2.12-1.42-1.42 2.12-2.12zM5.17 6.91L3.76 5.5 5.88 3.38l1.41 1.41-2.12 2.12zm13.43 10.18l1.41 1.41-2.12 2.12-1.41-1.41 2.12-2.12zM3.76 18.5l1.41 1.41 2.12-2.12-1.41-1.41L3.76 18.5zM12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0-6C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
          </button>
          <button class="btn-icon" id="btn-manga-desk" title="Manga Mode">
            <svg viewBox="0 0 24 24"><path d="M19 1L14 6V22L19 17V1M3 6V22L8 17H12V2H8L3 6M10 4.25C10 3.56 9.44 3 8.75 3S7.5 3.56 7.5 4.25 8.06 5.5 8.75 5.5 10 4.94 10 4.25Z"/></svg>
          </button>
          <button class="btn-icon" id="btn-theme-desk" title="Toggle Theme">
            <svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/></svg>
          </button>
          <button class="btn-primary" id="btn-upload-desk">
            <svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg> Upload
          </button>
        </div>
  
        <div class="mobile-only">
          <button class="btn-icon" id="btn-more-menu" title="More Options">
            <svg viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
          </button>
        </div>
        <input type="file" id="file-uploader" multiple style="display:none;">
        <input type="file" id="folder-uploader" webkitdirectory directory multiple style="display:none;">
      </div>
    </header>
  
    <div class="subbar-path">
      <nav class="breadcrumbs" id="breadcrumbs"></nav>
    </div>
  
    <div class="app-body">
      <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
      <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
          <div class="sidebar-title">Categories</div>
          <div class="filter-group">
            <div class="filter-item active" data-filter="all"><span>All Items</span><span class="filter-badge" id="badge-all">0</span></div>
            <div class="filter-item" data-filter="image"><span>Images</span><span class="filter-badge" id="badge-image">0</span></div>
            <div class="filter-item" data-filter="video"><span>Videos</span><span class="filter-badge" id="badge-video">0</span></div>
            <div class="filter-item" data-filter="audio"><span>Audio</span><span class="filter-badge" id="badge-audio">0</span></div>
            <div class="filter-item" data-filter="text"><span>Documents</span><span class="filter-badge" id="badge-text">0</span></div>
            <div class="filter-item" data-filter="archive"><span>Archives</span><span class="filter-badge" id="badge-archive">0</span></div>
          </div>
        </div>
  
        <div class="sidebar-section">
          <div class="sidebar-title">Folder Tree</div>
          <div id="tree-container"></div>
        </div>
      </aside>
  
      <main class="main-content" id="main-content">
        <div class="content-header">
          <div class="dir-info">
            <h1 id="dir-title">PHPFiles</h1>
            <div class="dir-stats" id="dir-stats">Loading...</div>
          </div>
  
          <div class="toolbar-actions">
            <button class="btn-icon active" data-layout="grid" title="Grid Layout">
              <svg viewBox="0 0 24 24"><path d="M3 3h8v8H3zm0 10h8v8H3zM13 3h8v8h-8zm0 10h8v8h-8z"/></svg>
            </button>
            <button class="btn-icon" data-layout="columns" title="Masonry Layout">
              <svg viewBox="0 0 24 24"><path d="M3 3h8v11H3zm10 0h8v6h-8zM3 16h8v5H3zm10-8h8v13h-8z"/></svg>
            </button>
            <button class="btn-icon" data-layout="list" title="List View">
              <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
            </button>
            <div style="width:1px; height:20px; background:var(--md-sys-color-outline-variant); margin:0 0.1rem;"></div>
            <button class="btn-icon" id="btn-sort" title="Sort Items">
              <svg viewBox="0 0 24 24"><path d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/></svg>
            </button>
            <button class="btn-icon" id="btn-folder-info" title="Folder Details">
              <svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>
            </button>
            <button class="btn-icon" id="btn-new-folder" title="New Folder">
              <svg viewBox="0 0 24 24"><path d="M20 6h-8l-2-2H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-1 8h-3v3h-2v-3h-3v-2h3v-3h2v3h3v2z"/></svg>
            </button>
            <button class="btn-icon" id="btn-new-file" title="New Text File">
              <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 14h-3v3h-2v-3H8v-2h3v-3h2v3h3v2zm-3-7V3.5L18.5 9H13z"/></svg>
            </button>
            <button class="btn-icon" id="btn-download-dir" title="Download ZIP">
              <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
            </button>
          </div>
        </div>
  
        <div class="gallery-container layout-grid" id="gallery-container"></div>
        <div id="infinite-scroll-trigger" style="height:20px;"></div>
        <div class="bottom-pad"></div>
      </main>
    </div>
  
    <div class="dropdown-menu" id="dropdown-more">
      <div class="dm-item" id="dm-upload-files"><svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg> Upload Files</div>
      <div class="dm-item" id="dm-upload-folder"><svg viewBox="0 0 24 24"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z"/></svg> Upload Folder</div>
      <div class="dm-item" id="dm-manga"><svg viewBox="0 0 24 24"><path d="M19 1L14 6V22L19 17V1M3 6V22L8 17H12V2H8L3 6M10 4.25C10 3.56 9.44 3 8.75 3S7.5 3.56 7.5 4.25 8.06 5.5 8.75 5.5 10 4.94 10 4.25Z"/></svg> Manga Mode</div>
      <div class="dm-item" id="dm-theme"><svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/></svg> Toggle Theme</div>
      <div class="dm-item" id="dm-clear-cache"><svg viewBox="0 0 24 24"><path d="M19.36 2.72l1.42 1.42-2.12 2.12-1.42-1.42 2.12-2.12zM5.17 6.91L3.76 5.5 5.88 3.38l1.41 1.41-2.12 2.12zm13.43 10.18l1.41 1.41-2.12 2.12-1.41-1.41 2.12-2.12zM3.76 18.5l1.41 1.41 2.12-2.12-1.41-1.41L3.76 18.5zM12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0-6C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg> Clear OPFS Cache</div>
      <?php if ($config['auth_enabled']): ?>
      <div class="dm-item danger" onclick="window.location.href='?action=logout'"><svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg> Logout</div>
      <?php endif; ?>
      <div class="dm-sep"></div>
      <div class="slider-container">
        <div class="slider-header"><span>Grid Columns</span><span id="slider-cols-val">Auto</span></div>
        <input type="range" class="slider-input" id="slider-cols" min="0" max="8" value="0">
      </div>
    </div>
  
    <div class="dropdown-menu" id="dropdown-sort">
      <div class="dm-item" data-sort="name_asc">
        <svg viewBox="0 0 24 24"><path d="M9.25 5v14l-4.5-4.5 1.41-1.41L8 14.92V5h1.25zm11.75 0v2h-8V5h8zm-2 6v2h-6v-2h6zm-2 6v2h-4v-2h4z"/></svg>
        <span>Name (A to Z)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
      <div class="dm-item" data-sort="name_desc">
        <svg viewBox="0 0 24 24"><path d="M9.25 19V5L4.75 9.5l1.41 1.41L8 9.08V19h1.25zm11.75-14v2h-4V5h4zm-2 6v2h-6v-2h6zm-2 6v2h-8v-2h8z"/></svg>
        <span>Name (Z to A)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
      <div class="dm-item" data-sort="date_desc">
        <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
        <span>Date (Newest first)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
      <div class="dm-item" data-sort="date_asc">
        <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
        <span>Date (Oldest first)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
    </div>
  
    <div class="batch-bar" id="batch-bar">
      <span class="batch-count" id="batch-count">0 selected</span>
      <button class="btn-primary" id="btn-batch-info" style="padding:0.35rem 0.75rem; font-size:0.8rem; background:var(--md-sys-color-surface-container-high); color:var(--md-sys-color-on-surface); border:1px solid var(--md-sys-color-outline-variant);">Info</button>
      <button class="btn-primary" id="btn-batch-download" style="padding:0.35rem 0.75rem; font-size:0.8rem;">Download ZIP</button>
      <button class="btn-primary" id="btn-batch-delete" style="background:#dc2626; color:#ffffff; padding:0.35rem 0.75rem; font-size:0.8rem; font-weight:700;">Delete</button>
      <button class="btn-icon" id="btn-batch-clear" title="Clear selection"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
  
    <div class="upload-dock" id="upload-dock">
      <div class="upload-dock-header">
        <div class="upload-dock-title" id="upload-dock-title">Uploading items...</div>
        <div class="upload-dock-controls">
          <button id="btn-dock-toggle" title="Minimize / Expand"><svg viewBox="0 0 24 24" id="dock-toggle-icon"><path d="M19 13H5v-2h14v2z"/></svg></button>
          <button id="btn-dock-close" title="Close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
      </div>
      <div class="upload-dock-progress">
        <div class="upload-dock-progress-bar" id="upload-dock-bar"></div>
      </div>
      <div class="upload-dock-body" id="upload-dock-body"></div>
    </div>
  
    <div class="manga-container" id="manga-viewer">
      <div class="manga-topbar" id="manga-topbar">
        <div class="manga-counter" id="manga-counter">1 / 1</div>
        <div class="manga-controls">
          <select id="manga-width-select" class="manga-select">
            <option value="800px">800px</option>
            <option value="1000px" selected>1000px</option>
            <option value="1200px">1200px</option>
            <option value="100%">Fit Width</option>
            <option value="fit-height">Fit Height</option>
            <option value="fit-screen">Fit Screen</option>
          </select>
          <button class="btn-icon" id="btn-manga-offline" title="Download as Offline HTML">
            <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
          </button>
          <button class="btn-icon" id="btn-manga-fs" title="Fullscreen">
            <svg viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
          </button>
          <button class="btn-icon" id="btn-manga-close" title="Close">
            <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
          </button>
        </div>
      </div>
  
      <div class="manga-pages-wrap" id="manga-pages"></div>
    </div>
  
    <div class="lightbox" id="lightbox">
      <div class="lightbox-header">
        <div class="lightbox-title" id="lb-title">image.jpg</div>
        <div style="display:flex; gap:0.4rem;">
          <button class="btn-icon" id="btn-lb-details" title="Metadata"><svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg></button>
          <button class="btn-icon" id="btn-lb-download" title="Download"><svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg></button>
          <button class="btn-icon" id="btn-lb-close" title="Close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
      </div>
      <div class="lightbox-body" id="lb-body">
        <img class="lightbox-media" id="lb-img" src="" alt="">
        <div class="lightbox-nav prev" id="lb-prev"><svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg></div>
        <div class="lightbox-nav next" id="lb-next"><svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></div>
      </div>
    </div>
  
    <div class="modal-backdrop" id="modal-backdrop">
      <div class="modal-box" id="modal-input" style="display:none;">
        <div class="modal-header">
          <span id="modal-input-title">Create Item</span>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content">
          <div class="form-group">
            <label class="form-label" id="modal-input-label">Name</label>
            <input type="text" class="form-input" id="modal-input-val">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-icon modal-close" style="width:auto; padding:0 0.8rem;">Cancel</button>
          <button class="btn-primary" id="modal-input-confirm">Confirm</button>
        </div>
      </div>
  
      <div class="modal-box large" id="modal-editor" style="display:none;">
        <div class="modal-header">
          <span id="editor-title">Edit File</span>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content" style="padding:0.4rem 1.4rem;">
          <textarea class="editor-textarea" id="editor-content" spellcheck="false"></textarea>
        </div>
        <div class="modal-footer">
          <button class="btn-icon modal-close" style="width:auto; padding:0 0.8rem;">Close</button>
          <button class="btn-primary" id="editor-save-btn">Save Changes</button>
        </div>
      </div>
  
      <div class="modal-box" id="modal-details" style="display:none;">
        <div class="modal-header">
          <span id="details-modal-title">Item Information</span>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content" id="details-content"></div>
        <div class="modal-footer">
          <button class="btn-primary modal-close" style="padding:0 1.2rem;">Close</button>
        </div>
      </div>
    </div>
  
    <div class="dropdown-menu" id="context-menu"></div>
    <div class="toast-container" id="toast-container"></div>
    <div class="dropzone-overlay" id="dropzone"><svg viewBox="0 0 24 24" style="width:36px; height:36px; margin-right:8px;"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg> Drop files or folders here</div>
  
    <script>
      class OPFSCacheManager {
        constructor() {
          this.supported = 'storage' in navigator && 'getDirectory' in navigator.storage;
          this.rootPromise = this.supported ? navigator.storage.getDirectory() : null;
          this.objectUrls = new Map();
        }
  
        async getSubdir() {
          if (!this.supported) return null;
          const root = await this.rootPromise;
          return await root.getDirectoryHandle('phpfiles_opfs_cache', { create: true });
        }
  
        async hash(key) {
          const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(key));
          return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('');
        }
  
        async getJSON(key) {
          if (!this.supported) return null;
          try {
            const dir = await this.getSubdir();
            const filename = (await this.hash(key)) + '.json';
            const fileHandle = await dir.getFileHandle(filename);
            const file = await fileHandle.getFile();
            return JSON.parse(await file.text());
          } catch (e) {
            return null;
          }
        }
  
        async setJSON(key, data) {
          if (!this.supported) return;
          try {
            const dir = await this.getSubdir();
            const filename = (await this.hash(key)) + '.json';
            const fileHandle = await dir.getFileHandle(filename, { create: true });
            const writable = await fileHandle.createWritable();
            await writable.write(JSON.stringify(data));
            await writable.close();
          } catch (e) {}
        }
  
        async clear() {
          this.objectUrls.forEach(url => URL.revokeObjectURL(url));
          this.objectUrls.clear();
          if (!this.supported) return;
          try {
            const root = await this.rootPromise;
            await root.removeEntry('phpfiles_opfs_cache', { recursive: true });
          } catch (e) {}
        }
      }
  
      function formatBytes(bytes, precision = 2) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(precision)) + ' ' + sizes[i];
      }
  
      function ltrim(str, chr) {
        const rgx = new RegExp('^[' + chr + ']+');
        return str.replace(rgx, '');
      }
  
      class UploadManager {
        constructor() {
          this.queue = [];
          this.isProcessing = false;
          this.chunkSize = 2 * 1024 * 1024;
          this.dock = document.getElementById('upload-dock');
          this.title = document.getElementById('upload-dock-title');
          this.bar = document.getElementById('upload-dock-bar');
          this.body = document.getElementById('upload-dock-body');
          this.toggleBtn = document.getElementById('btn-dock-toggle');
          this.closeBtn = document.getElementById('btn-dock-close');
          this.toggleIcon = document.getElementById('dock-toggle-icon');
          this.bindEvents();
        }
  
        bindEvents() {
          this.toggleBtn.addEventListener('click', () => {
            this.dock.classList.toggle('minimized');
            const isMin = this.dock.classList.contains('minimized');
            this.toggleIcon.innerHTML = isMin ? '<path d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14z"/>' : '<path d="M19 13H5v-2h14v2z"/>';
          });
  
          this.closeBtn.addEventListener('click', () => {
            if (this.isProcessing && !confirm('Uploads are in progress. Cancel remaining uploads?')) return;
            this.queue.forEach(item => {
              if (item.status === 'uploading') item.aborted = true;
            });
            this.queue = [];
            this.isProcessing = false;
            this.dock.classList.remove('active');
          });
        }
  
        enqueue(items, targetDir) {
          if (!items || !items.length) return;
          const newTasks = items.map((item, idx) => ({
            id: 'up_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9) + '_' + idx,
            file: item.file,
            fileName: item.file.name,
            relativePath: item.relativePath || item.file.name,
            targetDir: targetDir,
            size: item.file.size,
            uploadedBytes: 0,
            status: 'pending',
            aborted: false
          }));
  
          this.queue.push(...newTasks);
          this.dock.classList.add('active');
          this.renderDock();
          if (!this.isProcessing) this.processQueue();
        }
  
        renderDock() {
          const total = this.queue.length;
          const completed = this.queue.filter(i => i.status === 'completed').length;
          const totalBytes = this.queue.reduce((acc, i) => acc + i.size, 0) || 1;
          const loadedBytes = this.queue.reduce((acc, i) => acc + i.uploadedBytes, 0);
          const percent = Math.min(100, Math.round((loadedBytes / totalBytes) * 100));
  
          this.title.innerText = completed === total ? `${total} upload(s) complete` : `Uploading ${completed} of ${total} item(s) (${percent}%)`;
          this.bar.style.width = `${percent}%`;
  
          this.body.innerHTML = this.queue.map(item => {
            let statusText = 'Pending';
            let statusColor = 'var(--md-sys-color-on-surface-variant)';
  
            if (item.status === 'uploading') {
              const itemPercent = item.size ? Math.round((item.uploadedBytes / item.size) * 100) : 100;
              statusText = `${itemPercent}%`;
              statusColor = 'var(--md-sys-color-primary)';
            } else if (item.status === 'completed') {
              statusText = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;color:#4caf50;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
            } else if (item.status === 'error') {
              statusText = '<span style="color:var(--md-sys-color-error);">Failed</span>';
            }
  
            return `
              <div class="upload-item-row" id="row_${item.id}">
                <div class="upload-item-info">
                  <div class="upload-item-name" title="${item.relativePath}">${item.fileName}</div>
                  <div class="upload-item-sub">${item.relativePath !== item.fileName ? item.relativePath + ' • ' : ''}${formatBytes(item.size)}</div>
                </div>
                <div class="upload-item-status" style="color:${statusColor}">${statusText}</div>
              </div>
            `;
          }).join('');
        }
  
        updateItemProgress(item, uploadedChunkBytes) {
          item.uploadedBytes = Math.min(item.size, uploadedChunkBytes);
          const totalBytes = this.queue.reduce((acc, i) => acc + i.size, 0) || 1;
          const loadedBytes = this.queue.reduce((acc, i) => acc + i.uploadedBytes, 0);
          const percent = Math.min(100, Math.round((loadedBytes / totalBytes) * 100));
  
          const completed = this.queue.filter(i => i.status === 'completed').length;
          this.title.innerText = `Uploading ${completed} of ${this.queue.length} item(s) (${percent}%)`;
          this.bar.style.width = `${percent}%`;
  
          const row = document.getElementById(`row_${item.id}`);
          if (row) {
            const st = row.querySelector('.upload-item-status');
            if (st) {
              const itemPercent = item.size ? Math.round((item.uploadedBytes / item.size) * 100) : 100;
              st.innerText = `${itemPercent}%`;
              st.style.color = 'var(--md-sys-color-primary)';
            }
          }
        }
  
        async processQueue() {
          this.isProcessing = true;
  
          while (true) {
            const nextItem = this.queue.find(i => i.status === 'pending');
            if (!nextItem) break;
            await this.uploadFileChunked(nextItem);
          }
  
          this.isProcessing = false;
          const allDone = this.queue.every(i => i.status === 'completed');
          if (allDone) {
            this.title.innerText = `${this.queue.length} upload(s) complete`;
            this.bar.style.width = '100%';
            if (window.app) app.refresh();
          }
        }
  
        async uploadFileChunked(item) {
          item.status = 'uploading';
          const file = item.file;
          const totalChunks = Math.max(1, Math.ceil(file.size / this.chunkSize));
  
          for (let chunkIdx = 0; chunkIdx < totalChunks; chunkIdx++) {
            if (item.aborted) {
              item.status = 'error';
              this.renderDock();
              return;
            }
  
            const start = chunkIdx * this.chunkSize;
            const end = Math.min(file.size, start + this.chunkSize);
            const chunkBlob = file.slice(start, end);
  
            const fd = new FormData();
            fd.append('action', 'upload_chunk');
            fd.append('upload_id', item.id);
            fd.append('chunk_index', chunkIdx);
            fd.append('total_chunks', totalChunks);
            fd.append('file_name', item.fileName);
            fd.append('relative_path', item.relativePath);
            fd.append('dir', item.targetDir);
            fd.append('chunk', chunkBlob, item.fileName);
  
            try {
              const res = await fetch('', { method: 'POST', body: fd });
              if (!res.ok) throw new Error('Chunk upload failed');
              const data = await res.json();
              if (!data.success) throw new Error(data.error || 'Upload error');
              this.updateItemProgress(item, end);
            } catch (e) {
              item.status = 'error';
              this.renderDock();
              return;
            }
          }
  
          item.status = 'completed';
          item.uploadedBytes = item.size;
          this.renderDock();
        }
      }
  
      class MangaViewer {
        constructor() {
          this.el = document.getElementById('manga-viewer');
          this.topbar = document.getElementById('manga-topbar');
          this.pagesWrap = document.getElementById('manga-pages');
          this.counter = document.getElementById('manga-counter');
          this.widthSelect = document.getElementById('manga-width-select');
          this.images = [];
          this.currentDirPath = '';
          this.observer = null;
          this.bindEvents();
        }
  
        bindEvents() {
          document.getElementById('btn-manga-close').addEventListener('click', () => this.close());
          document.getElementById('btn-manga-fs').addEventListener('click', () => {
            if (!document.fullscreenElement) {
              document.documentElement.requestFullscreen().catch(() => {});
            } else {
              document.exitFullscreen().catch(() => {});
            }
          });
  
          document.getElementById('btn-manga-offline').addEventListener('click', () => {
            const dir = this.currentDirPath !== undefined ? this.currentDirPath : (window.app ? window.app.currentPath : '');
            window.location.href = `?action=manga_offline&dir=${encodeURIComponent(dir)}`;
          });
  
          this.widthSelect.addEventListener('change', (e) => {
            const val = e.target.value;
            this.pagesWrap.classList.remove('mode-fit-height', 'mode-fit-screen');
            if (val === 'fit-height') {
              this.pagesWrap.classList.add('mode-fit-height');
            } else if (val === 'fit-screen') {
              this.pagesWrap.classList.add('mode-fit-screen');
            } else {
              document.documentElement.style.setProperty('--manga-width', val);
            }
          });
  
          let lastScroll = 0;
          this.el.addEventListener('scroll', () => {
            const current = this.el.scrollTop;
            if (current > lastScroll && current > 80) {
              this.topbar.classList.add('autohide');
            } else {
              this.topbar.classList.remove('autohide');
            }
            lastScroll = current;
          });
  
          window.addEventListener('keydown', (e) => {
            if (this.el.classList.contains('active') && e.key === 'Escape') this.close();
          });
        }
  
        async open() {
          const appInstance = window.app;
          const currentPath = appInstance ? (appInstance.currentPath || '') : '';
          this.currentDirPath = currentPath;
  
          let files = (appInstance && appInstance.data && Array.isArray(appInstance.data.files)) ? appInstance.data.files : [];
          if (!files.length) {
            try {
              const res = await fetch(`?action=list&dir=${encodeURIComponent(currentPath)}`);
              const data = await res.json();
              files = data.files || [];
            } catch (e) {}
          }
  
          this.images = files.filter(f => f.type === 'image');
          this.images.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' }));
  
          if (!this.images.length) {
            if (appInstance) appInstance.toast('No images in this folder');
            return;
          }
          this.render();
        }
  
        async openPath(path) {
          this.currentDirPath = path || '';
          try {
            const res = await fetch(`?action=list&dir=${encodeURIComponent(path || '')}`);
            const data = await res.json();
            this.images = (data.files || []).filter(f => f.type === 'image');
            this.images.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' }));
            if (!this.images.length) {
              if (window.app) window.app.toast('No images in folder');
              return;
            }
            this.render();
          } catch (e) {
            if (window.app) window.app.toast('Failed to load folder images');
          }
        }
  
        render() {
          if (this.observer) this.observer.disconnect();
          this.pagesWrap.innerHTML = '';
          this.el.classList.add('active');
          this.counter.innerText = `1 / ${this.images.length}`;
  
          this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                const idx = parseInt(entry.target.dataset.index);
                this.counter.innerText = `${idx + 1} / ${this.images.length}`;
              }
            });
          }, { root: this.el, threshold: 0.1 });
  
          let html = '';
          this.images.forEach((img, idx) => {
            const rawUrl = `?action=raw&f=${encodeURIComponent(img.path)}`;
            html += `
              <div class="manga-page" data-index="${idx}" id="mpage-${idx}">
                <img src="${rawUrl}" alt="${img.name}" loading="lazy" decoding="async">
              </div>
            `;
          });
          this.pagesWrap.innerHTML = html;
          this.pagesWrap.querySelectorAll('.manga-page').forEach(page => this.observer.observe(page));
          this.el.scrollTop = 0;
        }
  
        close() {
          if (this.observer) this.observer.disconnect();
          this.el.classList.remove('active');
          this.pagesWrap.innerHTML = '';
          if (document.fullscreenElement) document.exitFullscreen().catch(() => {});
        }
      }
  
      class LightboxViewer {
        constructor() {
          this.el = document.getElementById('lightbox');
          this.img = document.getElementById('lb-img');
          this.title = document.getElementById('lb-title');
          this.body = document.getElementById('lb-body');
          this.currentIndex = 0;
          this.images = [];
          this.touchStartX = 0;
          this.touchStartY = 0;
          this.bindEvents();
        }
  
        bindEvents() {
          document.getElementById('btn-lb-close').addEventListener('click', () => this.close());
          document.getElementById('lb-prev').addEventListener('click', (e) => { e.stopPropagation(); this.nav(-1); });
          document.getElementById('lb-next').addEventListener('click', (e) => { e.stopPropagation(); this.nav(1); });
  
          document.getElementById('btn-lb-download').addEventListener('click', () => {
            const item = this.images[this.currentIndex];
            if (item) window.location.href = `?action=download&f=${encodeURIComponent(item.path)}`;
          });
  
          document.getElementById('btn-lb-details').addEventListener('click', () => {
            const item = this.images[this.currentIndex];
            if (item && window.app) app.showDetails(item.path);
          });
  
          window.addEventListener('keydown', (e) => {
            if (!this.el.classList.contains('active')) return;
            if (e.key === 'Escape') this.close();
            if (e.key === 'ArrowLeft') this.nav(-1);
            if (e.key === 'ArrowRight') this.nav(1);
          });
  
          this.body.addEventListener('touchstart', (e) => {
            this.touchStartX = e.changedTouches[0].screenX;
            this.touchStartY = e.changedTouches[0].screenY;
          }, { passive: true });
  
          this.body.addEventListener('touchend', (e) => {
            const touchEndX = e.changedTouches[0].screenX;
            const touchEndY = e.changedTouches[0].screenY;
            const diffX = touchEndX - this.touchStartX;
            const diffY = touchEndY - this.touchStartY;
  
            if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY) * 1.4) {
              if (diffX > 0) {
                this.nav(-1);
              } else {
                this.nav(1);
              }
            }
          }, { passive: true });
        }
  
        open(imagesList, startIndex) {
          this.images = imagesList || [];
          this.currentIndex = startIndex || 0;
          this.el.classList.add('active');
          this.initDOM();
          this.loadCurrent(0);
        }
  
        initDOM() {
          this.body.innerHTML = `
            <svg class="m3-spinner lb-spinner" id="lb-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
            <img class="lightbox-media" id="lb-img" src="" alt="" style="opacity:0;">
            <div class="lightbox-nav prev" id="lb-prev"><svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg></div>
            <div class="lightbox-nav next" id="lb-next"><svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></div>
          `;
          document.getElementById('lb-prev').onclick = (e) => { e.stopPropagation(); this.nav(-1); };
          document.getElementById('lb-next').onclick = (e) => { e.stopPropagation(); this.nav(1); };
        }
  
        openMedia(file) {
          this.images = [file];
          this.currentIndex = 0;
          this.el.classList.add('active');
          this.title.innerText = file.name;
          if (window.app) app.updateDocTitle(file.name);
          const rawUrl = `?action=raw&f=${encodeURIComponent(file.path)}`;
  
          if (file.type === 'video') {
            this.body.innerHTML = `<video controls autoplay class="lightbox-media" src="${rawUrl}"></video>`;
          } else if (file.type === 'audio') {
            this.body.innerHTML = `<div style="background:var(--md-sys-color-surface-container); padding:1.8rem; border-radius:24px; text-align:center; border:1px solid var(--md-sys-color-outline-variant);"><h3 style="margin-bottom:1rem; font-size:1rem;">${file.name}</h3><audio controls autoplay src="${rawUrl}"></audio></div>`;
          }
        }
  
        loadCurrent(dir = 0) {
          const item = this.images[this.currentIndex];
          if (!item) return;
  
          const targetHash = '#/' + ltrim(item.path, '/');
          if (window.location.hash !== targetHash) {
            window.location.hash = targetHash;
          }
  
          this.title.innerText = `${item.name} (${this.currentIndex + 1}/${this.images.length})`;
          if (window.app) app.updateDocTitle(item.name);
  
          let img = document.getElementById('lb-img');
          let spinner = document.getElementById('lb-spinner');
          if (!img || !spinner) {
            this.initDOM();
            img = document.getElementById('lb-img');
            spinner = document.getElementById('lb-spinner');
          }
  
          const nextSrc = `?action=raw&f=${encodeURIComponent(item.path)}`;
  
          spinner.classList.add('active');
          img.style.opacity = '0';
          if (dir !== 0) {
            img.style.transform = dir > 0 ? 'translateX(-28px) scale(0.98)' : 'translateX(28px) scale(0.98)';
          }
  
          const preloader = new Image();
          preloader.onload = () => {
            if (!this.images[this.currentIndex] || this.images[this.currentIndex].path !== item.path) return;
            spinner.classList.remove('active');
            img.src = nextSrc;
            img.alt = item.name;
  
            if (dir !== 0) {
              img.style.transform = dir > 0 ? 'translateX(28px) scale(0.98)' : 'translateX(-28px) scale(0.98)';
              void img.offsetWidth;
            }
  
            requestAnimationFrame(() => {
              img.style.opacity = '1';
              img.style.transform = 'translateX(0) scale(1)';
            });
          };
          preloader.onerror = () => {
            spinner.classList.remove('active');
            img.src = nextSrc;
            img.style.opacity = '1';
            img.style.transform = 'translateX(0) scale(1)';
          };
          preloader.src = nextSrc;
        }
  
        nav(dir) {
          if (!this.images || this.images.length <= 1) return;
          this.currentIndex = (this.currentIndex + dir + this.images.length) % this.images.length;
          this.loadCurrent(dir);
        }
  
        close(updateHash = true) {
          this.el.classList.remove('active');
          this.body.innerHTML = '';
  
          if (updateHash) {
            let parentDir = '';
            const currentItem = this.images[this.currentIndex];
            if (currentItem && currentItem.path && currentItem.path.includes('/')) {
              const parts = currentItem.path.split('/');
              parts.pop();
              parentDir = parts.join('/');
            } else if (window.app && app.currentPath) {
              parentDir = app.currentPath;
            }
  
            const targetHash = parentDir ? '#/' + ltrim(parentDir, '/') : '#/';
            if (window.location.hash !== targetHash) {
              window.location.hash = targetHash;
            }
            if (window.app) {
              app.currentPath = parentDir;
              app.updateDocTitle(parentDir ? parentDir.split('/').pop() : '');
            }
          }
        }
      }
  
      class GalleryApp {
        constructor() {
          this.appTitle = '<?= addslashes(htmlspecialchars($config['app_title'])) ?>';
          this.currentPath = null;
          this.data = { folders: [], files: [], stats: {} };
          this.filter = 'all';
          this.sortBy = localStorage.getItem('pg_sort') || 'name_asc';
          this.searchQuery = '';
          this.selectedItems = new Set();
          this.layout = localStorage.getItem('pg_layout') || 'grid';
          this.theme = localStorage.getItem('pg_theme') || 'dark';
          this.gridCols = parseInt(localStorage.getItem('pg_grid_cols')) || 0;
          this.renderLimit = 25;
          this.filteredList = [];
          this.searchDebounceTimer = null;
          this.isSearching = false;
          this.expandedTreeNodes = new Set(JSON.parse(localStorage.getItem('pg_tree_expanded') || '[]'));
  
          this.initDOM();
          this.bindEvents();
          this.applyTheme(this.theme);
          this.setLayout(this.layout);
          this.applyGridSizing();
          this.updateSortUI();
          this.loadTree();
  
          this.handleHashChange();
        }
  
        initDOM() {
          this.container = document.getElementById('gallery-container');
          this.breadcrumbs = document.getElementById('breadcrumbs');
          this.dirTitle = document.getElementById('dir-title');
          this.dirStats = document.getElementById('dir-stats');
          this.sidebar = document.getElementById('sidebar');
          this.sidebarBackdrop = document.getElementById('sidebar-backdrop');
          this.contextMenu = document.getElementById('context-menu');
          this.batchBar = document.getElementById('batch-bar');
          this.batchCount = document.getElementById('batch-count');
          this.dropdownMore = document.getElementById('dropdown-more');
          this.dropdownSort = document.getElementById('dropdown-sort');
          this.btnSort = document.getElementById('btn-sort');
          this.sliderCols = document.getElementById('slider-cols');
          this.sliderColsVal = document.getElementById('slider-cols-val');
          this.sliderColsDesk = document.getElementById('slider-cols-desk');
          this.sliderColsDeskVal = document.getElementById('slider-cols-desk-val');
          this.scrollTrigger = document.getElementById('infinite-scroll-trigger');
        }
  
        bindEvents() {
          document.getElementById('btn-sidebar').addEventListener('click', () => {
            if (window.innerWidth <= 768) {
              this.sidebar.classList.toggle('open');
              this.sidebarBackdrop.classList.toggle('active');
            } else {
              this.sidebar.classList.toggle('collapsed');
              localStorage.setItem('pg_sidebar_collapsed', this.sidebar.classList.contains('collapsed') ? '1' : '0');
            }
          });
  
          if (window.innerWidth > 768 && localStorage.getItem('pg_sidebar_collapsed') === '1') {
            this.sidebar.classList.add('collapsed');
          }
  
          this.sidebarBackdrop.addEventListener('click', () => {
            this.sidebar.classList.remove('open');
            this.sidebarBackdrop.classList.remove('active');
          });
  
          const toggleTheme = () => {
            const themes = ['dark', 'light'];
            this.theme = themes[(themes.indexOf(this.theme) + 1) % themes.length];
            this.applyTheme(this.theme);
          };
          document.getElementById('btn-theme-desk').addEventListener('click', toggleTheme);
          document.getElementById('dm-theme').addEventListener('click', toggleTheme);
  
          const handleClearCache = async () => {
            if (window.opfsCache) {
              await window.opfsCache.clear();
              this.toast('OPFS Cache cleared');
              this.refresh();
            }
          };
  
          const clearCacheBtn = document.getElementById('dm-clear-cache');
          if (clearCacheBtn) clearCacheBtn.addEventListener('click', handleClearCache);
  
          const clearCacheDeskBtn = document.getElementById('btn-clear-cache-desk');
          if (clearCacheDeskBtn) clearCacheDeskBtn.addEventListener('click', handleClearCache);
  
          document.querySelectorAll('[data-layout]').forEach(btn => {
            btn.addEventListener('click', (e) => this.setLayout(e.currentTarget.dataset.layout));
          });
  
          document.querySelectorAll('.filter-item').forEach(pill => {
            pill.addEventListener('click', (e) => {
              document.querySelectorAll('.filter-item').forEach(p => p.classList.remove('active'));
              e.currentTarget.classList.add('active');
              this.filter = e.currentTarget.dataset.filter;
              this.renderLimit = 25;
              this.renderGallery();
            });
          });
  
          document.getElementById('search-input').addEventListener('input', (e) => {
            const q = e.target.value.trim();
            clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => {
              this.searchQuery = q;
              if (q.length > 0) {
                this.performSearch(q);
              } else {
                this.isSearching = false;
                this.renderLimit = 25;
                this.renderGallery();
              }
            }, 280);
          });
  
          const openManga = () => mangaViewer.open();
          document.getElementById('btn-manga-desk').addEventListener('click', openManga);
          document.getElementById('dm-manga').addEventListener('click', openManga);
  
          const fileInput = document.getElementById('file-uploader');
          const folderInput = document.getElementById('folder-uploader');
  
          document.getElementById('btn-upload-desk').addEventListener('click', () => fileInput.click());
          document.getElementById('dm-upload-files').addEventListener('click', () => fileInput.click());
          document.getElementById('dm-upload-folder').addEventListener('click', () => folderInput.click());
  
          fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
              const list = Array.from(e.target.files).map(f => ({ file: f, relativePath: f.name }));
              uploadManager.enqueue(list, this.currentPath);
              e.target.value = '';
            }
          });
  
          folderInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
              const list = Array.from(e.target.files).map(f => ({
                file: f,
                relativePath: f.webkitRelativePath || f.name
              }));
              uploadManager.enqueue(list, this.currentPath);
              e.target.value = '';
            }
          });
  
          const toggleMoreMenu = (e) => {
            e.stopPropagation();
            const rect = e.currentTarget.getBoundingClientRect();
            this.dropdownMore.style.top = `${rect.bottom + 8}px`;
            this.dropdownMore.style.right = `${window.innerWidth - rect.right}px`;
            this.dropdownMore.classList.toggle('active');
          };
          document.getElementById('btn-more-menu').addEventListener('click', toggleMoreMenu);
  
          const handleColChange = (val) => {
            this.gridCols = parseInt(val);
            localStorage.setItem('pg_grid_cols', this.gridCols);
            if (this.sliderCols) this.sliderCols.value = this.gridCols;
            if (this.sliderColsDesk) this.sliderColsDesk.value = this.gridCols;
            this.applyGridSizing();
          };
  
          if (this.sliderCols) {
            this.sliderCols.value = this.gridCols;
            this.sliderCols.addEventListener('input', (e) => handleColChange(e.target.value));
          }
  
          if (this.sliderColsDesk) {
            this.sliderColsDesk.value = this.gridCols;
            this.sliderColsDesk.addEventListener('input', (e) => handleColChange(e.target.value));
          }
  
          window.addEventListener('dragover', (e) => {
            e.preventDefault();
            document.getElementById('dropzone').classList.add('active');
          });
          window.addEventListener('dragleave', (e) => {
            if (e.clientX <= 0 || e.clientY <= 0) {
              document.getElementById('dropzone').classList.remove('active');
            }
          });
          window.addEventListener('drop', async (e) => {
            e.preventDefault();
            document.getElementById('dropzone').classList.remove('active');
            if (e.dataTransfer.items && e.dataTransfer.items.length) {
              const items = await this.readDropData(e.dataTransfer.items);
              if (items.length) uploadManager.enqueue(items, this.currentPath);
            } else if (e.dataTransfer.files.length) {
              const list = Array.from(e.dataTransfer.files).map(f => ({ file: f, relativePath: f.name }));
              uploadManager.enqueue(list, this.currentPath);
            }
          });
  
          document.getElementById('btn-new-folder').addEventListener('click', () => {
            this.showInputModal('Create Folder', 'Folder Name', '', (name) => {
              this.api('create', { dir: this.currentPath, name, type: 'folder' }, () => this.refresh());
            });
          });
  
          document.getElementById('btn-new-file').addEventListener('click', () => {
            this.showInputModal('Create Text File', 'File Name (e.g. notes.txt)', '', (name) => {
              this.api('create', { dir: this.currentPath, name, type: 'file' }, () => this.refresh());
            });
          });
  
          document.getElementById('btn-folder-info').addEventListener('click', () => {
            this.showDetails(this.currentPath);
          });
  
          document.getElementById('btn-download-dir').addEventListener('click', () => {
            window.location.href = `?action=download_zip&dir=${encodeURIComponent(this.currentPath)}`;
          });
  
          document.getElementById('btn-batch-clear').addEventListener('click', () => this.clearSelection());
          document.getElementById('btn-batch-download').addEventListener('click', () => this.batchDownload());
          document.getElementById('btn-batch-delete').addEventListener('click', () => this.batchDelete());
          document.getElementById('btn-batch-info').addEventListener('click', () => this.showBatchDetails());
  
          window.addEventListener('hashchange', () => this.handleHashChange());
  
          if (this.btnSort) {
            this.btnSort.addEventListener('click', (e) => {
              e.stopPropagation();
              const rect = e.currentTarget.getBoundingClientRect();
              this.dropdownSort.style.top = `${rect.bottom + 8}px`;
              this.dropdownSort.style.left = `${Math.min(rect.left, window.innerWidth - 220)}px`;
              this.dropdownSort.classList.toggle('active');
              this.updateSortUI();
            });
          }
  
          document.querySelectorAll('#dropdown-sort .dm-item').forEach(item => {
            item.addEventListener('click', (e) => {
              const sortMode = e.currentTarget.dataset.sort;
              if (sortMode) {
                this.sortBy = sortMode;
                localStorage.setItem('pg_sort', sortMode);
                this.dropdownSort.classList.remove('active');
                this.renderLimit = 25;
                this.renderGallery();
              }
            });
          });
  
          window.addEventListener('click', () => {
            this.contextMenu.classList.remove('active');
            this.dropdownMore.classList.remove('active');
            if (this.dropdownSort) this.dropdownSort.classList.remove('active');
          });
          this.dropdownMore.addEventListener('click', (e) => e.stopPropagation());
          if (this.dropdownSort) this.dropdownSort.addEventListener('click', (e) => e.stopPropagation());
  
          const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
              if (entry.isIntersecting && this.renderLimit < this.filteredList.length) {
                this.renderLimit += 25;
                this.appendBatch();
              }
            });
          }, { root: document.getElementById('main-content'), rootMargin: '300px' });
          scrollObserver.observe(this.scrollTrigger);
        }
  
        async readDropData(items) {
          const fileList = [];
          const queue = [];
          for (let i = 0; i < items.length; i++) {
            const entry = items[i].webkitGetAsEntry ? items[i].webkitGetAsEntry() : null;
            if (entry) queue.push({ entry, path: '' });
          }
  
          while (queue.length > 0) {
            const { entry, path } = queue.shift();
            if (entry.isFile) {
              const f = await new Promise(r => entry.file(r));
              fileList.push({ file: f, relativePath: path ? `${path}/${f.name}` : f.name });
            } else if (entry.isDirectory) {
              const reader = entry.createReader();
              const readEntries = async () => new Promise(r => reader.readEntries(r));
              let entries = await readEntries();
              while (entries.length > 0) {
                for (const child of entries) {
                  queue.push({ entry: child, path: path ? `${path}/${entry.name}` : entry.name });
                }
                entries = await readEntries();
              }
            }
          }
          return fileList;
        }
  
        applyTheme(theme) {
          document.documentElement.setAttribute('data-theme', theme);
          localStorage.setItem('pg_theme', theme);
        }
  
        getMasonryColCount() {
          if (this.gridCols > 0) return this.gridCols;
          const w = this.container ? this.container.clientWidth : window.innerWidth;
          if (w < 520) return 2;
          if (w < 840) return 3;
          if (w < 1200) return 4;
          return 5;
        }
  
        setLayout(layout) {
          this.layout = layout;
          localStorage.setItem('pg_layout', layout);
          document.querySelectorAll('[data-layout]').forEach(b => b.classList.remove('active'));
          document.querySelector(`[data-layout="${layout}"]`)?.classList.add('active');
          this.container.className = `gallery-container layout-${layout}`;
          this.applyGridSizing();
          this.renderLimit = 25;
          this.renderGallery();
        }
  
        applyGridSizing() {
          const text = this.gridCols > 0 ? `${this.gridCols}` : 'Auto';
          if (this.gridCols > 0) {
            this.container.setAttribute('data-cols', this.gridCols);
          } else {
            this.container.removeAttribute('data-cols');
          }
          if (this.sliderColsVal) this.sliderColsVal.innerText = text;
          if (this.sliderColsDeskVal) this.sliderColsDeskVal.innerText = text;
          if (this.layout === 'columns') {
            this.renderLimit = 25;
            this.renderGallery();
          }
        }
  
        updateDocTitle(sub, count = null) {
          const countPrefix = count !== null && count !== undefined ? `(${count}) ` : '';
          const namePart = sub || '';
          if (namePart) {
            document.title = `${countPrefix}${namePart} · ${this.appTitle}`;
          } else {
            document.title = `${countPrefix}${this.appTitle}`;
          }
        }
  
        navigate(path) {
          window.location.hash = '#/' + ltrim(path, '/');
        }
  
        handleHashChange() {
          const raw = window.location.hash.replace(/^#\/?/, '');
          const decoded = decodeURIComponent(raw).replace(/^\/+|\/+$/g, '');
  
          if (!decoded) {
            if (window.lightbox && lightbox.el && lightbox.el.classList.contains('active')) {
              lightbox.close(false);
            }
            if (this.currentPath !== '') {
              this.loadDir('');
            }
            return;
          }
  
          const segments = decoded.split('/');
          const lastSegment = segments[segments.length - 1];
          const isFilePath = /\.[a-zA-Z0-9]{1,8}$/.test(lastSegment);
  
          if (isFilePath) {
            const dirPath = segments.slice(0, -1).join('/');
            const targetFile = decoded;
  
            if (this.currentPath !== dirPath) {
              this.loadDir(dirPath).then(() => {
                this.openFile(targetFile, false);
              });
            } else {
              const activeItem = (window.lightbox && lightbox.el.classList.contains('active'))
                ? lightbox.images[lightbox.currentIndex]
                : null;
              if (!activeItem || activeItem.path !== targetFile) {
                this.openFile(targetFile, false);
              }
            }
          } else {
            if (window.lightbox && lightbox.el && lightbox.el.classList.contains('active')) {
              lightbox.close(false);
            }
            if (this.currentPath !== decoded) {
              this.loadDir(decoded);
            } else {
              this.updateDocTitle(decoded ? decoded.split('/').pop() : '');
            }
          }
        }
  
        updateTreeActive() {
          const cur = this.currentPath || '';
          if (cur) {
            const parts = cur.split('/');
            let accum = '';
            parts.forEach(p => {
              accum = accum ? `${accum}/${p}` : p;
              this.expandedTreeNodes.add(accum);
              const branch = document.querySelector(`.tree-branch[data-branch="${accum}"]`);
              if (branch) branch.classList.remove('collapsed');
            });
            localStorage.setItem('pg_tree_expanded', JSON.stringify(Array.from(this.expandedTreeNodes)));
          }
          document.querySelectorAll('.tree-node').forEach(node => {
            const p = node.getAttribute('data-path') || '';
            if (p === cur) {
              node.classList.add('active');
            } else {
              node.classList.remove('active');
            }
          });
        }
  
        async loadDir(path) {
          this.currentPath = path;
          this.clearSelection();
          this.renderLimit = 25;
          this.isSearching = false;
          const searchInput = document.getElementById('search-input');
          if (searchInput) searchInput.value = '';
          this.searchQuery = '';
          this.sidebar.classList.remove('open');
          this.sidebarBackdrop.classList.remove('active');
          this.updateTreeActive();
  
          const cacheKey = 'dir_list_' + path;
          const cachedData = window.opfsCache ? await window.opfsCache.getJSON(cacheKey) : null;
  
          if (cachedData) {
            this.data = cachedData;
            this.renderGallery();
            this.updateBreadcrumbs();
            this.updateBadges();
            const totalItems = (cachedData.folders ? cachedData.folders.length : 0) + (cachedData.files ? cachedData.files.length : 0);
            this.updateDocTitle(this.data.path ? this.data.path.split('/').pop() : '', totalItems);
          }
  
          try {
            const res = await fetch(`?action=list&dir=${encodeURIComponent(path)}`);
            if (!res.ok) throw new Error('Failed to load directory');
            const freshData = await res.json();
            this.data = freshData;
            if (window.opfsCache) window.opfsCache.setJSON(cacheKey, freshData);
            this.renderGallery();
            this.updateBreadcrumbs();
            this.updateBadges();
            const totalItems = (freshData.folders ? freshData.folders.length : 0) + (freshData.files ? freshData.files.length : 0);
            this.updateDocTitle(this.data.path ? this.data.path.split('/').pop() : '', totalItems);
          } catch (e) {
            if (!cachedData) {
              this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
            }
          }
        }
  
        async performSearch(query) {
          this.isSearching = true;
          this.dirStats.innerText = `Searching for "${query}" in subfolders...`;
          this.container.innerHTML = `
            <div class="center-state">
              <svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
            </div>
          `;
  
          try {
            const res = await fetch(`?action=search&dir=${encodeURIComponent(this.currentPath)}&q=${encodeURIComponent(query)}`);
            if (!res.ok) throw new Error('Search failed');
            const results = await res.json();
  
            let filteredFiles = results.files.filter(f => this.filter === 'all' || f.type === this.filter);
            let filteredFolders = results.folders;
  
            this.filteredList = [
              ...filteredFolders.map(f => ({ ...f, isDir: true })),
              ...filteredFiles.map(f => ({ ...f, isDir: false }))
            ];
  
            this.dirTitle.innerText = `Search: "${query}"`;
            this.dirStats.innerText = `${results.count} matching item(s) found`;
  
            this.container.innerHTML = '';
            this.renderedCount = 0;
            this.renderLimit = 25;
  
            if (!this.filteredList.length) {
              this.container.innerHTML = `<div class="center-state"><svg viewBox="0 0 24 24" style="width:48px; height:48px; opacity:0.4;"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><p>No matches found</p></div>`;
              return;
            }
  
            this.appendBatch();
            this.updateBatchBar();
          } catch (e) {
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }
  
        toggleTreeNode(path) {
          if (this.expandedTreeNodes.has(path)) {
            this.expandedTreeNodes.delete(path);
          } else {
            this.expandedTreeNodes.add(path);
          }
          localStorage.setItem('pg_tree_expanded', JSON.stringify(Array.from(this.expandedTreeNodes)));
          this.loadTree();
        }
  
        async loadTree() {
          try {
            const cacheKey = 'tree_cache';
            const cachedTree = window.opfsCache ? await window.opfsCache.getJSON(cacheKey) : null;
            const container = document.getElementById('tree-container');
  
            if (this.currentPath) {
              const parts = this.currentPath.split('/');
              let accum = '';
              parts.forEach(p => {
                accum = accum ? `${accum}/${p}` : p;
                this.expandedTreeNodes.add(accum);
              });
            }
  
            const renderNodes = (nodes) => {
              let html = '';
              nodes.forEach(n => {
                const hasChildren = n.children && n.children.length > 0;
                const isExpanded = this.expandedTreeNodes.has(n.path);
                const isCollapsed = hasChildren && !isExpanded;
  
                html += `
                  <div class="tree-branch ${isCollapsed ? 'collapsed' : ''}" data-branch="${n.path}">
                    <div class="tree-node-row">
                      ${hasChildren ? `
                        <span class="tree-toggle" onclick="event.stopPropagation(); app.toggleTreeNode('${n.path}')">
                          <svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z" fill="currentColor"/></svg>
                        </span>
                      ` : '<span class="tree-spacer"></span>'}
                      <div class="tree-node ${n.path === (this.currentPath || '') ? 'active' : ''}" data-path="${n.path}" onclick="app.navigate('${n.path}')">
                        <svg viewBox="0 0 16 16"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg>
                        <span>${n.name}</span>
                      </div>
                    </div>
                    ${hasChildren ? `<div class="tree-children" style="padding-left:0.85rem;">${renderNodes(n.children)}</div>` : ''}
                  </div>
                `;
              });
              return html;
            };
  
            const rootRow = `
              <div class="tree-node-row">
                <span class="tree-spacer"></span>
                <div class="tree-node ${!this.currentPath ? 'active' : ''}" data-path="" onclick="app.navigate('')">
                  <svg viewBox="0 0 16 16"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg>
                  <span>Root</span>
                </div>
              </div>
            `;
  
            if (cachedTree) {
              container.innerHTML = rootRow + renderNodes(cachedTree);
            }
  
            const res = await fetch('?action=tree');
            const tree = await res.json();
            if (window.opfsCache) window.opfsCache.setJSON(cacheKey, tree);
            container.innerHTML = rootRow + renderNodes(tree);
            this.updateTreeActive();
          } catch (e) {}
        }
  
        applySort(items) {
          return [...items].sort((a, b) => {
            if (this.sortBy === 'name_asc') {
              return a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'name_desc') {
              return b.name.localeCompare(a.name, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'date_desc') {
              return (b.mtime || 0) - (a.mtime || 0);
            }
            if (this.sortBy === 'date_asc') {
              return (a.mtime || 0) - (b.mtime || 0);
            }
            return 0;
          });
        }
  
        updateSortUI() {
          const labels = {
            name_asc: 'Name (A-Z)',
            name_desc: 'Name (Z-A)',
            date_desc: 'Date (Newest)',
            date_asc: 'Date (Oldest)'
          };
          if (this.btnSort) {
            this.btnSort.title = `Sort: ${labels[this.sortBy] || 'Sort Items'}`;
          }
          document.querySelectorAll('#dropdown-sort .dm-item').forEach(el => {
            if (el.dataset.sort === this.sortBy) {
              el.classList.add('active');
            } else {
              el.classList.remove('active');
            }
          });
        }
  
        renderGallery() {
          let filteredFiles = this.data.files.filter(f => this.filter === 'all' || f.type === this.filter);
          let filteredFolders = this.data.folders;
  
          filteredFolders = this.applySort(filteredFolders);
          filteredFiles = this.applySort(filteredFiles);
  
          this.filteredList = [
            ...filteredFolders.map(f => ({ ...f, isDir: true })),
            ...filteredFiles.map(f => ({ ...f, isDir: false }))
          ];
  
          this.dirTitle.innerText = this.data.path ? this.data.path.split('/').pop() : this.appTitle;
          this.dirStats.innerText = `${filteredFolders.length} Folders, ${filteredFiles.length} Files (${this.data.stats.total_size || '0 B'})`;
  
          this.container.innerHTML = '';
          this.renderedCount = 0;
          this.masonryCols = [];
          this.hasUpCard = false;
  
          if (this.layout === 'columns') {
            const numCols = this.getMasonryColCount();
            for (let i = 0; i < numCols; i++) {
              const col = document.createElement('div');
              col.className = 'masonry-col';
              this.container.appendChild(col);
              this.masonryCols.push(col);
            }
          }
  
          if (this.data.path) {
            this.hasUpCard = true;
            const parts = this.data.path.split('/');
            parts.pop();
            const parent = parts.join('/');
            const upCard = document.createElement('div');
            upCard.className = 'file-card';
            upCard.onclick = () => app.navigate(parent);
            const upRatio = this.layout === 'list' ? '' : 'style="aspect-ratio: 1 / 1; min-height: 140px;"';
            upCard.innerHTML = `
              <div class="file-thumb" ${upRatio}>
                <div class="type-icon type-folder">
                  <svg viewBox="0 0 16 16"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg>
                </div>
              </div>
              <div class="file-info-overlay">
                <div class="file-name">.. (Go Up)</div>
                <div class="file-meta"><span>Parent Directory</span></div>
              </div>
            `;
            if (this.layout === 'columns' && this.masonryCols.length > 0) {
              this.masonryCols[0].appendChild(upCard);
            } else {
              this.container.appendChild(upCard);
            }
          }
  
          if (!this.filteredList.length && !this.data.path) {
            this.container.innerHTML = `<div class="center-state"><svg viewBox="0 0 16 16" style="width:48px; height:48px; opacity:0.4;"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg><p>Folder is empty</p></div>`;
            return;
          }
  
          this.appendBatch();
          this.updateBatchBar();
        }
  
        appendBatch() {
          const toRender = this.filteredList.slice(this.renderedCount, this.renderLimit);
          if (!toRender.length) return;
  
          const isColumns = this.layout === 'columns';
          const numCols = isColumns ? (this.masonryCols.length || this.getMasonryColCount()) : 0;
          const fragment = isColumns ? null : document.createDocumentFragment();
  
          toRender.forEach((item, idx) => {
            const card = document.createElement('div');
            const isSel = this.selectedItems.has(item.path);
            card.className = `file-card ${isSel ? 'selected' : ''}`;
  
            const formattedDate = item.mtime ? new Date(item.mtime * 1000).toLocaleDateString(undefined, { year: '2-digit', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }) : '';
  
            if (item.isDir) {
              const folderRatio = this.layout === 'list' ? '' : 'style="aspect-ratio: 1 / 1; min-height: 140px;"';
              card.onclick = (e) => app.handleItemClick(e, 'folder', item.path);
              card.oncontextmenu = (e) => app.showContextMenu(e, 'folder', item.path, item.name);
              card.innerHTML = `
                <div class="file-checkbox" onclick="app.toggleSelect(event, '${item.path}')"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                <div class="file-thumb" ${folderRatio}>
                  <div class="type-icon type-folder">
                    <svg viewBox="0 0 16 16"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg>
                  </div>
                </div>
                <div class="file-info-overlay">
                  <div class="file-name" title="${item.path}">${item.name}</div>
                  <div class="file-meta">
                    <span>${this.layout === 'list' && formattedDate ? formattedDate + ' • ' : ''}${item.items_count !== undefined ? item.items_count + ' items' : 'Folder'}</span>
                  </div>
                </div>
              `;
            } else {
              let thumbHtml = '';
              let thumbRatio = this.layout === 'list' ? '' : 'style="aspect-ratio: 1 / 1; min-height: 140px;"';
  
              if (item.type === 'image') {
                const ratioStyle = (item.width && item.height) ? `style="aspect-ratio:${item.width}/${item.height};"` : '';
                thumbHtml = `<img src="?action=thumb&f=${encodeURIComponent(item.path)}" alt="${item.name}" ${ratioStyle} loading="lazy" decoding="async">`;
                if (item.width && item.height) {
                  thumbRatio = `style="aspect-ratio:${item.width}/${item.height};"`;
                } else if (this.layout !== 'list') {
                  thumbRatio = 'style="min-height: 140px;"';
                }
              } else if (item.type === 'video') {
                thumbHtml = `<div class="type-icon" style="color:#a8c7fa;"><svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg></div>`;
              } else if (item.type === 'audio') {
                thumbHtml = `<div class="type-icon" style="color:#f2b8b5;"><svg viewBox="0 0 24 24"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg></div>`;
              } else if (item.type === 'archive') {
                thumbHtml = `<div class="type-icon" style="color:#fde293;"><svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg></div>`;
              } else {
                thumbHtml = `<div class="type-icon" style="color:#80cbc4;"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg></div>`;
              }
  
              card.onclick = (e) => app.handleItemClick(e, 'file', item.path);
              card.oncontextmenu = (e) => app.showContextMenu(e, 'file', item.path, item.name, item.type);
              card.innerHTML = `
                <div class="file-checkbox" onclick="app.toggleSelect(event, '${item.path}')"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                <div class="file-thumb" ${thumbRatio}>
                  ${thumbHtml}
                  <span class="file-badge">${item.ext}</span>
                </div>
                <div class="file-info-overlay">
                  <div class="file-name" title="${item.path}">${item.name}</div>
                  <div class="file-meta">
                    <span>${this.layout === 'list' && formattedDate ? formattedDate + ' • ' : ''}${item.size_fmt || '0 B'}</span>
                    <span>${this.layout === 'list' ? (item.ext ? item.ext.toUpperCase() : '') : (item.width ? `${item.width}×${item.height}` : item.ext.toUpperCase())}</span>
                  </div>
                </div>
              `;
            }
  
            if (isColumns && this.masonryCols.length > 0) {
              const slotIndex = (this.hasUpCard ? 1 : 0) + this.renderedCount + idx;
              const targetCol = slotIndex % this.masonryCols.length;
              this.masonryCols[targetCol].appendChild(card);
            } else if (fragment) {
              fragment.appendChild(card);
            }
          });
  
          if (!isColumns && fragment) {
            this.container.appendChild(fragment);
          }
          this.renderedCount += toRender.length;
        }
  
        handleItemClick(e, type, path) {
          if (e.target.closest('.file-checkbox')) return;
          if (this.selectedItems.size > 0) {
            this.toggleSelect(e, path);
            return;
          }
          if (type === 'folder') {
            this.navigate(path);
          } else {
            this.openFile(path, true);
          }
        }
  
        toggleSelect(e, path) {
          e.stopPropagation();
          if (this.selectedItems.has(path)) {
            this.selectedItems.delete(path);
          } else {
            this.selectedItems.add(path);
          }
          if (this.isSearching) {
            this.appendBatch();
          } else {
            this.renderGallery();
          }
        }
  
        clearSelection() {
          this.selectedItems.clear();
          this.updateBatchBar();
          if (this.isSearching) {
            this.appendBatch();
          } else {
            this.renderGallery();
          }
        }
  
        updateBatchBar() {
          const count = this.selectedItems.size;
          if (count > 0) {
            this.batchCount.innerText = `${count} selected`;
            this.batchBar.classList.add('active');
          } else {
            this.batchBar.classList.remove('active');
          }
        }
  
        batchDownload() {
          const items = Array.from(this.selectedItems);
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = `?action=download_zip&dir=${encodeURIComponent(this.currentPath)}`;
          items.forEach(i => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'items[]';
            input.value = i;
            form.appendChild(input);
          });
          document.body.appendChild(form);
          form.submit();
          form.remove();
        }
  
        batchDelete() {
          const items = Array.from(this.selectedItems);
          if (confirm(`Delete ${items.length} selected item(s)?`)) {
            this.api('delete', { items }, () => {
              this.toast('Items deleted');
              this.clearSelection();
              this.refresh();
            });
          }
        }
  
        showBatchDetails() {
          const items = Array.from(this.selectedItems);
          const fd = new FormData();
          fd.append('action', 'details');
          items.forEach(i => fd.append('items[]', i));
  
          fetch('', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => this.renderDetailsModal(res));
        }
  
        updateBreadcrumbs() {
          let html = `<a href="#/" class="bc-item">Home</a>`;
          if (this.currentPath) {
            const parts = this.currentPath.split('/');
            let accum = '';
            parts.forEach((p, idx) => {
              accum += (accum ? '/' : '') + p;
              const isLast = idx === parts.length - 1;
              html += `<span class="bc-sep">/</span><a href="#/${encodeURIComponent(accum)}" class="bc-item ${isLast ? 'active' : ''}">${p}</a>`;
            });
          }
          this.breadcrumbs.innerHTML = html;
        }
  
        updateBadges() {
          const counts = { all: this.data.files.length, image: 0, video: 0, audio: 0, text: 0, archive: 0 };
          this.data.files.forEach(f => { if (counts[f.type] !== undefined) counts[f.type]++; });
          for (let k in counts) {
            const el = document.getElementById(`badge-${k}`);
            if (el) el.innerText = counts[k];
          }
        }
  
        openFile(filePath, updateHash = true) {
          let file = this.filteredList.find(f => f.path === filePath) || this.data.files.find(f => f.path === filePath);
          if (!file) return;
  
          if (updateHash) {
            window.location.hash = '#/' + ltrim(file.path, '/');
          }
  
          if (file.type === 'image') {
            const imagesList = this.filteredList.filter(f => !f.isDir && f.type === 'image');
            const currentImgIndex = Math.max(0, imagesList.findIndex(f => f.path === file.path));
            lightbox.open(imagesList, currentImgIndex);
          } else if (file.type === 'video' || file.type === 'audio') {
            lightbox.openMedia(file);
          } else if (file.type === 'text') {
            this.openEditor(file.path, file.name);
          } else {
            window.location.href = `?action=download&f=${encodeURIComponent(file.path)}`;
          }
        }
  
        openEditor(path, name) {
          fetch(`?action=read_text&f=${encodeURIComponent(path)}`)
            .then(r => r.json())
            .then(res => {
              document.getElementById('editor-title').innerText = `Edit: ${name}`;
              document.getElementById('editor-content').value = res.content || '';
              this.showModal('modal-editor');
  
              document.getElementById('editor-save-btn').onclick = () => {
                const val = document.getElementById('editor-content').value;
                this.api('save_text', { f: path, content: val }, () => {
                  this.toast('File saved');
                  this.closeModals();
                });
              };
            });
        }
  
        showContextMenu(e, type, path, name, fileType) {
          e.preventDefault();
          e.stopPropagation();
          let html = '';
  
          if (type === 'folder') {
            html += `
              <div class="dm-item" onclick="app.navigate('${path}')"><svg viewBox="0 0 16 16"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg> Open</div>
              <div class="dm-item" onclick="mangaViewer.openPath('${path}')"><svg viewBox="0 0 24 24"><path d="M19 1L14 6V22L19 17V1M3 6V22L8 17H12V2H8L3 6M10 4.25C10 3.56 9.44 3 8.75 3S7.5 3.56 7.5 4.25 8.06 5.5 8.75 5.5 10 4.94 10 4.25Z"/></svg> Read as Manga</div>
              <div class="dm-item" onclick="app.showDetails('${path}')"><svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg> Folder Info</div>
              <div class="dm-item" onclick="window.location.href='?action=download_zip&dir=${encodeURIComponent(path)}'"><svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Download ZIP</div>
              <div class="dm-sep"></div>
              <div class="dm-item" onclick="app.renameItem('${path}', '${name}')"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> Rename</div>
              <div class="dm-item danger" onclick="app.deleteItem('${path}')"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg> Delete</div>
            `;
          } else {
            html += `
              <div class="dm-item" onclick="window.location.href='?action=download&f=${encodeURIComponent(path)}'"><svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Download</div>
              <div class="dm-item" onclick="app.showDetails('${path}')"><svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg> Details</div>
            `;
            if (fileType === 'archive') {
              html += `<div class="dm-item" onclick="app.unzipItem('${path}')"><svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7z"/></svg> Extract Here</div>`;
            }
            if (fileType === 'text') {
              html += `<div class="dm-item" onclick="app.openEditor('${path}', '${name}')"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/></svg> Edit Text</div>`;
            }
            html += `
              <div class="dm-sep"></div>
              <div class="dm-item" onclick="app.renameItem('${path}', '${name}')"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/></svg> Rename</div>
              <div class="dm-item danger" onclick="app.deleteItem('${path}')"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg> Delete</div>
            `;
          }
  
          this.contextMenu.innerHTML = html;
          this.contextMenu.style.left = `${Math.min(e.clientX, window.innerWidth - 220)}px`;
          this.contextMenu.style.top = `${Math.min(e.clientY, window.innerHeight - 260)}px`;
          this.contextMenu.classList.add('active');
        }
  
        renameItem(path, oldName) {
          this.showInputModal('Rename Item', 'New Name', oldName, (newName) => {
            this.api('rename', { path, new_name: newName }, () => this.refresh());
          });
        }
  
        deleteItem(path) {
          if (confirm('Delete this item?')) {
            this.api('delete', { items: [path] }, () => {
              this.toast('Deleted');
              this.refresh();
            });
          }
        }
  
        unzipItem(path) {
          this.api('unzip', { f: path }, () => {
            this.toast('Extracted');
            this.refresh();
          });
        }
  
        showDetails(path) {
          fetch(`?action=details&f=${encodeURIComponent(path)}`)
            .then(r => r.json())
            .then(res => this.renderDetailsModal(res));
        }
  
        renderDetailsModal(res) {
          document.getElementById('details-modal-title').innerText = res.title || 'Information';
          let html = '';
  
          if (res.general && Object.keys(res.general).length) {
            html += `
              <div class="details-section">
                <div class="details-title">
                  <svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>
                  General Details
                </div>
                <div class="details-grid">
            `;
            for (let k in res.general) {
              html += `<div class="details-row"><span class="details-label">${k}</span><span class="details-value">${res.general[k]}</span></div>`;
            }
            html += `</div></div>`;
          }
  
          if (res.exif && Object.keys(res.exif).length) {
            html += `
              <div class="details-section">
                <div class="details-title">
                  <svg viewBox="0 0 24 24"><path d="M12 12m-3 0a3 3 0 1 0 6 0 3 3 0 1 0-6 0M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9z"/></svg>
                  Camera EXIF
                </div>
                <div class="details-grid">
            `;
            for (let k in res.exif) {
              let val = k === 'Maps' ? `<a href="${res.exif[k]}" target="_blank" style="color:var(--md-sys-color-primary); text-decoration:underline;">Open Maps</a>` : res.exif[k];
              html += `<div class="details-row"><span class="details-label">${k}</span><span class="details-value">${val}</span></div>`;
            }
            html += `</div></div>`;
          }
  
          if (res.iptc && Object.keys(res.iptc).length) {
            html += `
              <div class="details-section">
                <div class="details-title">
                  <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                  IPTC Metadata
                </div>
                <div class="details-grid">
            `;
            for (let k in res.iptc) {
              html += `<div class="details-row"><span class="details-label">${k}</span><span class="details-value">${res.iptc[k]}</span></div>`;
            }
            html += `</div></div>`;
          }
  
          document.getElementById('details-content').innerHTML = html;
          this.showModal('modal-details');
        }
  
        async api(act, data, callback) {
          const fd = new FormData();
          fd.append('action', act);
          for (let k in data) {
            if (Array.isArray(data[k])) {
              data[k].forEach(val => fd.append(`${k}[]`, val));
            } else {
              fd.append(k, data[k]);
            }
          }
          try {
            const res = await fetch(`?action=${act}`, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
              if (callback) callback(json);
            } else throw new Error(json.error || 'Failed');
          } catch (e) { this.toast(e.message); }
        }
  
        refresh() {
          if (this.isSearching && this.searchQuery) {
            this.performSearch(this.searchQuery);
          } else {
            this.loadDir(this.currentPath);
          }
          this.loadTree();
        }
  
        showModal(id) {
          document.getElementById('modal-backdrop').classList.add('active');
          document.querySelectorAll('.modal-box').forEach(m => m.style.display = 'none');
          document.getElementById(id).style.display = 'flex';
        }
  
        closeModals() {
          document.getElementById('modal-backdrop').classList.remove('active');
        }
  
        showInputModal(title, label, defaultVal, callback) {
          document.getElementById('modal-input-title').innerText = title;
          document.getElementById('modal-input-label').innerText = label;
          const input = document.getElementById('modal-input-val');
          input.value = defaultVal;
          this.showModal('modal-input');
          input.focus();
  
          document.getElementById('modal-input-confirm').onclick = () => {
            const val = input.value.trim();
            if (val) {
              callback(val);
              this.closeModals();
            }
          };
        }
  
        toast(msg) {
          const container = document.getElementById('toast-container');
          const el = document.createElement('div');
          el.className = 'toast';
          el.innerHTML = `<span>${msg}</span>`;
          container.appendChild(el);
          setTimeout(() => el.remove(), 3000);
        }
      }
  
      window.opfsCache = new OPFSCacheManager();
      window.uploadManager = new UploadManager();
      window.mangaViewer = new MangaViewer();
      window.lightbox = new LightboxViewer();
      window.app = new GalleryApp();
  
      document.querySelectorAll('.modal-close').forEach(b => b.addEventListener('click', () => window.app.closeModals()));
    </script>
  </body>
</html>