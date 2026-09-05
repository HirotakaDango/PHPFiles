<?php
session_start();

$config = [
  'root_dir'           => __DIR__,
  'cache_dir'          => __DIR__ . '/.gallery_cache',
  'trash_dir'          => __DIR__ . '/.drive_trash_bin',
  'version_dir'        => __DIR__ . '/.file_version',
  'meta_file'          => __DIR__ . '/.gallery_cache/.drive_meta.json',
  'app_title'          => 'PHPFiles',
  'auth_enabled'       => false,
  'password'           => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: admin
  'encryption_key'     => 'Default_Secret_2026', // Change for production
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
  'video_extensions'   => ['mp4', 'webm', 'mov', 'm4v', 'ogv', 'mkv', 'avi', 'ts', '3gp', 'wmv', 'flv'],
  'audio_extensions'   => ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma', 'm4r', 'mid', 'midi'],
  'text_extensions'    => ['txt', 'md', 'markdown', 'json', 'js', 'css', 'html', 'htm', 'php', 'py', 'c', 'cpp', 'sh', 'log', 'xml', 'yaml', 'yml', 'ini', 'env', 'sql', 'csv', 'enc'],
  'archive_extensions' => ['zip', 'tar', 'gz', '7z', 'rar'],
];

ini_set('memory_limit', $config['memory_limit']);

if (isset($_GET['pwa'])) {
  $pwaMode = $_GET['pwa'];
  if ($pwaMode === 'manifest') {
    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode([
      "name"             => $config['app_title'],
      "short_name"       => $config['app_title'],
      "start_url"        => "./",
      "scope"            => "./",
      "display"          => "standalone",
      "orientation"      => "any",
      "background_color" => "#141218",
      "theme_color"      => "#141218",
      "description"      => "High-performance self-hosted cloud drive, media gallery, and markdown studio.",
      "icons"            => [
        [
          "src"     => "?action=icon",
          "sizes"   => "192x192",
          "type"    => "image/svg+xml",
          "purpose" => "any maskable"
        ],
        [
          "src"     => "?action=icon",
          "sizes"   => "512x512",
          "type"    => "image/svg+xml",
          "purpose" => "any maskable"
        ]
      ]
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
  }

  if ($pwaMode === 'sw') {
    header('Content-Type: application/javascript; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo <<<SW
const CACHE_NAME = 'phpfiles-cdn-cache-v1';
const STATIC_ASSETS = [
  './',
  '?action=icon',
  'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap',
  'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.8/purify.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/tokyo-night-dark.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/nord.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/search/searchcursor.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/markdown/markdown.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js',
  'https://cdn.jsdelivr.net/npm/mermaid@10.9.0/dist/mermaid.esm.min.mjs',
  'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js',
  'https://cdn.jsdelivr.net/npm/docx-preview@0.1.15/dist/docx-preview.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS.map(url => new Request(url, { mode: 'cors' }))).catch(() => {});
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Bypass caching for API actions, uploads, raw streams, or non-GET requests
  if (event.request.method !== 'GET' || url.searchParams.has('action') || (url.pathname.endsWith('.php') && url.searchParams.size > 0 && !url.searchParams.has('pwa'))) {
    return;
  }

  // Cache-first strategy for CDNs and fonts
  if (
    STATIC_ASSETS.some(asset => event.request.url.startsWith(asset)) ||
    url.hostname.includes('cdnjs.cloudflare.com') ||
    url.hostname.includes('cdn.jsdelivr.net') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com')
  ) {
    event.respondWith(
      caches.match(event.request).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(event.request).then(networkResponse => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
          }
          return networkResponse;
        }).catch(() => cachedResponse);
      })
    );
    return;
  }

  // Network-first with offline cache fallback for application shell
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).then(networkResponse => {
        const responseClone = networkResponse.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
        return networkResponse;
      }).catch(() => caches.match('./') || caches.match(event.request))
    );
  }
});
SW;
    exit;
  }
}

function ensure_getid3() {
  $target_file = __DIR__ . '/getid3/getid3.php';
  if (file_exists($target_file)) {
    return true;
  }

  if (!class_exists('ZipArchive')) {
    return false;
  }

  $urls = [
    'james' => 'https://github.com/JamesHeinrich/getID3/archive/refs/heads/master.zip',
    'dango' => 'https://github.com/HirotakaDango/PHP-Music/archive/refs/heads/main.zip'
  ];

  $temp_zip = __DIR__ . '/getid3_temp.zip';

  $delete_folder = function ($dir) use (&$delete_folder) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
      (is_dir("$dir/$file")) ? $delete_folder("$dir/$file") : @unlink("$dir/$file");
    }
    return @rmdir($dir);
  };

  foreach ($urls as $source => $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$data) {
      $context = stream_context_create([
        'http' => ['timeout' => 60, 'follow_location' => true],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
      ]);
      $data = @file_get_contents($url, false, $context);
    }

    if ($data && strlen($data) > 1000) {
      @file_put_contents($temp_zip, $data);
      if (file_exists($temp_zip)) {
        $zip = new ZipArchive;
        if ($zip->open($temp_zip) === true) {
          $temp_extract_dir = __DIR__ . '/getid3_temp_extract';
          if (!is_dir($temp_extract_dir)) {
            @mkdir($temp_extract_dir, 0755, true);
          }
          $zip->extractTo($temp_extract_dir);
          $zip->close();

          $extracted_getid3_path = '';
          if ($source === 'james') {
            $extracted_getid3_path = $temp_extract_dir . '/getID3-master/getid3';
          } else {
            $extracted_getid3_path = $temp_extract_dir . '/PHP-Music-main/getid3';
          }

          if (is_dir($extracted_getid3_path)) {
            $target_dir = __DIR__ . '/getid3';
            if (!is_dir($target_dir)) {
              @mkdir($target_dir, 0755, true);
            }

            $copy_folder = function ($src, $dst) use (&$copy_folder) {
              $dir = opendir($src);
              @mkdir($dst, 0755, true);
              while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                  if (is_dir($src . '/' . $file)) {
                    $copy_folder($src . '/' . $file, $dst . '/' . $file);
                  } else {
                    @copy($src . '/' . $file, $dst . '/' . $file);
                  }
                }
              }
              closedir($dir);
            };

            $copy_folder($extracted_getid3_path, $target_dir);
            $delete_folder($temp_extract_dir);
            @unlink($temp_zip);

            if (file_exists($target_file)) {
              return true;
            }
          }
          if (is_dir($temp_extract_dir)) {
            $delete_folder($temp_extract_dir);
          }
        }
        @unlink($temp_zip);
      }
    }
  }
  return false;
}

ensure_getid3();

if (file_exists(__DIR__ . '/getid3/getid3.php')) {
  require_once __DIR__ . '/getid3/getid3.php';
}

foreach ([$config['cache_dir'], $config['trash_dir'], $config['version_dir']] as $sysDir) {
  if (!is_dir($sysDir)) {
    @mkdir($sysDir, 0777, true);
    @file_put_contents($sysDir . '/.htaccess', "Order Deny,Allow\nDeny from all");
  }
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
  $cleanRel = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $requestPath ?? ''), DIRECTORY_SEPARATOR);
  $targetPath = $cleanRel === '' ? $realBase : ($realBase . DIRECTORY_SEPARATOR . $cleanRel);
  $realTarget = realpath($targetPath);
  if ($realTarget === false) {
    $check = normalizePath($targetPath);
    $normBase = normalizePath($realBase);
    if (stripos($check . DIRECTORY_SEPARATOR, $normBase . DIRECTORY_SEPARATOR) === 0 || strcasecmp($check, $normBase) === 0) return $check;
    return false;
  }
  $normTarget = normalizePath($realTarget);
  $normBase = normalizePath($realBase);
  if (stripos($normTarget . DIRECTORY_SEPARATOR, $normBase . DIRECTORY_SEPARATOR) !== 0 && strcasecmp($normTarget, $normBase) !== 0) return $realBase;
  return $realTarget;
}

function findRealFile($rootDir, $relPath) {
  $path = safePath($rootDir, $relPath);
  if ($path && file_exists($path)) return $path;

  // Fallback 1: Try sanitized filename (colons or special symbols stripped)
  $baseName = basename($relPath);
  $cleanName = preg_replace('/[^\w\s\d\.\-_~()[\]]/u', '', $baseName);
  $dir = dirname($relPath);
  $altRel = ($dir && $dir !== '.') ? ($dir . '/' . $cleanName) : $cleanName;
  $altPath = safePath($rootDir, $altRel);
  if ($altPath && file_exists($altPath)) return $altPath;

  // Fallback 2: Check if file exists in the root directory
  $rootPath = safePath($rootDir, $baseName);
  if ($rootPath && file_exists($rootPath)) return $rootPath;

  $rootCleanPath = safePath($rootDir, $cleanName);
  if ($rootCleanPath && file_exists($rootCleanPath)) return $rootCleanPath;

  return false;
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
  if (!is_dir($dirPath)) return ['size' => 0, 'files' => 0, 'folders' => 0];
  if (function_exists('set_time_limit')) @set_time_limit(120);

  $cacheReal = realpath($cacheDir);
  $size = 0.0;
  $files = 0;
  $folders = 0;
  $ignoreDirs = ['.gallery_cache', '.drive_trash_bin', '.file_version', '.git', 'node_modules', 'vendor'];

  $queue = [$dirPath];
  while (!empty($queue)) {
    $currentDir = array_shift($queue);
    $dh = @opendir($currentDir);
    if (!$dh) continue;

    while (($entry = @readdir($dh)) !== false) {
      if ($entry === '.' || $entry === '..' || in_array($entry, $ignoreDirs) || $entry[0] === '.' || preg_match('/\.(part|crdownload|tmp|swp)$/i', $entry)) {
        continue;
      }
      $full = $currentDir . DIRECTORY_SEPARATOR . $entry;
      if ($cacheReal && strpos($full, $cacheReal) === 0) continue;

      if (is_dir($full)) {
        $folders++;
        $queue[] = $full;
      } else {
        $files++;
        $sz = @filesize($full);
        if ($sz !== false) $size += (float)$sz;
      }
    }
    @closedir($dh);
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
          $meta['exif']['OpenStreetMap'] = "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lon}#map=16/{$lat}/{$lon}";
          $meta['exif']['OSM_Embed'] = "https://www.openstreetmap.org/export/embed.html?bbox=" . ($lon - 0.006) . "%2C" . ($lat - 0.003) . "%2C" . ($lon + 0.006) . "%2C" . ($lat + 0.003) . "&layer=mapnik&marker={$lat}%2C{$lon}";
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

function formatDuration($seconds) {
  if (!$seconds || $seconds <= 0) return '';
  $s = (int)round($seconds);
  $h = floor($s / 3600);
  $m = floor(($s % 3600) / 60);
  $sec = $s % 60;
  return $h > 0 ? sprintf('%02d:%02d:%02d', $h, $m, $sec) : sprintf('%02d:%02d', $m, $sec);
}

function getMediaMetadata($filePath) {
  $meta = ['tags' => [], 'cover_art' => null, 'raw_cover' => null];
  if (!file_exists($filePath)) return $meta;

  // 1. Primary parser: getID3 Engine for audio and video files
  if (class_exists('getID3')) {
    try {
      $getID3 = new getID3();
      $getID3->setOption([
        'option_md5_data'     => false,
        'option_md5_checksum' => false,
        'option_tags_images'  => true
      ]);

      $info = $getID3->analyze($filePath);

      // Deep Tag Map Resolution across ID3v2, Vorbis, QuickTime, RIFF & Matroska
      $tagMaps = [
        'Track Title'   => ['title', 'track_title', 'song_title', 'nam'],
        'Artist'        => ['artist', 'author', 'ART'],
        'Album Artist'  => ['album_artist', 'albumartist', 'band', 'aART'],
        'Album'         => ['album', 'alb'],
        'Track #'       => ['track_number', 'tracknumber', 'track', 'trkn'],
        'Disc #'        => ['disc_number', 'discnumber', 'disc', 'disk'],
        'Year'          => ['year', 'date', 'creation_date', 'recording_time', 'day'],
        'Genre'         => ['genre', 'gen'],
        'Composer'      => ['composer', 'writer', 'wrt'],
        'Publisher'     => ['publisher', 'label', 'organization', 'pub'],
        'Copyright'     => ['copyright', 'cprt', 'cpy'],
        'BPM'           => ['bpm', 'tempo'],
        'Comment'       => ['comment', 'description', 'cmt', 'des'],
      ];

      $allComments = [];
      if (!empty($info['comments'])) {
        $allComments[] = $info['comments'];
      }
      if (!empty($info['tags'])) {
        foreach ($info['tags'] as $fmtTags) {
          if (is_array($fmtTags)) $allComments[] = $fmtTags;
        }
      }

      foreach ($tagMaps as $label => $keys) {
        foreach ($allComments as $commentGroup) {
          foreach ($keys as $k) {
            if (!empty($commentGroup[$k])) {
              $val = is_array($commentGroup[$k]) ? $commentGroup[$k][0] : $commentGroup[$k];
              if (is_string($val) && trim($val) !== '') {
                $meta['tags'][$label] = trim($val);
                break 2;
              }
            }
          }
        }
      }

      if (!empty($info['playtime_seconds'])) {
        $meta['tags']['Duration'] = formatDuration($info['playtime_seconds']);
      }

      // Video Stream Metadata
      if (!empty($info['video']['resolution_x']) && !empty($info['video']['resolution_y'])) {
        $meta['tags']['Resolution'] = $info['video']['resolution_x'] . ' × ' . $info['video']['resolution_y'] . ' px';
      }
      if (!empty($info['video']['codec']) || !empty($info['video']['dataformat'])) {
        $meta['tags']['Video Codec'] = strtoupper($info['video']['codec'] ?? $info['video']['dataformat']);
      }
      if (!empty($info['video']['frame_rate'])) {
        $meta['tags']['Frame Rate'] = round($info['video']['frame_rate'], 2) . ' fps';
      }
      if (!empty($info['video']['bitrate'])) {
        $meta['tags']['Video Bitrate'] = round($info['video']['bitrate'] / 1000) . ' kbps';
      }

      // Audio Stream Metadata
      if (!empty($info['audio']['codec']) || !empty($info['audio']['dataformat'])) {
        $meta['tags']['Audio Codec'] = strtoupper($info['audio']['codec'] ?? $info['audio']['dataformat']);
      }
      if (!empty($info['audio']['bitrate'])) {
        $brMode = !empty($info['audio']['bitrate_mode']) ? ' (' . strtoupper($info['audio']['bitrate_mode']) . ')' : '';
        $meta['tags']['Audio Bitrate'] = round($info['audio']['bitrate'] / 1000) . ' kbps' . $brMode;
      }
      if (!empty($info['audio']['channels'])) {
        $ch = (int)$info['audio']['channels'];
        $meta['tags']['Channels'] = ($ch === 2) ? 'Stereo (2 ch)' : (($ch === 1) ? 'Mono (1 ch)' : "{$ch} Channels");
      }
      if (!empty($info['audio']['sample_rate'])) {
        $meta['tags']['Sample Rate'] = number_format($info['audio']['sample_rate']) . ' Hz';
      }
      if (!empty($info['audio']['bits_per_sample'])) {
        $meta['tags']['Bit Depth'] = $info['audio']['bits_per_sample'] . '-bit';
      }

      $coverData = null;
      $mimeType = 'image/jpeg';

      if (!empty($info['comments']['picture'][0]['data'])) {
        $coverData = $info['comments']['picture'][0]['data'];
        $mimeType = $info['comments']['picture'][0]['image_mime'] ?? 'image/jpeg';
      } elseif (!empty($info['id3v2']['APIC'][0]['data'])) {
        $coverData = $info['id3v2']['APIC'][0]['data'];
        $mimeType = $info['id3v2']['APIC'][0]['mime'] ?? 'image/jpeg';
      } elseif (!empty($info['matroska']['attachments'])) {
        foreach ($info['matroska']['attachments'] as $att) {
          if (stripos($att['filename'] ?? '', 'cover') !== false && !empty($att['data'])) {
            $coverData = $att['data'];
            $mimeType = $att['file_mime'] ?? 'image/jpeg';
            break;
          }
        }
      }

      if ($coverData) {
        $meta['raw_cover'] = $coverData;
        $meta['cover_art'] = 'data:' . $mimeType . ';base64,' . base64_encode($coverData);
        return $meta;
      }
    } catch (Exception $e) {}
  }

  // 2. Binary stream fallback
  $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

  if ($ext === 'mp3') {
    $fp = @fopen($filePath, 'rb');
    if ($fp) {
      $header = fread($fp, 10);
      if (strlen($header) === 10 && substr($header, 0, 3) === 'ID3') {
        $version = ord($header[3]);
        $tagSize = ((ord($header[6]) & 0x7f) << 21) | ((ord($header[7]) & 0x7f) << 14) | ((ord($header[8]) & 0x7f) << 7) | (ord($header[9]) & 0x7f);
        if ($tagSize > 0 && $tagSize < 10485760) {
          $data = fread($fp, $tagSize);
          $len = strlen($data);
          $pos = 0;
          $tagMap = [
            'TIT2' => 'Track Title',
            'TPE1' => 'Artist',
            'TPE2' => 'Album Artist',
            'TALB' => 'Album',
            'TRCK' => 'Track #',
            'TPOS' => 'Disc #',
            'TYER' => 'Year',
            'TDRC' => 'Year',
            'TCON' => 'Genre',
            'TCOM' => 'Composer',
            'TPUB' => 'Publisher',
            'TCOP' => 'Copyright',
            'TBPM' => 'BPM'
          ];

          while ($pos + 10 < $len) {
            $frameId = substr($data, $pos, 4);
            if (!preg_match('/^[A-Z0-9]{4}$/', $frameId)) break;
            $fSize = $version === 4
              ? ((ord($data[$pos+4]) & 0x7f) << 21) | ((ord($data[$pos+5]) & 0x7f) << 14) | ((ord($data[$pos+6]) & 0x7f) << 7) | (ord($data[$pos+7]) & 0x7f)
              : (ord($data[$pos+4]) << 24) | (ord($data[$pos+5]) << 16) | (ord($data[$pos+6]) << 8) | ord($data[$pos+7]);

            if ($fSize <= 0 || $pos + 10 + $fSize > $len) break;
            $content = substr($data, $pos + 10, $fSize);
            $pos += 10 + $fSize;

            if (isset($tagMap[$frameId]) && strlen($content) > 1) {
              $enc = ord($content[0]);
              $text = substr($content, 1);
              if ($enc === 1 || $enc === 2) $text = @mb_convert_encoding($text, 'UTF-8', 'UTF-16');
              $meta['tags'][$tagMap[$frameId]] = trim($text);
            } elseif ($frameId === 'APIC' && strlen($content) > 5 && empty($meta['cover_art'])) {
              $enc = ord($content[0]);
              $p = 1;
              $mime = '';
              while ($p < strlen($content) && $content[$p] !== "\x00") { $mime .= $content[$p]; $p++; }
              $p += 2;
              if ($enc === 1 || $enc === 2) {
                while ($p + 1 < strlen($content) && !($content[$p] === "\x00" && $content[$p+1] === "\x00")) $p += 2;
                $p += 2;
              } else {
                while ($p < strlen($content) && $content[$p] !== "\x00") $p++;
                $p++;
              }
              if ($p < strlen($content)) {
                $img = substr($content, $p);
                $mimeType = $mime ?: 'image/jpeg';
                $meta['cover_art'] = 'data:' . $mimeType . ';base64,' . base64_encode($img);
                $meta['raw_cover'] = $img;
              }
            }
          }
        }
      }
      fclose($fp);
    }
  } elseif (in_array($ext, ['mp4', 'm4a', 'mov'])) {
    $fp = @fopen($filePath, 'rb');
    if ($fp) {
      $fsize = filesize($filePath);
      while (ftell($fp) < $fsize) {
        $buf = fread($fp, 8);
        if (strlen($buf) < 8) break;
        $atomSize = unpack('N', substr($buf, 0, 4))[1];
        $atomType = substr($buf, 4, 4);
        if ($atomSize === 1) {
          $extBuf = fread($fp, 8);
          if (strlen($extBuf) < 8) break;
          $atomSize = unpack('N', substr($extBuf, 4, 4))[1];
        } elseif ($atomSize === 0) {
          $atomSize = $fsize - ftell($fp) + 8;
        }
        if ($atomSize < 8) break;

        if ($atomType === 'moov') {
          $moov = fread($fp, min($atomSize - 8, 12582912));
          $mvhd = strpos($moov, 'mvhd');
          if ($mvhd !== false) {
            $ver = ord($moov[$mvhd + 4]);
            $off = $mvhd + 8;
            $scale = unpack('N', substr($moov, $ver === 0 ? $off + 8 : $off + 16, 4))[1];
            $dur = unpack('N', substr($moov, $ver === 0 ? $off + 12 : $off + 24, 4))[1];
            if ($scale > 0 && $dur > 0) $meta['tags']['Duration'] = formatDuration($dur / $scale);
          }

          $tkhd = strpos($moov, 'tkhd');
          if ($tkhd !== false) {
            $ver = ord($moov[$tkhd + 4]);
            $wOff = $ver === 0 ? $tkhd + 8 + 76 : $tkhd + 8 + 88;
            if ($wOff + 8 <= strlen($moov)) {
              $w = unpack('N', substr($moov, $wOff, 4))[1] >> 16;
              $h = unpack('N', substr($moov, $wOff + 4, 4))[1] >> 16;
              if ($w > 0 && $h > 0) $meta['tags']['Resolution'] = "{$w} × {$h} px";
            }
          }

          $ilst = strpos($moov, 'ilst');
          if ($ilst !== false) {
            $pos = $ilst + 4;
            $end = strlen($moov);
            $tagMap = [
              "\xa9nam" => 'Track Title',
              "\xa9ART" => 'Artist',
              "aART"    => 'Album Artist',
              "\xa9alb" => 'Album',
              "\xa9day" => 'Year',
              "\xa9gen" => 'Genre',
              "\xa9wrt" => 'Composer',
              "\xa9pub" => 'Publisher',
              "\xa9cpy" => 'Copyright',
              "\xa9cmt" => 'Comment',
              "trkn"    => 'Track #'
            ];
            while ($pos + 8 < $end) {
              $sz = unpack('N', substr($moov, $pos, 4))[1];
              $type = substr($moov, $pos + 4, 4);
              if ($sz < 8 || $pos + $sz > $end) break;
              if (isset($tagMap[$type]) || $type === 'covr') {
                $dPos = strpos(substr($moov, $pos, $sz), 'data');
                if ($dPos !== false) {
                  $real = $pos + $dPos;
                  $dSz = unpack('N', substr($moov, $real - 4, 4))[1];
                  $dType = unpack('N', substr($moov, $real + 4, 4))[1];
                  $payload = substr($moov, $real + 8, $dSz - 16);
                  if ($type === 'covr') {
                    $mime = ($dType === 14) ? 'image/png' : 'image/jpeg';
                    $meta['cover_art'] = 'data:' . $mime . ';base64,' . base64_encode($payload);
                    $meta['raw_cover'] = $payload;
                  } else {
                    $meta['tags'][$tagMap[$type]] = trim($payload);
                  }
                }
              }
              $pos += $sz;
            }
          }
          break;
        } else {
          fseek($fp, $atomSize - 8, SEEK_CUR);
        }
      }
      fclose($fp);
    }
  } elseif ($ext === 'flac') {
    $fp = @fopen($filePath, 'rb');
    if ($fp && fread($fp, 4) === 'fLaC') {
      while (!feof($fp)) {
        $hdr = fread($fp, 4);
        if (strlen($hdr) < 4) break;
        $isLast = (ord($hdr[0]) & 0x80) !== 0;
        $type = ord($hdr[0]) & 0x7f;
        $sz = (ord($hdr[1]) << 16) | (ord($hdr[2]) << 8) | ord($hdr[3]);
        if ($sz <= 0) break;
        $block = fread($fp, $sz);

        if ($type === 0 && strlen($block) >= 18) {
          $sr = (ord($block[10]) << 12) | (ord($block[11]) << 4) | (ord($block[12]) >> 4);
          $samples = ((ord($block[13]) & 0x0f) << 32) | (ord($block[14]) << 24) | (ord($block[15]) << 16) | (ord($block[16]) << 8) | ord($block[17]);
          if ($sr > 0 && $samples > 0) {
            $meta['tags']['Duration'] = formatDuration($samples / $sr);
            $meta['tags']['Sample Rate'] = number_format($sr) . ' Hz';
          }
        } elseif ($type === 4) {
          $p = 4 + unpack('V', substr($block, 0, 4))[1];
          if ($p + 4 <= strlen($block)) {
            $n = unpack('V', substr($block, $p, 4))[1];
            $p += 4;
            $fMap = [
              'TITLE'       => 'Track Title',
              'ARTIST'      => 'Artist',
              'ALBUMARTIST' => 'Album Artist',
              'ALBUM ARTIST'=> 'Album Artist',
              'ALBUM'       => 'Album',
              'TRACKNUMBER' => 'Track #',
              'DISCNUMBER'  => 'Disc #',
              'DATE'        => 'Year',
              'YEAR'        => 'Year',
              'GENRE'       => 'Genre',
              'COMPOSER'    => 'Composer',
              'PUBLISHER'   => 'Publisher',
              'COPYRIGHT'   => 'Copyright',
              'COMMENT'     => 'Comment',
              'BPM'         => 'BPM'
            ];
            for ($i = 0; $i < $n && $p + 4 <= strlen($block); $i++) {
              $cl = unpack('V', substr($block, $p, 4))[1];
              $p += 4;
              if ($p + $cl <= strlen($block)) {
                $c = substr($block, $p, $cl);
                $parts = explode('=', $c, 2);
                if (count($parts) === 2 && isset($fMap[strtoupper(trim($parts[0]))])) {
                  $meta['tags'][$fMap[strtoupper(trim($parts[0]))]] = trim($parts[1]);
                }
                $p += $cl;
              }
            }
          }
        } elseif ($type === 6 && strlen($block) > 32) {
          $mLen = unpack('N', substr($block, 4, 4))[1];
          $mime = substr($block, 8, $mLen);
          $p = 8 + $mLen;
          $dLen = unpack('N', substr($block, $p, 4))[1];
          $p += 4 + $dLen + 16;
          if ($p + 4 <= strlen($block)) {
            $imgLen = unpack('N', substr($block, $p, 4))[1];
            $p += 4;
            if ($p + $imgLen <= strlen($block)) {
              $img = substr($block, $p, $imgLen);
              $meta['cover_art'] = 'data:' . ($mime ?: 'image/jpeg') . ';base64,' . base64_encode($img);
              $meta['raw_cover'] = $img;
            }
          }
        }
        if ($isLast) break;
      }
      fclose($fp);
    }
  }
  return $meta;
}

function createThumbnail($src, $dest, $size, $quality) {
  if (!file_exists($src) || !is_readable($src)) return false;

  $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
  if ($ext === 'svg') return false;

  $info = @getimagesize($src);
  if (!$info) return false;

  list($origW, $origH) = $info;
  if ($origW <= 0 || $origH <= 0) return false;

  $mime = $info['mime'] ?? '';

  $ratio = min($size / $origW, $size / $origH);
  $newW = max(1, (int)round($origW * $ratio));
  $newH = max(1, (int)round($origH * $ratio));

  $srcImg = false;
  switch ($mime) {
    case 'image/jpeg': $srcImg = @imagecreatefromjpeg($src); break;
    case 'image/png':  $srcImg = @imagecreatefrompng($src); break;
    case 'image/gif':  $srcImg = @imagecreatefromgif($src); break;
    case 'image/webp': $srcImg = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false; break;
    case 'image/avif': $srcImg = function_exists('imagecreatefromavif') ? @imagecreatefromavif($src) : false; break;
    case 'image/bmp':
    case 'image/x-ms-bmp': $srcImg = function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($src) : false; break;
  }
  if (!$srcImg) return false;

  if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
    $exif = @exif_read_data($src);
    if (!empty($exif['Orientation'])) {
      $rotated = false;
      switch ($exif['Orientation']) {
        case 3: $rotated = imagerotate($srcImg, 180, 0); break;
        case 6:
          $rotated = imagerotate($srcImg, -90, 0);
          list($origW, $origH) = [$origH, $origW];
          break;
        case 8:
          $rotated = imagerotate($srcImg, 90, 0);
          list($origW, $origH) = [$origH, $origW];
          break;
      }
      if ($rotated !== false) {
        imagedestroy($srcImg);
        $srcImg = $rotated;
        $ratio = min($size / $origW, $size / $origH);
        $newW = max(1, (int)round($origW * $ratio));
        $newH = max(1, (int)round($origH * $ratio));
      }
    }
  }

  $destImg = imagecreatetruecolor($newW, $newH);
  if (!$destImg) {
    imagedestroy($srcImg);
    return false;
  }

  // Pre-fill canvas with neutral dark container tone so transparent images blend cleanly
  $bg = imagecolorallocate($destImg, 33, 31, 38);
  imagefilledrectangle($destImg, 0, 0, $newW, $newH, $bg);
  imagecopyresampled($destImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

  $destDir = dirname($dest);
  if (!is_dir($destDir)) {
    @mkdir($destDir, 0777, true);
  }

  $ok = imagejpeg($destImg, $dest, $quality);
  imagedestroy($srcImg);
  imagedestroy($destImg);
  return $ok;
}

function streamRangeFile($path, $mime) {
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
  }
  @ini_set('zlib.output_compression', 'Off');
  if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
  }
  while (ob_get_level() > 0) {
    @ob_end_clean();
  }
  if (function_exists('set_time_limit')) {
    @set_time_limit(0);
  }

  $filesize = @filesize($path);
  if ($filesize === false || $filesize <= 0) {
    header('HTTP/1.1 404 Not Found');
    exit;
  }

  $start = 0;
  $end = $filesize - 1;
  $isRange = false;

  if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    if (preg_match('/bytes=\s*(\d+)?\s*-\s*(\d+)?/i', $range, $matches)) {
      if (!empty($matches[1])) {
        $start = floatval($matches[1]);
        if (!empty($matches[2])) {
          $end = min($filesize - 1, floatval($matches[2]));
        }
      } elseif (!empty($matches[2])) {
        $start = max(0, $filesize - floatval($matches[2]));
      }

      if ($start > $end || $start >= $filesize) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header('Content-Range: bytes */' . sprintf('%.0f', $filesize));
        exit;
      }
      $isRange = true;
    }
  }

  $length = $end - $start + 1;

  if ($isRange) {
    header('HTTP/1.1 206 Partial Content', true, 206);
    header('Content-Range: bytes ' . sprintf('%.0f-%.0f/%.0f', $start, $end, $filesize));
  } else {
    header('HTTP/1.1 200 OK', true, 200);
  }

  header('Content-Type: ' . $mime);
  header('Accept-Ranges: bytes');
  header('Content-Length: ' . sprintf('%.0f', $length));
  header('X-Accel-Buffering: no');
  header('X-Content-Type-Options: nosniff');
  $fn = basename($path);
  $fallbackFn = preg_replace('/[^\x20-\x7e]/', '_', $fn) ?: 'file';
  header("Content-Disposition: inline; filename=\"{$fallbackFn}\"; filename*=UTF-8''" . rawurlencode($fn));
  header('Cache-Control: public, max-age=31536000, immutable');
  header('Content-Transfer-Encoding: binary');

  $fp = @fopen($path, 'rb');
  if ($fp) {
    @flock($fp, LOCK_SH);
    if ($start > 0) {
      @fseek($fp, (int)$start, SEEK_SET);
    }
    $bytesLeft = $length;
    $bufferSize = ($length <= 2) ? 2 : 256 * 1024; // Instant Safari byte-probe + 256KB low-latency chunks
    while (!feof($fp) && $bytesLeft > 0) {
      if (connection_aborted()) break;
      $read = (int)min($bufferSize, $bytesLeft);
      $buff = fread($fp, $read);
      if ($buff === false || $buff === '') break;
      echo $buff;
      @flush();
      $bytesLeft -= strlen($buff);
    }
    @flock($fp, LOCK_UN);
    fclose($fp);
  }
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

function getDriveMeta($metaFile) {
  if (!file_exists($metaFile)) return ['starred' => [], 'shares' => [], 'trash' => []];
  $data = @json_decode(@file_get_contents($metaFile), true);
  return is_array($data) ? $data : ['starred' => [], 'shares' => [], 'trash' => []];
}

function saveDriveMeta($metaFile, $data) {
  @file_put_contents($metaFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function logDriveActivity($metaFile, $action, $relPath, $details = '') {
  $meta = getDriveMeta($metaFile);
  if (!isset($meta['activity'])) $meta['activity'] = [];
  array_unshift($meta['activity'], [
    'id'        => uniqid('act_'),
    'action'    => $action,
    'path'      => ltrim(str_replace(['\\', '//'], '/', $relPath), '/'),
    'name'      => basename($relPath),
    'details'   => $details,
    'timestamp' => time()
  ]);
  if (count($meta['activity']) > 300) {
    $meta['activity'] = array_slice($meta['activity'], 0, 300);
  }
  saveDriveMeta($metaFile, $meta);
}

function backupFileVersion($filePath, $config) {
  if (!file_exists($filePath) || is_dir($filePath)) return;
  $rel = ltrim(str_replace(['\\', '//'], '/', substr(realpath($filePath), strlen(realpath($config['root_dir'])))), '/');
  $hashDir = $config['version_dir'] . DIRECTORY_SEPARATOR . md5($rel);
  if (!is_dir($hashDir)) @mkdir($hashDir, 0777, true);
  $ts = date('Ymd_His');
  $dest = $hashDir . DIRECTORY_SEPARATOR . "{$ts}_" . basename($filePath);
  @copy($filePath, $dest);
}

function getFolderPreviewImage($dirPath, $relPath, $config) {
  $items = @scandir($dirPath) ?: [];
  $imageFiles = [];
  $otherFilesCount = 0;

  foreach ($items as $item) {
    if ($item === '.' || $item === '..' || substr($item, 0, 1) === '.') continue;
    $p = $dirPath . DIRECTORY_SEPARATOR . $item;
    if (is_file($p)) {
      $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
      if (in_array($ext, $config['image_extensions'])) {
        $imageFiles[] = ($relPath ? ($relPath . '/' . $item) : $item);
      } else {
        $otherFilesCount++;
      }
    }
  }

  if (empty($imageFiles)) return null;

  // If full of images (no non-image files): pick the first image sorted naturally
  if ($otherFilesCount === 0) {
    usort($imageFiles, fn($a, $b) => strnatcasecmp(basename($a), basename($b)));
    return $imageFiles[0];
  }

  // If mixed/random files: pick a random image
  return $imageFiles[array_rand($imageFiles)];
}

function cleanOldTrash($config) {
  $meta = getDriveMeta($config['meta_file']);
  $now = time();
  $updated = false;
  foreach ($meta['trash'] as $key => $item) {
    if ($now - ($item['trashed_at'] ?? 0) > (30 * 86400)) {
      $trashPath = $config['trash_dir'] . DIRECTORY_SEPARATOR . $item['trash_name'];
      if (file_exists($trashPath)) deleteRecursive($trashPath);
      unset($meta['trash'][$key]);
      $updated = true;
    }
  }
  if ($updated) saveDriveMeta($config['meta_file'], $meta);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$isAdmin = !$config['auth_enabled'] || !empty($_SESSION['authenticated']);
$isDemo = $config['auth_enabled'] && !$isAdmin;

if ($action === 'icon' || $action === 'app_icon') {
  header('Content-Type: image/svg+xml; charset=utf-8');
  header('Cache-Control: public, max-age=604800, immutable');
  echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="512" height="512" fill="#d0bcff">
  <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/>
</svg>
SVG;
  exit;
}

if ($action === 'og_image') {
  header('Content-Type: image/svg+xml; charset=utf-8');
  header('Cache-Control: public, max-age=604800, immutable');
  $title = htmlspecialchars($config['app_title']);
  echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630">
  <defs>
    <linearGradient id="cardGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#211f26"/>
      <stop offset="100%" stop-color="#141218"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="#141218"/>
  <rect x="40" y="40" width="1120" height="550" rx="32" fill="url(#cardGrad)" stroke="#49454f" stroke-width="2"/>
  
  <!-- Exact Folder SVG icon centered -->
  <g transform="translate(520, 110) scale(10)" fill="#d0bcff">
    <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/>
  </g>
  
  <text x="600" y="360" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="44" font-weight="700" fill="#e6e0e9" text-anchor="middle">{$title}</text>
  <text x="600" y="415" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="22" font-weight="500" fill="#cac4d0" text-anchor="middle">Self-Hosted Web Cloud Drive &amp; Media Studio</text>
  <text x="600" y="475" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="16" font-weight="400" fill="#938f99" text-anchor="middle">Single-File PHP • PWA Offline • Media Streaming • Document Studio</text>
</svg>
SVG;
  exit;
}

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

if ($action) {
  $writeActions = [
    'upload_chunk', 'create', 'rename', 'batch_rename', 'delete', 'save_text',
    'trash', 'trash_restore', 'trash_delete', 'trash_empty', 'version_restore',
    'star_toggle', 'clipboard_paste', 'fetch_url', 'encrypt_file',
    'decrypt_file', 'zip', 'unzip', 'save_image'
  ];

  if ($isDemo && in_array($action, $writeActions)) {
    jsonResponse(['error' => 'Demo mode: Read-only access. Please login as Admin.'], 403);
  }
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
      if (preg_match('/\.(part|crdownload|tmp|swp)$/i', $item)) continue;
      $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
      if ($itemPath === realpath($config['cache_dir'])) continue;

      $itemRel = $relDir ? ($relDir . '/' . $item) : $item;
      $mtime = @filemtime($itemPath);

      if (is_dir($itemPath)) {
        $validItems = 0;
        foreach (@scandir($itemPath) ?: [] as $subEntry) {
          if ($subEntry === '.' || $subEntry === '..' || $subEntry[0] === '.' || in_array($subEntry, ['.git', '.gallery_cache', '.drive_trash_bin', '.file_version']) || preg_match('/\.(part|crdownload|tmp|swp)$/i', $subEntry)) {
            continue;
          }
          $validItems++;
        }
        $folders[] = [
          'name'        => $item,
          'path'        => $itemRel,
          'mtime'       => $mtime,
          'items_count' => $validItems,
          'thumb_image' => getFolderPreviewImage($itemPath, $itemRel, $config),
        ];
      } else {
        $size = @filesize($itemPath);
        $totalSize += $size;
        $ext = strtolower(pathinfo($itemPath, PATHINFO_EXTENSION));
        $type = getFileType($ext, $config);

        $files[] = [
          'name'     => $item,
          'path'     => $itemRel,
          'size'     => $size,
          'size_fmt' => formatBytes($size),
          'mtime'    => $mtime,
          'ext'      => $ext,
          'type'     => $type,
          'width'    => 0,
          'height'   => 0,
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
    $extFilter = strtolower(trim($_GET['ext'] ?? ''));
    $typeFilter = strtolower(trim($_GET['type'] ?? ''));
    $dateFrom = !empty($_GET['date_from']) ? strtotime($_GET['date_from'] . ' 00:00:00') : 0;
    $dateTo = !empty($_GET['date_to']) ? strtotime($_GET['date_to'] . ' 23:59:59') : 0;
    $sizeMin = (isset($_GET['size_min']) && $_GET['size_min'] !== '') ? floatval($_GET['size_min']) : -1;
    $sizeMax = (isset($_GET['size_max']) && $_GET['size_max'] !== '') ? floatval($_GET['size_max']) : -1;

    $fullPath = safePath($config['root_dir'], $dir);
    $hasAdv = ($extFilter !== '' || $typeFilter !== '' || $dateFrom > 0 || $dateTo > 0 || $sizeMin >= 0 || $sizeMax >= 0);

    if (!$fullPath || !is_dir($fullPath) || ($query === '' && !$hasAdv)) {
      jsonResponse(['folders' => [], 'files' => [], 'query' => $query, 'count' => 0]);
    }

    $maxResults = 300;
    $foundFolders = [];
    $foundFiles = [];
    $rootLen = strlen(realpath($config['root_dir']));
    $cacheReal = realpath($config['cache_dir']);
    $trashReal = realpath($config['trash_dir']);
    $allowedExts = array_filter(array_map('trim', explode(',', str_replace('.', '', $extFilter))));

    $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS;
    $dirIterator = new RecursiveDirectoryIterator($fullPath, $flags);
    $filterIterator = new RecursiveCallbackFilterIterator($dirIterator, function ($current) use ($cacheReal, $trashReal) {
      $path = $current->getPathname();
      $filename = $current->getFilename();
      if ($filename[0] === '.' || ($cacheReal && strpos($path, $cacheReal) === 0) || ($trashReal && strpos($path, $trashReal) === 0)) {
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
      $isMatch = ($query === '' || stripos($name, $query) !== false);
      if (!$isMatch) continue;

      $itemPath = $item->getPathname();
      $rel = ltrim(str_replace(['\\', '//'], '/', substr($itemPath, $rootLen)), '/');
      $mtime = $item->getMTime();

      if ($item->isDir()) {
        if (!empty($allowedExts) || $typeFilter !== '' || $sizeMin >= 0 || $sizeMax >= 0) {
          continue;
        }
        if ($dateFrom > 0 && $mtime < $dateFrom) continue;
        if ($dateTo > 0 && $mtime > $dateTo) continue;

        $foundFolders[] = [
          'name'        => $name,
          'path'        => $rel,
          'mtime'       => $mtime,
          'items_count' => 0
        ];
        $count++;
      } else {
        $size = (float)$item->getSize();
        $ext = strtolower($item->getExtension());
        $type = getFileType($ext, $config);

        if (!empty($allowedExts) && !in_array($ext, $allowedExts)) continue;
        if ($typeFilter !== '' && $typeFilter !== 'all' && $type !== $typeFilter) continue;
        if ($dateFrom > 0 && $mtime < $dateFrom) continue;
        if ($dateTo > 0 && $mtime > $dateTo) continue;
        if ($sizeMin >= 0 && $size < $sizeMin) continue;
        if ($sizeMax >= 0 && $size > $sizeMax) continue;

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

  if ($action === 'gallery_list' || $action === 'video_list' || $action === 'audio_list' || $action === 'document_list') {
    $maxResults = 2000;
    $foundFiles = [];
    $rootLen = strlen(realpath($config['root_dir']));
    $cacheReal = realpath($config['cache_dir']);
    $trashReal = realpath($config['trash_dir']);

    $targetType = 'image';
    $targetExts = $config['image_extensions'];
    if ($action === 'video_list') {
      $targetType = 'video';
      $targetExts = $config['video_extensions'];
    } elseif ($action === 'audio_list') {
      $targetType = 'audio';
      $targetExts = $config['audio_extensions'];
    } elseif ($action === 'document_list') {
      $targetType = 'document';
      $docOfficeExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf', 'pages', 'ai', 'psd'];
      $targetExts = array_values(array_unique(array_merge($config['text_extensions'], $config['archive_extensions'], $docOfficeExts)));
    }

    $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS;
    $dirIterator = new RecursiveDirectoryIterator($config['root_dir'], $flags);
    $filterIterator = new RecursiveCallbackFilterIterator($dirIterator, function ($current) use ($cacheReal, $trashReal) {
      $path = $current->getPathname();
      $filename = $current->getFilename();
      if ($filename[0] === '.' || ($cacheReal && strpos($path, $cacheReal) === 0) || ($trashReal && strpos($path, $trashReal) === 0)) {
        return false;
      }
      return true;
    });

    $iterator = new RecursiveIteratorIterator($filterIterator, RecursiveIteratorIterator::SELF_FIRST);
    $iterator->setMaxDepth(10);

    $count = 0;
    $totalBytes = 0;
    foreach ($iterator as $item) {
      if ($item->isDir()) continue;
      $ext = strtolower($item->getExtension());
      if (in_array($ext, $targetExts)) {
        if ($count >= $maxResults) break;
        $size = $item->getSize();
        $totalBytes += $size;
        $rel = ltrim(str_replace(['\\', '//'], '/', substr($item->getRealPath(), $rootLen)), '/');
        $foundFiles[] = [
          'name'     => $item->getFilename(),
          'path'     => $rel,
          'size'     => $size,
          'size_fmt' => formatBytes($size),
          'mtime'    => $item->getMTime(),
          'ext'      => $ext,
          'type'     => $targetType,
          'width'    => 0,
          'height'   => 0
        ];
        $count++;
      }
    }

    usort($foundFiles, fn($a, $b) => $b['mtime'] - $a['mtime']);

    jsonResponse([
      'folders' => [],
      'files'   => $foundFiles,
      'stats'   => ['files' => count($foundFiles), 'folders' => 0, 'total_size' => formatBytes($totalBytes)]
    ]);
  }

  if ($action === 'tree') {
    function buildTree($base, $currentRel = '', $depth = 0) {
      if ($depth > 3) return [];
      $realBase = safePath($base, $currentRel);
      if (!$realBase || !is_dir($realBase)) return [];
      $items = @scandir($realBase) ?: [];
      $nodes = [];
      $skipDirs = ['.git', '.gallery_cache', '.drive_trash_bin', '.file_version', 'node_modules', 'vendor'];
      foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item[0] === '.' || in_array($item, $skipDirs)) continue;
        $full = $realBase . DIRECTORY_SEPARATOR . $item;
        if (!is_dir($full)) continue;
        $rel = $currentRel ? ($currentRel . '/' . $item) : $item;
        $nodes[] = [
          'name'     => $item,
          'path'     => $rel,
          'children' => buildTree($base, $rel, $depth + 1)
        ];
      }
      usort($nodes, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
      return $nodes;
    }
    jsonResponse(buildTree($config['root_dir']));
  }

  if ($action === 'thumb') {
    $file = $_GET['f'] ?? '';
    $fullPath = findRealFile($config['root_dir'], $file);
    if (!$fullPath) $fullPath = safePath($config['root_dir'], $file);

    if (!$fullPath || !is_file($fullPath)) {
      header('HTTP/1.0 404 Not Found');
      exit;
    }

    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    if ($ext === 'svg') {
      while (ob_get_level() > 0) @ob_end_clean();
      header('Content-Type: image/svg+xml');
      header('Cache-Control: public, max-age=31536000, immutable');
      readfile($fullPath);
      exit;
    }

    $mtime = (int)@filemtime($fullPath);
    $size = (int)@filesize($fullPath);
    $hash = md5($fullPath . $mtime . $size);
    $etag = '"' . $hash . '"';

    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
      header('HTTP/1.1 304 Not Modified');
      exit;
    }

    $cachePath = $config['cache_dir'] . DIRECTORY_SEPARATOR . $hash . '.jpg';

    if (!file_exists($cachePath) || filesize($cachePath) === 0) {
      if (in_array($ext, ['mp3', 'm4a', 'flac', 'mp4', 'mov', 'mkv', 'webm', 'ogg', 'wav', 'aac', 'opus', 'avi', 'ts', 'm4v'])) {
        $mediaMeta = getMediaMetadata($fullPath);
        if (!empty($mediaMeta['raw_cover'])) {
          $tmpCover = tempnam(sys_get_temp_dir(), 'cov_');
          @file_put_contents($tmpCover, $mediaMeta['raw_cover']);
          createThumbnail($tmpCover, $cachePath, $config['thumb_size'], $config['thumb_quality']);
          @unlink($tmpCover);
        }
      }

      if ((!file_exists($cachePath) || filesize($cachePath) === 0) && in_array($ext, $config['video_extensions']) && function_exists('exec')) {
        $escSrc = escapeshellarg($fullPath);
        $escCache = escapeshellarg($cachePath);
        $thumbSize = (int)$config['thumb_size'];
        @exec("ffmpeg -ss 00:00:01 -noaccurate_seek -i {$escSrc} -vframes 1 -an -sn -threads 2 -vf \"scale='min({$thumbSize},iw)':-2\" -q:v 3 -y {$escCache} 2>&1");
      }

      if ((!file_exists($cachePath) || filesize($cachePath) === 0) && in_array($ext, $config['image_extensions'])) {
        createThumbnail($fullPath, $cachePath, $config['thumb_size'], $config['thumb_quality']);
      }
    }

    while (ob_get_level() > 0) @ob_end_clean();

    if (file_exists($cachePath) && filesize($cachePath) > 0) {
      header('Content-Type: image/jpeg');
      header('ETag: ' . $etag);
      header('Cache-Control: public, max-age=31536000, immutable');
      header('Content-Length: ' . filesize($cachePath));
      readfile($cachePath);
    } else {
      $mime = @mime_content_type($fullPath) ?: 'application/octet-stream';
      if (strpos($mime, 'image/') === 0) {
        header('Content-Type: ' . $mime);
        header('ETag: ' . $etag);
        readfile($fullPath);
      } else {
        header('HTTP/1.0 404 Not Found');
      }
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
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mimeMap = [
      'mp4'  => 'video/mp4',
      'webm' => 'video/webm',
      'mov'  => 'video/quicktime',
      'm4v'  => 'video/mp4',
      'ogv'  => 'video/ogg',
      'mkv'  => 'video/x-matroska',
      'avi'  => 'video/x-msvideo',
      'ts'   => 'video/mp2t',
      '3gp'  => 'video/3gpp',
      'wmv'  => 'video/x-ms-wmv',
      'flv'  => 'video/x-flv',
      'mp3'  => 'audio/mpeg',
      'wav'  => 'audio/wav',
      'ogg'  => 'audio/ogg',
      'flac' => 'audio/flac',
      'm4a'  => 'audio/mp4',
      'aac'  => 'audio/aac',
      'opus' => 'audio/opus',
      'wma'  => 'audio/x-ms-wma',
      'm4r'  => 'audio/mp4',
      'mid'  => 'audio/midi',
      'midi' => 'audio/midi',
      'svg'  => 'image/svg+xml',
      'js'   => 'application/javascript',
      'css'  => 'text/css',
      'pdf'  => 'application/pdf',
    ];
    $mime = $mimeMap[$ext] ?? (mime_content_type($fullPath) ?: 'application/octet-stream');

    streamRangeFile($fullPath, $mime);
  }

  if ($action === 'download') {
    $file = $_GET['f'] ?? '';
    $fullPath = safePath($config['root_dir'], $file);
    if (!$fullPath || !is_file($fullPath)) jsonResponse(['error' => 'File not found'], 404);

    $fn = basename($fullPath);
    $fallbackFn = preg_replace('/[^\x20-\x7e]/', '_', $fn) ?: 'file';

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"{$fallbackFn}\"; filename*=UTF-8''" . rawurlencode($fn));
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
    $media = getMediaMetadata($fullPath);
    $meta['type'] = 'file';
    $meta['title'] = basename($fullPath);
    $meta['media'] = [
      'tags'      => $media['tags'] ?? [],
      'cover_art' => $media['cover_art'] ?? null
    ];
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
    
    // Unicode-safe sanitization: strips illegal filesystem and control characters across all languages
    $rawBaseName = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_POST['file_name'] ?? ''));
    $fileName = trim(preg_replace('/[\x00-\x1f\x7f\/:*?"<>|\\\\]/u', '', $rawBaseName));
    if ($fileName === '') {
      $fileName = 'upload_' . date('Ymd_His');
    }
    
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

      // Automatically duplicate into "filename_(1).ext" if an existing file is found
      $ext = pathinfo($fileName, PATHINFO_EXTENSION);
      $baseName = pathinfo($fileName, PATHINFO_FILENAME);
      $finalName = $fileName;
      $finalDest = $destDir . DIRECTORY_SEPARATOR . $finalName;

      $counter = 1;
      while (file_exists($finalDest)) {
        $finalName = ($ext !== '') ? "{$baseName}_({$counter}).{$ext}" : "{$baseName}_({$counter})";
        $finalDest = $destDir . DIRECTORY_SEPARATOR . $finalName;
        $counter++;
      }

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

      $uploadedRel = ltrim(str_replace(['\\', '//'], '/', substr(realpath($finalDest), strlen(realpath($config['root_dir'])))), '/');
      logDriveActivity($config['meta_file'], 'uploaded', $uploadedRel, 'Uploaded file');

      jsonResponse(['success' => true, 'completed' => true, 'file' => $finalName]);
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

    $newName = preg_replace('/[^\w\s\d\.\-_~()[\]]/u', '', $newName);
    $dest = dirname($item) . DIRECTORY_SEPARATOR . $newName;
    if (file_exists($dest)) jsonResponse(['error' => 'Destination already exists'], 400);

    $renamed = @rename($item, $dest);
    if ($renamed) {
      logDriveActivity($config['meta_file'], 'renamed', $newName, 'Renamed from ' . basename($item));
    }
    jsonResponse(['success' => $renamed]);
  }

  if ($action === 'batch_rename') {
    if (!$config['allow_rename']) jsonResponse(['error' => 'Rename disabled'], 403);
    $renames = json_decode($_POST['renames'] ?? '[]', true);
    if (!is_array($renames) || empty($renames)) jsonResponse(['error' => 'No rename items provided'], 400);

    $successCount = 0;
    $errors = [];

    foreach ($renames as $task) {
      $oldPath = $task['path'] ?? '';
      $newName = trim($task['new_name'] ?? '');
      if (!$oldPath || !$newName) continue;

      $fullSrc = safePath($config['root_dir'], $oldPath);
      if (!$fullSrc || !file_exists($fullSrc)) {
        $errors[] = "Source not found: " . basename($oldPath);
        continue;
      }

      $cleanName = preg_replace('/[^\w\s\d\.\-_~()[\]]/u', '', $newName);
      if (empty($cleanName) || $cleanName === basename($fullSrc)) continue;

      $destDir = dirname($fullSrc);
      $targetPath = $destDir . DIRECTORY_SEPARATOR . $cleanName;

      if (file_exists($targetPath)) {
        $errors[] = "File already exists: {$cleanName}";
        continue;
      }

      if (@rename($fullSrc, $targetPath)) {
        $successCount++;
        logDriveActivity($config['meta_file'], 'renamed', $cleanName, 'Renamed from ' . basename($fullSrc));
      } else {
        $errors[] = "Could not rename: " . basename($fullSrc);
      }
    }

    jsonResponse([
      'success' => true,
      'renamed_count' => $successCount,
      'errors' => $errors
    ]);
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
    $fullPath = findRealFile($config['root_dir'], $file);
    if (!$fullPath || !is_file($fullPath)) {
      jsonResponse(['error' => 'File not found'], 404);
    }
    jsonResponse([
      'content' => @file_get_contents($fullPath),
      'real_file' => ltrim(str_replace(['\\', '//'], '/', substr(realpath($fullPath), strlen(realpath($config['root_dir'])))), '/')
    ]);
  }

  if ($action === 'save_text') {
    if (!$config['allow_edit']) jsonResponse(['error' => 'Edit disabled'], 403);
    $file = $_POST['f'] ?? '';
    $content = $_POST['content'] ?? '';
    $fullPath = findRealFile($config['root_dir'], $file);
    if (!$fullPath) {
      $fullPath = safePath($config['root_dir'], $file);
      $parentDir = dirname($fullPath);
      if (!is_dir($parentDir)) @mkdir($parentDir, 0777, true);
    }

    backupFileVersion($fullPath, $config);
    $saved = @file_put_contents($fullPath, $content) !== false;
    if ($saved) {
      logDriveActivity($config['meta_file'], 'modified', $file, 'File edited and saved');
    }
    jsonResponse(['success' => $saved]);
  }

  if ($action === 'save_image') {
    if (!$config['allow_edit']) jsonResponse(['error' => 'Editing disabled'], 403);
    $file = $_POST['f'] ?? '';
    $dataUrl = $_POST['image_data'] ?? '';
    $saveMode = $_POST['save_mode'] ?? 'overwrite'; // 'overwrite' or 'copy'
    $fullPath = findRealFile($config['root_dir'], $file);
    if (!$fullPath) $fullPath = safePath($config['root_dir'], $file);
    if (!$fullPath || !is_file($fullPath)) jsonResponse(['error' => 'Image file not found'], 404);

    if (preg_match('/^data:image\/(png|jpeg|webp);base64,(.+)$/i', $dataUrl, $matches)) {
      $imgData = base64_decode($matches[2]);
      if ($imgData !== false) {
        $targetPath = $fullPath;
        $finalName = basename($fullPath);

        if ($saveMode === 'copy') {
          $dir = dirname($fullPath);
          $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
          $base = pathinfo($fullPath, PATHINFO_FILENAME);
          $counter = 1;
          do {
            $finalName = "{$base}_edited_({$counter})" . ($ext ? ".{$ext}" : '');
            $targetPath = $dir . DIRECTORY_SEPARATOR . $finalName;
            $counter++;
          } while (file_exists($targetPath));
        } else {
          backupFileVersion($fullPath, $config);
        }

        $saved = @file_put_contents($targetPath, $imgData) !== false;
        if ($saved) {
          if ($saveMode === 'overwrite') {
            $mtime = (int)@filemtime($targetPath);
            $size = (int)@filesize($targetPath);
            $cachePath = $config['cache_dir'] . DIRECTORY_SEPARATOR . md5($targetPath . $mtime . $size) . '.jpg';
            if (file_exists($cachePath)) @unlink($cachePath);
          }
          logDriveActivity($config['meta_file'], $saveMode === 'copy' ? 'uploaded' : 'modified', $targetPath, $saveMode === 'copy' ? 'Saved edited copy' : 'Edited image');
          jsonResponse(['success' => true, 'filename' => $finalName, 'is_copy' => $saveMode === 'copy']);
        }
      }
    }
    jsonResponse(['error' => 'Failed to save image payload'], 500);
  }

  if ($action === 'trash') {
    if (!$config['allow_delete']) jsonResponse(['error' => 'Delete disabled'], 403);
    $items = $_POST['items'] ?? [];
    if (!is_array($items) || empty($items)) jsonResponse(['error' => 'No items specified'], 400);

    cleanOldTrash($config);
    $meta = getDriveMeta($config['meta_file']);
    $trashed = 0;

    foreach ($items as $rel) {
      $full = safePath($config['root_dir'], $rel);
      if ($full && $full !== realpath($config['root_dir']) && file_exists($full)) {
        $trashId = uniqid('trash_') . '_' . basename($full);
        $dest = $config['trash_dir'] . DIRECTORY_SEPARATOR . $trashId;
        if (@rename($full, $dest)) {
          $meta['trash'][$trashId] = [
            'trash_name'   => $trashId,
            'original_rel' => $rel,
            'original_name'=> basename($full),
            'trashed_at'   => time(),
            'is_dir'       => is_dir($dest)
          ];
          $trashed++;
        }
      }
    }
    saveDriveMeta($config['meta_file'], $meta);
    foreach ($items as $rel) {
      logDriveActivity($config['meta_file'], 'trashed', $rel, 'Moved to trash');
    }
    jsonResponse(['success' => true, 'trashed_count' => $trashed]);
  }

  if ($action === 'trash_list') {
    cleanOldTrash($config);
    $meta = getDriveMeta($config['meta_file']);
    jsonResponse(['trash' => array_values($meta['trash'] ?? [])]);
  }

  if ($action === 'trash_restore') {
    $trashId = $_POST['trash_id'] ?? '';
    $meta = getDriveMeta($config['meta_file']);
    if (!isset($meta['trash'][$trashId])) jsonResponse(['error' => 'Trash item not found'], 404);

    $info = $meta['trash'][$trashId];
    $source = $config['trash_dir'] . DIRECTORY_SEPARATOR . $info['trash_name'];
    $target = safePath($config['root_dir'], $info['original_rel']);

    if (!file_exists($source)) {
      unset($meta['trash'][$trashId]);
      saveDriveMeta($config['meta_file'], $meta);
      jsonResponse(['error' => 'Source file missing'], 404);
    }

    if (file_exists($target)) {
      $dir = dirname($target);
      $ext = pathinfo($target, PATHINFO_EXTENSION);
      $base = pathinfo($target, PATHINFO_FILENAME);
      $counter = 1;
      while (file_exists($target)) {
        $target = $dir . DIRECTORY_SEPARATOR . "{$base}_restored_{$counter}" . ($ext ? ".{$ext}" : '');
        $counter++;
      }
    }

    if (@rename($source, $target)) {
      unset($meta['trash'][$trashId]);
      saveDriveMeta($config['meta_file'], $meta);
      jsonResponse(['success' => true]);
    }
    jsonResponse(['error' => 'Failed to restore item'], 500);
  }

  if ($action === 'trash_delete') {
    if (!$config['allow_delete']) jsonResponse(['error' => 'Delete disabled'], 403);
    $trashId = $_POST['trash_id'] ?? '';
    $meta = getDriveMeta($config['meta_file']);
    if (isset($meta['trash'][$trashId])) {
      $p = $config['trash_dir'] . DIRECTORY_SEPARATOR . $meta['trash'][$trashId]['trash_name'];
      if (file_exists($p)) deleteRecursive($p);
      unset($meta['trash'][$trashId]);
      saveDriveMeta($config['meta_file'], $meta);
      jsonResponse(['success' => true]);
    }
    jsonResponse(['error' => 'Trash item not found'], 404);
  }

  if ($action === 'trash_empty') {
    $meta = getDriveMeta($config['meta_file']);
    foreach ($meta['trash'] as $item) {
      $p = $config['trash_dir'] . DIRECTORY_SEPARATOR . $item['trash_name'];
      if (file_exists($p)) deleteRecursive($p);
    }
    $meta['trash'] = [];
    saveDriveMeta($config['meta_file'], $meta);
    jsonResponse(['success' => true]);
  }

  if ($action === 'versions_list') {
    $file = $_GET['f'] ?? '';
    $full = safePath($config['root_dir'], $file);
    if (!$full || !is_file($full)) jsonResponse(['versions' => []]);

    $rel = ltrim(str_replace(['\\', '//'], '/', substr(realpath($full), strlen(realpath($config['root_dir'])))), '/');
    $hashDir = $config['version_dir'] . DIRECTORY_SEPARATOR . md5($rel);
    $versions = [];

    if (is_dir($hashDir)) {
      foreach (scandir($hashDir) as $v) {
        if ($v === '.' || $v === '..') continue;
        $vp = $hashDir . DIRECTORY_SEPARATOR . $v;
        $versions[] = [
          'filename' => $v,
          'mtime'    => filemtime($vp),
          'size'     => formatBytes(filesize($vp)),
          'date'     => date('Y-m-d H:i:s', filemtime($vp))
        ];
      }
      usort($versions, fn($a, $b) => $b['mtime'] - $a['mtime']);
    }
    jsonResponse(['versions' => $versions]);
  }

  if ($action === 'version_read') {
    $file = $_GET['f'] ?? '';
    $versionName = $_GET['version'] ?? '';
    $full = safePath($config['root_dir'], $file);
    if (!$full || !is_file($full)) jsonResponse(['error' => 'Target file not found'], 404);

    $rel = ltrim(str_replace(['\\', '//'], '/', substr(realpath($full), strlen(realpath($config['root_dir'])))), '/');
    $vFile = $config['version_dir'] . DIRECTORY_SEPARATOR . md5($rel) . DIRECTORY_SEPARATOR . basename($versionName);
    if (!file_exists($vFile)) jsonResponse(['error' => 'Version snapshot not found'], 404);

    jsonResponse([
      'success'  => true,
      'current'  => @file_get_contents($full),
      'version'  => @file_get_contents($vFile),
      'filename' => basename($versionName)
    ]);
  }

  if ($action === 'version_restore') {
    $file = $_POST['f'] ?? '';
    $versionName = $_POST['version'] ?? '';
    $full = safePath($config['root_dir'], $file);
    if (!$full || !is_file($full)) jsonResponse(['error' => 'Target file not found'], 404);

    $rel = ltrim(str_replace(['\\', '//'], '/', substr(realpath($full), strlen(realpath($config['root_dir'])))), '/');
    $vFile = $config['version_dir'] . DIRECTORY_SEPARATOR . md5($rel) . DIRECTORY_SEPARATOR . basename($versionName);
    if (!file_exists($vFile)) jsonResponse(['error' => 'Version file not found'], 404);

    backupFileVersion($full, $config);
    $restored = @copy($vFile, $full);
    if ($restored) {
      logDriveActivity($config['meta_file'], 'restored', $file, 'Restored version ' . basename($versionName));
    }
    jsonResponse(['success' => $restored]);
  }

  if ($action === 'star_toggle') {
    $path = trim($_POST['path'] ?? '');
    $meta = getDriveMeta($config['meta_file']);
    $starred = $meta['starred'] ?? [];
    if (in_array($path, $starred)) {
      $starred = array_values(array_diff($starred, [$path]));
      $isStarred = false;
    } else {
      $starred[] = $path;
      $isStarred = true;
    }
    $meta['starred'] = $starred;
    saveDriveMeta($config['meta_file'], $meta);
    jsonResponse(['success' => true, 'is_starred' => $isStarred]);
  }

  if ($action === 'starred_list') {
    $meta = getDriveMeta($config['meta_file']);
    $starred = $meta['starred'] ?? [];
    $folders = [];
    $files = [];
    $totalSize = 0;

    foreach ($starred as $rel) {
      $full = safePath($config['root_dir'], $rel);
      if ($full && file_exists($full)) {
        $isDir = is_dir($full);
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $type = $isDir ? 'folder' : getFileType($ext, $config);
        $size = $isDir ? 0 : @filesize($full);
        if (!$isDir) $totalSize += $size;

        $width = 0;
        $height = 0;
        if ($type === 'image') {
          $imgSize = @getimagesize($full);
          if ($imgSize) {
            $width = $imgSize[0];
            $height = $imgSize[1];
          }
        }

        $itemData = [
          'name'        => basename($full),
          'path'        => $rel,
          'is_dir'      => $isDir,
          'size'        => $size,
          'size_fmt'    => $isDir ? '' : formatBytes($size),
          'mtime'       => @filemtime($full),
          'ext'         => $ext,
          'type'        => $type,
          'width'       => $width,
          'height'      => $height,
          'thumb_image' => $isDir ? getFolderPreviewImage($full, $rel, $config) : null,
          'items_count' => $isDir ? count(array_diff(@scandir($full) ?: [], ['.', '..', '.gallery_cache'])) : 0
        ];

        if ($isDir) {
          $folders[] = $itemData;
        } else {
          $files[] = $itemData;
        }
      }
    }
    jsonResponse([
      'starred'       => array_merge($folders, $files),
      'starred_paths' => array_values($starred),
      'folders'       => $folders,
      'files'         => $files,
      'stats'         => [
        'folders'    => count($folders),
        'files'      => count($files),
        'total_size' => formatBytes($totalSize)
      ]
    ]);
  }

  if ($action === 'recents_list') {
    $max = 100;
    $folders = [];
    $files = [];
    $seen = [];
    $realRoot = realpath($config['root_dir']);
    $rootLen = strlen($realRoot);
    $ignoreDirs = [
      realpath($config['cache_dir']),
      realpath($config['trash_dir']),
      realpath($config['version_dir'])
    ];

    $dirIterator = new RecursiveDirectoryIterator($config['root_dir'], FilesystemIterator::SKIP_DOTS);
    $filterIterator = new RecursiveCallbackFilterIterator($dirIterator, function ($current) use ($ignoreDirs) {
      $path = $current->getPathname();
      $filename = $current->getFilename();
      if ($filename[0] === '.' || in_array($filename, ['.git', '.gallery_cache', '.drive_trash_bin', '.file_version', 'node_modules', 'vendor'])) {
        return false;
      }
      foreach ($ignoreDirs as $ign) {
        if ($ign && strpos($path, $ign) === 0) return false;
      }
      return true;
    });

    $it = new RecursiveIteratorIterator($filterIterator, RecursiveIteratorIterator::SELF_FIRST);
    $it->setMaxDepth(6);

    foreach ($it as $item) {
      $realPath = $item->getRealPath();
      if (!$realPath || isset($seen[$realPath])) continue;
      $seen[$realPath] = true;

      $rel = ltrim(str_replace(['\\', '//'], '/', substr($realPath, $rootLen)), '/');
      if ($rel === '') continue;

      $mtime = (int)$item->getMTime();

      if ($item->isDir()) {
        $subCount = count(array_diff(@scandir($realPath) ?: [], ['.', '..', '.gallery_cache', '.drive_trash_bin', '.file_version']));
        $folders[] = [
          'name'        => $item->getFilename(),
          'path'        => $rel,
          'mtime'       => $mtime,
          'items_count' => $subCount,
          'thumb_image' => getFolderPreviewImage($realPath, $rel, $config)
        ];
      } else {
        if (preg_match('/\.(part|crdownload|tmp|swp)$/i', $item->getFilename())) continue;
        $size = (float)$item->getSize();
        $ext = strtolower($item->getExtension());

        $files[] = [
          'name'     => $item->getFilename(),
          'path'     => $rel,
          'size'     => $size,
          'size_fmt' => formatBytes($size),
          'mtime'    => $mtime,
          'ext'      => $ext,
          'type'     => getFileType($ext, $config),
          'width'    => 0,
          'height'   => 0
        ];
      }
    }

    usort($folders, fn($a, $b) => $b['mtime'] - $a['mtime']);
    usort($files, fn($a, $b) => $b['mtime'] - $a['mtime']);

    jsonResponse([
      'folders' => array_slice($folders, 0, $max),
      'files'   => array_slice($files, 0, $max)
    ]);
  }

  if ($action === 'clipboard_paste') {
    @ini_set('memory_limit', '512M');
    if (function_exists('set_time_limit')) @set_time_limit(0);

    $targetDir = safePath($config['root_dir'], $_POST['target_dir'] ?? '');
    $op = $_POST['operation'] ?? 'copy'; // 'copy' or 'cut'
    $items = $_POST['items'] ?? [];
    if (!$targetDir || !is_dir($targetDir) || !is_array($items)) jsonResponse(['error' => 'Invalid parameters'], 400);

    $processed = 0;
    foreach ($items as $rel) {
      $src = safePath($config['root_dir'], $rel);
      if (!$src || !file_exists($src)) continue;
      $name = basename($src);
      $dest = $targetDir . DIRECTORY_SEPARATOR . $name;

      if (file_exists($dest)) {
        if (is_dir($src)) {
          $counter = 1;
          while (file_exists($dest)) {
            $dest = $targetDir . DIRECTORY_SEPARATOR . "{$name}_({$counter})";
            $counter++;
          }
        } else {
          $ext = pathinfo($name, PATHINFO_EXTENSION);
          $baseName = pathinfo($name, PATHINFO_FILENAME);
          $counter = 1;
          while (file_exists($dest)) {
            $dest = $targetDir . DIRECTORY_SEPARATOR . "{$baseName}_({$counter})" . ($ext !== '' ? ".{$ext}" : '');
            $counter++;
          }
        }
      }

      if ($op === 'cut') {
        if (@rename($src, $dest)) {
          $processed++;
          logDriveActivity($config['meta_file'], 'moved', $dest, 'Moved item');
        }
      } else {
        if (is_dir($src)) {
          // Recursive copy with duplicate handling
          $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
          @mkdir($dest, 0777, true);
          foreach ($it as $item) {
            $subDest = $dest . DIRECTORY_SEPARATOR . $it->getSubPathName();
            if ($item->isDir()) {
              @mkdir($subDest, 0777, true);
            } else {
              @copy($item->getPathname(), $subDest);
            }
          }
          $processed++;
          logDriveActivity($config['meta_file'], 'copied', $dest, 'Copied folder');
        } else {
          if (@copy($src, $dest)) {
            $processed++;
            logDriveActivity($config['meta_file'], 'copied', $dest, 'Copied file');
          }
        }
      }
    }
    jsonResponse(['success' => true, 'processed' => $processed]);
  }

  if ($action === 'fetch_url') {
    if (!$config['allow_upload']) jsonResponse(['error' => 'Upload disabled'], 403);
    $url = trim($_POST['url'] ?? '');
    $customName = trim($_POST['custom_name'] ?? '');
    $targetDir = safePath($config['root_dir'], $_POST['dir'] ?? '');

    if (!$url || !$targetDir || !is_dir($targetDir) || !is_writable($targetDir)) {
      jsonResponse(['error' => 'Invalid or unwritable destination directory'], 400);
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
      $url = 'https://' . $url;
    }

    $tempDest = tempnam(sys_get_temp_dir(), 'phpfiles_dl_');
    $fp = @fopen($tempDest, 'wb');
    if (!$fp) {
      jsonResponse(['error' => 'Cannot create temporary download buffer'], 500);
    }

    $responseHeaders = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_FILE           => $fp,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS      => 10,
      CURLOPT_TIMEOUT        => 300,
      CURLOPT_CONNECTTIMEOUT => 15,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
      CURLOPT_AUTOREFERER    => true,
      CURLOPT_ENCODING       => '',
      CURLOPT_HEADERFUNCTION => function($curl, $header) use (&$responseHeaders) {
        $len = strlen($header);
        $headerParts = explode(':', $header, 2);
        if (count($headerParts) === 2) {
          $responseHeaders[strtolower(trim($headerParts[0]))] = trim($headerParts[1]);
        }
        return $len;
      }
    ]);

    $success = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
    curl_close($ch);
    fclose($fp);

    if (!$success || $httpCode < 200 || $httpCode >= 400 || !file_exists($tempDest) || filesize($tempDest) <= 0) {
      @unlink($tempDest);
      $msg = $curlError ? $curlError : "Remote server returned HTTP {$httpCode}";
      jsonResponse(['error' => "Download failed: {$msg}"], 500);
    }

    // Determine final filename: 1. Custom Name -> 2. Content-Disposition -> 3. URL Path -> 4. Content-Type Fallback
    $detectedName = '';
    if (!empty($customName)) {
      $detectedName = $customName;
    } elseif (!empty($responseHeaders['content-disposition'])) {
      $cd = $responseHeaders['content-disposition'];
      if (preg_match('/filename\*=UTF-8\'\'([^;\r\n]+)/i', $cd, $m)) {
        $detectedName = rawurldecode($m[1]);
      } elseif (preg_match('/filename="?([^";\r\n]+)"?/i', $cd, $m)) {
        $detectedName = $m[1];
      }
    }

    if (empty($detectedName)) {
      $pathFromUrl = parse_url($effectiveUrl, PHP_URL_PATH);
      $detectedName = $pathFromUrl ? rawurldecode(basename($pathFromUrl)) : '';
    }

    $detectedName = preg_replace('/[^\w\s\d\.\-_~()[\]]/u', '', trim($detectedName));
    $ext = strtolower(pathinfo($detectedName, PATHINFO_EXTENSION));

    if (!$ext) {
      $ct = explode(';', $responseHeaders['content-type'] ?? '')[0];
      $mimeMap = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
        'image/avif' => 'avif', 'image/bmp' => 'bmp', 'image/svg+xml' => 'svg', 'video/mp4' => 'mp4',
        'video/webm' => 'webm', 'video/x-matroska' => 'mkv', 'video/quicktime' => 'mov',
        'audio/mpeg' => 'mp3', 'audio/wav' => 'wav', 'audio/ogg' => 'ogg', 'audio/flac' => 'flac',
        'audio/mp4' => 'm4a', 'application/pdf' => 'pdf', 'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip', 'application/json' => 'json', 'text/plain' => 'txt',
        'text/html' => 'html', 'text/css' => 'css', 'application/javascript' => 'js'
      ];
      $ext = $mimeMap[strtolower(trim($ct))] ?? '';
    }

    $baseName = pathinfo($detectedName, PATHINFO_FILENAME);
    if (!$baseName) {
      $baseName = 'download_' . date('Ymd_His');
    }

    $finalName = $ext ? "{$baseName}.{$ext}" : $baseName;
    $dest = $targetDir . DIRECTORY_SEPARATOR . $finalName;

    $counter = 1;
    while (file_exists($dest)) {
      $finalName = $ext ? "{$baseName}_({$counter}).{$ext}" : "{$baseName}_({$counter})";
      $dest = $targetDir . DIRECTORY_SEPARATOR . $finalName;
      $counter++;
    }

    if (@rename($tempDest, $dest) || @copy($tempDest, $dest)) {
      @unlink($tempDest);
      jsonResponse(['success' => true, 'filename' => $finalName]);
    }

    @unlink($tempDest);
    jsonResponse(['error' => 'Failed to save downloaded file to storage'], 500);
  }

  if ($action === 'encrypt_file' || $action === 'decrypt_file') {
    $file = findRealFile($config['root_dir'], $_POST['f'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    if ($pass === '') $pass = $config['encryption_key'];
    if (!$file || !is_file($file)) jsonResponse(['error' => 'File not found'], 404);

    $content = @file_get_contents($file);
    if ($content === false) jsonResponse(['error' => 'Cannot read file content'], 500);
    backupFileVersion($file, $config);

    $key = hash('sha256', $pass, true);

    if ($action === 'encrypt_file') {
      $iv = openssl_random_pseudo_bytes(16);
      $ciphertext = openssl_encrypt($content, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
      if ($ciphertext === false) jsonResponse(['error' => 'Encryption failed'], 500);

      $final = base64_encode($iv . $ciphertext);
      $newPath = dirname($file) . DIRECTORY_SEPARATOR . basename($file) . '.enc';
      if (@file_put_contents($newPath, $final) === false) {
        jsonResponse(['error' => 'Cannot write encrypted file'], 500);
      }
      @unlink($file);
      logDriveActivity($config['meta_file'], 'modified', $newPath, 'Encrypted file');
      jsonResponse(['success' => true, 'file' => basename($newPath)]);
    } else {
      $rawPayload = base64_decode($content, true);
      if ($rawPayload === false || strlen($rawPayload) < 17) {
        jsonResponse(['error' => 'Invalid or corrupted encrypted payload'], 400);
      }

      $iv = substr($rawPayload, 0, 16);
      $ciphertext = substr($rawPayload, 16);
      $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

      if ($decrypted === false) {
        jsonResponse(['error' => 'Decryption failed. Incorrect password.'], 400);
      }

      $newPath = preg_replace('/\.enc$/i', '', $file);
      if ($newPath === $file) $newPath .= '_decrypted';
      if (@file_put_contents($newPath, $decrypted) === false) {
        jsonResponse(['error' => 'Cannot write decrypted file'], 500);
      }
      @unlink($file);
      logDriveActivity($config['meta_file'], 'modified', $newPath, 'Decrypted file');
      jsonResponse(['success' => true, 'file' => basename($newPath)]);
    }
  }

  if ($action === 'share_create') {
    $file = safePath($config['root_dir'], $_POST['f'] ?? '');
    if (!$file || !is_file($file)) jsonResponse(['error' => 'File not found'], 404);

    $token = bin2hex(random_bytes(16));
    $meta = getDriveMeta($config['meta_file']);
    $meta['shares'][$token] = [
      'rel'     => ltrim(str_replace(['\\', '//'], '/', substr(realpath($file), strlen(realpath($config['root_dir'])))), '/'),
      'created' => time()
    ];
    saveDriveMeta($config['meta_file'], $meta);
    jsonResponse(['success' => true, 'token' => $token]);
  }

  if ($action === 'archive_preview') {
    $file = findRealFile($config['root_dir'], $_GET['f'] ?? '');
    if (!$file || !is_file($file)) jsonResponse(['error' => 'Archive file not found'], 404);

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $entries = [];
    $totalSize = 0;

    if ($ext === 'zip' && class_exists('ZipArchive')) {
      $zip = new ZipArchive();
      if ($zip->open($file) === true) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
          $stat = $zip->statIndex($i);
          $isDir = substr($stat['name'], -1) === '/';
          $size = (float)($stat['size'] ?? 0);
          if (!$isDir) $totalSize += $size;
          $entries[] = [
            'name'       => $stat['name'],
            'size'       => $size,
            'size_fmt'   => $isDir ? '' : formatBytes($size),
            'comp_size'  => formatBytes((float)($stat['comp_size'] ?? 0)),
            'mtime'      => $stat['mtime'] ? date('Y-m-d H:i:s', $stat['mtime']) : '',
            'is_dir'     => $isDir
          ];
        }
        $zip->close();
      }
    } elseif (in_array($ext, ['tar', 'gz', 'tgz']) && class_exists('PharData')) {
      try {
        $phar = new PharData($file);
        foreach (new RecursiveIteratorIterator($phar) as $item) {
          $isDir = $item->isDir();
          $size = (float)$item->getSize();
          if (!$isDir) $totalSize += $size;
          $entries[] = [
            'name'      => $item->getPathname(),
            'size'      => $size,
            'size_fmt'  => $isDir ? '' : formatBytes($size),
            'comp_size' => '-',
            'mtime'     => date('Y-m-d H:i:s', $item->getMTime()),
            'is_dir'    => $isDir
          ];
        }
      } catch (Exception $e) {}
    } elseif ($ext === 'rar' && class_exists('RarArchive')) {
      $rar = @RarArchive::open($file);
      if ($rar) {
        $list = @$rar->getEntries();
        if ($list) {
          foreach ($list as $entry) {
            $isDir = $entry->isDirectory();
            $size = (float)$entry->getUnpackedSize();
            if (!$isDir) $totalSize += $size;
            $entries[] = [
              'name'      => $entry->getName(),
              'size'      => $size,
              'size_fmt'  => $isDir ? '' : formatBytes($size),
              'comp_size' => formatBytes((float)$entry->getPackedSize()),
              'mtime'     => $entry->getFileTime(),
              'is_dir'    => $isDir
            ];
          }
        }
        $rar->close();
      }
    }

    jsonResponse([
      'success'        => true,
      'archive_name'   => basename($file),
      'path'           => $_GET['f'],
      'entries'        => $entries,
      'total_elements' => count($entries),
      'total_size'     => formatBytes($totalSize)
    ]);
  }

  if ($action === 'zip') {
    if (!$config['allow_zip']) jsonResponse(['error' => 'Compression disabled'], 403);
    $parent = safePath($config['root_dir'], $_POST['dir'] ?? '');
    $items = $_POST['items'] ?? [];
    $format = strtolower(trim($_POST['format'] ?? 'zip'));
    $zipName = trim($_POST['zip_name'] ?? ('archive.' . ($format === 'tar.gz' ? 'tar.gz' : $format)));

    if (!$parent || empty($items)) jsonResponse(['error' => 'Invalid parameters'], 400);

    $zipName = preg_replace('/[^\w\s\d\.\-_~()[\]]/u', '', $zipName);
    
    // Auto-increment duplicate naming for archives
    $ext = '';
    $baseName = $zipName;
    if (preg_match('/\.tar\.(gz|bz2)$/i', $zipName, $m)) {
      $ext = 'tar.' . strtolower($m[1]);
      $baseName = substr($zipName, 0, -strlen('.' . $ext));
    } else {
      $ext = pathinfo($zipName, PATHINFO_EXTENSION);
      $baseName = pathinfo($zipName, PATHINFO_FILENAME);
    }

    $destPath = $parent . DIRECTORY_SEPARATOR . $zipName;
    $counter = 1;
    while (file_exists($destPath)) {
      $zipName = $ext ? "{$baseName}_({$counter}).{$ext}" : "{$baseName}_({$counter})";
      $destPath = $parent . DIRECTORY_SEPARATOR . $zipName;
      $counter++;
    }

    if ($format === 'tar' || $format === 'tar.gz' || $format === 'tgz') {
      if (!class_exists('PharData')) jsonResponse(['error' => 'PharData extension required for TAR creation'], 500);
      $tempTar = $parent . DIRECTORY_SEPARATOR . uniqid('tar_') . '.tar';
      $phar = new PharData($tempTar);

      foreach ($items as $rel) {
        $full = safePath($config['root_dir'], $rel);
        if ($full && file_exists($full)) {
          if (is_dir($full)) {
            $phar->buildFromDirectory($full);
          } else {
            $phar->addFile($full, basename($full));
          }
        }
      }

      if ($format === 'tar.gz' || $format === 'tgz') {
        $phar->compress(Phar::GZ);
        @unlink($tempTar);
        @rename($tempTar . '.gz', $destPath);
      } else {
        @rename($tempTar, $destPath);
      }
      logDriveActivity($config['meta_file'], 'uploaded', $destPath, "Created {$format} archive");
      jsonResponse(['success' => true, 'archive' => basename($destPath)]);
    }

    if (!class_exists('ZipArchive')) jsonResponse(['error' => 'ZipArchive unavailable'], 500);
    $zip = new ZipArchive();
    if ($zip->open($destPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      jsonResponse(['error' => 'Cannot create zip archive'], 500);
    }

    $parentBase = realpath($parent);
    foreach ($items as $rel) {
      $full = safePath($config['root_dir'], $rel);
      if ($full && file_exists($full)) {
        if (is_dir($full)) {
          $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full, RecursiveDirectoryIterator::SKIP_DOTS));
          foreach ($files as $f) {
            if (!$f->isFile()) continue;
            $localName = ltrim(str_replace(['\\', '//'], '/', substr($f->getRealPath(), strlen($parentBase))), '/');
            $zip->addFile($f->getRealPath(), $localName);
          }
        } else {
          $zip->addFile($full, basename($full));
        }
      }
    }
    $zip->close();
    logDriveActivity($config['meta_file'], 'uploaded', $destPath, 'Created ZIP archive');
    jsonResponse(['success' => true, 'archive' => basename($destPath)]);
  }

  if ($action === 'unzip') {
    if (!$config['allow_zip']) jsonResponse(['error' => 'Extraction disabled'], 403);
    @ini_set('memory_limit', '1024M');
    if (function_exists('set_time_limit')) @set_time_limit(0);

    $file = findRealFile($config['root_dir'], $_POST['f'] ?? '');
    if (!$file || !is_file($file)) jsonResponse(['error' => 'Archive not found'], 404);

    $destDir = dirname($file);
    $toFolder = !empty($_POST['to_folder']) || !empty($_POST['to_subfolder']);
    if ($toFolder) {
      $baseArchiveName = basename($file);
      $baseArchiveName = preg_replace('/\.(tar\.(gz|bz2|xz)|zip|tar|tgz|tbz2|tbz|gz|rar|7z|apk|epub)$/i', '', $baseArchiveName);
      $baseArchiveName = rtrim($baseArchiveName, " .\t\n\r\0\x0B");
      if ($baseArchiveName === '') $baseArchiveName = 'extracted_archive';

      $targetSubfolder = $destDir . DIRECTORY_SEPARATOR . $baseArchiveName;
      $counter = 1;
      while (file_exists($targetSubfolder)) {
        $targetSubfolder = $destDir . DIRECTORY_SEPARATOR . "{$baseArchiveName}_({$counter})";
        $counter++;
      }
      if (!is_dir($targetSubfolder)) {
        @mkdir($targetSubfolder, 0777, true);
      }
      $destDir = $targetSubfolder;
    }

    $realDest = realpath($destDir);
    if (!$realDest) {
      @mkdir($destDir, 0777, true);
      $realDest = realpath($destDir) ?: $destDir;
    }

    $lowerName = strtolower(basename($file));
    $isZip = str_ends_with($lowerName, '.zip') || str_ends_with($lowerName, '.apk') || str_ends_with($lowerName, '.epub');
    $isTarGz = str_ends_with($lowerName, '.tar.gz') || str_ends_with($lowerName, '.tgz');
    $isTarBz2 = str_ends_with($lowerName, '.tar.bz2') || str_ends_with($lowerName, '.tbz2') || str_ends_with($lowerName, '.tbz');
    $isTar = str_ends_with($lowerName, '.tar') || $isTarGz || $isTarBz2;
    $isRar = str_ends_with($lowerName, '.rar');
    $is7z = str_ends_with($lowerName, '.7z');
    $isGz = str_ends_with($lowerName, '.gz') && !$isTarGz;
    $extractedSuccess = false;

    // 1. Primary Engine: ZipArchive
    if ($isZip || (!$isTar && !$isRar && !$is7z && !$isGz)) {
      if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($file) === true) {
          $extractedCount = 0;
          for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false || $entryName === '') continue;

            $normalizedEntry = str_replace('\\', '/', $entryName);
            $isDir = str_ends_with($normalizedEntry, '/');

            $entryParts = explode('/', $normalizedEntry);
            $safeParts = [];
            foreach ($entryParts as $part) {
              if ($part === '' || $part === '.') continue;
              if ($part === '..') continue;
              if (DIRECTORY_SEPARATOR === '\\') {
                $part = preg_replace('/[\:*?"<>|]/', '_', $part);
                $part = rtrim($part, " .");
              }
              if ($part !== '') $safeParts[] = $part;
            }
            if (empty($safeParts)) continue;

            $targetFullPath = $realDest . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $safeParts);

            if ($isDir) {
              if (!is_dir($targetFullPath)) @mkdir($targetFullPath, 0777, true);
            } else {
              $targetParent = dirname($targetFullPath);
              if (!is_dir($targetParent)) @mkdir($targetParent, 0777, true);

              $written = false;
              $stream = @$zip->getStream($entryName);
              if ($stream) {
                $out = @fopen($targetFullPath, 'wb');
                if ($out) {
                  while (!feof($stream)) {
                    $buf = fread($stream, 524288);
                    if ($buf === false || $buf === '') break;
                    fwrite($out, $buf);
                  }
                  fclose($out);
                  $written = true;
                }
                fclose($stream);
              }

              if (!$written) {
                $content = @$zip->getFromIndex($i);
                if ($content !== false) {
                  @file_put_contents($targetFullPath, $content);
                  $written = true;
                }
              }

              if (!$written) {
                @$zip->extractTo($realDest, $entryName);
              }

              if (file_exists($targetFullPath)) $extractedCount++;
            }
          }
          $zip->close();
          if ($extractedCount > 0) $extractedSuccess = true;
        }
      }
    }

    // 2. Primary Engine: PharData (.tar, .tar.gz, .tgz, .tar.bz2)
    if (!$extractedSuccess && ($isTar || $isTarGz || $isTarBz2) && class_exists('PharData')) {
      try {
        $phar = new PharData($file);
        if ($phar->extractTo($realDest, null, true)) {
          $extractedSuccess = true;
        }
      } catch (Exception $e) {
        try {
          if ($isTarGz) {
            $phar = new PharData($file);
            $tarPhar = $phar->decompress();
            if ($tarPhar->extractTo($realDest, null, true)) {
              $extractedSuccess = true;
            }
          }
        } catch (Exception $ex) {}
      }
    }

    // 3. Primary Engine: gzopen (.gz single file)
    if (!$extractedSuccess && $isGz && function_exists('gzopen')) {
      $outName = pathinfo($file, PATHINFO_FILENAME);
      $targetPath = $realDest . DIRECTORY_SEPARATOR . $outName;
      $gz = @gzopen($file, 'rb');
      $out = @fopen($targetPath, 'wb');
      if ($gz && $out) {
        while (!gzeof($gz)) {
          $chunk = gzread($gz, 524288);
          if ($chunk === false || $chunk === '') break;
          fwrite($out, $chunk);
        }
        gzclose($gz);
        fclose($out);
        $extractedSuccess = true;
      }
      if ($gz) @gzclose($gz);
      if ($out) @fclose($out);
    }

    // 4. Primary Engine: RarArchive (.rar)
    if (!$extractedSuccess && $isRar && class_exists('RarArchive')) {
      $rar = @RarArchive::open($file);
      if ($rar) {
        $entries = @$rar->getEntries();
        if ($entries) {
          foreach ($entries as $entry) {
            $entryName = str_replace('\\', '/', $entry->getName());
            $parts = explode('/', $entryName);
            $safeParts = array_filter($parts, fn($p) => $p !== '' && $p !== '.' && $p !== '..');
            if (empty($safeParts)) continue;

            $targetFullPath = $realDest . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $safeParts);
            if ($entry->isDirectory()) {
              if (!is_dir($targetFullPath)) @mkdir($targetFullPath, 0777, true);
            } else {
              $targetParent = dirname($targetFullPath);
              if (!is_dir($targetParent)) @mkdir($targetParent, 0777, true);
              @$entry->extract($targetParent, basename($targetFullPath));
            }
          }
          $rar->close();
          $extractedSuccess = true;
        }
        @$rar->close();
      }
    }

    // 5. Universal CLI Fallback (7z, 7za, unzip, tar, unrar)
    if (!$extractedSuccess && function_exists('exec') && !ini_get('safe_mode')) {
      $escFile = escapeshellarg($file);
      $escDest = escapeshellarg($realDest);

      @exec("7z x -y -o{$escDest} {$escFile} 2>&1", $out7z, $ret7z);
      if ($ret7z === 0) $extractedSuccess = true;

      if (!$extractedSuccess) {
        @exec("7za x -y -o{$escDest} {$escFile} 2>&1", $out7za, $ret7za);
        if ($ret7za === 0) $extractedSuccess = true;
      }

      if (!$extractedSuccess && $isZip) {
        @exec("unzip -o -q {$escFile} -d {$escDest} 2>&1", $outUnzip, $retUnzip);
        if ($retUnzip === 0) $extractedSuccess = true;
      }

      if (!$extractedSuccess && $isTar) {
        @exec("tar -xf {$escFile} -C {$escDest} 2>&1", $outTar, $retTar);
        if ($retTar === 0) $extractedSuccess = true;
      }

      if (!$extractedSuccess && $isRar) {
        @exec("unrar x -y -o+ {$escFile} {$escDest}/ 2>&1", $outRar, $retRar);
        if ($retRar === 0) $extractedSuccess = true;
      }
    }

    if ($extractedSuccess) {
      logDriveActivity($config['meta_file'], 'modified', $destDir, 'Extracted archive');
      jsonResponse(['success' => true]);
    }

    jsonResponse(['error' => 'Failed to extract archive. Format may be unsupported or corrupted.'], 500);
  }

  if ($action === 'download_zip') {
    $dir = safePath($config['root_dir'], $_GET['dir'] ?? '');
    $items = $_POST['items'] ?? null;

    if (!$dir || !is_dir($dir) || !class_exists('ZipArchive')) jsonResponse(['error' => 'Invalid directory'], 400);

    $zipName = (basename($dir) ?: 'gallery') . '.zip';
    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    $zip = new ZipArchive();
    $zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $dirBase = realpath($dir);
    $cacheBase = realpath($config['cache_dir']);

    if ($items && is_array($items)) {
      foreach ($items as $rel) {
        $full = safePath($config['root_dir'], $rel);
        if ($full && file_exists($full)) {
          if (is_dir($full)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $f) {
              if (!$f->isFile()) continue;
              if ($cacheBase && strpos($f->getRealPath(), $cacheBase) === 0) continue;
              $localName = ltrim(str_replace(['\\', '//'], '/', substr($f->getRealPath(), strlen($dirBase))), '/');
              $zip->addFile($f->getRealPath(), $localName);
            }
          } else {
            $zip->addFile($full, basename($full));
          }
        }
      }
    } else {
      $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
      foreach ($files as $f) {
        if (!$f->isFile()) continue;
        if ($cacheBase && strpos($f->getRealPath(), $cacheBase) === 0) continue;
        $localName = ltrim(str_replace(['\\', '//'], '/', substr($f->getRealPath(), strlen($dirBase))), '/');
        $zip->addFile($f->getRealPath(), $localName);
      }
    }
    $zip->close();

    $fallbackZip = preg_replace('/[^\x20-\x7e]/', '_', $zipName) ?: 'archive.zip';
    header('Content-Type: application/zip');
    header("Content-Disposition: attachment; filename=\"{$fallbackZip}\"; filename*=UTF-8''" . rawurlencode($zipName));
    header('Content-Length: ' . filesize($tempZip));
    readfile($tempZip);
    @unlink($tempZip);
    exit;
  }

  if ($action === 'activity_list') {
    $meta = getDriveMeta($config['meta_file']);
    $activities = $meta['activity'] ?? [];
    $now = time();
    $todayCount = 0;
    $weekCount = 0;
    $modifiedToday = 0;
    $uniqueFiles = [];

    foreach ($activities as $act) {
      $ts = $act['timestamp'] ?? 0;
      $path = $act['path'] ?? '';
      if ($now - $ts <= 86400) {
        $todayCount++;
        if (($act['action'] ?? '') === 'modified') $modifiedToday++;
      }
      if ($now - $ts <= (7 * 86400)) {
        $weekCount++;
      }
      if ($path) {
        $uniqueFiles[$path] = true;
      }
    }

    jsonResponse([
      'activities' => $activities,
      'stats'      => [
        'total_events'   => count($activities),
        'today_count'    => $todayCount,
        'week_count'     => $weekCount,
        'modified_today' => $modifiedToday,
        'unique_files'   => count($uniqueFiles)
      ]
    ]);
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

    $mangaDlName = ($title ?: 'manga') . '_offline.html';
    $mangaFallback = preg_replace('/[^\x20-\x7e]/', '_', $mangaDlName) ?: 'manga_offline.html';
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$mangaFallback}\"; filename*=UTF-8''" . rawurlencode($mangaDlName));

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

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$appProtocol = $isHttps ? 'https://' : 'http://';
$appHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$appScript = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$canonicalUrl = $appProtocol . $appHost . $appScript;
$ogImageUrl = $canonicalUrl . '?action=og_image';
$pageTitle = htmlspecialchars($config['app_title']) . ' – Self-Hosted Cloud Drive & Media Studio';
$pageDesc = 'A lightweight, single-file self-hosted cloud drive and media gallery with markdown studio, video streaming, document previewing, and offline PWA capabilities.';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $pageTitle ?></title>
    <link rel="icon" type="image/svg+xml" href="?action=icon">

    <!-- Primary Meta & Search Engine Optimization (50-60 char title & 120-160 char description) -->
    <meta name="title" content="<?= $pageTitle ?>">
    <meta name="description" content="<?= $pageDesc ?>">
    <link rel="canonical" href="<?= $canonicalUrl ?>">

    <!-- Open Graph Social Media Protocol (Standard 1200x630 Aspect Ratio) -->
    <meta property="og:site_name" content="<?= htmlspecialchars($config['app_title']) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $pageDesc ?>">
    <meta property="og:image" content="<?= $ogImageUrl ?>">
    <meta property="og:image:secure_url" content="<?= $ogImageUrl ?>">
    <meta property="og:image:type" content="image/svg+xml">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= htmlspecialchars($config['app_title']) ?> Preview">

    <!-- X (Twitter) Large Image Summary Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= $canonicalUrl ?>">
    <meta name="twitter:title" content="<?= $pageTitle ?>">
    <meta name="twitter:description" content="<?= $pageDesc ?>">
    <meta name="twitter:image" content="<?= $ogImageUrl ?>">

    <!-- PWA Capabilities & Native Mobile App Shell -->
    <link rel="manifest" href="?pwa=manifest">
    <meta name="theme-color" content="#141218">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($config['app_title']) ?>">
    <link rel="apple-touch-icon" href="?action=icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- HDMarkDown Full Engine CDN Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.8/purify.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/tokyo-night-dark.min.css">
    
    <!-- CodeMirror 5 for Code Editing -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/nord.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/search/searchcursor.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/markdown/markdown.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js" defer></script>

    <!-- Mermaid Diagrams Module -->
    <script type="module">
      import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10.9.0/dist/mermaid.esm.min.mjs';
      mermaid.initialize({ startOnLoad: false, theme: 'dark', securityLevel: 'loose' });
      window.mermaid = mermaid;
      window.dispatchEvent(new Event('mermaidLoaded'));
    </script>

    <!-- Client-Side Mobile PDF & Office Document Renderers -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/docx-preview@0.1.15/dist/docx-preview.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" defer></script>
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
        background: transparent;
      }
      .search-box svg { color: var(--md-sys-color-on-surface-variant); width: 19px; height: 19px; }
      .search-adv-btn {
        width: 28px;
        height: 28px;
        border-radius: 14px;
        color: var(--md-sys-color-on-surface-variant);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: all 0.15s ease;
        position: relative;
      }
      .search-adv-btn:hover, .search-adv-btn.active {
        color: var(--md-sys-color-primary);
        background: var(--md-sys-color-surface-container-highest);
      }
      .search-adv-btn.active::after {
        content: '';
        position: absolute;
        top: 4px;
        right: 4px;
        width: 6px;
        height: 6px;
        background: var(--md-sys-color-primary);
        border-radius: 50%;
      }
      .trash-view-wrapper {
        grid-column: 1 / -1;
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        margin-top: 0;
      }

      /* Batch Rename Modal UI Styles */
      .br-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.65rem;
      }
      @media (max-width: 540px) {
        .br-grid {
          grid-template-columns: 1fr;
          gap: 0.5rem;
        }
      }

      .br-options-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        flex-wrap: wrap;
        background: var(--md-sys-color-surface-container-low);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 14px;
        padding: 0.45rem 0.65rem;
      }
      .br-segmented-control {
        display: flex;
        background: var(--md-sys-color-surface-container-highest);
        border-radius: 10px;
        padding: 2px;
        gap: 2px;
      }
      .br-chip-label {
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
      }
      .br-chip-label input {
        display: none;
      }
      .br-chip-label span {
        padding: 0.3rem 0.65rem;
        font-size: 0.76rem;
        font-weight: 500;
        color: var(--md-sys-color-on-surface-variant);
        border-radius: 8px;
        transition: all 0.15s ease;
      }
      .br-chip-label input:checked + span {
        background: var(--md-sys-color-primary);
        color: var(--md-sys-color-on-primary);
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
      }
      .br-checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.6rem;
      }
      .br-check-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.78rem;
        font-weight: 500;
        color: var(--md-sys-color-on-surface);
        cursor: pointer;
        user-select: none;
      }
      .br-check-pill input[type="checkbox"] {
        accent-color: var(--md-sys-color-primary);
        width: 15px;
        height: 15px;
        cursor: pointer;
      }

      .br-preview-box {
        max-height: 190px;
        min-height: 75px;
        overflow-y: auto;
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 12px;
        background: var(--md-sys-color-surface-container-lowest);
      }
      .batch-rename-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8rem;
      }
      .batch-rename-table th, .batch-rename-table td {
        padding: 0.45rem 0.75rem;
        border-bottom: 1px solid var(--md-sys-color-surface-container-high);
        text-align: left;
      }
      .batch-rename-table th {
        background: var(--md-sys-color-surface-container-high);
        color: var(--md-sys-color-primary);
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 2;
        padding-top: 0.4rem;
        padding-bottom: 0.4rem;
      }
      .batch-rename-table tr:last-child td {
        border-bottom: none;
      }
      .br-preview-new {
        font-weight: 600;
        color: var(--md-sys-color-on-surface);
      }
      .br-preview-new.modified {
        color: #7ee787;
      }
      .br-preview-new.collision {
        color: #ff7b72;
      }
  
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
        width: var(--sidebar-width, 280px);
        background: var(--md-sys-color-surface-container-low);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        overflow-y: auto;
        transition: margin-left 0.25s cubic-bezier(0.2, 0, 0, 1), transform 0.25s cubic-bezier(0.2, 0, 0, 1);
        z-index: 150;
        border-right: 1px solid var(--md-sys-color-outline-variant);
        position: relative;
      }
      .sidebar.collapsed {
        margin-left: calc(-1 * var(--sidebar-width, 280px));
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
        padding: 1rem 1.2rem 0 1.2rem;
        -webkit-overflow-scrolling: touch;
        min-height: 0;
        min-width: 0;
        width: 100%;
        box-sizing: border-box;
      }
  
      .content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.75rem;
        width: 100%;
      }
      .dir-info {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        min-width: 0;
        flex-shrink: 0;
        width: 100%;
      }
      .dir-info h1 {
        font-size: 1.3rem;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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
        overflow-x: auto;
        max-width: 100%;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
        flex-shrink: 0;
      }
      .toolbar-actions::-webkit-scrollbar {
        display: none;
      }
      .toolbar-actions > * {
        flex-shrink: 0;
      }
  
      .gallery-container {
        width: 100%;
        min-width: 0;
        flex: 1;
        box-sizing: border-box;
      }
  
      .gallery-container {
        --grid-gap: 12px;
        --card-radius: 14px;
        width: 100%;
        min-width: 0;
        flex: 1;
        box-sizing: border-box;
      }

      .layout-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
        grid-auto-rows: min-content;
        align-content: start;
        gap: var(--grid-gap, 12px) !important;
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
      }

      .layout-justified {
        display: flex;
        flex-wrap: wrap;
        gap: var(--grid-gap, 12px) !important;
        align-content: flex-start;
        width: 100%;
        box-sizing: border-box;
      }

      .layout-columns {
        display: flex;
        gap: var(--grid-gap, 12px) !important;
        align-items: flex-start;
        width: 100%;
        box-sizing: border-box;
      }

      .masonry-col {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--grid-gap, 12px) !important;
        box-sizing: border-box;
      }

      .layout-list {
        display: flex;
        flex-direction: column;
        gap: var(--grid-gap, 8px) !important;
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
        border-radius: var(--card-radius, 14px);
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

      .file-card.drag-over-folder {
        border: 2px dashed var(--md-sys-color-primary) !important;
        transform: scale(1.04);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        z-index: 15;
      }

      .folder-drop-overlay {
        position: absolute;
        inset: 0;
        background: rgba(20, 18, 24, 0.94);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        border-radius: inherit;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0.6rem;
        text-align: center;
        gap: 0.4rem;
        z-index: 25;
        pointer-events: none;
      }

      .file-card.drag-over-folder .folder-drop-overlay {
        display: flex;
      }

      .folder-drop-overlay svg {
        width: 32px;
        height: 32px;
        fill: var(--md-sys-color-primary);
        animation: drop-pulse 0.9s infinite alternate ease-in-out;
      }

      .folder-drop-overlay span {
        font-size: 0.76rem;
        font-weight: 700;
        color: #ffffff;
        word-break: break-word;
        line-height: 1.2;
      }

      @keyframes drop-pulse {
        from { transform: translateY(-3px); }
        to { transform: translateY(3px); }
      }

      .file-card.drag-over-folder {
        border: 2px dashed var(--md-sys-color-primary) !important;
        background: rgba(208, 188, 255, 0.18) !important;
        transform: scale(1.03);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
      }

      .osm-map-frame {
        width: 100%;
        height: 180px;
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 12px;
        margin-top: 0.5rem;
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
  
      .layout-justified {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        align-content: flex-start;
        width: 100%;
        box-sizing: border-box;
      }
      .layout-justified .file-card {
        height: var(--justified-row-height, 200px) !important;
        flex-grow: var(--card-grow, 1);
        flex-shrink: 0;
        flex-basis: auto;
        width: auto !important;
        min-width: 100px;
        max-width: 100%;
        aspect-ratio: var(--card-ratio, auto);
      }
      .layout-justified .file-thumb {
        height: 100% !important;
        width: 100% !important;
        min-height: 0 !important;
      }
      .layout-justified .file-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      /* Image Editor Toolbars & Canvas */
      .img-editor-nav {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        background: var(--md-sys-color-surface-container);
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
        padding: 0.35rem 0.8rem;
        overflow-x: auto;
        white-space: nowrap;
        flex-wrap: nowrap;
        flex-shrink: 0;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
      }
      .img-editor-nav::-webkit-scrollbar { display: none; }
      .img-editor-nav > * { flex-shrink: 0; }
      .img-editor-nav-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--md-sys-color-on-surface-variant);
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
        flex-shrink: 0;
      }
      .img-editor-nav-btn:hover {
        background: var(--md-sys-color-surface-container-high);
        color: var(--md-sys-color-on-surface);
      }
      .img-editor-nav-btn.active {
        background: var(--md-sys-color-primary-container);
        color: var(--md-sys-color-on-primary-container);
      }
      .img-editor-subbar {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: var(--md-sys-color-surface-container-low);
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
        padding: 0.45rem 0.85rem;
        overflow-x: auto;
        white-space: nowrap;
        flex-wrap: nowrap;
        flex-shrink: 0;
        font-size: 0.8rem;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
      }
      .img-editor-subbar > * { flex-shrink: 0; }
      .img-editor-canvas-wrap {
        position: relative;
        flex: 1;
        width: 100%;
        min-height: 380px;
        background: #080808;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        user-select: none;
      }
      .img-editor-canvas-wrap canvas {
        max-width: 95%;
        max-height: 85%;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.85);
        border-radius: 6px;
      }

      .layout-columns {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
        width: 100%;
        box-sizing: border-box;
      }
      .masonry-col {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        box-sizing: border-box;
      }
      .layout-columns .file-card {
        width: 100%;
        height: auto;
        margin-bottom: 0;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        aspect-ratio: auto;
        content-visibility: visible !important;
        contain-intrinsic-size: auto;
        box-sizing: border-box;
      }
      .layout-columns .file-card:not(.is-folder) .file-thumb {
        width: 100%;
        min-height: 140px;
        height: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        flex-shrink: 0;
        background: var(--md-sys-color-surface-container);
      }
      /* In Masonry: All folders (even with preview thumbnails) remain 1:1 square tiles */
      .layout-columns .file-card.is-folder,
      .layout-columns .file-card:not(.has-image) {
        aspect-ratio: 1 / 1 !important;
        height: auto;
      }
      .layout-columns .file-card.is-folder .file-thumb,
      .layout-columns .file-card:not(.has-image) .file-thumb {
        aspect-ratio: 1 / 1 !important;
        width: 100% !important;
        height: 100% !important;
        min-height: 120px;
      }
      .layout-columns .file-card.is-folder .file-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover !important;
      }

      /* Keyboard Shortcuts Modal Styles */
      .shortcuts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 0.8rem;
      }
      .shortcuts-group {
        background: var(--md-sys-color-surface-container-low);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 14px;
        padding: 0.8rem;
      }
      .shortcuts-group-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--md-sys-color-primary);
        letter-spacing: 0.6px;
        margin-bottom: 0.6rem;
      }
      .shortcut-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.3rem 0;
        font-size: 0.82rem;
        border-bottom: 1px solid var(--md-sys-color-surface-container-high);
      }
      .shortcut-row:last-child { border-bottom: none; }
      .shortcut-key-badge {
        background: var(--md-sys-color-surface-container-highest);
        border: 1px solid var(--md-sys-color-outline-variant);
        color: var(--md-sys-color-on-surface);
        padding: 0.15rem 0.45rem;
        border-radius: 6px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.75rem;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
      }

      /* Archive Inspector Table Styles */
      .archive-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
      }
      .archive-table th, .archive-table td {
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
        text-align: left;
      }
      .archive-table th {
        background: var(--md-sys-color-surface-container-high);
        color: var(--md-sys-color-primary);
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 2;
      }
      .layout-columns .file-thumb img {
        width: 100%;
        height: auto;
        display: block;
        object-fit: contain;
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
        border-radius: var(--card-radius, 14px) !important;
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

      /* Star Indicator on Cards */
      .file-star-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 28px;
        height: 28px;
        padding: 0;
        margin: 0;
        border: none;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.75);
        z-index: 10;
        cursor: pointer;
        box-sizing: border-box;
        line-height: 0;
        transition: transform 0.15s ease, background-color 0.15s ease, color 0.15s ease;
      }
      .file-star-btn svg {
        width: 16px;
        height: 16px;
        display: block;
        margin: 0 auto;
        fill: currentColor;
      }
      .file-star-btn:hover {
        background: rgba(0, 0, 0, 0.85);
        color: #f59e0b;
        transform: scale(1.1);
      }
      .file-star-btn.active {
        color: #f59e0b;
        background: rgba(0, 0, 0, 0.75);
        display: flex !important;
      }
      .layout-list .file-star-btn {
        position: static;
        width: 44px;
        height: 44px;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-left: auto;
      }

      .layout-list .file-star-btn svg {
        width: 24px;
        height: 24px;
      }

      .layout-list .file-star-btn:not(.active) {
        color: var(--md-sys-color-outline);
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
        padding: 0.35rem 0.75rem;
        border-radius: 32px;
        box-shadow: var(--md-elevation-2);
        display: flex;
        align-items: center;
        gap: 0.3rem;
        z-index: 500;
        transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1);
      }
      .batch-bar.active { transform: translateX(-50%) translateY(0); }
      .batch-bar .btn-icon {
        width: 36px;
        height: 36px;
        border-radius: 18px;
        color: var(--md-sys-color-on-surface);
      }
      .batch-bar .btn-icon:hover {
        background: var(--md-sys-color-surface-container);
        color: var(--md-sys-color-primary);
      }
      .batch-count { font-size: 0.85rem; font-weight: 600; color: var(--md-sys-color-primary); padding: 0 0.4rem; white-space: nowrap; }
  
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
        min-height: 35dvh;
        grid-column: 1 / -1;
        color: var(--md-sys-color-on-surface-variant);
        text-align: center;
        gap: 0.9rem;
        padding: 2.5rem 1rem;
      }

      .m3-spinner,
      svg.m3-spinner {
        width: 52px !important;
        height: 52px !important;
        animation: m3-rotate 1.4s linear infinite;
        display: block;
        margin: 0 auto;
        flex-shrink: 0;
      }
      .m3-spinner circle {
        stroke: var(--md-sys-color-primary);
        stroke-linecap: round;
        stroke-width: 4px;
        animation: m3-dash 1.4s ease-in-out infinite;
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
        position: relative;
      }
      .manga-page-btn {
        position: absolute;
        top: 14px;
        right: 14px;
        background: rgba(20, 18, 24, 0.88);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #ffffff;
        border: 1px solid var(--md-sys-color-outline-variant);
        padding: 0.35rem 0.8rem;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        cursor: pointer;
        opacity: 0;
        transform: translateY(-4px);
        transition: opacity 0.2s ease, transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
        z-index: 10;
        pointer-events: auto;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5);
      }
      .manga-page:hover .manga-page-btn,
      .manga-page:focus-within .manga-page-btn {
        opacity: 1;
        transform: translateY(0);
      }
      .manga-page-btn:hover {
        background: var(--md-sys-color-primary);
        color: var(--md-sys-color-on-primary);
        border-color: var(--md-sys-color-primary);
      }
      .manga-page-btn svg {
        width: 15px;
        height: 15px;
        fill: currentColor;
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
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        min-height: 100vh;
        min-height: 100dvh;
        max-height: 100vh;
        max-height: 100dvh;
        background: #000000;
        z-index: 99999 !important;
        display: none;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        overflow: hidden;
        user-select: none;
        box-sizing: border-box;
      }
      .lightbox.active {
        display: block;
      }
      .lightbox-header {
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1rem;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.35) 75%, transparent 100%);
        color: #ffffff;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 2550;
        opacity: 0;
        transform: translateY(-100%);
        transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1), opacity 0.25s ease;
        pointer-events: none;
      }
      .lightbox-header.active {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
      }
      .lightbox-title {
        font-weight: 600;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 55vw;
        color: #ffffff;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
      }
      .lightbox-bottom-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        padding: 0.4rem 0.8rem calc(0.6rem + env(safe-area-inset-bottom, 0px)) 0.8rem;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.94) 0%, rgba(0, 0, 0, 0.6) 65%, transparent 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
        z-index: 2550;
        opacity: 0;
        transform: translateY(100%);
        transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1), opacity 0.25s ease;
        pointer-events: none;
      }
      .lightbox-bottom-bar.active {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
      }
      .lightbox-carousel-wrap {
        width: 100%;
        max-width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        padding: 2px 0;
        pointer-events: auto;
        mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
        -webkit-mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
      }
      .lightbox-carousel {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        scrollbar-width: none;
        width: 100%;
        padding: 6px 32px;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
      }
      .lightbox-carousel::-webkit-scrollbar {
        display: none;
      }
      .lb-carousel-item {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.08);
        border: 2px solid rgba(255, 255, 255, 0.15);
        flex-shrink: 0;
        cursor: pointer;
        opacity: 0.45;
        transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        transition: transform 0.2s cubic-bezier(0.2, 0, 0, 1), opacity 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
      }
      .lb-carousel-item:hover {
        opacity: 0.85;
        transform: translateZ(0) scale(1.05);
      }
      .lb-carousel-item.active {
        opacity: 1;
        border-color: #ffffff;
        transform: translateZ(0) scale(1.12);
        box-shadow: 0 0 0 1.5px var(--md-sys-color-primary), 0 4px 14px rgba(0, 0, 0, 0.8), 0 0 12px rgba(208, 188, 255, 0.45);
        z-index: 5;
      }
      .lb-carousel-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        pointer-events: none;
      }
      .lb-carousel-item .lb-carousel-icon {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(30, 30, 30, 0.85);
        color: #fff;
      }
      .lb-carousel-item .lb-carousel-icon svg {
        width: 20px;
        height: 20px;
      }
      .lightbox-actions-row {
        display: flex;
        align-items: center;
        justify-content: space-around;
        width: 100%;
        max-width: 100%;
        pointer-events: auto;
      }
      .lb-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        color: #e0e0e0;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0.3rem 0.6rem;
        border-radius: 12px;
        transition: all 0.15s ease;
        min-width: 54px;
      }
      .lb-action-btn:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.12);
      }
      .lb-action-btn svg {
        width: 22px;
        height: 22px;
        fill: currentColor;
      }
      .lb-action-btn span {
        font-size: 0.72rem;
        font-weight: 500;
        letter-spacing: 0.2px;
      }
      .lb-action-btn.active svg {
        fill: #ff3b30;
        color: #ff3b30;
      }
      .lightbox-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 44px;
        height: 44px;
        background: rgba(20, 20, 20, 0.65);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 2550;
        opacity: 0;
        pointer-events: none;
        transition: all 0.25s cubic-bezier(0.2, 0, 0, 1);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
      }
      .lightbox-arrow:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-50%) scale(1.08);
      }
      .lightbox-arrow.left {
        left: 16px;
      }
      .lightbox-arrow.right {
        right: 16px;
      }
      .lightbox-arrow.active {
        opacity: 1;
        pointer-events: auto;
      }
      .lightbox-body {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        touch-action: none;
        z-index: 1;
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }
      .lightbox-media {
        display: block !important;
        max-width: 100% !important;
        max-height: 100% !important;
        width: auto !important;
        height: auto !important;
        object-fit: contain !important;
        object-position: center center !important;
        margin: 0 !important;
        user-select: none;
        -webkit-user-drag: none;
        transform-origin: center center;
        will-change: transform;
        border-radius: 0;
        box-shadow: none;
        cursor: default;
        pointer-events: auto;
      }
      .lightbox-media.zoomed {
        cursor: grab;
      }
      .lightbox-media.zoomed:active {
        cursor: grabbing;
      }
      .lightbox-media.smooth-zoom {
        transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1), opacity 0.2s ease;
      }
      .lightbox-media.disintegrate {
        opacity: 0 !important;
        filter: blur(18px) brightness(1.4) contrast(1.2) !important;
        transform: scale(1.06) translateZ(0) !important;
        transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1), filter 0.5s cubic-bezier(0.4, 0, 0.2, 1), transform 0.5s cubic-bezier(0.4, 0, 0.2, 1) !important;
      }
      .lightbox-media.reconstruct {
        opacity: 0;
        filter: blur(16px) brightness(1.3) contrast(1.1);
        transform: scale(0.95) translateZ(0);
        transition: opacity 0.65s cubic-bezier(0.2, 0, 0, 1), filter 0.65s cubic-bezier(0.2, 0, 0, 1), transform 0.65s cubic-bezier(0.2, 0, 0, 1);
      }
      .lightbox-media.reconstruct.ready {
        opacity: 1 !important;
        filter: none !important;
      }
      .lightbox-audio-card {
        background: rgba(28, 25, 34, 0.85);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 2.2rem 2rem 1.8rem 2rem;
        border-radius: 32px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.2rem;
        max-width: 420px;
        width: 88%;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.6);
        position: relative;
        z-index: 2515;
      }
      .lightbox-audio-badge {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: var(--md-sys-color-primary);
        background: rgba(208, 188, 255, 0.12);
        padding: 0.2rem 0.75rem;
        border-radius: 14px;
        border: 1px solid rgba(208, 188, 255, 0.2);
      }
      .lightbox-audio-disc-wrap {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.4rem;
      }
      .lightbox-audio-disc {
        width: 124px;
        height: 124px;
        border-radius: 50%;
        background: radial-gradient(circle, #1a1721 0%, #121016 45%, #24202e 48%, #121016 52%, #1e1b26 70%, #0d0c10 100%);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.6), inset 0 0 0 2px rgba(255, 255, 255, 0.08), inset 0 0 0 8px rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }
      .lightbox-audio-disc::after {
        content: '';
        position: absolute;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--md-sys-color-primary), #9a72ff);
        box-shadow: inset 0 0 0 3px rgba(0, 0, 0, 0.35), 0 2px 8px rgba(0, 0, 0, 0.4);
      }
      .lightbox-audio-disc svg {
        position: relative;
        z-index: 2;
        width: 20px;
        height: 20px;
        fill: #ffffff;
      }
      .lightbox-audio-disc.spinning {
        animation: spin-record 8s linear infinite;
        box-shadow: 0 14px 36px rgba(168, 120, 255, 0.28), inset 0 0 0 2px rgba(255, 255, 255, 0.14), inset 0 0 0 8px rgba(0, 0, 0, 0.6);
      }
      @keyframes spin-record {
        100% { transform: rotate(360deg); }
      }
      .lightbox-audio-title {
        font-weight: 700;
        font-size: 1.05rem;
        color: #ffffff;
        line-height: 1.4;
        word-break: break-word;
        max-width: 100%;
      }
      .lightbox-audio-card audio {
        width: 100%;
        height: 44px;
        outline: none;
        border-radius: 22px;
        color-scheme: dark;
        filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.25));
      }
      .lb-spinner {
        position: absolute;
        inset: 0;
        margin: auto;
        width: 48px;
        height: 48px;
        display: none !important;
        z-index: 2510;
        pointer-events: none;
      }
      .lb-spinner.active {
        display: block !important;
      }
      @media (max-width: 520px) {
        .lightbox-nav.prev { left: 0.6rem; }
        .lightbox-nav.next { right: 0.6rem; }
      }
  
      .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(4px);
        z-index: 100005 !important;
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
        max-height: 88dvh;
        max-height: calc(100dvh - 2rem);
        box-shadow: var(--md-elevation-2);
        overflow: hidden;
        display: flex;
        flex-direction: column;
      }
      .modal-box.large {
        max-width: 95vw;
        width: 95vw;
        height: 92dvh;
        border-radius: 20px;
      }
      .clipboard-bar {
        position: fixed;
        bottom: calc(5.2rem + env(safe-area-inset-bottom, 0px));
        left: 50%;
        transform: translateX(-50%) translateY(200%);
        background: var(--md-sys-color-surface-container-highest);
        border: 1px solid var(--md-sys-color-primary);
        padding: 0.4rem 0.9rem;
        border-radius: 32px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.85);
        display: flex;
        align-items: center;
        gap: 0.6rem;
        z-index: 5000;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: transform 0.25s cubic-bezier(0.2, 0, 0, 1), opacity 0.2s ease, visibility 0.2s;
      }
      .clipboard-bar.active {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
      }
      
      .editor-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.6rem 1rem;
        background: var(--md-sys-color-surface-container);
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
        gap: 0.75rem;
        flex-shrink: 0;
        min-height: 56px;
      }
      .editor-header-left {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        min-width: 0;
        flex: 1;
        overflow: hidden;
      }
      .editor-title-wrap {
        display: flex;
        flex-direction: column;
        min-width: 0;
        overflow: hidden;
      }
      .editor-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--md-sys-color-on-surface) !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        gap: 0.4rem;
      }
      .editor-metrics-badge {
        font-size: 0.7rem;
        font-weight: 500;
        color: var(--md-sys-color-on-surface-variant);
        background: var(--md-sys-color-surface-container-highest);
        padding: 0.15rem 0.5rem;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        width: fit-content;
        margin-top: 0.15rem;
      }
      .editor-header-actions {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-shrink: 0;
      }
      .doc-viewer-container {
        flex: 1;
        width: 100%;
        height: 100%;
        overflow-y: auto;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        background: var(--md-sys-color-surface-container-lowest);
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 1rem 0.6rem;
        box-sizing: border-box;
      }
      .pdf-page-canvas {
        max-width: 100% !important;
        height: auto !important;
        margin-bottom: 12px;
        border-radius: 6px;
        box-shadow: var(--md-elevation-1);
        display: block;
      }
      .docx-viewer-wrapper {
        background: #ffffff !important;
        color: #111111 !important;
        padding: 1.5rem !important;
        border-radius: 12px;
        width: 100% !important;
        max-width: 860px;
        box-sizing: border-box;
        box-shadow: var(--md-elevation-1);
      }
      .sheet-viewer-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
        background: var(--md-sys-color-surface-container-low);
        border-radius: 10px;
        overflow: hidden;
      }
      .sheet-viewer-table th, .sheet-viewer-table td {
        border: 1px solid var(--md-sys-color-outline-variant);
        padding: 0.45rem 0.75rem;
        text-align: left;
        white-space: nowrap;
      }
      .sheet-viewer-table th {
        background: var(--md-sys-color-surface-container-high);
        color: var(--md-sys-color-primary);
        font-weight: 600;
      }
      .hdm-segmented-control {
        display: flex;
        gap: 2px;
        background: var(--md-sys-color-surface-container-high);
        padding: 2px;
        border-radius: 8px;
      }

      /* HDMarkDown Workspace Styles */
      .hdm-workspace {
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
        overflow: hidden;
        background: var(--md-sys-color-surface-container-lowest);
      }
      .hdm-toolbar {
        background: var(--md-sys-color-surface-container);
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
        display: flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.35rem 0.6rem;
        overflow-x: auto;
        flex-shrink: 0;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
      }
      .hdm-toolbar::-webkit-scrollbar {
        display: none;
      }
      .hdm-toolbar .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        flex-shrink: 0;
      }

      @media (max-width: 768px) {
        .modal-backdrop {
          padding: 0 !important;
        }
        .modal-box:not(.large) {
          max-width: calc(100vw - 2rem);
          margin: 1rem;
        }
        .modal-box.large {
          position: fixed;
          inset: 0;
          max-width: 100vw;
          width: 100vw;
          height: 100dvh;
          max-height: 100dvh;
          border-radius: 0;
          margin: 0;
          border: none;
        }
        .editor-header-actions .desktop-split-btn,
        .editor-header-actions .desktop-present-btn {
          display: none !important;
        }
        .editor-header-actions .btn-icon {
          width: 30px;
          height: 30px;
        }
        #editor-save-btn {
          height: 30px !important;
          padding: 0 0.6rem !important;
          font-size: 0.8rem !important;
        }
      }
      .hdm-find-card {
        position: absolute;
        top: 60px;
        right: 1rem;
        z-index: 1000;
        background: var(--md-sys-color-surface-container-high);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 16px;
        padding: 0.35rem 0.55rem;
        box-shadow: var(--md-elevation-2);
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        width: 320px;
        max-width: calc(100vw - 2rem);
        backdrop-filter: blur(14px);
      }
      .find-card-row {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.2rem 0.25rem;
      }
      .find-card-divider {
        height: 1px;
        background: var(--md-sys-color-outline-variant);
        margin: 0.1rem 0;
      }
      .find-card-icon {
        width: 17px;
        height: 17px;
        color: var(--md-sys-color-on-surface-variant);
        flex-shrink: 0;
      }
      .find-card-input {
        flex: 1;
        border: none;
        background: transparent;
        color: var(--md-sys-color-on-surface);
        font-size: 0.88rem;
        outline: none;
        min-width: 50px;
      }
      .find-card-counter {
        font-size: 0.76rem;
        color: var(--md-sys-color-outline);
        font-variant-numeric: tabular-nums;
        padding: 0 0.2rem;
        white-space: nowrap;
      }
      .find-card-btn-icon {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        color: var(--md-sys-color-on-surface-variant);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
      }
      .find-card-btn-icon:hover {
        background: var(--md-sys-color-surface-container-highest);
        color: var(--md-sys-color-on-surface);
      }
      .find-card-btn-icon svg {
        width: 16px;
        height: 16px;
      }
      .find-card-actions {
        display: flex;
        gap: 0.35rem;
        margin-left: auto;
      }
      .find-card-btn {
        border: 1px solid var(--md-sys-color-outline-variant);
        background: var(--md-sys-color-surface-container);
        color: var(--md-sys-color-on-surface);
        padding: 0.2rem 0.6rem;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
      }
      .find-card-btn:hover {
        background: var(--md-sys-color-surface-container-highest);
        border-color: var(--md-sys-color-primary);
        color: var(--md-sys-color-primary);
      }
      .hdm-panes {
        display: flex;
        flex: 1;
        width: 100%;
        height: 100%;
        min-height: 0;
        overflow: hidden;
        position: relative;
      }
      .hdm-pane {
        height: 100%;
        overflow-y: auto;
        min-width: 0;
        min-height: 0;
      }
      .hdm-editor-pane {
        width: 100%;
        height: 100%;
        flex: 1;
        background: var(--md-sys-color-surface-container-lowest);
        display: flex;
        flex-direction: column;
      }
      .hdm-preview-pane {
        flex: 1;
        padding: 1.5rem;
        background: var(--md-sys-color-surface-container-lowest);
        color: var(--md-sys-color-on-surface);
        line-height: 1.6;
      }
      .hdm-resizer {
        width: 6px;
        background: var(--md-sys-color-outline-variant);
        cursor: col-resize;
        transition: background 0.2s;
      }
      .hdm-resizer:hover { background: var(--md-sys-color-primary); }

      /* Markdown Elements inside Preview */
      .hdm-preview-pane h1, .hdm-preview-pane h2, .hdm-preview-pane h3, .hdm-preview-pane h4 {
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
        padding-bottom: 0.3rem;
        margin: 1.2rem 0 0.8rem 0;
      }
      .hdm-preview-pane img {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        margin: 0.8rem 0;
        box-shadow: var(--md-elevation-1);
        display: inline-block;
      }
      .hdm-preview-pane mark {
        background: rgba(253, 224, 71, 0.35);
        color: inherit;
        padding: 0.1rem 0.3rem;
        border-radius: 4px;
      }
      .hdm-preview-pane u {
        text-decoration: underline;
      }
      .hdm-preview-pane pre code {
        border-radius: 8px;
        padding: 1rem !important;
        display: block;
        overflow-x: auto;
      }
      .hdm-preview-pane blockquote {
        border-left: 4px solid var(--md-sys-color-primary);
        padding-left: 1rem;
        color: var(--md-sys-color-on-surface-variant);
        margin: 1rem 0;
        background: var(--md-sys-color-surface-container-low);
        border-radius: 0 8px 8px 0;
        padding: 0.6rem 1rem;
      }
      .hdm-preview-pane table {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-collapse: collapse;
        margin: 1.2rem 0;
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 8px;
      }
      .hdm-preview-pane th, .hdm-preview-pane td {
        border: 1px solid var(--md-sys-color-outline-variant);
        padding: 0.6rem 0.9rem;
        white-space: nowrap;
      }
      .hdm-preview-pane th {
        background: var(--md-sys-color-surface-container-high);
        color: var(--md-sys-color-primary);
        font-weight: 600;
      }
      .hdm-preview-pane hr {
        border: none;
        height: 1px;
        background: var(--md-sys-color-outline-variant);
        margin: 1.5rem 0;
      }
      .hdm-preview-pane details {
        background: var(--md-sys-color-surface-container-low);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 8px;
        padding: 0.6rem 0.9rem;
        margin: 0.8rem 0;
      }
      .hdm-preview-pane summary {
        font-weight: 600;
        cursor: pointer;
        color: var(--md-sys-color-primary);
      }
      .hdm-preview-pane div[align="center"] { text-align: center; }
      .hdm-preview-pane div[align="right"] { text-align: right; }
      .hdm-preview-pane div[align="left"] { text-align: left; }

      /* Mermaid Container with Interactive Pan/Zoom */
      .mermaid-container {
        position: relative;
        background: var(--md-sys-color-surface-container);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 12px;
        padding: 1rem;
        margin: 1.5rem 0;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        min-height: 240px;
        resize: vertical;
      }
      .mermaid-container svg {
        cursor: grab;
        max-width: none !important;
      }
      .mermaid-container svg:active { cursor: grabbing; }
      .mermaid-controls {
        position: absolute;
        top: 8px;
        right: 8px;
        display: flex;
        gap: 4px;
        background: var(--md-sys-color-surface-container-high);
        padding: 4px;
        border-radius: 8px;
        opacity: 0.2;
        transition: opacity 0.2s;
      }
      .mermaid-container:hover .mermaid-controls { opacity: 1; }

      /* Activity Tracker Styles */
      .activity-view-wrapper {
        grid-column: 1 / -1;
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 1rem;
      }
      .activity-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.6rem;
        width: 100%;
      }
      @media (min-width: 680px) {
        .activity-stats-grid {
          grid-template-columns: repeat(4, 1fr);
          gap: 0.75rem;
        }
      }
      .activity-stat-card {
        background: var(--md-sys-color-surface-container-low);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 14px;
        padding: 0.75rem 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
      }
      .activity-stat-num {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--md-sys-color-primary);
        line-height: 1.2;
      }
      .activity-stat-label {
        font-size: 0.72rem;
        color: var(--md-sys-color-on-surface-variant);
        font-weight: 500;
        line-height: 1.3;
      }
      .activity-list-container {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
      }
      .activity-row {
        background: var(--md-sys-color-surface-container-low);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 14px;
        padding: 0.7rem 0.85rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
      }
      .activity-row:hover {
        background: var(--md-sys-color-surface-container);
        border-color: var(--md-sys-color-primary);
      }
      .activity-badge {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        flex-shrink: 0;
      }
      .activity-badge.modified { background: rgba(56, 189, 248, 0.18); color: #38bdf8; }
      .activity-badge.uploaded { background: rgba(74, 222, 128, 0.18); color: #4ade80; }
      .activity-badge.renamed { background: rgba(251, 191, 36, 0.18); color: #fbbf24; }
      .activity-badge.trashed, .activity-badge.deleted { background: rgba(248, 113, 113, 0.18); color: #f87171; }
      .activity-badge.restored { background: rgba(192, 132, 252, 0.18); color: #c084fc; }
      @media (max-width: 480px) {
        .activity-row-time {
          display: none;
        }
      }

      /* Recents Tray */
      .recents-tray {
        display: flex;
        gap: 0.6rem;
        overflow-x: auto;
        padding: 0.6rem 0;
        margin-bottom: 1rem;
        scrollbar-width: none;
      }
      .recents-card {
        min-width: 130px;
        max-width: 150px;
        background: var(--md-sys-color-surface-container-low);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 12px;
        padding: 0.6rem;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        flex-shrink: 0;
      }
      .recents-card:hover { background: var(--md-sys-color-surface-container); border-color: var(--md-sys-color-primary); }

      /* Presentation Mode Fullscreen */
      .presentation-overlay {
        position: fixed;
        inset: 0;
        background: #080a0f;
        color: #ffffff;
        z-index: 9999;
        display: none;
        flex-direction: column;
      }
      .presentation-overlay.active { display: flex; }
      .slide-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 2.5rem;
        font-size: 1.5rem;
        max-width: 1000px;
        margin: 0 auto;
        overflow-y: auto;
      }
      .slide-content h1 { font-size: 3.5rem; margin-bottom: 1.5rem; }
      .slide-content h2 { font-size: 2.5rem; color: var(--md-sys-color-primary); }

      /* CodeMirror Theme Adaptation */
      .CodeMirror {
        width: 100% !important;
        height: 100% !important;
        flex: 1;
        min-height: 0;
        font-family: 'JetBrains Mono', Consolas, monospace !important;
        font-size: 0.88rem !important;
        line-height: 1.6 !important;
        background: var(--md-sys-color-surface-container-lowest) !important;
        color: var(--md-sys-color-on-surface) !important;
      }
      .CodeMirror-gutters {
        background: var(--md-sys-color-surface-container-low) !important;
        border-right: 1px solid var(--md-sys-color-outline-variant) !important;
        padding-right: 4px !important;
      }
      .CodeMirror-linenumber {
        color: var(--md-sys-color-outline) !important;
        font-size: 0.78rem !important;
        padding: 0 6px 0 2px !important;
      }
      .CodeMirror-cursor {
        border-left: 2px solid var(--md-sys-color-primary) !important;
      }
      .CodeMirror-selected {
        background: var(--md-sys-color-secondary-container) !important;
      }
      .CodeMirror-scroll {
        min-height: 100%;
        height: 100%;
      }
      .modal-header {
        padding: 1.2rem 1.4rem 0.8rem 1.4rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 700;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--md-sys-color-outline-variant);
        gap: 0.75rem;
        min-width: 0;
      }
      .modal-header > span, #details-modal-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
        flex: 1;
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
        box-sizing: border-box;
      }
      .form-input:focus { border-color: var(--md-sys-color-primary); }
      select.form-input,
      input[type="date"].form-input {
        position: relative;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        padding-right: 2.5rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='%23cac4d0'%3E%3Cpath d='M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 18px 18px;
        cursor: pointer;
      }
      input[type="date"].form-input::-webkit-calendar-picker-indicator {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
      }
      input[type="date"].form-input::-webkit-inner-spin-button {
        display: none;
        -webkit-appearance: none;
      }
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
        z-index: 9500;
        display: none;
        flex-direction: column;
        min-width: 220px;
        max-height: calc(100dvh - 20px);
        overflow-y: auto;
        border: 1px solid var(--md-sys-color-outline-variant);
      }

      /* Beautified Version History Timeline */
      .version-timeline {
        display: flex;
        flex-direction: column;
        position: relative;
        padding-left: 1.2rem;
        margin: 0.3rem 0;
      }
      .version-timeline::before {
        content: '';
        position: absolute;
        left: 5px;
        top: 14px;
        bottom: 14px;
        width: 2px;
        background: var(--md-sys-color-outline-variant);
      }
      .version-item {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 0.85rem;
        background: var(--md-sys-color-surface-container-low);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 14px;
        margin-bottom: 0.65rem;
        transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        gap: 0.6rem;
      }
      .version-item:hover {
        background: var(--md-sys-color-surface-container);
        border-color: var(--md-sys-color-primary);
        box-shadow: var(--md-elevation-1);
      }
      .version-item::before {
        content: '';
        position: absolute;
        left: -1.2rem;
        top: 18px;
        transform: translateX(-50%);
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--md-sys-color-primary);
        border: 2px solid var(--md-sys-color-surface);
        z-index: 2;
      }
      .version-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        min-width: 0;
        flex: 1 1 auto;
      }
      .version-date {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--md-sys-color-on-surface);
        display: flex;
        align-items: center;
        gap: 0.35rem;
        white-space: nowrap;
      }
      .version-meta {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.72rem;
        color: var(--md-sys-color-on-surface-variant);
        white-space: nowrap;
      }
      .version-badge {
        background: var(--md-sys-color-surface-container-highest);
        color: var(--md-sys-color-primary);
        padding: 0.1rem 0.5rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
      }
      .version-actions {
        display: flex;
        gap: 0.35rem;
        align-items: center;
        flex-shrink: 0;
      }

      @media (max-width: 480px) {
        .version-item {
          flex-direction: column;
          align-items: stretch;
          gap: 0.6rem;
          padding: 0.75rem 0.8rem;
        }
        .version-actions {
          width: 100%;
          justify-content: flex-end;
          gap: 0.4rem;
        }
        .version-actions button {
          flex: 1;
          justify-content: center;
        }
      }

      /* Diff Viewer Styles */
      .diff-container {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.8rem;
        background: var(--md-sys-color-surface-container-lowest);
        border: 1px solid var(--md-sys-color-outline-variant);
        border-radius: 12px;
        overflow: auto;
        max-height: 480px;
        line-height: 1.45;
      }
      .diff-line {
        display: flex;
        padding: 0.12rem 0.6rem;
        white-space: pre-wrap;
        word-break: break-all;
      }
      .diff-line.diff-add {
        background: rgba(46, 160, 67, 0.22);
        color: #7ee787;
      }
      .diff-line.diff-del {
        background: rgba(248, 81, 73, 0.22);
        color: #ff7b72;
      }
      .diff-line.diff-same {
        color: var(--md-sys-color-on-surface-variant);
      }
      .diff-num {
        width: 42px;
        flex-shrink: 0;
        user-select: none;
        opacity: 0.45;
        text-align: right;
        padding-right: 0.8rem;
      }
      .diff-sign {
        width: 16px;
        flex-shrink: 0;
        user-select: none;
        font-weight: 700;
      }
      .diff-text {
        flex: 1;
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
  
      .sidebar-resizer {
        position: absolute;
        top: 0;
        right: 0;
        width: 4px;
        height: 100%;
        cursor: col-resize;
        z-index: 10;
        transition: background 0.2s;
      }
      .sidebar-resizer:hover, .sidebar-resizer.resizing {
        background: var(--md-sys-color-primary);
      }

      .btn-icon.active,
      .toolbar-actions .btn-icon.active,
      .toolbar-actions button[data-layout].active {
        background: var(--md-sys-color-secondary-container) !important;
        color: var(--md-sys-color-on-secondary-container) !important;
      }

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
        appearance: none;
        border-radius: 3px;
        cursor: pointer;
        transition: background 0.2s ease;
      }
      .slider-input::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        background: var(--md-sys-color-primary);
        border: 2px solid var(--md-sys-color-surface);
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
        transition: transform 0.1s ease, box-shadow 0.1s ease;
      }
      .slider-input::-webkit-slider-thumb:hover,
      .slider-input:active::-webkit-slider-thumb {
        transform: scale(1.25);
        box-shadow: 0 0 0 4px rgba(208, 188, 255, 0.25);
      }
      .slider-input::-moz-range-thumb {
        width: 16px;
        height: 16px;
        background: var(--md-sys-color-primary);
        border: 2px solid var(--md-sys-color-surface);
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
        transition: transform 0.1s ease;
      }
      .slider-input::-moz-range-thumb:hover,
      .slider-input:active::-moz-range-thumb {
        transform: scale(1.25);
      }
  
      .toast-container {
        position: fixed;
        bottom: calc(1.5rem + env(safe-area-inset-bottom, 0px));
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.45rem;
        z-index: 9999;
        pointer-events: none;
        width: max-content;
        max-width: calc(100vw - 2rem);
      }
      .toast {
        background: var(--md-sys-color-surface-container-highest);
        border: 1px solid var(--md-sys-color-outline-variant);
        padding: 0.6rem 1.1rem;
        border-radius: 24px;
        box-shadow: var(--md-elevation-2);
        font-size: 0.82rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: var(--md-sys-color-on-surface);
        pointer-events: auto;
        text-align: center;
        animation: toast-in 0.2s cubic-bezier(0.2, 0, 0, 1);
      }
      @keyframes toast-in {
        from { opacity: 0; transform: translateY(10px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
      }
  
      .dropzone-overlay {
        position: fixed;
        inset: 12px;
        background: rgba(15, 13, 19, 0.65);
        border: 2px dashed var(--md-sys-color-primary);
        border-radius: 24px;
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 8000;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--md-sys-color-primary);
        pointer-events: none;
        box-shadow: 0 0 0 100vmax rgba(0, 0, 0, 0.35);
      }
      .dropzone-overlay.active { display: flex; }

      .folder-drop-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 13, 19, 0.94);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        border: 2px dashed var(--md-sys-color-primary);
        border-radius: inherit;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0.6rem;
        text-align: center;
        gap: 0.35rem;
        z-index: 30;
        pointer-events: none;
      }

      .file-card.drag-over-folder .folder-drop-overlay {
        display: flex;
      }

      .folder-drop-overlay svg {
        width: 30px;
        height: 30px;
        fill: var(--md-sys-color-primary);
        animation: drop-bounce 0.8s infinite alternate ease-in-out;
      }

      .folder-drop-overlay span {
        font-size: 0.74rem;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.25;
      }

      @keyframes drop-bounce {
        from { transform: translateY(-3px); }
        to { transform: translateY(3px); }
      }
  
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
        .sidebar,
        .sidebar.collapsed {
          margin-left: 0 !important;
        }
        .sidebar {
          position: fixed !important;
          inset: 0 auto 0 0 !important;
          transform: translateX(-100%) !important;
          box-shadow: var(--md-elevation-2);
          width: 280px !important;
          height: 100dvh !important;
        }
        .sidebar.open,
        .sidebar.collapsed.open {
          transform: translateX(0) !important;
        }
        .layout-columns { column-count: 2; }
        .main-content { padding: 0.6rem 0.6rem 0 0.6rem; }
      }
    </style>
  </head>
  <body>
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
          <button class="search-adv-btn" id="btn-adv-search" title="Advanced Search & Filters">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/></svg>
          </button>
        </div>
      </div>
  
      <div class="topbar-right">
        <div class="desktop-only" style="display:flex; align-items:center; gap:0.5rem;">
          <button class="btn-icon" id="btn-grid-adjust" title="Grid, Gap & Radius Settings">
            <svg viewBox="0 0 24 24"><path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z"/></svg>
          </button>
          <button class="btn-icon" id="btn-clear-cache-desk" title="Clear Cache">
            <svg viewBox="0 0 24 24"><path d="M15 16h4v2h-4zm0-8h7v2h-7zm0 4h6v2h-6zM3 18c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V8H3v10zM14 5h-3l-1-1H6L5 5H2v2h12V5z"/></svg>
          </button>
          <button class="btn-icon" id="btn-manga-desk" title="Manga Mode">
            <svg viewBox="0 0 24 24"><path d="M19 1L14 6V22L19 17V1M3 6V22L8 17H12V2H8L3 6M10 4.25C10 3.56 9.44 3 8.75 3S7.5 3.56 7.5 4.25 8.06 5.5 8.75 5.5 10 4.94 10 4.25Z"/></svg>
          </button>
          <button class="btn-icon" id="btn-refresh-desk" title="Refresh">
            <svg viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
          </button>
          <button class="btn-icon" id="btn-theme-desk" title="Toggle Theme">
            <svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/></svg>
          </button>
          <button class="btn-icon" id="btn-shortcuts-desk" title="Keyboard Shortcuts (?)">
            <svg viewBox="0 0 24 24"><path d="M20 5H4c-1.1 0-1.99.9-1.99 2L2 17c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm-9 3h2v2h-2V8zm0 3h2v2h-2v-2zM8 8h2v2H8V8zm0 3h2v2H8v-2zm-1 2H5v-2h2v2zm0-3H5V8h2v2zm9 7H8v-2h8v2zm0-4h-2v-2h2v2zm0-3h-2V8h2v2zm3 3h-2v-2h2v2zm0-3h-2V8h2v2z"/></svg>
          </button>
          <?php if ($config['auth_enabled']): ?>
          <button class="btn-icon" id="btn-auth-desk" title="<?= $isAdmin ? 'Admin (Click to Logout)' : 'Login as Admin (Demo Mode)' ?>" style="<?= $isDemo ? 'color:var(--md-sys-color-primary);' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          </button>
          <?php endif; ?>
          <button class="btn-primary" id="btn-upload-desk" title="Upload Options">
            <svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg> Upload <svg viewBox="0 0 24 24" style="width:14px;height:14px;margin-left:2px;"><path d="M7 10l5 5 5-5z"/></svg>
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
        <div class="sidebar-resizer desktop-only" id="sidebar-resizer"></div>
        <div class="sidebar-section">
          <div class="sidebar-title">Drive Navigation</div>
          <div class="filter-group">
            <div class="filter-item active" id="nav-home" onclick="app.switchDriveSection('home')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> Home</span>
            </div>
            <div class="filter-item" id="nav-gallery" onclick="app.switchDriveSection('gallery')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M22 16V4c0-1.1-.9-2-2-2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2zm-11-4l2.03 2.71L16 11l4 5H8l3-4zM2 6v14c0 1.1.9 2 2 2h14v-2H4V6H2z"/></svg> Gallery</span>
            </div>
            <div class="filter-item" id="nav-videos" onclick="app.switchDriveSection('videos')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg> Videos</span>
            </div>
            <div class="filter-item" id="nav-audio" onclick="app.switchDriveSection('audio')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg> Audio</span>
            </div>
            <div class="filter-item" id="nav-documents" onclick="app.switchDriveSection('documents')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg> Documents</span>
            </div>
            <div class="filter-item" id="nav-recents" onclick="app.switchDriveSection('recents')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg> Recents</span>
            </div>
            <div class="filter-item" id="nav-starred" onclick="app.switchDriveSection('starred')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg> Starred</span>
            </div>
            <div class="filter-item" id="nav-activity" onclick="app.switchDriveSection('activity')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg> Activity</span>
            </div>
            <div class="filter-item" id="nav-trash" onclick="app.switchDriveSection('trash')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg> Trash Bin</span>
            </div>
          </div>
        </div>

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
        <div class="content-header" style="justify-content: flex-end; margin-bottom: 0.5rem;">
          <div class="toolbar-actions">
            <button class="btn-icon active" data-layout="grid" title="Grid Layout">
              <svg viewBox="0 0 24 24"><path d="M3 3h8v8H3zm0 10h8v8H3zM13 3h8v8h-8zm0 10h8v8h-8z"/></svg>
            </button>
            <button class="btn-icon" data-layout="columns" title="Masonry Layout">
              <svg viewBox="0 0 24 24"><path d="M3 3h8v11H3zm10 0h8v6h-8zM3 16h8v5H3zm10-8h8v13h-8z"/></svg>
            </button>
            <button class="btn-icon" data-layout="justified" title="Justified Masonry Layout">
              <svg viewBox="0 0 24 24"><path d="M3 4h8v7H3V4zm10 0h8v7h-8V4zm-10 9h5v7H3v-7zm7 0h11v7H10v-7z"/></svg>
            </button>
            <button class="btn-icon" data-layout="list" title="List View">
              <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
            </button>
            <div style="width:1px; height:20px; background:var(--md-sys-color-outline-variant); margin:0 0.1rem;"></div>
            <button class="btn-icon" id="btn-select-all" title="Select All / Deselect All (Ctrl+A)">
              <svg viewBox="0 0 24 24"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/></svg>
            </button>
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

        <div class="dir-info" style="margin-bottom: 1rem; width: 100%;">
          <h1 id="dir-title">PHPFiles</h1>
          <div class="dir-stats" id="dir-stats">Loading...</div>
        </div>
  
        <div class="gallery-container layout-grid" id="gallery-container"></div>
        <div id="infinite-scroll-trigger" style="height:20px;"></div>
        <div class="bottom-pad"></div>
      </main>
    </div>
  
    <!-- Desktop Upload Options Dropdown -->
    <div class="dropdown-menu" id="dropdown-upload">
      <div class="dm-item" id="du-upload-files"><svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg> Upload Files</div>
      <div class="dm-item" id="du-upload-folder"><svg viewBox="0 0 24 24"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z"/></svg> Upload Folder</div>
      <div class="dm-item" id="du-upload-url"><svg viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg> Upload from URL</div>
    </div>

    <!-- Layout & Visual Adjustments Dropdown (Columns, Gap, Radius & Reset) -->
    <div class="dropdown-menu" id="dropdown-grid-adjust" style="min-width: 250px; padding: 0.75rem 0.9rem;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--md-sys-color-primary); letter-spacing: 0.6px;">Grid & Appearance</span>
      </div>
      
      <div class="slider-container" style="padding: 0.35rem 0;">
        <div class="slider-header"><span>Columns</span><span id="slider-cols-val">Auto</span></div>
        <input type="range" class="slider-input" id="slider-cols" min="0" max="8" value="0">
      </div>

      <div class="slider-container" style="padding: 0.35rem 0;">
        <div class="slider-header"><span>Item Gap</span><span id="slider-gap-val">12px</span></div>
        <input type="range" class="slider-input" id="slider-gap" min="2" max="36" value="12">
      </div>

      <div class="slider-container" style="padding: 0.35rem 0;">
        <div class="slider-header"><span>Border Radius</span><span id="slider-radius-val">14px</span></div>
        <input type="range" class="slider-input" id="slider-radius" min="0" max="32" value="14">
      </div>

      <div class="dm-sep" style="margin: 0.5rem 0;"></div>
      <button type="button" class="dm-item" id="btn-reset-grid-adjust" style="width: 100%; padding: 0.45rem 0.6rem; border-radius: 10px; font-size: 0.8rem; color: var(--md-sys-color-on-surface-variant); justify-content: center; gap: 0.4rem;">
        <svg viewBox="0 0 24 24" style="width: 15px; height: 15px;"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
        <span>Reset to Defaults</span>
      </button>
    </div>

    <div class="dropdown-menu" id="dropdown-more">
      <div class="dm-item" id="dm-upload-files"><svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg> Upload Files</div>
      <div class="dm-item" id="dm-upload-folder"><svg viewBox="0 0 24 24"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z"/></svg> Upload Folder</div>
      <div class="dm-item" id="dm-upload-url"><svg viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg> Upload from URL</div>
      <div class="dm-item" id="dm-manga"><svg viewBox="0 0 24 24"><path d="M19 1L14 6V22L19 17V1M3 6V22L8 17H12V2H8L3 6M10 4.25C10 3.56 9.44 3 8.75 3S7.5 3.56 7.5 4.25 8.06 5.5 8.75 5.5 10 4.94 10 4.25Z"/></svg> Manga Mode</div>
      <div class="dm-item" id="dm-theme"><svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/></svg> Toggle Theme</div>
      <div class="dm-item desktop-only" id="dm-shortcuts"><svg viewBox="0 0 24 24"><path d="M20 5H4c-1.1 0-1.99.9-1.99 2L2 17c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm-9 3h2v2h-2V8zm0 3h2v2h-2v-2zM8 8h2v2H8V8zm0 3h2v2H8v-2zm-1 2H5v-2h2v2zm0-3H5V8h2v2zm9 7H8v-2h8v2zm0-4h-2v-2h2v2zm0-3h-2V8h2v2zm3 3h-2v-2h2v2zm0-3h-2V8h2v2z"/></svg> Keyboard Shortcuts</div>
      <div class="dm-item" id="dm-refresh-mob"><svg viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg> Refresh</div>
      <div class="dm-item" id="dm-clear-cache"><svg viewBox="0 0 24 24"><path d="M15 16h4v2h-4zm0-8h7v2h-7zm0 4h6v2h-6zM3 18c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V8H3v10zM14 5h-3l-1-1H6L5 5H2v2h12V5z"/></svg> Clear Cache</div>
      <?php if ($config['auth_enabled']): ?>
        <?php if ($isAdmin): ?>
        <div class="dm-item danger" onclick="window.location.href='?action=logout'"><svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg> Logout (Admin)</div>
        <?php else: ?>
        <div class="dm-item" id="dm-login"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Login as Admin</div>
        <?php endif; ?>
      <?php endif; ?>
      <div class="dm-sep" id="mobile-cols-sep"></div>
      <div id="mobile-grid-adjust-container">
        <div class="slider-container" id="mobile-cols-container" style="padding: 0.35rem 0.8rem;">
          <div class="slider-header"><span>Grid Columns</span><span id="slider-cols-val-mob">Auto</span></div>
          <input type="range" class="slider-input" id="slider-cols-mob" min="0" max="8" value="0">
        </div>
        <div class="slider-container" style="padding: 0.35rem 0.8rem;">
          <div class="slider-header"><span>Item Gap</span><span id="slider-gap-val-mob">12px</span></div>
          <input type="range" class="slider-input" id="slider-gap-mob" min="2" max="36" value="12">
        </div>
        <div class="slider-container" style="padding: 0.35rem 0.8rem;">
          <div class="slider-header"><span>Border Radius</span><span id="slider-radius-val-mob">14px</span></div>
          <input type="range" class="slider-input" id="slider-radius-mob" min="0" max="32" value="14">
        </div>
        <div class="dm-sep" style="margin: 0.4rem 0;"></div>
        <button type="button" class="dm-item" id="btn-reset-grid-adjust-mob" style="width: 100%; padding: 0.45rem 0.6rem; border-radius: 10px; font-size: 0.8rem; color: var(--md-sys-color-on-surface-variant); justify-content: center; gap: 0.4rem;">
          <svg viewBox="0 0 24 24" style="width: 15px; height: 15px;"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
          <span>Reset Layout Defaults</span>
        </button>
      </div>
    </div>
  
    <!-- Floating Paste Indicator Bar -->
    <div class="clipboard-bar" id="drive-clipboard-bar">
      <span style="font-size:0.85rem; font-weight:700; color:var(--md-sys-color-primary);" id="drive-clipboard-txt">1 item ready</span>
      <div style="width:1px; height:18px; background:var(--md-sys-color-outline-variant);"></div>
      <button class="btn-primary" id="btn-drive-clipboard-paste" style="height:32px; padding:0 0.85rem; font-size:0.8rem;">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;margin-right:4px;"><path d="M19 2h-4.18C14.4.84 13.3 0 12 0c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm7 18H5V4h2v3h10V4h2v16z"/></svg> Paste Here
      </button>
      <button class="btn-icon" id="btn-drive-clipboard-cancel" title="Clear Clipboard" style="width:30px; height:30px;">
        <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
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
      <div class="dm-sep"></div>
      <div class="dm-item" data-sort="date_desc">
        <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
        <span>Date (Newest first)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
      <div class="dm-item" data-sort="date_asc">
        <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
        <span>Date (Oldest first)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
      <div class="dm-sep"></div>
      <div class="dm-item" data-sort="size_desc">
        <svg viewBox="0 0 24 24"><path d="M2 17h20v2H2v-2zm0-4h14v2H2v-2zm0-4h8v2H2V9zm0-4h4v2H2V5z"/></svg>
        <span>Size (Largest first)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
      <div class="dm-item" data-sort="size_asc">
        <svg viewBox="0 0 24 24"><path d="M2 5h4v2H2V5zm0 4h8v2H2V9zm0 4h14v2H2v-2zm0 4h20v2H2v-2z"/></svg>
        <span>Size (Smallest first)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
      <div class="dm-sep"></div>
      <div class="dm-item" data-sort="ext_asc">
        <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
        <span>Extension (A to Z)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
      <div class="dm-item" data-sort="ext_desc">
        <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm-3 7V3.5L18.5 9H13zm5 9H8v-2h8v2zm0-4H8v-2h8v2z"/></svg>
        <span>Extension (Z to A)</span>
        <svg class="sort-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      </div>
    </div>
  
    <div class="batch-bar" id="batch-bar">
      <span class="batch-count" id="batch-count">0 selected</span>
      <div style="width:1px; height:20px; background:var(--md-sys-color-outline-variant); margin:0 0.15rem;"></div>
      <button class="btn-icon" id="btn-batch-download" title="Download ZIP">
        <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
      </button>
      <button class="btn-icon" id="btn-batch-delete" title="Move to Trash / Delete">
        <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
      </button>
      <button class="btn-icon" id="btn-batch-more" title="More Actions">
        <svg viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
      </button>
      <div style="width:1px; height:20px; background:var(--md-sys-color-outline-variant); margin:0 0.15rem;"></div>
      <button class="btn-icon" id="btn-batch-clear" title="Clear selection">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>

    <!-- Dropdown Batch More Menu -->
    <div class="dropdown-menu" id="dropdown-batch-more">
      <div class="dm-item" id="dbm-rename">
        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        <span>Batch Rename</span>
      </div>
      <div class="dm-item" id="dbm-compress">
        <svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
        <span>Compress to ZIP</span>
      </div>
      <div class="dm-item" id="dbm-info">
        <svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>
        <span>Item Information</span>
      </div>
      <div class="dm-sep"></div>
      <div class="dm-item" id="dbm-copy">
        <svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
        <span>Copy Selected</span>
      </div>
      <div class="dm-item" id="dbm-cut">
        <svg viewBox="0 0 24 24"><path d="M9.64 7.64c.23-.5.36-1.05.36-1.64 0-2.21-1.79-4-4-4S2 3.79 2 6s1.79 4 4 4c.59 0 1.14-.13 1.64-.36L10 12l-2.36 2.36C7.14 14.13 6.59 14 6 14c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4c0-.59-.13-1.14-.36-1.64L12 14l7 7h3v-1L9.64 7.64zM6 8c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm0 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6-7.5c-.28 0-.5-.22-.5-.5s.22-.5.5-.5.5.22.5.5-.22.5-.5.5zM19 3l-6 6 2 2 7-7V3h-3z"/></svg>
        <span>Cut (Move) Selected</span>
      </div>
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
        <div style="display:flex; align-items:center; gap:0.6rem; min-width:0;">
          <button class="btn-icon" id="btn-lb-close" title="Close">
            <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
          </button>
          <div class="lightbox-title" id="lb-title">image.jpg</div>
        </div>
        <div style="display:flex; gap:0.4rem; align-items:center;">
          <button class="btn-icon" id="btn-lb-zoom-out" title="Zoom Out (-)">
            <svg viewBox="0 0 24 24"><path d="M19 13H5v-2h14v2z"/></svg>
          </button>
          <button class="btn-icon" id="btn-lb-zoom-in" title="Zoom In (+)">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
          </button>
          <button class="btn-icon" id="btn-lb-slideshow" title="Play Slideshow (15s)">
            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
          </button>
          <button class="btn-icon" id="btn-lb-search-google" title="Search on Google">
            <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14zm2.5-4h-2v2H9v-2H7V9h2V7h1v2h2v1z"/></svg>
          </button>
          <button class="btn-icon" id="btn-lb-edit" title="Edit Image">
            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
          </button>
        </div>
      </div>
      <button class="lightbox-arrow left" id="btn-lb-prev" title="Previous Media (←)">
        <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
      </button>
      <button class="lightbox-arrow right" id="btn-lb-next" title="Next Media (→)">
        <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </button>
      <div class="lightbox-body" id="lb-body">
        <img class="lightbox-media" id="lb-img" src="" alt="">
      </div>
      <div id="lb-slideshow-track" style="display:none; position:absolute; bottom:0; left:0; width:100%; height:3px; background:rgba(255,255,255,0.25); z-index:3600; pointer-events:none; overflow:hidden;">
        <div id="lb-slideshow-bar" style="width:0%; height:100%; background:rgba(255,0,0,0.85); transition:none;"></div>
      </div>
      <div class="lightbox-bottom-bar" id="lb-bottom-bar">
        <div class="lightbox-carousel-wrap" id="lb-carousel-wrap">
          <div class="lightbox-carousel" id="lb-carousel"></div>
        </div>
        <div class="lightbox-actions-row">
          <button class="lb-action-btn" id="btn-lb-manga" title="Read as Manga">
            <svg viewBox="0 0 24 24"><path d="M19 1L14 6V22L19 17V1M3 6V22L8 17H12V2H8L3 6M10 4.25C10 3.56 9.44 3 8.75 3S7.5 3.56 7.5 4.25 8.06 5.5 8.75 5.5 10 4.94 10 4.25Z"/></svg>
            <span>Manga</span>
          </button>
          <button class="lb-action-btn" id="btn-lb-rename" title="Rename">
            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            <span>Rename</span>
          </button>
          <button class="lb-action-btn" id="btn-lb-share" title="Share">
            <svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg>
            <span>Share</span>
          </button>
          <button class="lb-action-btn" id="btn-lb-star" title="Favorite">
            <svg id="lb-star-icon" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
            <span>Favorite</span>
          </button>
          <button class="lb-action-btn" id="btn-lb-details" title="Info">
            <svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>
            <span>Info</span>
          </button>
          <button class="lb-action-btn" id="btn-lb-download" title="Download">
            <svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            <span>Download</span>
          </button>
        </div>
      </div>
    </div>
  
    <div class="modal-backdrop" id="modal-backdrop">
      <!-- Share Modal -->
      <div class="modal-box" id="modal-share" style="display:none; max-width: 420px;">
        <div class="modal-header">
          <div style="display:flex; align-items:center; gap:0.5rem; overflow:hidden;">
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;color:var(--md-sys-color-primary);"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg>
            <span id="share-modal-title" style="font-weight:700; font-size:1rem;">Share Link</span>
          </div>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content" style="padding: 1.5rem;">
          <div style="text-align:center; margin-bottom: 1.5rem;">
            <div style="width: 64px; height: 64px; background: var(--md-sys-color-surface-container-high); color: var(--md-sys-color-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto; border: 1px solid var(--md-sys-color-outline-variant);">
              <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg>
            </div>
            <h5 style="margin:0; font-weight:700; color:var(--md-sys-color-on-surface); word-break: break-all;" id="share-modal-filename">filename.ext</h5>
            <p style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); margin-top:0.5rem;">Anyone with this link can access the file.</p>
          </div>
          <div class="form-group" style="margin:0;">
            <div style="display:flex; align-items:center; background: var(--md-sys-color-surface-container-low); border: 1px solid var(--md-sys-color-outline-variant); border-radius: 12px; padding: 0.3rem; gap: 0.4rem;">
              <input type="text" id="share-link-input" class="form-input" style="border:none !important; background:transparent !important; box-shadow:none !important; flex:1; padding:0.5rem; color:var(--md-sys-color-on-surface);" readonly>
              <button class="btn-primary" id="share-copy-btn" style="height:36px; padding:0 1rem; border-radius:10px;"><svg viewBox="0 0 24 24" style="width:16px;height:16px;margin-right:0.3rem;"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg> Copy</button>
            </div>
          </div>
          <div style="display:none; justify-content:center; margin-top:1.2rem;" id="share-native-container">
            <button class="btn-primary" id="share-native-btn" style="background:transparent !important; color:var(--md-sys-color-on-surface) !important; border:1px solid var(--md-sys-color-outline-variant) !important; width:100%; justify-content:center;">
              <svg viewBox="0 0 24 24" style="width:16px;height:16px;margin-right:0.4rem;"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg> Share via Device Apps
            </button>
          </div>
        </div>
      </div>

      <!-- Admin Login Modal -->
      <div class="modal-box" id="modal-login" style="display:none;">
        <div class="modal-header">
          <span>Login as Admin</span>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <form id="admin-login-form">
          <div class="modal-content">
            <div class="form-group">
              <label class="form-label">Password</label>
              <input type="password" class="form-input" id="admin-login-pass" placeholder="Enter admin password" required autocomplete="current-password">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-icon modal-close" style="width:auto; padding:0 0.8rem;">Cancel</button>
            <button type="submit" class="btn-primary" id="admin-login-submit">Login</button>
          </div>
        </form>
      </div>

      <!-- Advanced Batch Rename Modal -->
      <div class="modal-box" id="modal-batch-rename" style="display:none; max-width:600px; max-height:90dvh;">
        <div class="modal-header">
          <div style="display:flex; align-items:center; gap:0.5rem; overflow:hidden;">
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;color:var(--md-sys-color-primary);"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            <span id="br-title" style="font-weight:700; font-size:1rem;">Batch Rename Items</span>
          </div>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content" style="padding:1rem 1.25rem; display:flex; flex-direction:column; gap:0.65rem;">
          <div class="br-grid">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Find Pattern</label>
              <input type="text" class="form-input" id="br-find" placeholder="e.g. IMG_ or _copy">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Replace With</label>
              <input type="text" class="form-input" id="br-replace" placeholder="Leave empty to remove">
            </div>
          </div>
          <div class="br-grid">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Add Prefix</label>
              <input type="text" class="form-input" id="br-prefix" placeholder="e.g. 2026_">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Add Suffix</label>
              <input type="text" class="form-input" id="br-suffix" placeholder="e.g. _final">
            </div>
          </div>
          <div class="br-options-container">
            <div class="br-segmented-control">
              <label class="br-chip-label"><input type="radio" name="br-target" value="name" checked><span>Name</span></label>
              <label class="br-chip-label"><input type="radio" name="br-target" value="full"><span>Full</span></label>
              <label class="br-chip-label"><input type="radio" name="br-target" value="ext"><span>Ext</span></label>
            </div>
            <div class="br-checkbox-group">
              <label class="br-check-pill"><input type="checkbox" id="br-case"><span>Match Case</span></label>
              <label class="br-check-pill"><input type="checkbox" id="br-regex"><span>Regex</span></label>
            </div>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.2rem; font-size:0.78rem; color:var(--md-sys-color-on-surface-variant);">
            <span id="br-status-summary">0 item(s) will be modified</span>
            <span style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Live Preview</span>
          </div>
          <div class="br-preview-box">
            <table class="batch-rename-table">
              <thead>
                <tr>
                  <th>Original Name</th>
                  <th style="width:24px; text-align:center;">➔</th>
                  <th>New Name</th>
                </tr>
              </thead>
              <tbody id="br-preview-body"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
          <button class="btn-primary modal-close" style="background:transparent; color:var(--md-sys-color-on-surface); border:1px solid var(--md-sys-color-outline-variant); height:36px; padding:0 0.9rem; font-size:0.82rem;">Cancel</button>
          <button class="btn-primary" id="br-confirm-btn" style="gap:0.4rem; height:36px; padding:0 1rem; font-size:0.82rem;">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            <span id="br-confirm-label">Apply Rename</span>
          </button>
        </div>
      </div>

      <!-- Advanced Search Modal -->
      <div class="modal-box" id="modal-advanced-search" style="display:none; max-width:460px;">
        <div class="modal-header">
          <span>Advanced Search & Filters</span>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content" style="padding:1rem 1.4rem;">
          <div class="form-group">
            <label class="form-label">Extension (e.g. php, webp, pdf, zip)</label>
            <input type="text" class="form-input" id="adv-ext" placeholder="Comma separated, e.g. webp, jpg">
          </div>
          <div class="form-group">
            <label class="form-label">Category / Media Type</label>
            <select class="form-input" id="adv-type">
              <option value="">All Categories</option>
              <option value="image">Images</option>
              <option value="video">Videos</option>
              <option value="audio">Audio</option>
              <option value="text">Documents / Code</option>
              <option value="archive">Archives</option>
            </select>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem;">
            <div class="form-group">
              <label class="form-label">Date Modified From</label>
              <input type="date" class="form-input" id="adv-date-from">
            </div>
            <div class="form-group">
              <label class="form-label">Date Modified To</label>
              <input type="date" class="form-input" id="adv-date-to">
            </div>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem;">
            <div class="form-group">
              <label class="form-label">Min Size (MB)</label>
              <input type="number" class="form-input" id="adv-size-min" placeholder="0" min="0" step="any">
            </div>
            <div class="form-group">
              <label class="form-label">Max Size (MB)</label>
              <input type="number" class="form-input" id="adv-size-max" placeholder="e.g. 50" min="0" step="any">
            </div>
          </div>
        </div>
        <div class="modal-footer" style="display:flex; justify-content:space-between;">
          <button class="btn-primary" id="btn-adv-reset" style="background:var(--md-sys-color-surface-container-high); color:var(--md-sys-color-on-surface); border:1px solid var(--md-sys-color-outline-variant);">Reset</button>
          <div style="display:flex; gap:0.4rem;">
            <button class="btn-icon modal-close" style="width:auto; padding:0 0.8rem;">Cancel</button>
            <button class="btn-primary" id="btn-adv-apply">Apply Filter</button>
          </div>
        </div>
      </div>

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
  
      <!-- Advanced Remote Download Modal (TinyFileManager style) -->
      <div class="modal-box" id="modal-remote-download" style="display:none;">
        <div class="modal-header">
          <span>Remote URL Download</span>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content">
          <div class="form-group">
            <label class="form-label">File URL</label>
            <input type="text" class="form-input" id="remote-url-input" placeholder="https://example.com/file.zip" autofocus>
          </div>
          <div class="form-group" style="margin-bottom:0.2rem;">
            <label class="form-label">Custom Filename (Optional)</label>
            <input type="text" class="form-input" id="remote-name-input" placeholder="Leave blank to auto-detect">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-icon modal-close" style="width:auto; padding:0 0.8rem;">Cancel</button>
          <button class="btn-primary" id="remote-download-confirm">Download</button>
        </div>
      </div>
  
      <!-- Full HDMarkDown & Programming Code Editor Modal -->
      <div class="modal-box large" id="modal-editor" style="display:none; padding:0; position:relative;">
        <div id="editor-loader" style="position:absolute; inset:0; background:var(--md-sys-color-surface-container-lowest); display:none; align-items:center; justify-content:center; flex-direction:column; gap:0.8rem; z-index:500; border-radius:inherit;">
          <svg class="m3-spinner" style="margin:0;" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
          <span style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500; margin:0;">Loading document...</span>
        </div>
        <div class="editor-modal-header">
          <div class="editor-header-left">
            <button class="btn-icon modal-close" title="Back / Close" style="width:34px;height:34px;flex-shrink:0;">
              <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            </button>
            <div class="editor-title-wrap">
              <div id="editor-title" class="editor-title">Document.md</div>
              <div id="editor-metrics" class="editor-metrics-badge">0 chars • 0 words</div>
            </div>
          </div>
          <div class="editor-header-actions">
            <!-- Search Button -->
            <button class="btn-icon" id="hdm-btn-find-header" title="Find & Replace">
              <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            </button>
            <!-- Undo Button -->
            <button class="btn-icon" id="hdm-btn-undo" title="Undo">
              <svg viewBox="0 0 24 24"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
            </button>
            <!-- Redo Button -->
            <button class="btn-icon" id="hdm-btn-redo" title="Redo">
              <svg viewBox="0 0 24 24"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
            </button>
            <!-- Save Button (Floppy Disk) -->
            <button class="btn-icon" id="editor-save-btn" title="Save Document" style="color:var(--md-sys-color-primary);">
              <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
            </button>
            <!-- Three Dots Button -->
            <button class="btn-icon" id="btn-editor-more" title="More Options">
              <svg viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
            </button>
          </div>
        </div>

        <!-- Editor Three-Dots Dropdown Menu -->
        <div class="dropdown-menu" id="dropdown-editor-more">
          <div class="dm-item" id="dem-wrap">
            <svg viewBox="0 0 24 24"><path d="M4 19h6v-2H4v2zM20 5H4v2h16V5zm-3 6H4v2h13.25c1.1 0 2 .9 2 2s-.9 2-2 2H15v-2l-3 3 3 3v-2h2c2.21 0 4-1.79 4-4s-1.79-4-4-4z"/></svg>
            <span id="dem-wrap-text">Word Wrap: On</span>
          </div>
          <div class="dm-item" id="dem-versions">
            <svg viewBox="0 0 24 24"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
            <span>Version History</span>
          </div>
          <div class="dm-item" id="dem-mode-edit">
            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            <span>Editor Only</span>
          </div>
          <div class="dm-item desktop-only" id="dem-mode-split">
            <svg viewBox="0 0 24 24"><path d="M3 3h8v18H3zm10 0h8v18h-8z"/></svg>
            <span>Split View</span>
          </div>
          <div class="dm-item" id="dem-mode-preview">
            <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
            <span>Preview Only</span>
          </div>
          <div class="dm-item" id="dem-present">
            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            <span>Presentation Mode</span>
          </div>
        </div>

        <div class="hdm-workspace">
          <!-- Formatting Toolbar for Markdown -->
          <div class="hdm-toolbar" id="hdm-toolbar">
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('bold')" title="Bold (Ctrl+B)"><svg viewBox="0 0 24 24"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('italic')" title="Italic (Ctrl+I)"><svg viewBox="0 0 24 24"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('underline')" title="Underline"><svg viewBox="0 0 24 24"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('strikethrough')" title="Strikethrough"><svg viewBox="0 0 24 24"><path d="M10 19h4v-3h-4v3zM5 4v3h5v3h4V7h5V4H5zM3 14h18v-2H3v2z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('mark')" title="Highlight"><svg viewBox="0 0 24 24"><path d="M15.24 3.76L13.77 2.3c-.39-.39-1.02-.39-1.41 0L3 11.66V16h4.34l9.31-9.31c.39-.39.39-1.02 0-1.41l-1.41-1.52zM6.21 14H5v-1.21l7.35-7.35 1.21 1.21L6.21 14zM20 18H4v2h16v-2z"/></svg></button>
            <div style="width:1px;height:16px;background:var(--md-sys-color-outline-variant);"></div>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('h1')" title="Heading 1" style="font-weight:700; font-size:0.85rem;">H1</button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('h2')" title="Heading 2" style="font-weight:700; font-size:0.85rem;">H2</button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('h3')" title="Heading 3" style="font-weight:700; font-size:0.85rem;">H3</button>
            <div style="width:1px;height:16px;background:var(--md-sys-color-outline-variant);"></div>
            <!-- Align Left, Center, Right -->
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('align-left')" title="Align Left"><svg viewBox="0 0 24 24"><path d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('align-center')" title="Align Center"><svg viewBox="0 0 24 24"><path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('align-right')" title="Align Right"><svg viewBox="0 0 24 24"><path d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"/></svg></button>
            <div style="width:1px;height:16px;background:var(--md-sys-color-outline-variant);"></div>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('ul')" title="Bulleted List"><svg viewBox="0 0 24 24"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('ol')" title="Numbered List"><svg viewBox="0 0 24 24"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('task')" title="Task Checklist"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM17.99 9l-1.41-1.42-6.59 6.59-2.58-2.57-1.42 1.41 4 4z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('quote')" title="Blockquote"><svg viewBox="0 0 24 24"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('codeblock')" title="Code Block"><svg viewBox="0 0 24 24"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('link')" title="Insert Link"><svg viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('image')" title="Insert Image"><svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('table')" title="Table"><svg viewBox="0 0 24 24"><path d="M20 3H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h15c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 2v3H5V5h15zm-5 5v4h-4v-4h4zM5 10h4v4H5v-4zm0 6h4v3H5v-3zm6 3v-3h4v3h-4zm6 0v-3h3v3h-3zm3-5h-3v-4h3v4z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('hr')" title="Horizontal Rule"><svg viewBox="0 0 24 24"><path d="M19 13H5v-2h14v2z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('details')" title="Spoiler / Collapse"><svg viewBox="0 0 24 24"><path d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('mermaid')" title="Mermaid Diagram" style="color:var(--md-sys-color-primary);"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.66 0 3 1.34 3 3 0 .74-.27 1.41-.71 1.93l1.85 3.19.86-.5V12h2v4.5l-2 1.15-2-1.15V15.3l-1.85-3.19C12.72 12.19 12.38 12.2 12 12.2c-.38 0-.72-.01-1.15-.09L9 15.3v1.2l-2 1.15-2-1.15V12h2v1.62l.86.5 1.85-3.19C9.27 10.41 9 9.74 9 9c0-1.66 1.34-3 3-3z"/></svg></button>
            <button class="btn-icon" onclick="hdmEngine.insertSyntax('youtube')" title="YouTube Video"><svg viewBox="0 0 24 24"><path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/></svg></button>
          </div>

          <!-- Floating Find & Replace Card -->
          <div class="hdm-find-card" id="hdm-find-bar" style="display:none;">
            <div class="find-card-row">
              <svg viewBox="0 0 24 24" class="find-card-icon"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
              <input type="text" id="hdm-find-input" class="find-card-input" placeholder="Find">
              <span class="find-card-counter" id="hdm-find-count">0/0</span>
              <button class="find-card-btn-icon" id="hdm-btn-find-prev" title="Previous match">
                <svg viewBox="0 0 24 24"><path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/></svg>
              </button>
              <button class="find-card-btn-icon" id="hdm-btn-find-next" title="Next match">
                <svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z"/></svg>
              </button>
              <button class="find-card-btn-icon" id="hdm-btn-find-close" title="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
              </button>
            </div>
            <div class="find-card-divider"></div>
            <div class="find-card-row">
              <svg viewBox="0 0 24 24" class="find-card-icon"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
              <input type="text" id="hdm-replace-input" class="find-card-input" placeholder="Replace">
              <div class="find-card-actions">
                <button class="find-card-btn" id="hdm-btn-replace-one">Replace</button>
                <button class="find-card-btn" id="hdm-btn-replace-all">All</button>
              </div>
            </div>
          </div>

          <div class="hdm-panes" id="hdm-panes">
            <div class="hdm-pane hdm-editor-pane" id="hdm-editor-pane">
              <textarea id="hdm-raw-textarea" style="display:none;"></textarea>
            </div>
            <div class="hdm-resizer" id="hdm-resizer"></div>
            <div class="hdm-pane hdm-preview-pane" id="hdm-preview-pane"></div>
          </div>
        </div>
      </div>

      <!-- Archive Inspector Modal (Like TinyFileManager) -->
      <div class="modal-box large" id="modal-archive-preview" style="display:none; max-width:900px; height:85dvh;">
        <div class="modal-header">
          <div style="display:flex; align-items:center; gap:0.6rem; overflow:hidden;">
            <svg viewBox="0 0 24 24" style="width:22px;height:22px;color:#fde293;"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
            <span id="archive-preview-title" style="font-weight:700; font-size:1.05rem;">Archive Contents</span>
          </div>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div style="padding:0.6rem 1.4rem; background:var(--md-sys-color-surface-container-low); display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; gap:0.5rem; flex-wrap:wrap;">
          <span id="archive-preview-stats" style="color:var(--md-sys-color-on-surface-variant);">0 files found</span>
          <div style="display:flex; gap:0.4rem;">
            <button class="btn-primary" id="archive-extract-folder-btn" style="height:32px; padding:0 0.8rem; gap:0.35rem; background:var(--md-sys-color-surface-container-highest); color:var(--md-sys-color-on-surface); border:1px solid var(--md-sys-color-outline-variant);">
              <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M20 6h-8l-2-2H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm0 12H4V8h16v10z"/></svg> Extract to Folder
            </button>
            <button class="btn-primary" id="archive-extract-btn" style="height:32px; padding:0 0.8rem; gap:0.35rem;">
              <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Extract Here
            </button>
          </div>
        </div>
        <div class="modal-content" style="padding:0; overflow-y:auto;" id="archive-preview-body"></div>
      </div>

      <!-- Keyboard Shortcuts Cheat Sheet Modal -->
      <div class="modal-box large" id="modal-shortcuts" style="display:none; max-width:680px; height:auto; max-height:85dvh;">
        <div class="modal-header">
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;color:var(--md-sys-color-primary);"><path d="M20 5H4c-1.1 0-1.99.9-1.99 2L2 17c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm-9 3h2v2h-2V8zm0 3h2v2h-2v-2zM8 8h2v2H8V8zm0 3h2v2H8v-2zm-1 2H5v-2h2v2zm0-3H5V8h2v2zm9 7H8v-2h8v2zm0-4h-2v-2h2v2zm0-3h-2V8h2v2zm3 3h-2v-2h2v2zm0-3h-2V8h2v2z"/></svg>
            <span style="font-weight:700;">Keyboard Shortcuts</span>
          </div>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content" style="padding:1rem 1.4rem;">
          <div class="shortcuts-grid">
            <div class="shortcuts-group">
              <div class="shortcuts-group-title">File Manager</div>
              <div class="shortcut-row"><span>Search Files</span><span class="shortcut-key-badge">Ctrl + F</span></div>
              <div class="shortcut-row"><span>Select All</span><span class="shortcut-key-badge">Ctrl + A</span></div>
              <div class="shortcut-row"><span>New Folder</span><span class="shortcut-key-badge">Ctrl + Shift + N</span></div>
              <div class="shortcut-row"><span>New Text File</span><span class="shortcut-key-badge">Ctrl + Shift + F</span></div>
              <div class="shortcut-row"><span>Copy / Cut</span><span class="shortcut-key-badge">Ctrl + C / X</span></div>
              <div class="shortcut-row"><span>Paste</span><span class="shortcut-key-badge">Ctrl + V</span></div>
              <div class="shortcut-row"><span>Rename Item</span><span class="shortcut-key-badge">F2</span></div>
              <div class="shortcut-row"><span>Delete Item</span><span class="shortcut-key-badge">Delete</span></div>
              <div class="shortcut-row"><span>Shortcuts Guide</span><span class="shortcut-key-badge">? / F1</span></div>
            </div>
            <div class="shortcuts-group">
              <div class="shortcuts-group-title">Editor & Lightbox</div>
              <div class="shortcut-row"><span>Save Document</span><span class="shortcut-key-badge">Ctrl + S</span></div>
              <div class="shortcut-row"><span>Find & Replace</span><span class="shortcut-key-badge">Ctrl + F</span></div>
              <div class="shortcut-row"><span>Bold Text</span><span class="shortcut-key-badge">Ctrl + B</span></div>
              <div class="shortcut-row"><span>Italic Text</span><span class="shortcut-key-badge">Ctrl + I</span></div>
              <div class="shortcut-row"><span>Undo / Redo</span><span class="shortcut-key-badge">Ctrl + Z / Y</span></div>
              <div class="shortcut-row"><span>Next / Prev Media</span><span class="shortcut-key-badge">← / →</span></div>
              <div class="shortcut-row"><span>Play / Pause</span><span class="shortcut-key-badge">Space</span></div>
              <div class="shortcut-row"><span>Close Active View</span><span class="shortcut-key-badge">Esc</span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Version History Modal -->
      <div class="modal-box" id="modal-versions" style="display:none; max-width:560px;">
        <div class="modal-header">
          <div style="display:flex; flex-direction:column; gap:0.15rem; overflow:hidden;">
            <span style="font-weight:700; font-size:1.05rem;">Version History</span>
            <span id="versions-title-sub" style="font-size:0.75rem; color:var(--md-sys-color-on-surface-variant); font-weight:400; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></span>
          </div>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content" id="versions-content" style="max-height:420px; overflow-y:auto; padding:1.2rem;"></div>
      </div>

      <!-- Universal In-App Document & PDF Viewer Modal -->
      <div class="modal-box large" id="modal-doc-viewer" style="display:none; padding:0;">
        <div class="editor-modal-header">
          <div class="editor-header-left">
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;color:var(--md-sys-color-primary);"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            <div class="editor-title" id="doc-viewer-title">Document</div>
          </div>
          <div class="editor-header-actions">
            <button class="btn-primary" id="doc-viewer-direct-btn" style="height:32px; padding:0 0.8rem;">Download / Raw</button>
            <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
          </div>
        </div>
        <div class="doc-viewer-container" id="doc-viewer-container"></div>
      </div>

      <!-- Diff Preview Modal -->
      <div class="modal-box large" id="modal-diff" style="display:none; max-width:860px; height:85dvh;">
        <div class="modal-header">
          <div style="display:flex; flex-direction:column; gap:0.15rem;">
            <span style="font-weight:700; font-size:1.05rem;" id="diff-modal-title">Diff Preview</span>
            <span id="diff-modal-subtitle" style="font-size:0.75rem; color:var(--md-sys-color-on-surface-variant);"></span>
          </div>
          <button class="btn-icon modal-close"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="modal-content" style="display:flex; flex-direction:column; gap:0.6rem; padding:1rem; overflow:hidden;">
          <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; flex-wrap:wrap; gap:0.4rem;">
            <div id="diff-stats" style="display:flex; gap:0.6rem; font-weight:600;"></div>
            <div style="color:var(--md-sys-color-on-surface-variant); font-size:0.75rem;">Showing comparison: <span style="color:#ff7b72;">Current</span> ↔ <span style="color:#7ee787;">Rollback Version</span></div>
          </div>
          <div class="diff-container" id="diff-content" style="flex:1;"></div>
        </div>
        <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
          <button class="btn-primary modal-close" style="background:var(--md-sys-color-surface-container-high); color:var(--md-sys-color-on-surface); border:1px solid var(--md-sys-color-outline-variant);">Close</button>
          <button class="btn-primary" id="diff-rollback-btn" style="gap:0.4rem;">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
            <span>Rollback to this Version</span>
          </button>
        </div>
      </div>

      <!-- Fullscreen Presentation Mode -->
      <div class="presentation-overlay" id="presentation-overlay">
        <div style="position:absolute; top:1rem; right:1rem; z-index:100;">
          <button class="btn-icon" onclick="hdmEngine.closePresentation()"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div class="slide-content" id="presentation-slide-box"></div>
        <div style="position:absolute; bottom:1rem; left:50%; transform:translateX(-50%); display:flex; align-items:center; gap:1rem;">
          <button class="btn-primary" onclick="hdmEngine.prevSlide()">← Prev</button>
          <span id="presentation-indicator" style="font-weight:700;">1 / 1</span>
          <button class="btn-primary" onclick="hdmEngine.nextSlide()">Next →</button>
        </div>
      </div>
  
      <!-- Comprehensive In-App Image Editor Modal -->
      <div class="modal-box large" id="modal-image-editor" style="display:none; padding:0;">
        <div class="editor-modal-header">
          <div class="editor-header-left">
            <button class="btn-icon modal-close" title="Back / Close" style="width:34px;height:34px;flex-shrink:0;">
              <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            </button>
            <div class="editor-title" id="image-editor-title">Image Editor</div>
          </div>
          <div class="editor-header-actions">
            <button class="btn-primary" id="btn-save-image-copy" style="height:32px; padding:0 0.8rem; background:transparent; color:var(--md-sys-color-on-surface); border:1px solid var(--md-sys-color-outline-variant);">Save as Copy</button>
            <button class="btn-primary" id="btn-save-image-overwrite" style="height:32px; padding:0 0.85rem;">Save (Overwrite)</button>
          </div>
        </div>

        <div class="img-editor-nav">
          <button class="btn-icon" id="ie-undo" title="Undo (Ctrl+Z)" style="width:30px; height:30px;">
            <svg viewBox="0 0 24 24"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
          </button>
          <button class="btn-icon" id="ie-redo" title="Redo (Ctrl+Y)" style="width:30px; height:30px;">
            <svg viewBox="0 0 24 24"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
          </button>
          <div style="width:1px; height:18px; background:var(--md-sys-color-outline-variant); margin:0 0.2rem;"></div>
          <button class="img-editor-nav-btn active" data-ietab="tab-transform">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg> Transform
          </button>
          <button class="img-editor-nav-btn" data-ietab="tab-crop">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M17 15h2V7c0-1.1-.9-2-2-2H9v2h8v8zM7 17V1H5v4H1v2h4v10c0 1.1.9 2 2 2h10v4h2v-4h4v-2H7z"/></svg> Crop
          </button>
          <button class="img-editor-nav-btn" data-ietab="tab-adjust">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z"/></svg> Adjust & Filters
          </button>
          <button class="img-editor-nav-btn" data-ietab="tab-text">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M5 4v3h5.5v12h3V7H19V4H5z"/></svg> Freeform Text
          </button>
          <button class="img-editor-nav-btn" data-ietab="tab-draw">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> Free Drawing
          </button>
          <button class="btn-icon" id="ie-global-reset" title="Reset to Original" style="margin-left:auto; width:30px; height:30px;">
            <svg viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
          </button>
        </div>

        <!-- Transform Subbar -->
        <div class="img-editor-subbar" id="ietab-tab-transform">
          <button class="btn-icon" id="ie-rotate-left" title="Rotate Left 90°"><svg viewBox="0 0 24 24"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg></button>
          <button class="btn-icon" id="ie-rotate-right" title="Rotate Right 90°"><svg viewBox="0 0 24 24"><path d="M12 5V1l5 5-5 5V7c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6h2c0 4.42-3.58 8-8 8s-8-3.58-8-8 8-8 8-8z"/></svg></button>
          <button class="btn-icon" id="ie-flip-h" title="Flip Horizontal"><svg viewBox="0 0 24 24"><path d="M15 21h2v-2h-2v2zm4-12h2V7h-2v2zM3 5v14c0 1.1.9 2 2 2h4v-2H5V5h4V3H5c-1.1 0-2 .9-2 2zm16-2v2h2c0-1.1-.9-2-2-2zm-8 20h2V1h-2v22zm8-6h2v-2h-2v2zM15 5h2V3h-2v2zm4 8h2v-2h-2v2zm0 8c1.1 0 2-.9 2-2h-2v2z"/></svg></button>
          <button class="btn-icon" id="ie-flip-v" title="Flip Vertical"><svg viewBox="0 0 24 24" style="transform:rotate(90deg);"><path d="M15 21h2v-2h-2v2zm4-12h2V7h-2v2zM3 5v14c0 1.1.9 2 2 2h4v-2H5V5h4V3H5c-1.1 0-2 .9-2 2zm16-2v2h2c0-1.1-.9-2-2-2zm-8 20h2V1h-2v22zm8-6h2v-2h-2v2zM15 5h2V3h-2v2zm4 8h2v-2h-2v2zm0 8c1.1 0 2-.9 2-2h-2v2z"/></svg></button>
        </div>

        <!-- Crop Subbar -->
        <div class="img-editor-subbar" id="ietab-tab-crop" style="display:none;">
          <span style="font-weight:600;">Aspect:</span>
          <select id="ie-crop-ratio" class="form-input" style="width:auto; padding:0.25rem 1.8rem 0.25rem 0.6rem; height:32px; font-size:0.78rem;">
            <option value="free">Freeform (Drag Handles)</option>
            <option value="1:1">1:1 Square</option>
            <option value="16:9">16:9 Landscape</option>
            <option value="4:3">4:3 Standard</option>
            <option value="9:16">9:16 Story</option>
          </select>
          <button class="btn-primary" id="ie-apply-crop" style="height:30px; padding:0 0.85rem; font-size:0.75rem;">Apply Crop</button>
          <button class="btn-primary" id="ie-cancel-crop" style="height:30px; padding:0 0.85rem; font-size:0.75rem; background:transparent; border:1px solid var(--md-sys-color-outline-variant);">Cancel</button>
        </div>

        <!-- Adjust Subbar -->
        <div class="img-editor-subbar" id="ietab-tab-adjust" style="display:none;">
          <div style="display:flex; align-items:center; gap:0.35rem;">
            <span>Bright</span>
            <input type="range" class="slider-input" id="ie-brightness" min="40" max="180" value="100" style="width:60px;">
          </div>
          <div style="display:flex; align-items:center; gap:0.35rem;">
            <span>Contrast</span>
            <input type="range" class="slider-input" id="ie-contrast" min="40" max="180" value="100" style="width:60px;">
          </div>
          <div style="display:flex; align-items:center; gap:0.35rem;">
            <span>Saturate</span>
            <input type="range" class="slider-input" id="ie-saturate" min="0" max="200" value="100" style="width:60px;">
          </div>
          <label style="display:flex; align-items:center; gap:0.25rem; cursor:pointer;"><input type="checkbox" id="ie-grayscale"><span>Grayscale</span></label>
          <label style="display:flex; align-items:center; gap:0.25rem; cursor:pointer;"><input type="checkbox" id="ie-sepia"><span>Sepia</span></label>
          <label style="display:flex; align-items:center; gap:0.25rem; cursor:pointer;"><input type="checkbox" id="ie-invert"><span>Invert</span></label>
        </div>

        <!-- Freeform Text & Header Subbar -->
        <div class="img-editor-subbar" id="ietab-tab-text" style="display:none;">
          <input type="text" id="ie-text-input" class="form-input" placeholder="Type text here..." style="width:140px; height:30px; padding:0.2rem 0.5rem; font-size:0.78rem;">
          <input type="number" id="ie-text-size" class="form-input" value="36" min="10" max="200" title="Font Size (px)" style="width:58px; height:30px; padding:0.2rem; font-size:0.78rem; text-align:center;">
          <input type="color" id="ie-text-color" value="#ffffff" title="Text Color" style="width:28px; height:28px; border:none; background:transparent; cursor:pointer;">
          <label style="display:flex; align-items:center; gap:0.3rem; cursor:pointer; font-size:0.75rem;">
            <input type="checkbox" id="ie-text-banner"><span>Header Banner</span>
          </label>
          <button class="btn-primary" id="ie-add-text-btn" style="height:30px; padding:0 0.8rem; font-size:0.75rem;">Stamp Text</button>
          <span style="font-size:0.72rem; color:var(--md-sys-color-outline); margin-left:auto;">💡 Click/drag canvas to position text</span>
        </div>

        <!-- Freehand Drawing Subbar -->
        <div class="img-editor-subbar" id="ietab-tab-draw" style="display:none;">
          <div style="display:flex; align-items:center; gap:0.35rem;">
            <span>Size:</span>
            <input type="range" class="slider-input" id="ie-draw-size" min="1" max="50" value="6" style="width:70px;">
            <span id="ie-draw-size-val" style="font-size:0.75rem; min-width:24px;">6px</span>
          </div>
          <input type="color" id="ie-draw-color" value="#ff0000" title="Brush Color" style="width:28px; height:28px; border:none; background:transparent; cursor:pointer;">
          <label style="display:flex; align-items:center; gap:0.3rem; cursor:pointer; font-size:0.75rem;">
            <input type="checkbox" id="ie-draw-eraser"><span>Eraser</span>
          </label>
          <button class="btn-primary" id="ie-draw-clear-btn" style="height:30px; padding:0 0.75rem; font-size:0.75rem; background:transparent; border:1px solid var(--md-sys-color-outline-variant);">Clear Drawing</button>
          <span style="font-size:0.72rem; color:var(--md-sys-color-outline); margin-left:auto;">✏️ Draw directly on the canvas</span>
        </div>

        <div class="img-editor-canvas-wrap">
          <canvas id="image-editor-canvas"></canvas>
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

          // Deduplicate incoming files within the same target directory
          const uniqueItems = [];
          const seenKeys = new Set();

          for (const item of items) {
            const rel = item.relativePath || item.file.name;
            const key = `${targetDir || ''}::${rel}`;
            const isAlreadyQueued = this.queue.some(q => q.status === 'pending' && q.targetDir === targetDir && q.relativePath === rel);

            if (!seenKeys.has(key) && !isAlreadyQueued) {
              seenKeys.add(key);
              uniqueItems.push(item);
            }
          }

          if (!uniqueItems.length) return;

          const newTasks = uniqueItems.map((item, idx) => ({
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
          this.dock.classList.remove('minimized');
          this.dock.classList.add('active');
          this.renderDock();
          if (!this.isProcessing) this.processQueue();
        }
  
        enqueueRemoteDownload(url, customName, targetDir) {
          const id = 'remote_' + Date.now();
          const displayName = customName || url.split('/').pop().split('?')[0] || url;
          const task = {
            id: id,
            fileName: displayName,
            relativePath: 'Remote Download',
            targetDir: targetDir,
            size: 0,
            uploadedBytes: 0,
            status: 'uploading'
          };

          this.queue.push(task);
          this.dock.classList.remove('minimized');
          this.dock.classList.add('active');
          this.renderDock();

          const fd = new FormData();
          fd.append('action', 'fetch_url');
          fd.append('url', url);
          fd.append('custom_name', customName);
          fd.append('dir', targetDir);

          fetch('', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
              if (res.success) {
                task.status = 'completed';
                task.fileName = res.filename;
                this.renderDock();
                if (window.app) {
                  app.toast(`Downloaded: ${res.filename}`);
                  app.refresh();
                }
              } else {
                task.status = 'error';
                this.renderDock();
                if (window.app) app.toast(res.error || 'Remote download failed');
              }
            })
            .catch(() => {
              task.status = 'error';
              this.renderDock();
              if (window.app) app.toast('Network error during remote download');
            });
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
            const count = this.queue.length;
            this.title.innerText = `${count} upload(s) complete`;
            this.bar.style.width = '100%';
            // Clear queue on completion to prevent duplicate processing on subsequent uploads
            this.queue = [];
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
  
        async open(targetImageName = null) {
          const appInstance = window.app;
          const currentPath = appInstance ? (appInstance.currentPath || '') : '';
          this.currentDirPath = currentPath;
          this.images = [];
          this.targetImageName = targetImageName;

          try {
            const res = await fetch(`?action=list&dir=${encodeURIComponent(currentPath)}`);
            const data = await res.json();
            const files = data.files || [];
            this.images = files.filter(f => f.type === 'image');
            this.images.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' }));
          } catch (e) {
            this.images = [];
          }

          if (!this.images.length) {
            if (appInstance) appInstance.toast('No images in this folder');
            return;
          }
          this.render();
        }

        async openPath(path, targetImageName = null) {
          this.currentDirPath = path || '';
          this.images = [];
          this.targetImageName = targetImageName;

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
                <button type="button" class="manga-page-btn" onclick="mangaViewer.goToImage(${idx})" title="View in Lightbox">
                  <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                  <span>Go to this image</span>
                </button>
              </div>
            `;
          });
          this.pagesWrap.innerHTML = html;
          this.pagesWrap.querySelectorAll('.manga-page').forEach(page => this.observer.observe(page));
          
          if (this.targetImageName) {
            const targetIdx = this.images.findIndex(img => img.name === this.targetImageName);
            if (targetIdx !== -1) {
              setTimeout(() => {
                const targetEl = document.getElementById(`mpage-${targetIdx}`);
                if (targetEl) {
                  targetEl.scrollIntoView({ behavior: 'instant', block: 'start' });
                  this.counter.innerText = `${targetIdx + 1} / ${this.images.length}`;
                }
              }, 50);
            } else {
              this.el.scrollTop = 0;
            }
            this.targetImageName = null;
          } else {
            this.el.scrollTop = 0;
          }
        }
  
        goToImage(idx) {
          const target = this.images[idx];
          if (!target) return;
          this.close(false);
          if (window.app) {
            app.openFile(target.path, true);
          }
        }

        close(updateLocation = true) {
          if (this.observer) this.observer.disconnect();
          this.el.classList.remove('active');
          this.pagesWrap.innerHTML = '';
          const lastDir = this.currentDirPath;
          this.images = [];
          this.currentDirPath = '';
          if (document.fullscreenElement) document.exitFullscreen().catch(() => {});

          if (updateLocation && window.app && lastDir !== undefined) {
            app.navigate(lastDir);
          }
        }
      }
  
      class LightboxViewer {
        constructor() {
          this.el = document.getElementById('lightbox');
          this.title = document.getElementById('lb-title');
          this.body = document.getElementById('lb-body');
          this.header = document.querySelector('.lightbox-header');
          this.bottomBar = document.getElementById('lb-bottom-bar');
          this.carouselEl = document.getElementById('lb-carousel');
          this.currentIndex = 0;
          this.mediaList = [];
          this.uiTimer = null;
          this.isUiVisible = true;
          this.isPinnedUI = false;
          this.lastTouchEndTime = 0;

          // Smooth Zoom & Drag Engine State
          this.scale = 1;
          this.panX = 0;
          this.panY = 0;
          this.isMouseDragging = false;
          this.mouseStartX = 0;
          this.mouseStartY = 0;
          this.mouseMoveDist = 0;
          this.lastTapTime = 0;
          this.tapTimeout = null;

          // Touch Gesture Engine State
          this.touchStartX = 0;
          this.touchStartY = 0;
          this.touchStartTime = 0;
          this.initialPanX = 0;
          this.initialPanY = 0;
          this.initialPinchDistance = 0;
          this.initialPinchScale = 1;
          this.isTouchPanning = false;
          this.swipeDeltaX = 0;

          // Carousel Pagination State
          this.carouselBatchSize = 25;
          this.carouselLoadedCount = 0;

          // Slideshow State
          this.slideshowTimer = null;
          this.isSlideshowActive = false;

          this.bindEvents();
        }

        showUI(autoHide = true) {
          this.isUiVisible = true;
          if (this.header) this.header.classList.add('active');
          if (this.bottomBar) this.bottomBar.classList.add('active');
          document.getElementById('btn-lb-prev')?.classList.add('active');
          document.getElementById('btn-lb-next')?.classList.add('active');
          clearTimeout(this.uiTimer);
          if (autoHide && !this.isPinnedUI) {
            this.uiTimer = setTimeout(() => this.hideUI(), 3600);
          }
        }

        hideUI() {
          this.isUiVisible = false;
          clearTimeout(this.uiTimer);
          if (this.header) this.header.classList.remove('active');
          if (this.bottomBar) this.bottomBar.classList.remove('active');
          document.getElementById('btn-lb-prev')?.classList.remove('active');
          document.getElementById('btn-lb-next')?.classList.remove('active');
        }

        toggleUI(fromUserTap = false) {
          clearTimeout(this.uiTimer);
          if (fromUserTap) {
            if (this.isUiVisible) {
              this.isPinnedUI = false;
              this.hideUI();
            } else {
              this.isPinnedUI = true;
              this.showUI(false);
            }
          } else {
            if (this.isUiVisible) {
              this.hideUI();
            } else {
              this.showUI(!this.isPinnedUI);
            }
          }
        }

        bindEvents() {
          document.getElementById('btn-lb-close')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.close();
          });

          document.getElementById('btn-lb-zoom-in')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.zoomIn();
          });

          document.getElementById('btn-lb-zoom-out')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.zoomOut();
          });

          document.getElementById('btn-lb-slideshow')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleSlideshow();
          });

          // Integrated Header Next / Prev Buttons
          document.getElementById('btn-lb-prev')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.resetTransform(true);
            this.nav(-1);
          });

          document.getElementById('btn-lb-next')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.resetTransform(true);
            this.nav(1);
          });

          // Top Action Buttons
          document.getElementById('btn-lb-search-google')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = this.mediaList[this.currentIndex];
            if (item && window.app) app.searchImageOnGoogle(item.path);
          });

          document.getElementById('btn-lb-edit')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = this.mediaList[this.currentIndex];
            if (item && item.type === 'image' && window.app) {
              this.close(false);
              app.openImageEditor(item.path, item.name || item.path.split('/').pop());
            }
          });

          document.getElementById('btn-lb-manga')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = this.mediaList[this.currentIndex];
            if (item && window.app) {
              const parts = item.path.split('/');
              const imgName = parts.pop();
              const folderPath = parts.join('/');
              this.close(false);
              window.mangaViewer.openPath(folderPath, imgName);
            }
          });

          // Bottom Bar Action Buttons
          document.getElementById('btn-lb-rename')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = this.mediaList[this.currentIndex];
            if (item && window.app) {
              const name = item.name || item.path.split('/').pop();
              app.renameItem(item.path, name);
            }
          });

          document.getElementById('btn-lb-share')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = this.mediaList[this.currentIndex];
            if (item && window.app) app.createShareLink(item.path);
          });

          document.getElementById('btn-lb-star')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = this.mediaList[this.currentIndex];
            if (item && window.app) {
              app.toggleStarDirect(e, item.path);
              setTimeout(() => this.updateStarUI(item.path), 120);
            }
          });

          document.getElementById('btn-lb-details')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = this.mediaList[this.currentIndex];
            if (item && window.app) app.showDetails(item.path);
          });

          document.getElementById('btn-lb-download')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = this.mediaList[this.currentIndex];
            if (item) window.location.href = `?action=download&f=${encodeURIComponent(item.path)}`;
          });

          // Carousel Scroll Infinite Batch Loading
          this.carouselEl?.addEventListener('scroll', () => {
            if (this.carouselEl.scrollLeft + this.carouselEl.clientWidth >= this.carouselEl.scrollWidth - 60) {
              this.loadMoreCarouselItems();
            }
          }, { passive: true });

          // Keyboard Navigation & Fast Media Controls
          window.addEventListener('keydown', (e) => {
            if (!this.el.classList.contains('active')) return;
            const mediaEl = this.body.querySelector('video, audio');

            if (e.key === 'Escape') {
              this.close();
            } else if (e.key === '+' || e.key === '=') {
              e.preventDefault();
              this.zoomIn();
            } else if (e.key === '-' || e.key === '_') {
              e.preventDefault();
              this.zoomOut();
            } else if (e.key === '0') {
              e.preventDefault();
              this.resetTransform(true);
            } else if (e.key === ' ' && (e.target === document.body || e.target === this.el || e.target === mediaEl)) {
              if (mediaEl) {
                e.preventDefault();
                if (mediaEl.paused) mediaEl.play(); else mediaEl.pause();
              }
            } else if (e.key.toLowerCase() === 'm' && mediaEl) {
              e.preventDefault();
              mediaEl.muted = !mediaEl.muted;
            } else if (e.key === 'ArrowLeft') {
              if (mediaEl && !mediaEl.paused) {
                e.preventDefault();
                mediaEl.currentTime = Math.max(0, mediaEl.currentTime - 5);
              } else {
                this.resetTransform(true);
                this.nav(-1);
              }
            } else if (e.key === 'ArrowRight') {
              if (mediaEl && !mediaEl.paused) {
                e.preventDefault();
                mediaEl.currentTime = Math.min(mediaEl.duration || 0, mediaEl.currentTime + 5);
              } else {
                this.resetTransform(true);
                this.nav(1);
              }
            }
          });

          // Desktop Wheel Zoom
          this.body.addEventListener('wheel', (e) => {
            const img = this.body.querySelector('.lightbox-media');
            if (!img || img.tagName !== 'IMG') return;
            e.preventDefault();

            const rect = this.body.getBoundingClientRect();
            const pointerX = e.clientX - rect.left - rect.width / 2;
            const pointerY = e.clientY - rect.top - rect.height / 2;

            const zoomDelta = e.deltaY < 0 ? 1.25 : 0.8;
            const prevScale = this.scale;
            this.scale = Math.min(5, Math.max(1, this.scale * zoomDelta));

            if (this.scale === 1) {
              this.panX = 0;
              this.panY = 0;
            } else {
              this.panX -= (pointerX - this.panX) * (this.scale / prevScale - 1);
              this.panY -= (pointerY - this.panY) * (this.scale / prevScale - 1);
              this.clampPan();
            }
            this.applyTransform(true);
          }, { passive: false });

          // Desktop Mouse Down / Drag
          this.body.addEventListener('mousedown', (e) => {
            if (Date.now() - this.lastTouchEndTime < 750) return;
            if (e.button !== 0 || e.target.closest('video, audio, button, .lightbox-carousel-wrap, .lightbox-header, .lightbox-bottom-bar, .lightbox-arrow')) return;
            this.isMouseDragging = true;
            this.mouseStartX = e.clientX;
            this.mouseStartY = e.clientY;
            this.initialPanX = this.panX;
            this.initialPanY = this.panY;
            this.mouseMoveDist = 0;
          });

          window.addEventListener('mousemove', (e) => {
            if (!this.isMouseDragging) return;
            const dx = e.clientX - this.mouseStartX;
            const dy = e.clientY - this.mouseStartY;
            this.mouseMoveDist = Math.hypot(dx, dy);

            if (this.scale > 1) {
              this.panX = this.initialPanX + dx;
              this.panY = this.initialPanY + dy;
              this.applyTransform(false);
            }
          });

          window.addEventListener('mouseup', (e) => {
            if (Date.now() - this.lastTouchEndTime < 750) return;
            if (!this.isMouseDragging) return;
            this.isMouseDragging = false;

            if (this.scale > 1) {
              this.clampPan();
              this.applyTransform(true);
            }

            if (this.mouseMoveDist < 5 && e.target.closest('#lb-body') && !e.target.closest('video, audio, button, .lightbox-carousel-wrap, .lightbox-header, .lightbox-bottom-bar, .lightbox-arrow')) {
              this.toggleUI(true);
            }
          });

          // Desktop Double-Click Zoom
          this.body.addEventListener('dblclick', (e) => {
            const img = this.body.querySelector('.lightbox-media');
            if (!img || img.tagName !== 'IMG') return;
            e.preventDefault();

            if (this.scale > 1.2) {
              this.resetTransform(true);
            } else {
              this.scale = 2.5;
              const rect = this.body.getBoundingClientRect();
              const tapX = e.clientX - rect.left - rect.width / 2;
              const tapY = e.clientY - rect.top - rect.height / 2;
              this.panX = -tapX * 1.5;
              this.panY = -tapY * 1.5;
              this.clampPan();
              this.applyTransform(true);
            }
          });

          // Mobile Touch Handling (Tap Toggle, Double Tap Zoom, Pinch & Swipe)
          this.body.addEventListener('touchstart', (e) => {
            const isImg = !!this.body.querySelector('img.lightbox-media');
            if (e.target.closest('video, audio, .lightbox-audio-card, button, .lightbox-carousel-wrap, .lightbox-header, .lightbox-bottom-bar, .lightbox-arrow')) return;

            if (e.touches.length === 2 && isImg) {
              this.isTouchPanning = false;
              this.initialPinchDistance = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
              );
              this.initialPinchScale = this.scale;
            } else if (e.touches.length === 1) {
              this.touchStartX = e.touches[0].clientX;
              this.touchStartY = e.touches[0].clientY;
              this.touchStartTime = Date.now();
              this.initialPanX = this.panX;
              this.initialPanY = this.panY;
              this.swipeDeltaX = 0;

              if (this.scale > 1 && isImg) {
                this.isTouchPanning = true;
              }
            }
          }, { passive: false });

          this.body.addEventListener('touchmove', (e) => {
            const isImg = !!this.body.querySelector('img.lightbox-media');

            if (e.touches.length === 2 && isImg) {
              e.preventDefault();
              const currDist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
              );
              if (this.initialPinchDistance > 0) {
                this.scale = Math.min(5, Math.max(1, this.initialPinchScale * (currDist / this.initialPinchDistance)));
                if (this.scale === 1) {
                  this.panX = 0;
                  this.panY = 0;
                }
                this.applyTransform(false);
              }
            } else if (e.touches.length === 1) {
              const diffX = e.touches[0].clientX - this.touchStartX;
              const diffY = e.touches[0].clientY - this.touchStartY;

              if (this.scale > 1 && this.isTouchPanning && isImg) {
                e.preventDefault();
                this.panX = this.initialPanX + diffX;
                this.panY = this.initialPanY + diffY;
                this.applyTransform(false);
              } else if (this.scale === 1) {
                if (Math.abs(diffX) > 8 && Math.abs(diffX) > Math.abs(diffY)) {
                  e.preventDefault();
                  this.swipeDeltaX = diffX;
                  const img = this.body.querySelector('.lightbox-media');
                  if (img) {
                    img.classList.remove('smooth-zoom');
                    img.style.transform = `translate3d(${diffX}px, 0, 0)`;
                  }
                }
              }
            }
          }, { passive: false });

          this.body.addEventListener('touchend', (e) => {
            const isImg = !!this.body.querySelector('img.lightbox-media');
            this.lastTouchEndTime = Date.now();

            if (e.touches.length === 0) {
              if (this.scale > 1) {
                this.isTouchPanning = false;
                this.clampPan();
                this.applyTransform(true);
              }

              const touchEndX = e.changedTouches[0].clientX;
              const touchEndY = e.changedTouches[0].clientY;
              const diffX = touchEndX - this.touchStartX;
              const diffY = touchEndY - this.touchStartY;
              const duration = Date.now() - this.touchStartTime;

              // Tap detection
              if (Math.abs(diffX) < 10 && Math.abs(diffY) < 10 && duration < 250) {
                const now = Date.now();
                if (now - this.lastTapTime < 300 && isImg) {
                  clearTimeout(this.tapTimeout);
                  this.lastTapTime = 0;
                  if (this.scale > 1.2) {
                    this.resetTransform(true);
                  } else {
                    this.scale = 2.5;
                    const rect = this.body.getBoundingClientRect();
                    const tapX = touchEndX - rect.left - rect.width / 2;
                    const tapY = touchEndY - rect.top - rect.height / 2;
                    this.panX = -tapX * 1.5;
                    this.panY = -tapY * 1.5;
                    this.clampPan();
                    this.applyTransform(true);
                  }
                  return;
                }

                this.lastTapTime = now;
                clearTimeout(this.tapTimeout);
                this.tapTimeout = setTimeout(() => {
                  this.toggleUI(true);
                  this.lastTapTime = 0;
                }, 260);
                return;
              }

              // Swipe between images when not zoomed
              if (this.scale === 1) {
                const img = this.body.querySelector('.lightbox-media');
                if (Math.abs(this.swipeDeltaX) > 55) {
                  const dir = this.swipeDeltaX < 0 ? 1 : -1;
                  if (img) {
                    img.classList.add('smooth-zoom');
                    img.style.transform = `translate3d(${dir > 0 ? -100 : 100}vw, 0, 0)`;
                    img.style.opacity = '0';
                  }
                  setTimeout(() => {
                    this.resetTransform(false);
                    this.nav(dir);
                  }, 180);
                } else if (img) {
                  img.classList.add('smooth-zoom');
                  img.style.transform = 'translate3d(0, 0, 0)';
                }
                this.swipeDeltaX = 0;
              }
            }
          });
        }

        zoomIn(factor = 1.35) {
          const img = this.body.querySelector('.lightbox-media');
          if (!img || img.tagName !== 'IMG') return;
          this.scale = Math.min(5, this.scale * factor);
          this.clampPan();
          this.applyTransform(true);
        }

        zoomOut(factor = 1.35) {
          const img = this.body.querySelector('.lightbox-media');
          if (!img || img.tagName !== 'IMG') return;
          this.scale = Math.max(1, this.scale / factor);
          if (this.scale === 1) {
            this.panX = 0;
            this.panY = 0;
          } else {
            this.clampPan();
          }
          this.applyTransform(true);
        }

        clampPan() {
          const img = this.body.querySelector('.lightbox-media');
          if (!img || this.scale <= 1) {
            this.panX = 0;
            this.panY = 0;
            return;
          }
          const bodyRect = this.body.getBoundingClientRect();
          const imgW = img.offsetWidth || bodyRect.width;
          const imgH = img.offsetHeight || bodyRect.height;
          const maxPanX = Math.max(0, (imgW * this.scale - bodyRect.width) / 2);
          const maxPanY = Math.max(0, (imgH * this.scale - bodyRect.height) / 2);
          this.panX = Math.min(maxPanX, Math.max(-maxPanX, this.panX));
          this.panY = Math.min(maxPanY, Math.max(-maxPanY, this.panY));
        }

        applyTransform(smooth = false) {
          const img = this.body.querySelector('.lightbox-media');
          if (!img) return;
          img.classList.toggle('smooth-zoom', smooth);
          img.classList.toggle('zoomed', this.scale > 1);
          img.style.setProperty('transform', `translate3d(${this.panX}px, ${this.panY}px, 0) scale(${this.scale})`, 'important');
        }

        applyTransform(smooth = false) {
          const img = this.body.querySelector('.lightbox-media');
          if (!img) return;
          img.classList.toggle('smooth-zoom', smooth);
          img.classList.toggle('zoomed', this.scale > 1);
          img.style.transform = `translate3d(${this.panX}px, ${this.panY}px, 0) scale(${this.scale})`;
        }

        resetTransform(smooth = false) {
          this.scale = 1;
          this.panX = 0;
          this.panY = 0;
          this.swipeDeltaX = 0;
          const img = this.body.querySelector('.lightbox-media');
          if (img) {
            img.classList.toggle('smooth-zoom', smooth);
            img.classList.remove('zoomed');
            img.style.transform = 'translate3d(0px, 0px, 0px) scale(1)';
            img.style.opacity = '1';
          }
        }

        toggleSlideshow() {
          if (this.isSlideshowActive) {
            this.stopSlideshow();
          } else {
            this.startSlideshow();
          }
        }

        startSlideshow() {
          if (!this.mediaList || this.mediaList.length <= 1) return;
          const isImg = (item) => {
            if (!item) return false;
            const ext = (item.name ? item.name.split('.').pop() : '').toLowerCase();
            return item.type === 'image' || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'].includes(ext);
          };
          if (!this.mediaList.some(isImg)) return;

          this.isSlideshowActive = true;
          this.updateSlideshowUI();

          if (!isImg(this.mediaList[this.currentIndex])) {
            this.navNextImage();
          } else {
            this.runSlideshowStep();
          }
        }

        navNextImage() {
          if (!this.mediaList || this.mediaList.length === 0) return;
          const len = this.mediaList.length;
          let nextIdx = (this.currentIndex + 1) % len;
          let count = 0;

          const isImg = (item) => {
            if (!item) return false;
            const ext = (item.name ? item.name.split('.').pop() : '').toLowerCase();
            return item.type === 'image' || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'].includes(ext);
          };

          while (!isImg(this.mediaList[nextIdx]) && count < len) {
            nextIdx = (nextIdx + 1) % len;
            count++;
          }

          if (isImg(this.mediaList[nextIdx])) {
            this.resetTransform(false);
            this.currentIndex = nextIdx;
            this.loadCurrent();
          } else {
            this.stopSlideshow();
          }
        }

        runSlideshowStep() {
          if (!this.isSlideshowActive) return;
          this.resetSlideshowProgress();
          clearTimeout(this.slideshowTimer);
          this.slideshowTimer = setTimeout(() => {
            if (!this.isSlideshowActive) return;
            const img = this.body.querySelector('.lightbox-media');
            if (img && img.tagName === 'IMG') {
              img.classList.add('disintegrate');
              setTimeout(() => {
                if (!this.isSlideshowActive) return;
                this.navNextImage();
              }, 500);
            } else {
              this.navNextImage();
            }
          }, 14500);
        }

        resetSlideshowProgress() {
          const track = document.getElementById('lb-slideshow-track');
          const bar = document.getElementById('lb-slideshow-bar');
          if (!track || !bar) return;
          if (!this.isSlideshowActive) {
            track.style.display = 'none';
            bar.style.transition = 'none';
            bar.style.width = '0%';
            return;
          }
          track.style.display = 'block';
          bar.style.transition = 'none';
          bar.style.width = '0%';
          void bar.offsetWidth; // Force layout reflow
          bar.style.transition = 'width 15s linear';
          bar.style.width = '100%';
        }

        stopSlideshow() {
          this.isSlideshowActive = false;
          clearTimeout(this.slideshowTimer);
          this.slideshowTimer = null;
          this.updateSlideshowUI();
          this.resetSlideshowProgress();
        }

        updateSlideshowUI() {
          const btn = document.getElementById('btn-lb-slideshow');
          if (!btn) return;
          btn.classList.toggle('active', this.isSlideshowActive);
          btn.title = this.isSlideshowActive ? 'Pause Slideshow' : 'Play Slideshow (15s)';
          btn.innerHTML = this.isSlideshowActive
            ? '<svg viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>'
            : '<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';
        }

        updateStarUI(path) {
          const isStarred = window.app ? app.starredSet.has(path) : false;
          const starBtn = document.getElementById('btn-lb-star');
          const starIcon = document.getElementById('lb-star-icon');
          if (starBtn && starIcon) {
            starBtn.classList.toggle('active', isStarred);
            starIcon.innerHTML = isStarred
              ? '<path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>'
              : '<path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/>';
          }
        }

        renderCarousel() {
          if (!this.carouselEl) return;
          this.carouselEl.innerHTML = '';
          
          // Determine initial slice window surrounding current position (25 items per load)
          const total = this.mediaList.length;
          const batchCount = Math.min(total, Math.max(this.carouselBatchSize, this.currentIndex + 12));
          this.carouselLoadedCount = batchCount;

          for (let i = 0; i < batchCount; i++) {
            this.appendCarouselTile(i);
          }
          this.scrollCarouselToActive();
        }

        loadMoreCarouselItems() {
          if (!this.carouselEl || this.carouselLoadedCount >= this.mediaList.length) return;
          const start = this.carouselLoadedCount;
          const end = Math.min(this.mediaList.length, start + this.carouselBatchSize);
          for (let i = start; i < end; i++) {
            this.appendCarouselTile(i);
          }
          this.carouselLoadedCount = end;
        }

        appendCarouselTile(index) {
          const item = this.mediaList[index];
          if (!item) return;

          const tile = document.createElement('div');
          tile.className = `lb-carousel-item ${index === this.currentIndex ? 'active' : ''}`;
          tile.dataset.index = index;
          tile.style.position = 'relative';

          const ext = (item.name ? item.name.split('.').pop() : '').toLowerCase();
          const isVid = item.type === 'video' || ['mp4', 'webm', 'mov', 'm4v', 'ogv', 'mkv', 'avi', 'ts', '3gp', 'wmv', 'flv'].includes(ext);
          const isImage = item.type === 'image' || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'].includes(ext);

          let fallbackIconSvg = '<svg viewBox="0 0 24 24"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>';
          if (isVid) {
            fallbackIconSvg = '<svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>';
          } else if (isImage) {
            fallbackIconSvg = '<svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>';
          }

          const thumbUrl = `?action=thumb&f=${encodeURIComponent(item.path)}`;
          const onErrorLogic = isVid 
            ? `if(!this.dataset.tried){this.dataset.tried=true; window.app.captureVideoThumb(this, '${encodeURIComponent(item.path)}');}else{this.remove();}` 
            : `this.remove();`;

          tile.innerHTML = `
            <div class="lb-carousel-icon" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; z-index:1;">
              ${fallbackIconSvg}
            </div>
            <img src="${thumbUrl}" loading="lazy" decoding="async" alt="" style="position:relative; z-index:2; width:100%; height:100%; object-fit:cover; display:block; background-color:#1a1a1a;" onerror="${onErrorLogic}">
          `;

          tile.addEventListener('click', (e) => {
            e.stopPropagation();
            if (index !== this.currentIndex) {
              this.resetTransform(false);
              this.currentIndex = index;
              this.loadCurrent();
            }
          });

          this.carouselEl.appendChild(tile);
        }

        scrollCarouselToActive() {
          if (!this.carouselEl) return;
          while (this.currentIndex >= this.carouselLoadedCount && this.carouselLoadedCount < this.mediaList.length) {
            this.loadMoreCarouselItems();
          }

          this.carouselEl.querySelectorAll('.lb-carousel-item').forEach(el => {
            el.classList.toggle('active', parseInt(el.dataset.index) === this.currentIndex);
          });

          const activeTile = this.carouselEl.querySelector(`.lb-carousel-item[data-index="${this.currentIndex}"]`);
          if (activeTile) {
            const scrollTarget = activeTile.offsetLeft - (this.carouselEl.clientWidth / 2) + (activeTile.clientWidth / 2);
            this.carouselEl.scrollTo({ left: Math.max(0, scrollTarget), behavior: 'smooth' });
          }
        }

        open(mediaList, startIndex) {
          this.mediaList = mediaList || [];
          this.currentIndex = startIndex || 0;
          this.isPinnedUI = false;
          this.el.classList.add('active');
          this.showUI(true);
          this.renderCarousel();
          this.loadCurrent();
        }

        cleanupMedia() {
          const activeMedia = this.body.querySelectorAll('video, audio');
          activeMedia.forEach(m => {
            try {
              m.pause();
              m.removeAttribute('src');
              m.load();
            } catch(e) {}
          });
        }

        preloadAdjacent() {
          if (!this.mediaList || this.mediaList.length <= 1) return;
          const indices = [];
          const len = this.mediaList.length;
          for (let offset = -2; offset <= 2; offset++) {
            if (offset === 0) continue;
            const targetIdx = (this.currentIndex + offset + len * 2) % len;
            if (!indices.includes(targetIdx)) {
              indices.push(targetIdx);
            }
          }
          // Only preload image decodes and audio/video thumbnail covers (never full video streams)
          indices.forEach(idx => {
            const item = this.mediaList[idx];
            if (item) {
              const ext = (item.name ? item.name.split('.').pop() : '').toLowerCase();
              const isImg = item.type === 'image' || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'].includes(ext);
              const img = new Image();
              img.src = isImg ? `?action=raw&f=${encodeURIComponent(item.path)}` : `?action=thumb&f=${encodeURIComponent(item.path)}`;
            }
          });
        }

        loadCurrent() {
          const item = this.mediaList[this.currentIndex];
          if (!item) return;

          this.cleanupMedia();
          this.scale = 1;
          this.panX = 0;
          this.panY = 0;
          this.swipeDeltaX = 0;

          const targetRel = ltrim(item.path, '/');
          let currentDecoded = '';
          try { currentDecoded = decodeURIComponent(window.location.hash.replace(/^#\/?/, '')); } catch (e) { currentDecoded = window.location.hash.replace(/^#\/?/, ''); }

          if (currentDecoded !== targetRel) {
            if (window.history && window.history.replaceState) {
              window.history.replaceState(null, '', '#/' + encodeURI(targetRel));
            } else {
              window.location.hash = '#/' + encodeURI(targetRel);
            }
          }

          const fileName = item.name || item.path.split('/').pop() || '';
          this.title.innerText = `${fileName} (${this.currentIndex + 1}/${this.mediaList.length})`;
          if (window.app) app.updateDocTitle(fileName);

          const rawUrl = `?action=raw&f=${encodeURIComponent(item.path)}`;
          const ext = (fileName.split('.').pop() || '').toLowerCase();
          const isImage = item.type === 'image' || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'].includes(ext);
          const isAudio = item.type === 'audio' || ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma', 'm4r', 'mid', 'midi'].includes(ext);
          const isVideo = item.type === 'video' || ['mp4', 'webm', 'mov', 'm4v', 'ogv', 'mkv', 'avi', 'ts', '3gp', 'wmv', 'flv'].includes(ext);

          const btnZoomIn = document.getElementById('btn-lb-zoom-in');
          const btnZoomOut = document.getElementById('btn-lb-zoom-out');
          if (btnZoomIn) btnZoomIn.style.display = isImage ? 'flex' : 'none';
          if (btnZoomOut) btnZoomOut.style.display = isImage ? 'flex' : 'none';
          const btnSlideshow = document.getElementById('btn-lb-slideshow');
          if (btnSlideshow) btnSlideshow.style.display = isImage ? 'flex' : 'none';
          const btnSearchGoogle = document.getElementById('btn-lb-search-google');
          if (btnSearchGoogle) btnSearchGoogle.style.display = isImage ? 'flex' : 'none';
          const btnLbEdit = document.getElementById('btn-lb-edit');
          if (btnLbEdit) btnLbEdit.style.display = isImage ? 'flex' : 'none';
          const btnLbManga = document.getElementById('btn-lb-manga');
          if (btnLbManga) btnLbManga.style.display = isImage ? 'flex' : 'none';

          this.updateStarUI(item.path);
          this.scrollCarouselToActive();
          this.preloadAdjacent();

          if (this.isSlideshowActive) {
            if (!isImage) {
              this.stopSlideshow();
            } else {
              this.runSlideshowStep();
            }
          }

          if (isVideo) {
            this.body.innerHTML = `
              <video class="lightbox-media" src="${rawUrl}" controls autoplay playsinline preload="metadata" style="max-height:100%; max-width:100%; width:auto; height:auto; object-fit:contain; background:#000; margin:0;">
                Your browser does not support HTML5 video.
              </video>
            `;
            const vid = this.body.querySelector('video');
            if (vid) vid.play().catch(() => { vid.controls = true; });
          } else if (isAudio) {
            const thumbUrl = `?action=thumb&f=${encodeURIComponent(item.path)}`;
            this.body.innerHTML = `
              <div class="lightbox-audio-card">
                <div class="lightbox-audio-badge">Audio Track</div>
                <div class="lightbox-audio-disc-wrap">
                  <div class="lightbox-audio-disc" id="lb-audio-disc" style="background-image:url('${thumbUrl}'); background-size:cover; background-position:center;">
                    <svg viewBox="0 0 24 24" style="background:rgba(0,0,0,0.45); border-radius:50%; padding:2px;"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>
                  </div>
                </div>
                <div class="lightbox-audio-title">${fileName}</div>
                <audio src="${rawUrl}" controls autoplay preload="auto">
                  Your browser does not support HTML5 audio.
                </audio>
              </div>
            `;
            const aud = this.body.querySelector('audio');
            const disc = document.getElementById('lb-audio-disc');
            if (aud) {
              aud.addEventListener('play', () => disc?.classList.add('spinning'));
              aud.addEventListener('pause', () => disc?.classList.remove('spinning'));
              aud.addEventListener('ended', () => disc?.classList.remove('spinning'));
              aud.load();
              aud.play().catch(() => { aud.controls = true; });
            }
          } else {
            this.body.innerHTML = '';
            
            const img = document.createElement('img');
            img.className = 'lightbox-media reconstruct';
            img.id = 'lb-img';
            img.alt = fileName;
            img.decoding = 'async';
            
            const onReady = () => {
              requestAnimationFrame(() => {
                img.classList.add('ready');
                this.resetTransform(false);
              });
            };

            img.onload = onReady;
            img.onerror = onReady;
            img.src = rawUrl;
            
            this.body.appendChild(img);
            this.resetTransform(false);
          }
        }

        nav(dir) {
          if (!this.mediaList || this.mediaList.length <= 1) return;
          if (this.isPinnedUI) {
            this.showUI(false);
          } else {
            this.hideUI();
          }
          this.resetTransform(false);
          this.currentIndex = (this.currentIndex + dir + this.mediaList.length) % this.mediaList.length;
          this.loadCurrent();
        }

        close(updateHash = true) {
          this.stopSlideshow();
          this.isPinnedUI = false;
          this.cleanupMedia();
          this.resetTransform(false);
          this.el.classList.remove('active');
          this.body.innerHTML = '';

          if (updateHash && window.app) {
            const returnSection = app.originSection || (app.currentSection !== 'home' ? app.currentSection : null);
            if (returnSection) {
              app.originSection = null;
              const targetHash = `#/@${returnSection}`;
              if (window.location.hash !== targetHash) {
                window.location.hash = targetHash;
              } else {
                app.switchDriveSection(returnSection, false);
              }
              return;
            }

            let parentDir = '';
            const currentItem = this.mediaList[this.currentIndex];
            if (currentItem && currentItem.path && currentItem.path.includes('/')) {
              const parts = currentItem.path.split('/');
              parts.pop();
              parentDir = parts.join('/');
            } else if (app.currentPath) {
              parentDir = app.currentPath;
            }

            const targetHash = parentDir ? '#/' + ltrim(parentDir, '/') : '#/';
            if (window.location.hash !== targetHash) {
              window.location.hash = targetHash;
            } else {
              app.currentPath = parentDir;
              app.updateDocTitle(parentDir ? parentDir.split('/').pop() : '');
            }
          }
        }
      }
  
      const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
      const IS_DEMO = <?= $isDemo ? 'true' : 'false' ?>;
      const AUTH_ENABLED = <?= $config['auth_enabled'] ? 'true' : 'false' ?>;

      class GalleryApp {
        constructor() {
          this.appTitle = '<?= addslashes(htmlspecialchars($config['app_title'])) ?>';
          this.isAdmin = IS_ADMIN;
          this.isDemo = IS_DEMO;
          this.currentPath = null;
          this.data = { folders: [], files: [], stats: {} };
          this.filter = 'all';
          this.sortBy = localStorage.getItem('pg_sort') || 'name_asc';
          this.searchQuery = '';
          this.selectedItems = new Set();
          this.layout = localStorage.getItem('pg_layout') || 'grid';
          this.theme = localStorage.getItem('pg_theme') || 'dark';
          this.gridCols = parseInt(localStorage.getItem('pg_grid_cols')) || 0;
          this.gridGap = parseInt(localStorage.getItem('pg_grid_gap')) || 12;
          this.gridRadius = parseInt(localStorage.getItem('pg_grid_radius')) || 14;
          this.renderLimit = 25;
          this.filteredList = [];
          this.searchDebounceTimer = null;
          this.searchSeq = 0;
          this.navSeq = 0;
          this.isSearching = false;
          this.advFilters = { ext: '', type: '', date_from: '', date_to: '', size_min: '', size_max: '' };
          this.starredSet = new Set();
          this.currentSection = 'home';
          this.expandedTreeNodes = new Set(JSON.parse(localStorage.getItem('pg_tree_expanded') || '[]'));
          this.modalStack = [];
  
          this.initDOM();
          this.convertLegacyUrl();
          this.loadStarredSet();
          this.bindEvents();
          this.initAuthEvents();
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
          this.dropdownGridAdjust = document.getElementById('dropdown-grid-adjust');
          this.btnGridAdjust = document.getElementById('btn-grid-adjust');
          this.btnResetGridAdjust = document.getElementById('btn-reset-grid-adjust');
          this.sliderCols = document.getElementById('slider-cols');
          this.sliderColsVal = document.getElementById('slider-cols-val');
          this.sliderGap = document.getElementById('slider-gap');
          this.sliderGapVal = document.getElementById('slider-gap-val');
          this.sliderRadius = document.getElementById('slider-radius');
          this.sliderRadiusVal = document.getElementById('slider-radius-val');
          this.sliderColsMob = document.getElementById('slider-cols-mob');
          this.sliderColsValMob = document.getElementById('slider-cols-val-mob');
          this.sliderGapMob = document.getElementById('slider-gap-mob');
          this.sliderGapValMob = document.getElementById('slider-gap-val-mob');
          this.sliderRadiusMob = document.getElementById('slider-radius-mob');
          this.sliderRadiusValMob = document.getElementById('slider-radius-val-mob');
          this.btnResetGridAdjustMob = document.getElementById('btn-reset-grid-adjust-mob');
          this.scrollTrigger = document.getElementById('infinite-scroll-trigger');
        }
  
        bindEvents() {
          document.getElementById('btn-sidebar').addEventListener('click', () => {
            if (window.innerWidth <= 768) {
              this.sidebar.classList.remove('collapsed');
              this.sidebar.classList.toggle('open');
              this.sidebarBackdrop.classList.toggle('active');
            } else {
              this.sidebar.classList.remove('open');
              this.sidebar.classList.toggle('collapsed');
              localStorage.setItem('pg_sidebar_collapsed', this.sidebar.classList.contains('collapsed') ? '1' : '0');
            }
          });

          window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
              this.sidebar.classList.remove('open');
              this.sidebarBackdrop.classList.remove('active');
              if (localStorage.getItem('pg_sidebar_collapsed') === '1') {
                this.sidebar.classList.add('collapsed');
              } else {
                this.sidebar.classList.remove('collapsed');
              }
            } else {
              this.sidebar.classList.remove('collapsed');
            }
          });

          document.getElementById('dm-shortcuts')?.addEventListener('click', () => {
            this.dropdownMore.classList.remove('active');
            this.showModal('modal-shortcuts');
          });

          document.getElementById('btn-shortcuts-desk')?.addEventListener('click', () => {
            this.showModal('modal-shortcuts');
          });

          // Global Keyboard Shortcuts
          window.addEventListener('keydown', (e) => {
            const inInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) || document.activeElement?.classList.contains('CodeMirror-code');
            const hasModalOpen = document.getElementById('modal-backdrop').classList.contains('active');
            const isLightbox = document.getElementById('lightbox').classList.contains('active');

            if (!inInput && !hasModalOpen && !isLightbox) {
              if (e.key === '?' || e.key === 'F1') {
                e.preventDefault();
                this.showModal('modal-shortcuts');
              } else if (e.key === '/' || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f')) {
                e.preventDefault();
                document.getElementById('search-input')?.focus();
              } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'a') {
                e.preventDefault();
                this.toggleSelectAll();
              } else if (e.key === 'Escape') {
                this.clearSelection();
              } else if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'n') {
                e.preventDefault();
                document.getElementById('btn-new-folder')?.click();
              } else if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                document.getElementById('btn-new-file')?.click();
              } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c') {
                if (this.selectedItems.size > 0) {
                  e.preventDefault();
                  this.setClipboard('copy');
                }
              } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'x') {
                if (this.selectedItems.size > 0) {
                  e.preventDefault();
                  this.setClipboard('cut');
                }
              } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'v') {
                if (this.clipboard && this.clipboard.items.length > 0) {
                  e.preventDefault();
                  this.pasteClipboard();
                }
              } else if (e.key === 'Delete') {
                if (this.selectedItems.size > 0) {
                  e.preventDefault();
                  this.batchDelete();
                }
              } else if (e.key === 'F2') {
                if (this.selectedItems.size === 1) {
                  e.preventDefault();
                  const target = Array.from(this.selectedItems)[0];
                  this.renameItem(target, target.split('/').pop());
                }
              }
            }
          });

          this.sidebar.addEventListener('transitionend', (e) => {
            if (e.propertyName === 'margin-left' && this.layout === 'columns') {
              this.renderLimit = 25;
              this.renderGallery();
            }
          });

          if (window.ResizeObserver && this.container) {
            let lastCols = this.getMasonryColCount();
            const ro = new ResizeObserver(() => {
              if (this.layout === 'columns') {
                const currentCols = this.getMasonryColCount();
                if (currentCols !== lastCols) {
                  lastCols = currentCols;
                  this.renderLimit = 25;
                  this.renderGallery();
                }
              }
            });
            ro.observe(this.container);
          }
  
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
  
          const btnAuthDesk = document.getElementById('btn-auth-desk');
          if (btnAuthDesk) {
            btnAuthDesk.addEventListener('click', () => {
              if (this.isAdmin && AUTH_ENABLED) {
                if (confirm('You are logged in as Admin. Do you want to logout?')) {
                  window.location.href = '?action=logout';
                }
              } else {
                this.showLoginModal();
              }
            });
          }

          const dmLogin = document.getElementById('dm-login');
          if (dmLogin) {
            dmLogin.addEventListener('click', () => this.showLoginModal());
          }
  
          document.querySelectorAll('[data-layout]').forEach(btn => {
            btn.addEventListener('click', (e) => this.setLayout(e.currentTarget.dataset.layout));
          });
  
          document.querySelectorAll('.filter-item[data-filter]').forEach(pill => {
            pill.addEventListener('click', (e) => {
              document.querySelectorAll('.filter-item[data-filter]').forEach(p => p.classList.remove('active'));
              e.currentTarget.classList.add('active');
              this.filter = e.currentTarget.dataset.filter || 'all';
              this.sidebar.classList.remove('open');
              this.sidebarBackdrop.classList.remove('active');
              this.renderLimit = 25;

              if (this.currentSection === 'activity') {
                this.renderActivityView();
              } else if (this.currentSection === 'trash') {
                this.loadTrash();
              } else {
                this.renderGallery();
              }
            });
          });
  
          document.getElementById('search-input').addEventListener('input', (e) => {
            const q = e.target.value.trim();
            this.searchQuery = q;
            clearTimeout(this.searchDebounceTimer);

            // If user cleared the query and no advanced filters are active, cancel dimming immediately
            if (q.length === 0 && !this.hasActiveAdvFilters()) {
              this.isSearching = false;
              this.searchSeq++;
              this.container.style.opacity = '1';
              this.renderLimit = 25;
              if (this.currentSection === 'activity') {
                this.renderActivityView();
              } else if (this.currentSection === 'trash') {
                this.renderTrashView();
              } else if (this.currentSection === 'starred') {
                this.loadStarred();
              } else if (this.currentSection === 'recents') {
                this.loadRecents();
              } else if (this.currentSection === 'gallery') {
                this.loadGallery();
              } else {
                this.renderGallery();
              }
              return;
            }

            this.searchDebounceTimer = setTimeout(() => {
              this.performSearch(q);
            }, 260);
          });

          // Modal backdrop click listener to safely dismiss modals
          const mb = document.getElementById('modal-backdrop');
          if (mb) {
            mb.addEventListener('click', (e) => {
              if (e.target.id === 'modal-backdrop') {
                this.closeModals();
              }
            });
          }

          // Advanced Search Button & Dialog Listeners
          const btnAdv = document.getElementById('btn-adv-search');
          if (btnAdv) {
            btnAdv.addEventListener('click', (e) => {
              e.stopPropagation();
              document.getElementById('adv-ext').value = this.advFilters.ext || '';
              document.getElementById('adv-type').value = this.advFilters.type || '';
              document.getElementById('adv-date-from').value = this.advFilters.date_from || '';
              document.getElementById('adv-date-to').value = this.advFilters.date_to || '';
              document.getElementById('adv-size-min').value = this.advFilters.size_min || '';
              document.getElementById('adv-size-max').value = this.advFilters.size_max || '';
              this.showModal('modal-advanced-search');
            });
          }

          document.getElementById('btn-adv-reset')?.addEventListener('click', () => {
            this.advFilters = { ext: '', type: '', date_from: '', date_to: '', size_min: '', size_max: '' };
            document.getElementById('adv-ext').value = '';
            document.getElementById('adv-type').value = '';
            document.getElementById('adv-date-from').value = '';
            document.getElementById('adv-date-to').value = '';
            document.getElementById('adv-size-min').value = '';
            document.getElementById('adv-size-max').value = '';
            this.updateAdvBtnState();
            this.closeModals();
            this.performSearch(this.searchQuery);
          });

          document.getElementById('btn-adv-apply')?.addEventListener('click', () => {
            this.advFilters.ext = document.getElementById('adv-ext').value.trim();
            this.advFilters.type = document.getElementById('adv-type').value.trim();
            this.advFilters.date_from = document.getElementById('adv-date-from').value.trim();
            this.advFilters.date_to = document.getElementById('adv-date-to').value.trim();
            this.advFilters.size_min = document.getElementById('adv-size-min').value.trim();
            this.advFilters.size_max = document.getElementById('adv-size-max').value.trim();
            this.updateAdvBtnState();
            this.closeModals();
            this.performSearch(this.searchQuery);
          });
  
          const openManga = () => mangaViewer.open();
          document.getElementById('btn-manga-desk').addEventListener('click', openManga);
          document.getElementById('dm-manga').addEventListener('click', openManga);

          document.getElementById('btn-refresh-desk')?.addEventListener('click', () => this.refresh());
          document.getElementById('dm-refresh-mob')?.addEventListener('click', () => {
            this.dropdownMore.classList.remove('active');
            this.refresh();
          });

          const driveSidebar = document.getElementById('sidebar');
          const driveResizer = document.getElementById('sidebar-resizer');
          if (driveSidebar && driveResizer) {
            const savedDriveSidebarWidth = localStorage.getItem('pg_drive_sidebar_width');
            if (savedDriveSidebarWidth && window.innerWidth > 768) {
              driveSidebar.style.setProperty('--sidebar-width', savedDriveSidebarWidth + 'px');
            }

            let isResizingDriveSidebar = false;
            driveResizer.addEventListener('mousedown', (e) => {
              isResizingDriveSidebar = true;
              driveResizer.classList.add('resizing');
              document.body.style.cursor = 'col-resize';
              e.preventDefault();
            });
            document.addEventListener('mousemove', (e) => {
              if (!isResizingDriveSidebar) return;
              const newW = e.clientX - driveSidebar.getBoundingClientRect().left;
              if (newW >= 200 && newW <= 600) {
                driveSidebar.style.setProperty('--sidebar-width', newW + 'px');
              }
            });
            document.addEventListener('mouseup', () => {
              if (isResizingDriveSidebar) {
                isResizingDriveSidebar = false;
                driveResizer.classList.remove('resizing');
                document.body.style.cursor = 'default';
                const currentW = getComputedStyle(driveSidebar).getPropertyValue('--sidebar-width').replace('px', '').trim();
                if (currentW) localStorage.setItem('pg_drive_sidebar_width', currentW);
                this.applyGridSizing();
              }
            });
          }
  
          const fileInput = document.getElementById('file-uploader');
          const folderInput = document.getElementById('folder-uploader');
          const dropdownUpload = document.getElementById('dropdown-upload');

          const toggleUploadDropdown = (e) => {
            e.stopPropagation();
            const rect = e.currentTarget.getBoundingClientRect();
            dropdownUpload.style.top = `${rect.bottom + 8}px`;
            dropdownUpload.style.right = `${window.innerWidth - rect.right}px`;
            dropdownUpload.classList.toggle('active');
          };

          document.getElementById('btn-upload-desk').addEventListener('click', toggleUploadDropdown);
          document.getElementById('du-upload-files').addEventListener('click', () => { dropdownUpload.classList.remove('active'); fileInput.click(); });
          document.getElementById('du-upload-folder').addEventListener('click', () => { dropdownUpload.classList.remove('active'); folderInput.click(); });
          document.getElementById('du-upload-url').addEventListener('click', () => { dropdownUpload.classList.remove('active'); this.promptUploadUrl(); });

          document.getElementById('dm-upload-files').addEventListener('click', () => fileInput.click());
          document.getElementById('dm-upload-folder').addEventListener('click', () => folderInput.click());
          document.getElementById('dm-upload-url').addEventListener('click', () => this.promptUploadUrl());
  
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
  
          if (this.btnGridAdjust && this.dropdownGridAdjust) {
            this.btnGridAdjust.addEventListener('click', (e) => {
              e.stopPropagation();
              const rect = this.btnGridAdjust.getBoundingClientRect();
              this.dropdownGridAdjust.style.top = `${rect.bottom + 8}px`;
              this.dropdownGridAdjust.style.right = `${window.innerWidth - rect.right}px`;
              this.dropdownGridAdjust.classList.toggle('active');
            });
          }

          // Isolate all slider and dropdown interactions from window closing listeners
          const isolatedElements = [
            this.dropdownGridAdjust,
            this.dropdownMore,
            document.getElementById('mobile-grid-adjust-container'),
            this.sliderCols, this.sliderGap, this.sliderRadius,
            this.sliderColsMob, this.sliderGapMob, this.sliderRadiusMob
          ];
          isolatedElements.forEach(el => {
            if (el) {
              ['click', 'mousedown', 'mouseup', 'touchstart', 'touchend', 'pointerdown', 'pointerup', 'input', 'change'].forEach(evt => {
                el.addEventListener(evt, e => {
                  if (!e.target.closest('.dm-item:not(#btn-reset-grid-adjust):not(#btn-reset-grid-adjust-mob)')) {
                    e.stopPropagation();
                  }
                });
              });
            }
          });

          if (this.sliderCols) {
            this.sliderCols.value = this.gridCols;
            this.sliderCols.addEventListener('input', (e) => {
              this.gridCols = parseInt(e.target.value);
              localStorage.setItem('pg_grid_cols', this.gridCols);
              this.applyGridSizing(true);
            });
          }

          if (this.sliderGap) {
            this.sliderGap.value = this.gridGap;
            this.sliderGap.addEventListener('input', (e) => {
              this.gridGap = parseInt(e.target.value);
              localStorage.setItem('pg_grid_gap', this.gridGap);
              this.applyGridSizing(false);
            });
          }

          if (this.sliderRadius) {
            this.sliderRadius.value = this.gridRadius;
            this.sliderRadius.addEventListener('input', (e) => {
              this.gridRadius = parseInt(e.target.value);
              localStorage.setItem('pg_grid_radius', this.gridRadius);
              this.applyGridSizing(false);
            });
          }

          const handleResetLayout = (e) => {
            if (e) e.stopPropagation();
            this.gridCols = 0;
            this.gridGap = 12;
            this.gridRadius = 14;
            localStorage.removeItem('pg_grid_cols');
            localStorage.removeItem('pg_grid_gap');
            localStorage.removeItem('pg_grid_radius');

            this.applyGridSizing();
            this.toast('Layout reset to default');
          };

          if (this.btnResetGridAdjust) {
            this.btnResetGridAdjust.addEventListener('click', handleResetLayout);
          }

          if (this.btnResetGridAdjustMob) {
            this.btnResetGridAdjustMob.addEventListener('click', handleResetLayout);
          }

          if (this.sliderColsMob) {
            this.sliderColsMob.addEventListener('input', (e) => {
              this.gridCols = parseInt(e.target.value);
              localStorage.setItem('pg_grid_cols', this.gridCols);
              this.applyGridSizing(true);
            });
          }

          if (this.sliderGapMob) {
            this.sliderGapMob.addEventListener('input', (e) => {
              this.gridGap = parseInt(e.target.value);
              localStorage.setItem('pg_grid_gap', this.gridGap);
              this.applyGridSizing(false);
            });
          }

          if (this.sliderRadiusMob) {
            this.sliderRadiusMob.addEventListener('input', (e) => {
              this.gridRadius = parseInt(e.target.value);
              localStorage.setItem('pg_grid_radius', this.gridRadius);
              this.applyGridSizing(false);
            });
          }
  
          let dragCounter = 0;
          window.addEventListener('dragenter', (e) => {
            e.preventDefault();
            dragCounter++;
            document.getElementById('dropzone')?.classList.add('active');
          });

          window.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dragCounter--;
            if (dragCounter <= 0) {
              dragCounter = 0;
              document.getElementById('dropzone')?.classList.remove('active');
            }
          });

          window.addEventListener('dragover', (e) => {
            e.preventDefault();
          });

          window.addEventListener('drop', async (e) => {
            e.preventDefault();
            dragCounter = 0;
            document.getElementById('dropzone')?.classList.remove('active');
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
  
          document.getElementById('btn-select-all')?.addEventListener('click', () => {
            this.toggleSelectAll();
          });

          document.getElementById('btn-folder-info').addEventListener('click', () => {
            this.showDetails(this.currentPath);
          });
  
          document.getElementById('btn-download-dir').addEventListener('click', () => {
            const targetDir = this.currentPath || '';
            const zipName = (targetDir.split('/').pop() || 'gallery') + '.zip';
            this.downloadZipWithProgress(`?action=download_zip&dir=${encodeURIComponent(targetDir)}`, null, zipName);
          });

          document.getElementById('btn-drive-clipboard-paste')?.addEventListener('click', () => {
            this.pasteClipboard();
          });

          document.getElementById('btn-drive-clipboard-cancel')?.addEventListener('click', () => {
            this.clearClipboard();
          });

          document.getElementById('btn-batch-clear').addEventListener('click', () => this.clearSelection());
          document.getElementById('btn-batch-download').addEventListener('click', () => this.batchDownload());
          document.getElementById('btn-batch-delete').addEventListener('click', () => this.batchDelete());

          // Batch Bar 3-Dots Menu Toggle
          const btnBatchMore = document.getElementById('btn-batch-more');
          const dropdownBatchMore = document.getElementById('dropdown-batch-more');
          if (btnBatchMore && dropdownBatchMore) {
            btnBatchMore.addEventListener('click', (e) => {
              e.stopPropagation();
              const rect = btnBatchMore.getBoundingClientRect();
              dropdownBatchMore.style.bottom = `${window.innerHeight - rect.top + 10}px`;
              dropdownBatchMore.style.top = 'auto';
              dropdownBatchMore.style.left = `${Math.max(10, Math.min(rect.left - 90, window.innerWidth - 230))}px`;
              dropdownBatchMore.classList.toggle('active');
            });
          }

          document.getElementById('dbm-rename')?.addEventListener('click', () => {
            dropdownBatchMore?.classList.remove('active');
            this.openBatchRename();
          });
          document.getElementById('dbm-compress')?.addEventListener('click', () => {
            dropdownBatchMore?.classList.remove('active');
            this.batchCompress();
          });
          document.getElementById('dbm-info')?.addEventListener('click', () => {
            dropdownBatchMore?.classList.remove('active');
            this.showBatchDetails();
          });
          document.getElementById('dbm-copy')?.addEventListener('click', () => {
            dropdownBatchMore?.classList.remove('active');
            this.setClipboard('copy');
          });
          document.getElementById('dbm-cut')?.addEventListener('click', () => {
            dropdownBatchMore?.classList.remove('active');
            this.setClipboard('cut');
          });

          // Batch Rename Inputs Live Listener
          ['br-find', 'br-replace', 'br-prefix', 'br-suffix', 'br-case', 'br-regex'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => this.updateBatchRenamePreview());
            document.getElementById(id)?.addEventListener('change', () => this.updateBatchRenamePreview());
          });
          document.querySelectorAll('input[name="br-target"]').forEach(r => {
            r.addEventListener('change', () => this.updateBatchRenamePreview());
          });
          document.getElementById('br-confirm-btn')?.addEventListener('click', () => this.executeBatchRename());
  
          window.addEventListener('hashchange', () => this.handleHashChange());
  
          if (this.btnSort) {
            this.btnSort.addEventListener('click', (e) => this.openSortDropdown(e));
          }
  
          document.querySelectorAll('#dropdown-sort .dm-item').forEach(item => {
            item.addEventListener('click', (e) => {
              const sortMode = e.currentTarget.dataset.sort;
              if (sortMode) {
                this.sortBy = sortMode;
                localStorage.setItem('pg_sort', sortMode);
                this.dropdownSort.classList.remove('active');
                this.renderLimit = 25;
                if (this.currentSection === 'activity') {
                  this.renderActivityView();
                } else if (this.currentSection === 'trash') {
                  this.renderTrashView();
                } else if (this.currentSection === 'starred') {
                  this.loadStarred();
                } else if (this.currentSection === 'recents') {
                  this.loadRecents();
                } else if (this.currentSection === 'gallery') {
                  this.loadGallery();
                } else {
                  this.renderGallery();
                }
              }
            });
          });
  
          const closeAllDropdowns = () => {
            this.contextMenu.classList.remove('active');
            this.dropdownMore.classList.remove('active');
            this.dropdownGridAdjust?.classList.remove('active');
            if (this.dropdownSort) this.dropdownSort.classList.remove('active');
            document.getElementById('dropdown-upload')?.classList.remove('active');
            document.getElementById('dropdown-batch-more')?.classList.remove('active');
          };

          window.addEventListener('click', closeAllDropdowns);
          document.getElementById('main-content')?.addEventListener('scroll', closeAllDropdowns, { passive: true });
          document.getElementById('search-input')?.addEventListener('focus', closeAllDropdowns);
          this.dropdownMore.addEventListener('click', (e) => {
            if (e.target.closest('.dm-item')) {
              this.dropdownMore.classList.remove('active');
            } else {
              e.stopPropagation();
            }
          });
          if (this.dropdownSort) this.dropdownSort.addEventListener('click', (e) => e.stopPropagation());
  
          const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                this.renderLimit += 25;
                if (this.currentSection === 'activity') {
                  if (this.renderLimit < (this.rawActivities?.length || 0) + 25) this.renderActivityView();
                } else if (this.currentSection === 'trash') {
                  if (this.renderLimit < (this.rawTrash?.length || 0) + 25) this.renderTrashView();
                } else if (this.renderLimit < this.filteredList.length + 25) {
                  this.appendBatch();
                }
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
          let cols = 5;
          if (w < 520) cols = 2;
          else if (w < 840) cols = 3;
          else if (w < 1200) cols = 4;
          else if (w < 1600) cols = 5;
          else cols = 6;

          const isCollapsed = this.sidebar && this.sidebar.classList.contains('collapsed');
          if (isCollapsed && window.innerWidth > 768) {
            cols += 1;
          }
          return cols;
        }
  
        setLayout(layout) {
          this.layout = layout;
          localStorage.setItem('pg_layout', layout);
          this.updateLayoutUI();
          this.updateControlsVisibility();
          this.container.className = `gallery-container layout-${layout}`;
          this.applyGridSizing();
          this.renderLimit = 25;
          this.renderGallery();
        }

        updateLayoutUI() {
          document.querySelectorAll('[data-layout]').forEach(b => {
            if (b.dataset.layout === this.layout) {
              b.classList.add('active');
            } else {
              b.classList.remove('active');
            }
          });
        }
  
        updateControlsVisibility() {
          const isStarred = this.currentSection === 'starred';
          const isTrash = this.currentSection === 'trash';
          const isActivity = this.currentSection === 'activity';
          const isGallery = this.currentSection === 'gallery';
          const isRecents = this.currentSection === 'recents';
          const hideUpload = isStarred || isTrash || isActivity || isGallery;
          const hideNewItems = isStarred || isTrash || isActivity || isGallery;
          const hideManga = isTrash || isActivity || isStarred || isRecents;

          // Centralized Content Header & Toolbar Visibility
          const contentHeader = document.querySelector('.content-header');
          if (contentHeader) {
            contentHeader.style.display = (isTrash || isActivity) ? 'none' : 'flex';
          }

          const toolbar = document.querySelector('.toolbar-actions');
          if (toolbar) {
            toolbar.style.display = (isTrash || isActivity) ? 'none' : 'flex';
          }

          // Hide Layout Settings button in Trash & Activity
          const btnGridAdjust = document.getElementById('btn-grid-adjust');
          if (btnGridAdjust) {
            btnGridAdjust.style.display = (isTrash || isActivity) ? 'none' : 'flex';
          }

          // In List Layout, hide the Columns adjustment slider (Desktop & Mobile)
          const colsSliderContainer = document.getElementById('slider-cols')?.closest('.slider-container');
          if (colsSliderContainer) {
            colsSliderContainer.style.display = (this.layout === 'list') ? 'none' : 'flex';
          }

          const mobColsContainer = document.getElementById('mobile-cols-container');
          if (mobColsContainer) {
            mobColsContainer.style.display = (this.layout === 'list') ? 'none' : 'flex';
          }

          // Hide mobile grid adjustments completely in Trash & Activity
          const mobAdjustContainer = document.getElementById('mobile-grid-adjust-container');
          const mobColsSep = document.getElementById('mobile-cols-sep');
          if (mobAdjustContainer) {
            mobAdjustContainer.style.display = (isTrash || isActivity) ? 'none' : 'block';
          }
          if (mobColsSep) {
            mobColsSep.style.display = (isTrash || isActivity) ? 'none' : 'block';
          }

          // Hide Manga Mode in Trash, Activity, Starred & Recents
          const btnMangaDesk = document.getElementById('btn-manga-desk');
          const dmManga = document.getElementById('dm-manga');
          if (btnMangaDesk) btnMangaDesk.style.display = hideManga ? 'none' : 'flex';
          if (dmManga) dmManga.style.display = hideManga ? 'none' : 'flex';
    
          // Desktop Upload Button
          const btnUploadDesk = document.getElementById('btn-upload-desk');
          if (btnUploadDesk) btnUploadDesk.style.display = hideUpload ? 'none' : 'flex';
    
          // Mobile Upload Menu Items
          ['dm-upload-files', 'dm-upload-folder', 'dm-upload-url'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = hideUpload ? 'none' : 'flex';
          });
    
          // New Folder / New File buttons
          const btnNewFolder = document.getElementById('btn-new-folder');
          const btnNewFile = document.getElementById('btn-new-file');
          if (btnNewFolder) btnNewFolder.style.display = hideNewItems ? 'none' : 'flex';
          if (btnNewFile) btnNewFile.style.display = hideNewItems ? 'none' : 'flex';
    
          // Column slider visibility
          const isList = this.layout === 'list';
          const showCols = !isTrash && !isActivity && !isList;
          const deskCols = document.getElementById('desk-cols-container');
          const mobCols = document.getElementById('mobile-cols-container');
          const mobSep = document.getElementById('mobile-cols-sep');
          if (deskCols) deskCols.style.display = showCols ? 'flex' : 'none';
          if (mobCols) mobCols.style.display = showCols ? 'flex' : 'none';
          if (mobSep) mobSep.style.display = showCols ? 'block' : 'none';
        }
    
        applyGridSizing(recalcColumns = false) {
          const text = this.gridCols > 0 ? `${this.gridCols}` : 'Auto';
          if (this.container) {
            if (this.gridCols > 0 && this.layout !== 'list' && this.currentSection !== 'activity' && this.currentSection !== 'trash') {
              this.container.setAttribute('data-cols', this.gridCols);
            } else {
              this.container.removeAttribute('data-cols');
            }
            this.container.style.setProperty('--grid-gap', `${this.gridGap}px`);
            this.container.style.setProperty('--card-radius', `${this.gridRadius}px`);
          }

          if (this.sliderColsVal) this.sliderColsVal.innerText = text;
          if (this.sliderGapVal) this.sliderGapVal.innerText = `${this.gridGap}px`;
          if (this.sliderRadiusVal) this.sliderRadiusVal.innerText = `${this.gridRadius}px`;

          if (this.sliderCols) this.sliderCols.value = this.gridCols;
          if (this.sliderGap) this.sliderGap.value = this.gridGap;
          if (this.sliderRadius) this.sliderRadius.value = this.gridRadius;

          if (this.sliderColsMob) this.sliderColsMob.value = this.gridCols;
          if (this.sliderGapMob) this.sliderGapMob.value = this.gridGap;
          if (this.sliderRadiusMob) this.sliderRadiusMob.value = this.gridRadius;

          if (this.sliderColsValMob) this.sliderColsValMob.innerText = text;
          if (this.sliderGapValMob) this.sliderGapValMob.innerText = `${this.gridGap}px`;
          if (this.sliderRadiusValMob) this.sliderRadiusValMob.innerText = `${this.gridRadius}px`;

          document.documentElement.style.setProperty('--grid-gap', `${this.gridGap}px`);
          document.documentElement.style.setProperty('--card-radius', `${this.gridRadius}px`);

          const heightMap = { 0: '200px', 1: '380px', 2: '320px', 3: '270px', 4: '220px', 5: '185px', 6: '155px', 8: '125px' };
          if (this.container) {
            this.container.style.setProperty('--justified-row-height', heightMap[this.gridCols] || '200px');
          }

          if (recalcColumns && (this.layout === 'columns' || this.layout === 'justified') && this.currentSection !== 'activity' && this.currentSection !== 'trash') {
            this.renderLimit = 25;
            this.renderGallery(true);
          }
        }
  
        async runServerTaskWithProgress(title, act, data, callback) {
          const scrollEl = document.getElementById('main-content');
          if (!this.savedScrollTop && scrollEl) {
            this.savedScrollTop = scrollEl.scrollTop;
          }

          const toastId = 'task_toast_' + Date.now();
          const container = document.getElementById('toast-container');
          const el = document.createElement('div');
          el.className = 'toast';
          el.id = toastId;
          el.style.display = 'flex';
          el.style.flexDirection = 'column';
          el.style.alignItems = 'stretch';
          el.style.gap = '0.4rem';
          el.style.minWidth = '240px';
          el.innerHTML = `
            <div style="display:flex; justify-content:space-between; font-weight:600; font-size:0.8rem;">
              <span id="${toastId}_label">${title}...</span>
              <span id="${toastId}_pct">0%</span>
            </div>
            <div style="height:5px; width:100%; background:var(--md-sys-color-surface-container-high); border-radius:3px; overflow:hidden;">
              <div id="${toastId}_bar" style="height:100%; width:0%; background:var(--md-sys-color-primary); transition:width 0.2s linear;"></div>
            </div>
          `;
          container.appendChild(el);

          let pct = 0;
          const interval = setInterval(() => {
            pct += (90 - pct) * 0.1;
            const pctEl = document.getElementById(`${toastId}_pct`);
            const barEl = document.getElementById(`${toastId}_bar`);
            if (pctEl) pctEl.innerText = Math.round(pct) + '%';
            if (barEl) barEl.style.width = pct + '%';
          }, 500);

          try {
            const fd = new FormData();
            fd.append('action', act);
            for (let k in data) {
              if (Array.isArray(data[k])) {
                data[k].forEach(val => fd.append(`${k}[]`, val));
              } else {
                fd.append(k, data[k]);
              }
            }
            const res = await fetch('', { method: 'POST', body: fd });
            const json = await res.json();
            clearInterval(interval);
            const pctEl = document.getElementById(`${toastId}_pct`);
            const barEl = document.getElementById(`${toastId}_bar`);
            if (pctEl) pctEl.innerText = '100%';
            if (barEl) barEl.style.width = '100%';
            
            setTimeout(() => el.remove(), 1500);
            
            if (json.success) {
              if (callback) callback(json);
            } else {
              throw new Error(json.error || 'Failed');
            }
          } catch (e) {
            clearInterval(interval);
            el.innerHTML = `<span style="color:var(--md-sys-color-error);">${e.message}</span>`;
            setTimeout(() => el.remove(), 3500);
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
  
        convertLegacyUrl() {
          const urlParams = new URLSearchParams(window.location.search);
          if (urlParams.has('action')) return;

          const pathParam = urlParams.get('path') || urlParams.get('dir') || '';
          const fileParam = urlParams.get('edit') || urlParams.get('file') || urlParams.get('f') || urlParams.get('open') || '';

          if (pathParam || fileParam) {
            let target = '';
            if (pathParam && fileParam) {
              const cleanP = pathParam.replace(/^\/+|\/+$/g, '');
              const cleanF = fileParam.replace(/^\/+|\/+$/g, '');
              target = cleanP ? `${cleanP}/${cleanF}` : cleanF;
            } else {
              target = (pathParam || fileParam).replace(/^\/+|\/+$/g, '');
            }

            if (target) {
              const cleanUrl = window.location.pathname + '#/' + encodeURI(target);
              window.history.replaceState(null, '', cleanUrl);
            }
          }
        }

        navigate(path) {
          window.location.hash = '#/' + ltrim(path, '/');
        }
  
        handleHashChange() {
          const raw = window.location.hash.replace(/^#\/?/, '');
          let decoded = '';
          try { decoded = decodeURIComponent(raw); } catch (e) { decoded = raw; }
          decoded = decoded.replace(/^\/+|\/+$/g, '').replace(/\.part$/i, '');

          // Distinguish special virtual tabs (@gallery, @videos, @audio, @recents, etc.) from physical folders
          if (decoded.startsWith('@')) {
            const secName = decoded.substring(1).toLowerCase();
            const specialSections = ['recents', 'starred', 'activity', 'trash', 'gallery', 'videos', 'audio', 'documents'];
            if (specialSections.includes(secName)) {
              if (window.lightbox && lightbox.el && lightbox.el.classList.contains('active')) {
                lightbox.close(false);
              }
              this.switchDriveSection(secName, false);
              return;
            }
          }

          if (!decoded) {
            if (window.lightbox && lightbox.el && lightbox.el.classList.contains('active')) {
              lightbox.close(false);
            }
            if (this.currentSection !== 'home' || this.currentPath !== '') {
              this.switchDriveSection('home', false);
            }
            return;
          }
  
          const segments = decoded.split('/');
          const lastSegment = segments[segments.length - 1];
          const isFilePath = /\.[a-zA-Z0-9]{1,8}$/.test(lastSegment);

          if (isFilePath) {
            const dirPath = segments.slice(0, -1).join('/');
            const targetFile = decoded.replace(/^\/+/, '');

            const activeItem = (window.lightbox && lightbox.el && lightbox.el.classList.contains('active'))
              ? (lightbox.mediaList ? lightbox.mediaList[lightbox.currentIndex] : null)
              : null;
            const activePath = activeItem ? (activeItem.path || '').replace(/^\/+/, '') : '';

            if (activePath && activePath === targetFile) {
              return;
            }

            const specialSections = ['recents', 'starred', 'activity', 'trash', 'gallery', 'videos', 'audio', 'documents'];
            if (this.currentSection && specialSections.includes(this.currentSection)) {
              this.originSection = this.currentSection;
              this.openFile(targetFile, false);
              return;
            }

            if (this.currentPath !== dirPath) {
              this.loadDir(dirPath).finally(() => {
                this.openFile(targetFile, false);
              });
            } else {
              this.openFile(targetFile, false);
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
  
        async loadDir(path, clearSearch = true) {
          const seq = ++this.navSeq;
          this.currentSection = 'home';
          this.updateControlsVisibility();
          document.querySelectorAll('#nav-home, #nav-recents, #nav-starred, #nav-activity, #nav-trash, #nav-gallery').forEach(el => el.classList.remove('active'));
          document.getElementById('nav-home')?.classList.add('active');
          this.currentPath = path;
          this.selectedItems.clear();
          this.updateBatchBar();

          if (clearSearch) {
            this.isSearching = false;
            const searchInput = document.getElementById('search-input');
            if (searchInput) searchInput.value = '';
            this.searchQuery = '';
          }

          this.sidebar.classList.remove('open');
          this.sidebarBackdrop.classList.remove('active');
          this.updateTreeActive();

          // Only wipe container with a short spinner if navigating to a new directory without saved scroll
          if (!this.savedScrollTop) {
            this.renderLimit = 25;
            this.container.style.opacity = '1';
            this.container.innerHTML = `
              <div class="center-state">
                <svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
                <div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading files...</div>
              </div>
            `;
          } else {
            this.container.style.opacity = '0.7';
          }

          try {
            const res = await fetch(`?action=list&dir=${encodeURIComponent(path || '')}`);
            if (seq !== this.navSeq) return;
            if (!res.ok) {
              if (path && path !== '') {
                this.toast(`Folder "${path}" not found. Redirecting to Home...`);
                this.navigate('');
                return;
              }
              throw new Error('Failed to load directory');
            }
            const freshData = await res.json();
            if (seq !== this.navSeq) return;
            this.data = freshData;
            this.renderGallery(!!this.savedScrollTop);
            this.updateBreadcrumbs();
            this.updateBadges();
            const totalItems = (freshData.folders ? freshData.folders.length : 0) + (freshData.files ? freshData.files.length : 0);
            this.updateDocTitle(this.data.path ? this.data.path.split('/').pop() : '', totalItems);
          } catch (e) {
            if (seq !== this.navSeq) return;
            this.container.innerHTML = `
              <div class="center-state" style="color:var(--md-sys-color-error);">
                <p>${e.message}</p>
                <button class="btn-primary" style="margin-top:0.6rem;" onclick="app.navigate('')">Back to Home</button>
              </div>
            `;
          }
        }
  
        async performSearch(query = '') {
          this.isSearching = true;
          this.renderLimit = 25;
          const queryText = query !== undefined ? query : this.searchQuery;
          const seq = ++this.searchSeq;

          if (!queryText && !this.hasActiveAdvFilters()) {
            this.isSearching = false;
            this.container.style.opacity = '1';
            this.renderLimit = 25;
            if (this.currentSection === 'activity') this.renderActivityView();
            else if (this.currentSection === 'trash') this.renderTrashView();
            else if (this.currentSection === 'starred') this.loadStarred();
            else if (this.currentSection === 'recents') this.loadRecents();
            else if (this.currentSection === 'gallery') this.loadGallery();
            else this.renderGallery();
            return;
          }

          if (this.currentSection === 'activity') {
            this.container.style.opacity = '1';
            this.renderActivityView();
            return;
          }
          if (this.currentSection === 'trash') {
            this.container.style.opacity = '1';
            this.renderTrashView();
            return;
          }

          if (this.currentSection === 'gallery') {
            this.container.style.opacity = '1';
            let list = (this.data.files || []).filter(f => f.name.toLowerCase().includes(queryText.toLowerCase()));
            if (this.advFilters.ext) {
              const exts = this.advFilters.ext.toLowerCase().split(',').map(s => s.trim().replace('.', ''));
              list = list.filter(f => exts.includes((f.ext || '').toLowerCase()));
            }
            this.filteredList = this.applySort(list);
            this.dirTitle.innerText = `Gallery Search: "${queryText || 'Filtered'}"`;
            this.dirStats.innerText = `${this.filteredList.length} matching photo(s) found`;
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
            if (!this.filteredList.length) {
              this.container.innerHTML = `<div class="center-state"><p>No matching photos found</p></div>`;
              return;
            }
            this.appendBatch();
            return;
          }

          this.dirStats.innerText = `Searching in files & subfolders...`;
          this.container.style.opacity = '0.6';

          const params = new URLSearchParams();
          params.append('action', 'search');
          params.append('dir', this.currentPath || '');
          params.append('q', queryText);
          if (this.advFilters.ext) params.append('ext', this.advFilters.ext);
          if (this.advFilters.type) params.append('type', this.advFilters.type);
          if (this.advFilters.date_from) params.append('date_from', this.advFilters.date_from);
          if (this.advFilters.date_to) params.append('date_to', this.advFilters.date_to);
          if (this.advFilters.size_min) params.append('size_min', parseFloat(this.advFilters.size_min) * 1024 * 1024);
          if (this.advFilters.size_max) params.append('size_max', parseFloat(this.advFilters.size_max) * 1024 * 1024);

          try {
            const res = await fetch(`?${params.toString()}`);
            if (seq !== this.searchSeq) return; // Stale search result discarded
            if (!res.ok) throw new Error('Search failed');
            const results = await res.json();
            if (seq !== this.searchSeq) return;

            let filteredFiles = (results.files || []).filter(f => this.filter === 'all' || f.type === this.filter);
            let filteredFolders = this.filter === 'all' ? (results.folders || []) : [];

            filteredFolders = this.applySort(filteredFolders);
            filteredFiles = this.applySort(filteredFiles);

            this.filteredList = [
              ...filteredFolders.map(f => ({ ...f, isDir: true })),
              ...filteredFiles.map(f => ({ ...f, isDir: false }))
            ];

            this.dirTitle.innerText = queryText ? `Search: "${queryText}"` : 'Advanced Search Results';
            this.dirStats.innerText = `${this.filteredList.length} matching item(s) found`;

            this.container.style.opacity = '1';
            this.container.innerHTML = '';
            this.renderedCount = 0;
            this.renderLimit = 25;
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

            if (!this.filteredList.length) {
              this.container.innerHTML = `<div class="center-state"><svg viewBox="0 0 24 24" style="width:48px; height:48px; opacity:0.4;"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><p>No matches found</p></div>`;
              return;
            }

            this.appendBatch();
            this.updateBatchBar();
          } catch (e) {
            this.container.style.opacity = '1';
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
  
        hasActiveAdvFilters() {
          const f = this.advFilters;
          return !!(f.ext || f.type || f.date_from || f.date_to || f.size_min || f.size_max);
        }

        updateAdvBtnState() {
          const btn = document.getElementById('btn-adv-search');
          if (btn) {
            if (this.hasActiveAdvFilters()) {
              btn.classList.add('active');
            } else {
              btn.classList.remove('active');
            }
          }
        }

        applySort(items) {
          return [...items].sort((a, b) => {
            const nameA = a.name || '';
            const nameB = b.name || '';
            if (this.sortBy === 'name_asc') {
              return nameA.localeCompare(nameB, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'name_desc') {
              return nameB.localeCompare(nameA, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'date_desc') {
              return (b.mtime || 0) - (a.mtime || 0);
            }
            if (this.sortBy === 'date_asc') {
              return (a.mtime || 0) - (b.mtime || 0);
            }
            if (this.sortBy === 'size_desc') {
              return (b.size || 0) - (a.size || 0);
            }
            if (this.sortBy === 'size_asc') {
              return (a.size || 0) - (b.size || 0);
            }
            if (this.sortBy === 'ext_asc') {
              const extA = (a.ext || (a.name ? a.name.split('.').pop() : '')).toLowerCase();
              const extB = (b.ext || (b.name ? b.name.split('.').pop() : '')).toLowerCase();
              return extA.localeCompare(extB, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'ext_desc') {
              const extA = (a.ext || (a.name ? a.name.split('.').pop() : '')).toLowerCase();
              const extB = (b.ext || (b.name ? b.name.split('.').pop() : '')).toLowerCase();
              return extB.localeCompare(extA, undefined, { numeric: true, sensitivity: 'base' });
            }
            return 0;
          });
        }
  
        getSortLabel() {
          const labels = {
            name_asc: 'Name (A-Z)',
            name_desc: 'Name (Z-A)',
            date_desc: 'Date (Newest)',
            date_asc: 'Date (Oldest)',
            size_desc: 'Size (Largest)',
            size_asc: 'Size (Smallest)',
            ext_asc: 'Extension (A-Z)',
            ext_desc: 'Extension (Z-A)'
          };
          return labels[this.sortBy] || 'Sort';
        }

        openSortDropdown(e) {
          e.stopPropagation();
          const target = e.currentTarget || this.btnSort;
          const rect = target.getBoundingClientRect();
          this.dropdownSort.style.top = `${rect.bottom + 8}px`;
          this.dropdownSort.style.left = `${Math.min(rect.left, window.innerWidth - 230)}px`;
          this.dropdownSort.classList.toggle('active');
          this.updateSortUI();
        }

        updateSortUI() {
          if (this.btnSort) {
            this.btnSort.title = `Sort: ${this.getSortLabel()}`;
          }
          document.querySelectorAll('#dropdown-sort .dm-item').forEach(el => {
            if (el.dataset.sort === this.sortBy) {
              el.classList.add('active');
            } else {
              el.classList.remove('active');
            }
          });
        }
  
        renderGallery(preserveScroll = false) {
          this.updateLayoutUI();
          this.updateControlsVisibility();
          this.container.style.opacity = '1';
          this.container.className = `gallery-container layout-${this.layout}`;
          this.applyGridSizing(false);
          const toolbar = document.querySelector('.toolbar-actions');
          if (toolbar && this.currentSection !== 'trash' && this.currentSection !== 'activity') {
            toolbar.style.display = 'flex';
          }

          const scrollEl = document.getElementById('main-content');
          const savedScroll = (preserveScroll || this.savedScrollTop) ? (this.savedScrollTop || (scrollEl ? scrollEl.scrollTop : 0)) : 0;
          
          // Lock minimum container height so the browser layout engine never collapses scroll to top during DOM refresh
          if (savedScroll > 0) {
            this.container.style.minHeight = (savedScroll + (scrollEl ? scrollEl.clientHeight : 800)) + 'px';
            const estRowHeight = (this.layout === 'list') ? 56 : 140;
            const cols = (this.layout === 'list') ? 1 : this.getMasonryColCount();
            const neededRows = Math.ceil(savedScroll / estRowHeight) + 8;
            const neededItems = Math.max(25, neededRows * cols);
            if (neededItems > this.renderLimit) {
              this.renderLimit = Math.min(this.filteredList.length, neededItems);
            }
          }

          const activeFilter = this.filter || 'all';
          let filteredFiles = (this.data.files || []).filter(f => activeFilter === 'all' || f.type === activeFilter);
          let filteredFolders = activeFilter === 'all' ? (this.data.folders || []) : [];

          if (this.currentSection === 'recents') {
            // Strictly sort all items chronologically by newest modification timestamp
            this.filteredList = [
              ...filteredFolders.map(f => ({ ...f, isDir: true })),
              ...filteredFiles.map(f => ({ ...f, isDir: false }))
            ];
            this.filteredList.sort((a, b) => (b.mtime || 0) - (a.mtime || 0));
          } else {
            filteredFolders = this.applySort(filteredFolders);
            filteredFiles = this.applySort(filteredFiles);

            this.filteredList = [
              ...filteredFolders.map(f => ({ ...f, isDir: true })),
              ...filteredFiles.map(f => ({ ...f, isDir: false }))
            ];
          }
  
          if (this.currentSection === 'starred') {
            this.dirTitle.innerText = 'Starred Items';
            this.dirStats.innerText = `${filteredFolders.length} Folders, ${filteredFiles.length} Files (${this.data.stats?.total_size || '0 B'})`;
          } else if (this.currentSection === 'recents') {
            this.dirTitle.innerText = 'Recents';
            this.dirStats.innerText = `${filteredFolders.length} Folders, ${filteredFiles.length} Files`;
          } else if (this.currentSection === 'activity') {
            this.dirTitle.innerText = 'File Activity';
          } else if (this.currentSection === 'trash') {
            this.dirTitle.innerText = 'Trash Bin';
          } else if (this.currentSection === 'gallery') {
            this.dirTitle.innerText = 'Gallery';
            this.dirStats.innerText = `${filteredFiles.length} Photos (${this.data.stats?.total_size || '0 B'})`;
          } else if (this.currentSection === 'videos') {
            this.dirTitle.innerText = 'Videos';
            this.dirStats.innerText = `${filteredFiles.length} Videos (${this.data.stats?.total_size || '0 B'})`;
          } else if (this.currentSection === 'audio') {
            this.dirTitle.innerText = 'Audio';
            this.dirStats.innerText = `${filteredFiles.length} Audio Tracks (${this.data.stats?.total_size || '0 B'})`;
          } else if (this.currentSection === 'documents') {
            this.dirTitle.innerText = 'Documents & Archives';
            this.dirStats.innerText = `${filteredFiles.length} Document(s) & Archive(s) (${this.data.stats?.total_size || '0 B'})`;
          } else {
            this.dirTitle.innerText = this.data.path ? this.data.path.split('/').pop() : this.appTitle;
            this.dirStats.innerText = `${filteredFolders.length} Folders, ${filteredFiles.length} Files (${this.data.stats?.total_size || '0 B'})`;
          }
  
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
  
          if (this.data.path && this.currentSection === 'home') {
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

          if (savedScroll > 0 && scrollEl) {
            scrollEl.scrollTop = savedScroll;
            requestAnimationFrame(() => {
              scrollEl.scrollTop = savedScroll;
              this.savedScrollTop = 0;
              this.container.style.minHeight = '';
            });
          } else {
            this.container.style.minHeight = '';
          }
        }
  
        appendBatch() {
          const toRender = this.filteredList.slice(this.renderedCount, this.renderLimit);
          if (!toRender.length) return;

          const isColumns = this.layout === 'columns';
          const fragment = isColumns ? null : document.createDocumentFragment();

          toRender.forEach((item, idx) => {
            const card = document.createElement('div');
            const isSel = this.selectedItems.has(item.path);
            card.className = `file-card ${item.isDir ? 'is-folder' : 'is-file'} ${isSel ? 'selected' : ''}`;
            card.dataset.path = item.path;

            const formattedDate = item.mtime ? new Date(item.mtime * 1000).toLocaleDateString(undefined, { year: '2-digit', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }) : '';
            const isStarred = this.starredSet.has(item.path);
            const starSvg = isStarred
              ? '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'
              : '<svg viewBox="0 0 24 24"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>';

            if (item.isDir) {
              const folderRatio = (this.layout === 'columns' || this.layout === 'list') ? '' : 'style="aspect-ratio: 1 / 1; min-height: 140px;"';
              card.onclick = (e) => app.handleItemClick(e, 'folder', item.path);
              card.oncontextmenu = (e) => app.showContextMenu(e, 'folder', item.path, item.name);

              // Direct Folder Drag & Drop without opening the folder
              card.ondragover = (e) => {
                e.preventDefault();
                e.stopPropagation();
                card.classList.add('drag-over-folder');
              };
              card.ondragenter = (e) => {
                e.preventDefault();
                e.stopPropagation();
                card.classList.add('drag-over-folder');
              };
              card.ondragleave = (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!card.contains(e.relatedTarget)) {
                  card.classList.remove('drag-over-folder');
                }
              };
              card.ondrop = async (e) => {
                e.preventDefault();
                e.stopPropagation();
                card.classList.remove('drag-over-folder');
                document.getElementById('dropzone')?.classList.remove('active');

                let count = 0;
                if (e.dataTransfer.items && e.dataTransfer.items.length) {
                  const itemsList = await app.readDropData(e.dataTransfer.items);
                  if (itemsList.length) {
                    count = itemsList.length;
                    uploadManager.enqueue(itemsList, item.path);
                  }
                } else if (e.dataTransfer.files && e.dataTransfer.files.length) {
                  const itemsList = Array.from(e.dataTransfer.files).map(f => ({ file: f, relativePath: f.name }));
                  count = itemsList.length;
                  uploadManager.enqueue(itemsList, item.path);
                }
                if (count > 0) {
                  app.toast(`Uploading ${count} item(s) directly to "${item.name}"`);
                }
              };

              if (item.thumb_image) card.classList.add('has-image');

              let folderThumbHtml = `
                <div class="type-icon type-folder" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; z-index:1;">
                  <svg viewBox="0 0 16 16"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg>
                </div>
              `;

              if (item.thumb_image) {
                folderThumbHtml += `<img src="?action=thumb&f=${encodeURIComponent(item.thumb_image)}" alt="" loading="lazy" decoding="async" style="position:relative; z-index:2; width:100%; height:100%; object-fit:cover;" onerror="this.remove(); this.closest('.file-card')?.classList.remove('has-image');">`;
              }

              card.innerHTML = `
                <div class="file-checkbox"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                <div class="file-thumb" ${folderRatio}>${folderThumbHtml}</div>
                <div class="file-info-overlay">
                  <div class="file-name"></div>
                  <div class="file-meta">
                    <span>${this.layout === 'list' && formattedDate ? formattedDate + ' • ' : ''}${item.items_count !== undefined ? item.items_count + ' items' : ''}</span>
                    <span>FOLDER</span>
                  </div>
                </div>
                <div class="file-star-btn ${isStarred ? 'active' : ''}" title="${isStarred ? 'Unstar' : 'Star'}">${starSvg}</div>
                <div class="folder-drop-overlay">
                  <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
                  <span>Drop to upload into<br><strong>${this.escapeHtml(item.name)}</strong></span>
                </div>
              `;
            } else {
              let thumbHtml = '';
              let thumbRatio = this.layout === 'list' ? '' : (this.layout === 'columns' ? '' : 'style="aspect-ratio: 1 / 1; min-height: 140px;"');
              const ext = item.ext || '';

              if (item.type === 'image') {
                card.classList.add('has-image');
                thumbHtml = `
                  <div class="type-icon" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#80cbc4; z-index:1;">
                    <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                  </div>
                  <img src="?action=thumb&f=${encodeURIComponent(item.path)}" alt="" loading="lazy" decoding="async" style="position:relative; z-index:2; width:100%; height:100%; object-fit:cover;" onload="this.style.opacity='1'; if(this.naturalWidth && this.naturalHeight && window.app && window.app.layout==='justified'){ const c=this.closest('.file-card'); if(c){ const r=this.naturalWidth/this.naturalHeight; c.style.setProperty('--card-grow', r); c.style.setProperty('--card-ratio', r); } }" onerror="this.remove(); this.closest('.file-card')?.classList.remove('has-image');">
                `;
                if (this.layout === 'columns') {
                  thumbRatio = 'style="min-height:140px; height:auto;"';
                }
              } else if (item.type === 'video') {
                thumbHtml = `
                  <div class="type-icon" style="color:#a8c7fa;"><svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg></div>
                  <img src="?action=thumb&f=${encodeURIComponent(item.path)}" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0; transition:opacity 0.2s; z-index:3;" onload="this.style.opacity='1'; this.closest('.file-card')?.classList.add('has-image');" onerror="app.captureVideoThumb(this, '${encodeURIComponent(item.path)}')">
                `;
              } else if (item.type === 'audio') {
                thumbHtml = `
                  <div class="type-icon" style="color:#f2b8b5;"><svg viewBox="0 0 24 24"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg></div>
                  <img src="?action=thumb&f=${encodeURIComponent(item.path)}" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0; transition:opacity 0.2s; z-index:3;" onload="this.style.opacity='1'; this.closest('.file-card')?.classList.add('has-image');" onerror="this.remove();">
                `;
              } else if (item.type === 'archive') {
                thumbHtml = `<div class="type-icon" style="color:#fde293;"><svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg></div>`;
              } else {
                thumbHtml = `<div class="type-icon" style="color:#80cbc4;"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg></div>`;
              }

              card.onclick = (e) => app.handleItemClick(e, 'file', item.path);
              card.oncontextmenu = (e) => app.showContextMenu(e, 'file', item.path, item.name, item.type);
              card.innerHTML = `
                <div class="file-checkbox"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                <div class="file-thumb" ${thumbRatio}>${thumbHtml}</div>
                <div class="file-info-overlay">
                  <div class="file-name"></div>
                  <div class="file-meta">
                    <span>${this.layout === 'list' && formattedDate ? formattedDate + ' • ' : ''}${item.size_fmt || '0 B'}</span>
                    <span>${this.layout === 'list' ? ext.toUpperCase() : (item.width ? `${item.width}×${item.height}` : ext.toUpperCase())}</span>
                  </div>
                </div>
                <div class="file-star-btn ${isStarred ? 'active' : ''}" title="${isStarred ? 'Unstar' : 'Star'}">${starSvg}</div>
              `;
            }

            const nameEl = card.querySelector('.file-name');
            if (nameEl) {
              nameEl.innerText = item.name;
              nameEl.title = item.name;
            }

            const checkEl = card.querySelector('.file-checkbox');
            if (checkEl) checkEl.onclick = (e) => app.toggleSelect(e, item.path);

            const starEl = card.querySelector('.file-star-btn');
            if (starEl) starEl.onclick = (e) => app.toggleStarDirect(e, item.path);

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
          if (e.target.closest('.file-checkbox') || e.target.closest('.file-star-btn')) return;
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
          const isSelected = this.selectedItems.has(path);
          if (isSelected) {
            this.selectedItems.delete(path);
          } else {
            this.selectedItems.add(path);
          }

          // In-place DOM update prevents destroying the gallery and preserves exact scroll
          const card = this.container.querySelector(`.file-card[data-path="${CSS.escape(path)}"]`);
          if (card) {
            card.classList.toggle('selected', !isSelected);
          }

          this.updateBatchBar();
        }
  
        toggleSelectAll() {
          if (this.selectedItems.size === this.filteredList.length && this.filteredList.length > 0) {
            this.clearSelection();
          } else {
            this.filteredList.forEach(item => this.selectedItems.add(item.path));
            this.container.querySelectorAll('.file-card').forEach(c => c.classList.add('selected'));
            this.updateBatchBar();
          }
        }

        clearSelection() {
          this.selectedItems.clear();
          const dbm = document.getElementById('dropdown-batch-more');
          if (dbm) dbm.classList.remove('active');
          
          // Instantly uncheck visible cards without wiping the DOM or resetting scroll
          this.container.querySelectorAll('.file-card.selected').forEach(c => c.classList.remove('selected'));
          this.updateBatchBar();
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
  
        async downloadZipWithProgress(url, postData = null, defaultFilename = 'archive.zip') {
          const toastId = 'zip_toast_' + Date.now();
          const container = document.getElementById('toast-container');
          const el = document.createElement('div');
          el.className = 'toast';
          el.id = toastId;
          el.style.display = 'flex';
          el.style.flexDirection = 'column';
          el.style.alignItems = 'stretch';
          el.style.gap = '0.4rem';
          el.style.minWidth = '240px';
          el.innerHTML = `
            <div style="display:flex; justify-content:space-between; font-weight:600; font-size:0.8rem;">
              <span id="${toastId}_label">Preparing ZIP archive...</span>
              <span id="${toastId}_pct">0%</span>
            </div>
            <div style="height:5px; width:100%; background:var(--md-sys-color-surface-container-high); border-radius:3px; overflow:hidden;">
              <div id="${toastId}_bar" style="height:100%; width:0%; background:var(--md-sys-color-primary); transition:width 0.1s linear;"></div>
            </div>
          `;
          container.appendChild(el);

          try {
            const fetchOptions = postData ? { method: 'POST', body: postData } : { method: 'GET' };
            const response = await fetch(url, fetchOptions);
            if (!response.ok) throw new Error('ZIP generation failed');

            const totalBytes = parseInt(response.headers.get('Content-Length') || '0', 10);
            const reader = response.body.getReader();
            let receivedBytes = 0;
            const chunks = [];

            const labelEl = document.getElementById(`${toastId}_label`);
            const pctEl = document.getElementById(`${toastId}_pct`);
            const barEl = document.getElementById(`${toastId}_bar`);

            if (labelEl) labelEl.innerText = 'Downloading ZIP...';

            while (true) {
              const { done, value } = await reader.read();
              if (done) break;
              chunks.push(value);
              receivedBytes += value.length;

              if (totalBytes > 0) {
                const percent = Math.min(100, Math.round((receivedBytes / totalBytes) * 100));
                if (pctEl) pctEl.innerText = `${percent}%`;
                if (barEl) barEl.style.width = `${percent}%`;
              } else {
                if (pctEl) pctEl.innerText = formatBytes(receivedBytes);
              }
            }

            if (pctEl) pctEl.innerText = '100%';
            if (barEl) barEl.style.width = '100%';
            if (labelEl) labelEl.innerText = 'Download complete';

            const blob = new Blob(chunks, { type: 'application/zip' });
            const blobUrl = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = defaultFilename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
            setTimeout(() => el.remove(), 2500);
          } catch (e) {
            el.innerHTML = `<span style="color:var(--md-sys-color-error);">${e.message || 'Download failed'}</span>`;
            setTimeout(() => el.remove(), 3500);
          }
        }

        batchDownload() {
          const items = Array.from(this.selectedItems);
          if (!items.length) return;
          const fd = new FormData();
          fd.append('action', 'download_zip');
          fd.append('dir', this.currentPath || '');
          items.forEach(i => fd.append('items[]', i));
          this.downloadZipWithProgress('?action=download_zip', fd, 'selected_items.zip');
        }
  
        openBatchRename() {
          const items = Array.from(this.selectedItems);
          if (!items.length) return;

          this.batchRenameTasks = items.map(path => {
            const fileName = path.split('/').pop();
            const dotIdx = fileName.lastIndexOf('.');
            const isDir = (this.filteredList.find(f => f.path === path) || {}).isDir || false;
            return {
              path: path,
              isDir: isDir,
              originalName: fileName,
              baseName: (!isDir && dotIdx > 0) ? fileName.substring(0, dotIdx) : fileName,
              ext: (!isDir && dotIdx > 0) ? fileName.substring(dotIdx + 1) : '',
              newName: fileName
            };
          });

          document.getElementById('br-title').innerText = `Batch Rename (${items.length} items)`;
          document.getElementById('br-find').value = '';
          document.getElementById('br-replace').value = '';
          document.getElementById('br-prefix').value = '';
          document.getElementById('br-suffix').value = '';
          document.getElementById('br-case').checked = false;
          document.getElementById('br-regex').checked = false;

          const nameRadio = document.querySelector('input[name="br-target"][value="name"]');
          if (nameRadio) nameRadio.checked = true;

          this.updateBatchRenamePreview();
          this.showModal('modal-batch-rename');
        }

        updateBatchRenamePreview() {
          if (!this.batchRenameTasks || !this.batchRenameTasks.length) return;

          const findVal = document.getElementById('br-find')?.value || '';
          const replaceVal = document.getElementById('br-replace')?.value || '';
          const prefix = document.getElementById('br-prefix')?.value || '';
          const suffix = document.getElementById('br-suffix')?.value || '';
          const isCase = document.getElementById('br-case')?.checked || false;
          const isRegex = document.getElementById('br-regex')?.checked || false;
          const target = document.querySelector('input[name="br-target"]:checked')?.value || 'name';

          let regex = null;
          let regexError = false;

          if (findVal) {
            try {
              regex = isRegex ? new RegExp(findVal, isCase ? 'g' : 'gi') : null;
            } catch (e) {
              regexError = true;
            }
          }

          const seenNames = new Set();
          let modifiedCount = 0;
          let hasCollisions = false;

          this.batchRenameTasks.forEach(task => {
            let curBase = task.baseName;
            let curExt = task.ext;
            let curFull = task.originalName;

            if (findVal && !regexError) {
              if (target === 'name') {
                if (regex) {
                  curBase = curBase.replace(regex, replaceVal);
                } else {
                  const flags = isCase ? 'g' : 'gi';
                  const esc = findVal.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                  curBase = curBase.replace(new RegExp(esc, flags), replaceVal);
                }
              } else if (target === 'ext' && !task.isDir && curExt) {
                if (regex) {
                  curExt = curExt.replace(regex, replaceVal);
                } else {
                  const flags = isCase ? 'g' : 'gi';
                  const esc = findVal.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                  curExt = curExt.replace(new RegExp(esc, flags), replaceVal);
                }
              } else if (target === 'full') {
                if (regex) {
                  curFull = curFull.replace(regex, replaceVal);
                } else {
                  const flags = isCase ? 'g' : 'gi';
                  const esc = findVal.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                  curFull = curFull.replace(new RegExp(esc, flags), replaceVal);
                }
                const dIdx = curFull.lastIndexOf('.');
                if (!task.isDir && dIdx > 0) {
                  curBase = curFull.substring(0, dIdx);
                  curExt = curFull.substring(dIdx + 1);
                } else {
                  curBase = curFull;
                  curExt = '';
                }
              }
            }

            if (prefix) curBase = prefix + curBase;
            if (suffix) curBase = curBase + suffix;

            let finalName = (!task.isDir && curExt) ? `${curBase}.${curExt}` : curBase;
            finalName = finalName.replace(/[^\w\s\d\.\-_~()[\]]/u, '');
            task.newName = finalName || task.originalName;

            if (task.newName !== task.originalName) modifiedCount++;
            if (seenNames.has(task.newName.toLowerCase())) {
              hasCollisions = true;
              task.collision = true;
            } else {
              task.collision = false;
              seenNames.add(task.newName.toLowerCase());
            }
          });

          const tbody = document.getElementById('br-preview-body');
          if (tbody) {
            tbody.innerHTML = this.batchRenameTasks.map(task => {
              const isMod = task.newName !== task.originalName;
              const statusClass = task.collision ? 'collision' : (isMod ? 'modified' : '');
              return `
                <tr>
                  <td style="font-family:'JetBrains Mono', monospace; color:var(--md-sys-color-on-surface-variant); max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${this.escapeHtml(task.originalName)}</td>
                  <td style="color:var(--md-sys-color-outline); width:20px;">➔</td>
                  <td class="br-preview-new ${statusClass}" style="font-family:'JetBrains Mono', monospace; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${this.escapeHtml(task.newName)}${task.collision ? ' (duplicate)' : ''}</td>
                </tr>
              `;
            }).join('');
          }

          const summary = document.getElementById('br-status-summary');
          const confirmBtn = document.getElementById('br-confirm-btn');

          if (regexError) {
            if (summary) summary.innerHTML = `<span style="color:#ff7b72;">Invalid Regular Expression syntax</span>`;
            if (confirmBtn) confirmBtn.disabled = true;
          } else if (hasCollisions) {
            if (summary) summary.innerHTML = `<span style="color:#ff7b72;">Conflict detected: duplicate destination filename(s)</span>`;
            if (confirmBtn) confirmBtn.disabled = true;
          } else {
            if (summary) summary.innerHTML = `<span>${modifiedCount} of ${this.batchRenameTasks.length} item(s) will change</span>`;
            if (confirmBtn) confirmBtn.disabled = modifiedCount === 0;
          }
        }

        async executeBatchRename() {
          const tasks = (this.batchRenameTasks || []).filter(t => t.newName && t.newName !== t.originalName);
          if (!tasks.length) return;

          const renamesPayload = tasks.map(t => ({
            path: t.path,
            new_name: t.newName
          }));

          const confirmBtn = document.getElementById('br-confirm-btn');
          if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<svg class="m3-spinner" style="width:16px;height:16px;margin:0;" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg> Renaming...';
          }

          try {
            const fd = new FormData();
            fd.append('action', 'batch_rename');
            fd.append('renames', JSON.stringify(renamesPayload));

            const res = await fetch('', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
              this.toast(`Renamed ${data.renamed_count} item(s) successfully`);
              this.closeModals();
              this.clearSelection();
              this.refresh();
            } else {
              throw new Error(data.error || 'Batch rename failed');
            }
          } catch (e) {
            this.toast(e.message || 'Batch rename failed');
          } finally {
            if (confirmBtn) {
              confirmBtn.disabled = false;
              confirmBtn.innerHTML = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Apply Rename';
            }
          }
        }
  
        batchCompress() {
          const items = Array.from(this.selectedItems);
          if (!items.length) return;
          const defaultName = (items.length === 1 ? items[0].split('/').pop() : 'archive') + '.zip';
          this.showInputModal('Compress Items', 'Archive Filename (.zip)', defaultName, (zipName) => {
            this.runServerTaskWithProgress('Compressing items', 'zip', {
              dir: this.currentPath || '',
              items: items,
              zip_name: zipName || defaultName,
              format: 'zip'
            }, () => {
              this.toast('Archive created successfully');
              this.clearSelection();
              this.refresh();
            });
          });
        }
  
        batchDelete() {
          const items = Array.from(this.selectedItems);
          if (!items.length) return;

          if (this.currentSection === 'trash') {
            if (confirm(`Permanently delete ${items.length} selected item(s)? This cannot be undone.`)) {
              this.api('delete', { items }, () => {
                this.toast('Items permanently deleted');
                this.clearSelection();
                this.refresh();
              });
            }
          } else {
            if (confirm(`Move ${items.length} selected item(s) to Trash?`)) {
              this.api('trash', { items }, () => {
                this.toast('Items moved to Trash');
                this.clearSelection();
                this.refresh();
              });
            }
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
          let html = `<a href="#/" class="bc-item ${this.currentSection === 'home' && !this.currentPath ? 'active' : ''}">Home</a>`;
          if (this.currentSection === 'recents') {
            html += `<span class="bc-sep">/</span><a href="#/@recents" class="bc-item active">Recents</a>`;
          } else if (this.currentSection === 'starred') {
            html += `<span class="bc-sep">/</span><a href="#/@starred" class="bc-item active">Starred Items</a>`;
          } else if (this.currentSection === 'activity') {
            html += `<span class="bc-sep">/</span><a href="#/@activity" class="bc-item active">File Activity</a>`;
          } else if (this.currentSection === 'trash') {
            html += `<span class="bc-sep">/</span><a href="#/@trash" class="bc-item active">Trash Bin</a>`;
          } else if (this.currentSection === 'gallery') {
            html += `<span class="bc-sep">/</span><a href="#/@gallery" class="bc-item active">Gallery</a>`;
          } else if (this.currentSection === 'videos') {
            html += `<span class="bc-sep">/</span><a href="#/@videos" class="bc-item active">Videos</a>`;
          } else if (this.currentSection === 'audio') {
            html += `<span class="bc-sep">/</span><a href="#/@audio" class="bc-item active">Audio</a>`;
          } else if (this.currentSection === 'documents') {
            html += `<span class="bc-sep">/</span><a href="#/@documents" class="bc-item active">Documents</a>`;
          } else if (this.currentPath) {
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
  
        getFileTypeByExt(ext) {
          ext = (ext || '').toLowerCase();
          const img = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'];
          const vid = ['mp4', 'webm', 'mov', 'm4v', 'ogv', 'mkv', 'avi', 'ts', '3gp', 'wmv', 'flv'];
          const aud = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma', 'm4r', 'mid', 'midi'];
          const doc = ['txt', 'md', 'markdown', 'json', 'js', 'css', 'html', 'htm', 'php', 'py', 'c', 'cpp', 'sh', 'log', 'xml', 'yaml', 'yml', 'ini', 'env', 'sql', 'csv', 'enc', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
          const arc = ['zip', 'tar', 'gz', '7z', 'rar', 'tgz'];
          if (img.includes(ext)) return 'image';
          if (vid.includes(ext)) return 'video';
          if (aud.includes(ext)) return 'audio';
          if (doc.includes(ext)) return 'text';
          if (arc.includes(ext)) return 'archive';
          return 'file';
        }

        captureVideoThumb(imgEl, encodedPath) {
          if (!imgEl) return;
          if (!this.vThumbQueue) {
            this.vThumbQueue = [];
            this.vThumbActive = 0;
            this.vThumbMax = 3; // Maximum 3 concurrent hardware decoding pipelines
            this.vThumbCanvas = document.createElement('canvas');
          }

          this.vThumbQueue.push({ imgEl, encodedPath });
          this.processVideoThumbQueue();
        }

        processVideoThumbQueue() {
          if (this.vThumbActive >= this.vThumbMax || !this.vThumbQueue.length) return;
          const { imgEl, encodedPath } = this.vThumbQueue.shift();
          if (!imgEl || !imgEl.isConnected) {
            return this.processVideoThumbQueue();
          }

          this.vThumbActive++;
          const video = document.createElement('video');
          video.preload = 'metadata';
          video.muted = true;
          video.playsInline = true;
          video.crossOrigin = 'anonymous';

          const cleanup = () => {
            video.onloadeddata = null;
            video.onerror = null;
            video.onseeked = null;
            video.removeAttribute('src');
            video.load();
            video.remove();
            this.vThumbActive--;
            this.processVideoThumbQueue();
          };

          const timeoutId = setTimeout(() => {
            imgEl.remove();
            cleanup();
          }, 6000);

          video.onloadeddata = () => {
            try {
              video.currentTime = Math.min(0.8, (video.duration || 1) / 2);
            } catch(e) {}
          };

          video.onseeked = () => {
            clearTimeout(timeoutId);
            try {
              const canvas = this.vThumbCanvas;
              const w = Math.min(480, video.videoWidth || 320);
              const h = Math.round(w * ((video.videoHeight || 240) / (video.videoWidth || 320)));
              canvas.width = w;
              canvas.height = h;
              const ctx = canvas.getContext('2d', { alpha: false, desynchronized: true });
              ctx.drawImage(video, 0, 0, w, h);
              imgEl.src = canvas.toDataURL('image/jpeg', 0.82);
              imgEl.style.opacity = '1';
              imgEl.closest('.file-card')?.classList.add('has-image');
            } catch (e) {
              imgEl.remove();
            }
            cleanup();
          };

          video.onerror = () => {
            clearTimeout(timeoutId);
            imgEl.remove();
            cleanup();
          };

          video.src = `?action=raw&f=${encodedPath}`;
        }

        updateBadges() {
          const files = this.data.files || [];
          const counts = { all: files.length, image: 0, video: 0, audio: 0, text: 0, archive: 0 };
          files.forEach(f => { if (counts[f.type] !== undefined) counts[f.type]++; });
          for (let k in counts) {
            const el = document.getElementById(`badge-${k}`);
            if (el) el.innerText = counts[k];
          }
        }
  
        openFile(filePath, updateHash = true) {
          let decodedRaw = String(filePath || '');
          try { decodedRaw = decodeURIComponent(decodedRaw); } catch (e) {}

          if (this.currentSection && this.currentSection !== 'home') {
            this.originSection = this.currentSection;
          }

          const cleanPath = decodedRaw.replace(/\\/g, '/').replace(/^\/+/, '').replace(/\.part$/i, '');
          const fileName = cleanPath.split('/').pop();
          const ext = (fileName.split('.').pop() || '').toLowerCase();

          const videoExts = ['mp4', 'webm', 'mov', 'm4v', 'ogv', 'mkv', 'avi', 'ts', '3gp', 'wmv', 'flv'];
          const audioExts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma', 'm4r', 'mid', 'midi'];
          const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'];
          const officeExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf', 'pages', 'ai', 'psd'];
          const textExts = ['txt', 'md', 'markdown', 'json', 'js', 'css', 'html', 'htm', 'php', 'py', 'c', 'cpp', 'sh', 'log', 'xml', 'yaml', 'yml', 'ini', 'env', 'sql', 'csv', 'enc'];

          let resolvedType = 'file';
          if (audioExts.includes(ext)) resolvedType = 'audio';
          else if (videoExts.includes(ext)) resolvedType = 'video';
          else if (imageExts.includes(ext)) resolvedType = 'image';
          else if (officeExts.includes(ext)) resolvedType = 'office';
          else if (textExts.includes(ext) || ext === 'enc') resolvedType = 'text';

          const normalize = p => {
            try { return decodeURIComponent(p || '').replace(/\\/g, '/').replace(/^\/+/, '').replace(/\.part$/i, ''); }
            catch (e) { return (p || '').replace(/\\/g, '/').replace(/^\/+/, '').replace(/\.part$/i, ''); }
          };

          let file = this.filteredList.find(f => normalize(f.path) === cleanPath)
            || (this.data.files || []).find(f => normalize(f.path) === cleanPath);

          if (!file) {
            file = { name: fileName, path: cleanPath, ext: ext, type: resolvedType };
          } else {
            file.path = cleanPath;
            file.name = fileName;
            file.type = resolvedType;
          }

          if (updateHash) {
            window.location.hash = '#/' + encodeURI(ltrim(cleanPath, '/'));
          }

          if (['image', 'video', 'audio'].includes(resolvedType)) {
            let mediaList = this.filteredList
              .filter(f => !f.isDir)
              .map(f => {
                const p = normalize(f.path);
                const fName = f.name || p.split('/').pop() || '';
                const fExt = (fName.split('.').pop() || '').toLowerCase();
                let fType = 'file';
                if (audioExts.includes(fExt)) fType = 'audio';
                else if (videoExts.includes(fExt)) fType = 'video';
                else if (imageExts.includes(fExt)) fType = 'image';
                return { ...f, path: p, name: fName, ext: fExt, type: fType };
              })
              .filter(f => ['image', 'video', 'audio'].includes(f.type));

            let currentMediaIndex = mediaList.findIndex(f => normalize(f.path) === cleanPath);
            if (currentMediaIndex === -1) {
              mediaList.push(file);
              currentMediaIndex = mediaList.length - 1;
            }
            lightbox.open(mediaList, currentMediaIndex);
          } else if (resolvedType === 'office') {
            this.openDocViewer(file.path, file.name);
          } else if (resolvedType === 'text') {
            this.openEditor(file.path, file.name);
          } else {
            // Optimize all other file extensions with in-app viewer fallback
            this.openDocViewer(file.path, file.name);
          }
        }

        async openDocViewer(path, name) {
          this.activeModalPath = path;
          document.getElementById('doc-viewer-title').innerText = name;
          const directUrl = `?action=raw&f=${encodeURIComponent(path)}`;
          const container = document.getElementById('doc-viewer-container');
          const ext = (name.split('.').pop() || '').toLowerCase();

          document.getElementById('doc-viewer-direct-btn').onclick = () => {
            window.open(directUrl, '_blank');
          };

          container.innerHTML = `
            <div class="center-state" style="min-height:300px;">
              <svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
              <div style="font-size:0.85rem; font-weight:500;">Rendering ${ext.toUpperCase()} document...</div>
            </div>
          `;
          this.showModal('modal-doc-viewer');

          try {
            const response = await fetch(directUrl);
            if (!response.ok) throw new Error('Failed to load file data');
            const arrayBuffer = await response.arrayBuffer();

            if (ext === 'pdf') {
              if (window.pdfjsLib) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                const pdf = await window.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                container.innerHTML = '';

                // High-DPI Retina scale factor (ensures razor-sharp text on mobile and 4K displays)
                const dpr = Math.max(window.devicePixelRatio || 1, 2);

                for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                  const page = await pdf.getPage(pageNum);
                  const unscaledViewport = page.getViewport({ scale: 1.0 });
                  const containerWidth = Math.max(320, (container.clientWidth || window.innerWidth) - 24);
                  const cssScale = Math.min(2.0, containerWidth / unscaledViewport.width);
                  
                  // Render viewport scaled by device pixel ratio
                  const renderViewport = page.getViewport({ scale: cssScale * dpr });

                  const canvas = document.createElement('canvas');
                  canvas.className = 'pdf-page-canvas';
                  canvas.width = Math.floor(renderViewport.width);
                  canvas.height = Math.floor(renderViewport.height);
                  
                  // Set CSS display dimensions to scale down the high-res buffer
                  canvas.style.width = `${Math.floor(renderViewport.width / dpr)}px`;
                  canvas.style.height = 'auto';

                  const ctx = canvas.getContext('2d', { alpha: false });
                  container.appendChild(canvas);

                  await page.render({
                    canvasContext: ctx,
                    viewport: renderViewport
                  }).promise;
                }
              } else {
                throw new Error('PDF viewer library failed to load');
              }
            } else if (ext === 'docx') {
              container.innerHTML = '';
              const docxWrapper = document.createElement('div');
              docxWrapper.className = 'docx-viewer-wrapper';
              container.appendChild(docxWrapper);

              if (window.docx && window.docx.renderAsync) {
                await window.docx.renderAsync(arrayBuffer, docxWrapper, null, { inWrapper: false });
              } else {
                throw new Error('Word document parser not available');
              }
            } else if (['xlsx', 'xls', 'csv'].includes(ext)) {
              if (window.XLSX) {
                const workbook = window.XLSX.read(new Uint8Array(arrayBuffer), { type: 'array' });
                const sheetName = workbook.SheetNames[0];
                const htmlTable = window.XLSX.utils.sheet_to_html(workbook.Sheets[sheetName], { id: 'sheet-table', editable: false });

                container.innerHTML = `
                  <div style="width:100%; max-width:1000px; overflow-x:auto; padding-bottom:1rem;">
                    <div style="font-weight:600; font-size:0.85rem; margin-bottom:0.6rem; color:var(--md-sys-color-primary);">Sheet: ${sheetName}</div>
                    ${htmlTable}
                  </div>
                `;
                const tbl = container.querySelector('table');
                if (tbl) tbl.className = 'sheet-viewer-table';
              } else {
                throw new Error('Spreadsheet parser not available');
              }
            } else {
              container.innerHTML = `
                <div class="center-state" style="min-height:320px;">
                  <svg viewBox="0 0 24 24" style="width:54px; height:54px; opacity:0.35; color:var(--md-sys-color-outline); margin-bottom:0.6rem;"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                  <div style="font-weight:700; font-size:1.1rem; color:var(--md-sys-color-on-surface); margin-bottom:0.3rem;">This file can't be viewed.</div>
                  <div style="font-size:0.82rem; color:var(--md-sys-color-on-surface-variant); margin-bottom:1.2rem;">Preview is not supported for .${ext.toUpperCase() || 'this'} files. You can download it directly.</div>
                  <a href="${directUrl}" class="btn-primary" download style="text-decoration:none; padding:0.5rem 1.4rem; gap:0.4rem;">
                    <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                    Download File
                  </a>
                </div>
              `;
            }
          } catch (err) {
            container.innerHTML = `
              <div class="center-state" style="min-height:300px; color:var(--md-sys-color-error);">
                <div style="font-weight:600; font-size:0.95rem; margin-bottom:0.3rem;">Unable to render document</div>
                <div style="font-size:0.8rem; opacity:0.8; margin-bottom:1rem;">${err.message || 'Error occurred during parsing'}</div>
                <a href="${directUrl}" class="btn-primary" download style="text-decoration:none;">Download Directly</a>
              </div>
            `;
          }
        }

        openImageEditor(path, name) {
          this.activeModalPath = path;
          document.getElementById('image-editor-title').innerText = `Edit: ${name}`;
          const canvas = document.getElementById('image-editor-canvas');
          const ctx = canvas.getContext('2d');

          // Prevent native browser drag & selection on canvas
          canvas.onselectstart = () => false;
          canvas.ondragstart = (e) => { e.preventDefault(); return false; };

          let baseImg = new Image();
          baseImg.crossOrigin = 'anonymous';

          let historyStack = [];
          let redoStack = [];

          let state = {
            rotation: 0,
            flipH: 1,
            flipV: 1,
            brightness: 100,
            contrast: 100,
            saturate: 100,
            grayscale: false,
            sepia: false,
            invert: false,
            cropActive: false,
            cropRatio: 'free',
            textActive: false,
            drawActive: false
          };

          let drawing = {
            isDrawing: false,
            lastX: 0,
            lastY: 0,
            strokes: [] // Store committed path segments
          };

          let crop = {
            x: 0, y: 0, w: 0, h: 0,
            activeHandle: null,
            startX: 0, startY: 0,
            origX: 0, origY: 0, origW: 0, origH: 0
          };

          let freeText = {
            x: 100, y: 100,
            isDragging: false,
            hasMoved: false,
            dragOffsetX: 0,
            dragOffsetY: 0
          };

          window.resetImageEditorSession = () => {
            historyStack = [];
            redoStack = [];
            baseImg.onload = null;
            baseImg.src = '';
            canvas.width = 1;
            canvas.height = 1;
            ctx.clearRect(0, 0, 1, 1);
            const txtInput = document.getElementById('ie-text-input');
            if (txtInput) txtInput.value = '';
            state.textActive = false;
            state.cropActive = false;
            freeText.isDragging = false;
          };

          const updateCanvasSize = () => {
            const isVert = state.rotation % 180 !== 0;
            const targetW = isVert ? baseImg.height : baseImg.width;
            const targetH = isVert ? baseImg.width : baseImg.height;
            if (canvas.width !== targetW || canvas.height !== targetH) {
              canvas.width = targetW;
              canvas.height = targetH;
            }
          };

          const renderFilters = () => {
            let f = [];
            if (state.brightness !== 100) f.push(`brightness(${state.brightness}%)`);
            if (state.contrast !== 100) f.push(`contrast(${state.contrast}%)`);
            if (state.saturate !== 100) f.push(`saturate(${state.saturate}%)`);
            if (state.grayscale) f.push(`grayscale(100%)`);
            if (state.sepia) f.push(`sepia(100%)`);
            if (state.invert) f.push(`invert(100%)`);
            return f.length ? f.join(' ') : 'none';
          };

          const pushSnapshot = () => {
            const temp = document.createElement('canvas');
            const isVert = state.rotation % 180 !== 0;
            temp.width = isVert ? baseImg.height : baseImg.width;
            temp.height = isVert ? baseImg.width : baseImg.height;
            const tctx = temp.getContext('2d');
            tctx.translate(temp.width / 2, temp.height / 2);
            tctx.rotate((state.rotation * Math.PI) / 180);
            tctx.scale(state.flipH, state.flipV);
            tctx.filter = renderFilters();
            tctx.drawImage(baseImg, -baseImg.width / 2, -baseImg.height / 2);

            historyStack.push(temp.toDataURL('image/png'));
            if (historyStack.length > 30) historyStack.shift();
            redoStack = [];
            updateUndoUI();
          };

          const updateUndoUI = () => {
            const btnUndo = document.getElementById('ie-undo');
            const btnRedo = document.getElementById('ie-redo');
            if (btnUndo) btnUndo.style.opacity = historyStack.length > 0 ? '1' : '0.35';
            if (btnRedo) btnRedo.style.opacity = redoStack.length > 0 ? '1' : '0.35';
          };

          const initCropBounds = () => {
            let cw = canvas.width * 0.75;
            let ch = canvas.height * 0.75;
            if (state.cropRatio === '1:1') { const s = Math.min(cw, ch); cw = s; ch = s; }
            else if (state.cropRatio === '16:9') { ch = cw * (9 / 16); }
            else if (state.cropRatio === '4:3') { ch = cw * (3 / 4); }
            else if (state.cropRatio === '9:16') { cw = ch * (9 / 16); }
            crop.x = Math.round((canvas.width - cw) / 2);
            crop.y = Math.round((canvas.height - ch) / 2);
            crop.w = Math.round(cw);
            crop.h = Math.round(ch);
          };

          const getTextMetrics = () => {
            const txt = document.getElementById('ie-text-input')?.value || 'Your Text Here';
            const size = parseInt(document.getElementById('ie-text-size')?.value || '36', 10);
            const isBanner = document.getElementById('ie-text-banner')?.checked;

            ctx.save();
            ctx.font = `bold ${size}px sans-serif`;
            const metrics = ctx.measureText(txt);
            ctx.restore();

            const w = isBanner ? canvas.width : metrics.width + 30;
            const h = isBanner ? size * 2.2 : size + 20;

            return {
              txt, size, isBanner, w, h,
              left: isBanner ? 0 : freeText.x - w / 2,
              top: freeText.y - h / 2,
              right: isBanner ? canvas.width : freeText.x + w / 2,
              bottom: freeText.y + h / 2
            };
          };

          const isInsideText = (pt) => {
            const tm = getTextMetrics();
            return pt.x >= tm.left && pt.x <= tm.right && pt.y >= tm.top && pt.y <= tm.bottom;
          };

          const redraw = () => {
            updateCanvasSize();

            if (!freeText.hasMoved) {
              freeText.x = Math.round(canvas.width / 2);
              freeText.y = Math.round(canvas.height / 2);
            }

            ctx.save();
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.translate(canvas.width / 2, canvas.height / 2);
            ctx.rotate((state.rotation * Math.PI) / 180);
            ctx.scale(state.flipH, state.flipV);
            ctx.filter = renderFilters();
            ctx.drawImage(baseImg, -baseImg.width / 2, -baseImg.height / 2);
            ctx.restore();

            // Clear Viewport Crop Drawing
            if (state.cropActive) {
              if (crop.w === 0 || crop.h === 0) initCropBounds();

              ctx.save();
              ctx.fillStyle = 'rgba(0, 0, 0, 0.55)';
              ctx.fillRect(0, 0, canvas.width, crop.y);
              ctx.fillRect(0, crop.y + crop.h, canvas.width, canvas.height - (crop.y + crop.h));
              ctx.fillRect(0, crop.y, crop.x, crop.h);
              ctx.fillRect(crop.x + crop.w, crop.y, canvas.width - (crop.x + crop.w), crop.h);

              ctx.strokeStyle = 'rgba(255, 255, 255, 0.35)';
              ctx.lineWidth = 1;
              ctx.strokeRect(crop.x + crop.w / 3, crop.y, 0, crop.h);
              ctx.strokeRect(crop.x + (crop.w * 2) / 3, crop.y, 0, crop.h);
              ctx.strokeRect(crop.x, crop.y + crop.h / 3, crop.w, 0);
              ctx.strokeRect(crop.x, crop.y + (crop.h * 2) / 3, crop.w, 0);

              ctx.strokeStyle = '#ffffff';
              ctx.lineWidth = 2;
              ctx.strokeRect(crop.x, crop.y, crop.w, crop.h);

              const hs = Math.max(10, Math.round(canvas.width / 100));
              ctx.fillStyle = '#ff0000';
              ctx.strokeStyle = '#ffffff';
              ctx.lineWidth = 1.5;

              const handles = [
                [crop.x, crop.y], [crop.x + crop.w, crop.y],
                [crop.x, crop.y + crop.h], [crop.x + crop.w, crop.y + crop.h],
                [crop.x + crop.w / 2, crop.y], [crop.x + crop.w / 2, crop.y + crop.h],
                [crop.x, crop.y + crop.h / 2], [crop.x + crop.w, crop.y + crop.h / 2]
              ];
              handles.forEach(([hx, hy]) => {
                ctx.fillRect(hx - hs / 2, hy - hs / 2, hs, hs);
                ctx.strokeRect(hx - hs / 2, hy - hs / 2, hs, hs);
              });
              ctx.restore();
            }

            // Live Draggable Freeform Text Preview
            if (state.textActive) {
              const tm = getTextMetrics();
              const color = document.getElementById('ie-text-color')?.value || '#ffffff';

              ctx.save();
              ctx.font = `bold ${tm.size}px sans-serif`;

              if (tm.isBanner) {
                ctx.fillStyle = 'rgba(0, 0, 0, 0.75)';
                ctx.fillRect(0, freeText.y - tm.h / 2, canvas.width, tm.h);
                ctx.fillStyle = color;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(tm.txt, canvas.width / 2, freeText.y);

                ctx.fillStyle = '#ff0000';
                ctx.beginPath();
                ctx.arc(canvas.width / 2, freeText.y, 6, 0, Math.PI * 2);
                ctx.fill();
              } else {
                ctx.fillStyle = color;
                ctx.shadowColor = 'rgba(0, 0, 0, 0.9)';
                ctx.shadowBlur = 8;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(tm.txt, freeText.x, freeText.y);

                ctx.shadowBlur = 0;
                ctx.strokeStyle = 'rgba(255, 0, 0, 0.85)';
                ctx.lineWidth = 1.5;
                ctx.setLineDash([4, 4]);
                ctx.strokeRect(tm.left, tm.top, tm.w, tm.h);
                ctx.setLineDash([]);

                ctx.fillStyle = '#ff0000';
                ctx.beginPath();
                ctx.arc(freeText.x, freeText.y, 5, 0, Math.PI * 2);
                ctx.fill();
              }
              ctx.restore();
            }
          };

          const getCanvasPoint = (e) => {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const cx = e.touches ? e.touches[0].clientX : e.clientX;
            const cy = e.touches ? e.touches[0].clientY : e.clientY;
            return {
              x: Math.max(0, Math.min(canvas.width, (cx - rect.left) * scaleX)),
              y: Math.max(0, Math.min(canvas.height, (cy - rect.top) * scaleY))
            };
          };

          const getCropHandleAt = (pt) => {
            const threshold = Math.max(16, Math.round(canvas.width / 45));
            const near = (hx, hy) => Math.hypot(pt.x - hx, pt.y - hy) < threshold;

            if (near(crop.x, crop.y)) return 'nw';
            if (near(crop.x + crop.w, crop.y)) return 'ne';
            if (near(crop.x, crop.y + crop.h)) return 'sw';
            if (near(crop.x + crop.w, crop.y + crop.h)) return 'se';

            if (near(crop.x + crop.w / 2, crop.y)) return 'n';
            if (near(crop.x + crop.w / 2, crop.y + crop.h)) return 's';
            if (near(crop.x, crop.y + crop.h / 2)) return 'w';
            if (near(crop.x + crop.w, crop.y + crop.h / 2)) return 'e';

            if (pt.x >= crop.x && pt.x <= crop.x + crop.w && pt.y >= crop.y && pt.y <= crop.y + crop.h) return 'move';
            return null;
          };

          // Mouse & Touch Down Binding
          canvas.onmousedown = canvas.ontouchstart = (e) => {
            if (e.cancelable) e.preventDefault();
            const pt = getCanvasPoint(e);

            if (state.cropActive) {
              const handle = getCropHandleAt(pt);
              if (handle) {
                crop.activeHandle = handle;
                crop.startX = pt.x;
                crop.startY = pt.y;
                crop.origX = crop.x;
                crop.origY = crop.y;
                crop.origW = crop.w;
                crop.origH = crop.h;
              }
            } else if (state.textActive) {
              freeText.isDragging = true;
              freeText.hasMoved = true;
              if (isInsideText(pt)) {
                freeText.dragOffsetX = pt.x - freeText.x;
                freeText.dragOffsetY = pt.y - freeText.y;
              } else {
                freeText.x = pt.x;
                freeText.y = pt.y;
                freeText.dragOffsetX = 0;
                freeText.dragOffsetY = 0;
              }
              redraw();
            } else if (state.drawActive) {
              pushSnapshot();
              drawing.isDrawing = true;
              drawing.lastX = pt.x;
              drawing.lastY = pt.y;
            }
          };

          canvas.onmousemove = (e) => {
            if (state.cropActive && !crop.activeHandle) {
              const pt = getCanvasPoint(e);
              const h = getCropHandleAt(pt);
              if (h === 'nw' || h === 'se') canvas.style.cursor = 'nwse-resize';
              else if (h === 'ne' || h === 'sw') canvas.style.cursor = 'nesw-resize';
              else if (h === 'n' || h === 's') canvas.style.cursor = 'ns-resize';
              else if (h === 'e' || h === 'w') canvas.style.cursor = 'ew-resize';
              else if (h === 'move') canvas.style.cursor = 'move';
              else canvas.style.cursor = 'crosshair';
            } else if (state.textActive) {
              const pt = getCanvasPoint(e);
              canvas.style.cursor = isInsideText(pt) ? 'grab' : 'crosshair';
            } else {
              canvas.style.cursor = 'default';
            }
          };

          window.onmousemove = window.ontouchmove = (e) => {
            if (state.drawActive && drawing.isDrawing) {
              if (e.cancelable) e.preventDefault();
              const pt = getCanvasPoint(e);
              const brushSize = parseInt(document.getElementById('ie-draw-size')?.value || '6', 10);
              const brushColor = document.getElementById('ie-draw-color')?.value || '#ff0000';
              const isEraser = document.getElementById('ie-draw-eraser')?.checked;

              ctx.save();
              ctx.lineJoin = 'round';
              ctx.lineCap = 'round';
              ctx.lineWidth = brushSize;

              if (isEraser) {
                ctx.globalCompositeOperation = 'destination-out';
              } else {
                ctx.globalCompositeOperation = 'source-over';
                ctx.strokeStyle = brushColor;
              }

              ctx.beginPath();
              ctx.moveTo(drawing.lastX, drawing.lastY);
              ctx.lineTo(pt.x, pt.y);
              ctx.stroke();
              ctx.restore();

              drawing.lastX = pt.x;
              drawing.lastY = pt.y;
            } else if (state.cropActive && crop.activeHandle) {
              if (e.cancelable) e.preventDefault();
              const pt = getCanvasPoint(e);
              const dx = pt.x - crop.startX;
              const dy = pt.y - crop.startY;

              if (crop.activeHandle === 'move') {
                crop.x = Math.max(0, Math.min(canvas.width - crop.w, crop.origX + dx));
                crop.y = Math.max(0, Math.min(canvas.height - crop.h, crop.origY + dy));
              } else {
                let nx = crop.origX, ny = crop.origY, nw = crop.origW, nh = crop.origH;

                if (crop.activeHandle.includes('e')) nw = Math.max(20, crop.origW + dx);
                if (crop.activeHandle.includes('s')) nh = Math.max(20, crop.origH + dy);
                if (crop.activeHandle.includes('w')) {
                  const cdx = Math.min(dx, crop.origW - 20);
                  nx = crop.origX + cdx;
                  nw = crop.origW - cdx;
                }
                if (crop.activeHandle.includes('n')) {
                  const cdy = Math.min(dy, crop.origH - 20);
                  ny = crop.origY + cdy;
                  nh = crop.origH - cdy;
                }

                if (state.cropRatio !== 'free') {
                  let r = 1;
                  if (state.cropRatio === '16:9') r = 16 / 9;
                  else if (state.cropRatio === '4:3') r = 4 / 3;
                  else if (state.cropRatio === '9:16') r = 9 / 16;

                  if (crop.activeHandle.includes('e') || crop.activeHandle.includes('w')) {
                    nh = nw / r;
                  } else {
                    nw = nh * r;
                  }
                }

                if (nx >= 0 && ny >= 0 && nx + nw <= canvas.width && ny + nh <= canvas.height) {
                  crop.x = nx; crop.y = ny; crop.w = nw; crop.h = nh;
                }
              }
              redraw();
            } else if (state.textActive && freeText.isDragging) {
              if (e.cancelable) e.preventDefault();
              const pt = getCanvasPoint(e);
              freeText.x = Math.max(10, Math.min(canvas.width - 10, pt.x - freeText.dragOffsetX));
              freeText.y = Math.max(10, Math.min(canvas.height - 10, pt.y - freeText.dragOffsetY));
              redraw();
            }
          };

          window.onmouseup = window.ontouchend = () => {
            crop.activeHandle = null;
            freeText.isDragging = false;
            if (state.drawActive && drawing.isDrawing) {
              drawing.isDrawing = false;
              const nextImg = new Image();
              nextImg.crossOrigin = 'anonymous';
              nextImg.onload = () => {
                baseImg = nextImg;
                state.rotation = 0;
                state.flipH = 1;
                state.flipV = 1;
              };
              nextImg.src = canvas.toDataURL('image/png');
            }
          };

          baseImg.onload = () => {
            state = { rotation: 0, flipH: 1, flipV: 1, brightness: 100, contrast: 100, saturate: 100, grayscale: false, sepia: false, invert: false, cropActive: false, cropRatio: 'free', textActive: false };
            historyStack = [];
            redoStack = [];
            crop.w = 0;
            freeText.hasMoved = false;
            updateCanvasSize();
            updateUndoUI();
            redraw();
          };
          baseImg.src = `?action=raw&f=${encodeURIComponent(path)}&t=${Date.now()}`;

          // Tab Navigation
          const tabs = ['tab-transform', 'tab-crop', 'tab-adjust', 'tab-text', 'tab-draw'];
          document.querySelectorAll('.img-editor-nav-btn').forEach(btn => {
            btn.onclick = () => {
              document.querySelectorAll('.img-editor-nav-btn').forEach(b => b.classList.remove('active'));
              btn.classList.add('active');
              const target = btn.dataset.ietab;
              tabs.forEach(t => {
                const el = document.getElementById('ietab-' + t);
                if (el) el.style.display = (t === target) ? 'flex' : 'none';
              });
              state.cropActive = (target === 'tab-crop');
              state.textActive = (target === 'tab-text');
              state.drawActive = (target === 'tab-draw');
              if (state.cropActive) initCropBounds();
              redraw();
            };
          });

          // Draw Controls Binding
          const drawSizeInput = document.getElementById('ie-draw-size');
          const drawSizeVal = document.getElementById('ie-draw-size-val');
          if (drawSizeInput && drawSizeVal) {
            drawSizeInput.oninput = (e) => {
              drawSizeVal.innerText = `${e.target.value}px`;
            };
          }

          document.getElementById('ie-draw-clear-btn').onclick = () => {
            pushSnapshot();
            baseImg.src = `?action=raw&f=${encodeURIComponent(path)}&t=${Date.now()}`;
            this.toast('Cleared drawing strokes');
          };

          // Clean Undo System (Clears live text so it never gets redrawn over restored state)
          const applyHistoryState = (dataUrl) => {
            const nextImg = new Image();
            nextImg.crossOrigin = 'anonymous';
            nextImg.onload = () => {
              baseImg = nextImg;
              state.rotation = 0;
              state.flipH = 1;
              state.flipV = 1;
              state.cropActive = false;
              state.textActive = false;
              freeText.hasMoved = false;
              document.querySelectorAll('.img-editor-nav-btn').forEach(b => b.classList.remove('active'));
              document.querySelector('.img-editor-nav-btn[data-ietab="tab-transform"]')?.classList.add('active');
              tabs.forEach(t => {
                const el = document.getElementById('ietab-' + t);
                if (el) el.style.display = (t === 'tab-transform') ? 'flex' : 'none';
              });
              const txtInput = document.getElementById('ie-text-input');
              if (txtInput) txtInput.value = '';
              redraw();
              updateUndoUI();
            };
            nextImg.src = dataUrl;
          };

          document.getElementById('ie-undo').onclick = () => {
            if (historyStack.length === 0) return;
            const temp = document.createElement('canvas');
            temp.width = canvas.width;
            temp.height = canvas.height;
            temp.getContext('2d').drawImage(canvas, 0, 0);
            redoStack.push(temp.toDataURL('image/png'));

            const prev = historyStack.pop();
            applyHistoryState(prev);
          };

          document.getElementById('ie-redo').onclick = () => {
            if (redoStack.length === 0) return;
            const temp = document.createElement('canvas');
            temp.width = canvas.width;
            temp.height = canvas.height;
            temp.getContext('2d').drawImage(canvas, 0, 0);
            historyStack.push(temp.toDataURL('image/png'));

            const next = redoStack.pop();
            applyHistoryState(next);
          };

          window.addEventListener('keydown', (e) => {
            const modalOpen = document.getElementById('modal-image-editor')?.style.display === 'flex';
            if (!modalOpen) return;
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
              e.preventDefault();
              document.getElementById('ie-undo')?.click();
            } else if ((e.ctrlKey || e.metaKey) && (e.key.toLowerCase() === 'y' || (e.shiftKey && e.key.toLowerCase() === 'z'))) {
              e.preventDefault();
              document.getElementById('ie-redo')?.click();
            }
          });

          // Transform Controls
          document.getElementById('ie-rotate-left').onclick = () => { pushSnapshot(); state.rotation = (state.rotation - 90 + 360) % 360; redraw(); };
          document.getElementById('ie-rotate-right').onclick = () => { pushSnapshot(); state.rotation = (state.rotation + 90) % 360; redraw(); };
          document.getElementById('ie-flip-h').onclick = () => { pushSnapshot(); state.flipH *= -1; redraw(); };
          document.getElementById('ie-flip-v').onclick = () => { pushSnapshot(); state.flipV *= -1; redraw(); };

          // Adjust Sliders
          document.getElementById('ie-brightness').oninput = (e) => { state.brightness = e.target.value; redraw(); };
          document.getElementById('ie-contrast').oninput = (e) => { state.contrast = e.target.value; redraw(); };
          document.getElementById('ie-saturate').oninput = (e) => { state.saturate = e.target.value; redraw(); };
          document.getElementById('ie-grayscale').onchange = (e) => { state.grayscale = e.target.checked; redraw(); };
          document.getElementById('ie-sepia').onchange = (e) => { state.sepia = e.target.checked; redraw(); };
          document.getElementById('ie-invert').onchange = (e) => { state.invert = e.target.checked; redraw(); };

          // Crop Controls
          document.getElementById('ie-crop-ratio').onchange = (e) => {
            state.cropRatio = e.target.value;
            initCropBounds();
            redraw();
          };

          document.getElementById('ie-apply-crop').onclick = () => {
            pushSnapshot();
            const temp = document.createElement('canvas');
            temp.width = Math.max(1, crop.w);
            temp.height = Math.max(1, crop.h);
            const tctx = temp.getContext('2d');
            state.cropActive = false;
            redraw();
            tctx.drawImage(canvas, crop.x, crop.y, crop.w, crop.h, 0, 0, crop.w, crop.h);

            baseImg = new Image();
            baseImg.onload = () => {
              state.rotation = 0;
              state.flipH = 1;
              state.flipV = 1;
              crop.w = 0;
              redraw();
            };
            baseImg.src = temp.toDataURL('image/png');
            this.toast('Crop applied');
          };

          document.getElementById('ie-cancel-crop').onclick = () => {
            state.cropActive = false;
            redraw();
          };

          // Freeform Text Controls
          ['ie-text-input', 'ie-text-size', 'ie-text-color', 'ie-text-banner'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', redraw);
          });

          document.getElementById('ie-add-text-btn').onclick = () => {
            const txt = document.getElementById('ie-text-input')?.value.trim();
            if (!txt) return;
            pushSnapshot();

            state.textActive = false;

            updateCanvasSize();
            ctx.save();
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.translate(canvas.width / 2, canvas.height / 2);
            ctx.rotate((state.rotation * Math.PI) / 180);
            ctx.scale(state.flipH, state.flipV);
            ctx.filter = renderFilters();
            ctx.drawImage(baseImg, -baseImg.width / 2, -baseImg.height / 2);
            ctx.restore();

            const size = parseInt(document.getElementById('ie-text-size')?.value || '36', 10);
            const color = document.getElementById('ie-text-color')?.value || '#ffffff';
            const isBanner = document.getElementById('ie-text-banner')?.checked;

            ctx.save();
            ctx.font = `bold ${size}px sans-serif`;

            if (isBanner) {
              const bannerH = size * 2.2;
              ctx.fillStyle = 'rgba(0, 0, 0, 0.75)';
              ctx.fillRect(0, freeText.y - bannerH / 2, canvas.width, bannerH);
              ctx.fillStyle = color;
              ctx.textAlign = 'center';
              ctx.textBaseline = 'middle';
              ctx.fillText(txt, canvas.width / 2, freeText.y);
            } else {
              ctx.fillStyle = color;
              ctx.shadowColor = 'rgba(0, 0, 0, 0.9)';
              ctx.shadowBlur = 8;
              ctx.textAlign = 'center';
              ctx.textBaseline = 'middle';
              ctx.fillText(txt, freeText.x, freeText.y);
            }
            ctx.restore();

            const nextImg = new Image();
            nextImg.crossOrigin = 'anonymous';
            nextImg.onload = () => {
              baseImg = nextImg;
              state.rotation = 0;
              state.flipH = 1;
              state.flipV = 1;
              const txtInp = document.getElementById('ie-text-input');
              if (txtInp) txtInp.value = '';
              redraw();
            };
            nextImg.src = canvas.toDataURL('image/png');
            this.toast('Text stamped to image');
          };

          document.getElementById('ie-global-reset').onclick = () => {
            baseImg.src = `?action=raw&f=${encodeURIComponent(path)}&t=${Date.now()}`;
            this.toast('Reset all changes');
          };

          const executeSave = (mode) => {
            state.cropActive = false;
            state.textActive = false;
            redraw();

            const ext = (name.split('.').pop() || 'jpg').toLowerCase();
            const mime = (ext === 'png') ? 'image/png' : (ext === 'webp' ? 'image/webp' : 'image/jpeg');
            const dataUrl = canvas.toDataURL(mime, 0.94);

            this.api('save_image', { f: path, image_data: dataUrl, save_mode: mode }, (res) => {
              this.toast(mode === 'copy' ? `Saved duplicate: ${res.filename}` : 'Image updated successfully');
              this.closeModals();
              this.refresh();
            });
          };

          document.getElementById('btn-save-image-overwrite').onclick = () => {
            if (confirm(`Permanently overwrite and replace "${name}"?`)) {
              executeSave('overwrite');
            }
          };
          document.getElementById('btn-save-image-copy').onclick = () => executeSave('copy');

          this.showModal('modal-image-editor');
        }

        openEditor(path, name) {
          this.activeModalPath = path;
          document.getElementById('editor-title').innerText = name;
          const loader = document.getElementById('editor-loader');
          if (loader) loader.style.display = 'flex';
          this.showModal('modal-editor');

          fetch(`?action=read_text&f=${encodeURIComponent(path)}`)
            .then(r => r.json())
            .then(res => {
              if (loader) loader.style.display = 'none';
              window.hdmEngine.open(path, name, res.content || '');

              const saveBtn = document.getElementById('editor-save-btn');
              const origSaveIcon = '<svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>';
              const savedCheckIcon = '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';

              saveBtn.onclick = () => {
                const val = window.hdmEngine.editor.getValue();
                saveBtn.disabled = true;
                saveBtn.style.color = '#eab308'; // Amber saving state
                saveBtn.innerHTML = '<svg class="m3-spinner" style="width:20px;height:20px;margin:0;" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4" stroke="#eab308"></circle></svg>';

                this.api('save_text', { f: path, content: val }, () => {
                  this.toast('Document saved');
                  
                  // Trigger Green Checkmark Saved Indicator
                  saveBtn.style.color = '#22c55e';
                  saveBtn.innerHTML = savedCheckIcon;
                  saveBtn.title = 'Saved successfully!';

                  // Centered floating indicator
                  const editorPane = document.getElementById('hdm-editor-pane');
                  if (editorPane) {
                    const indicator = document.createElement('div');
                    indicator.innerHTML = '<svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:#22c55e;margin-bottom:10px;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg><div style="font-weight:bold;font-size:1.1rem;">Saved Successfully</div>';
                    indicator.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);background:rgba(0,0,0,0.8);color:#fff;padding:20px 30px;border-radius:16px;z-index:9999;display:flex;flex-direction:column;align-items:center;pointer-events:none;transition:opacity 0.3s;';
                    editorPane.appendChild(indicator);
                    setTimeout(() => { indicator.style.opacity = '0'; }, 1200);
                    setTimeout(() => { indicator.remove(); }, 1500);
                  }

                  // Update metrics bar with saved confirmation badge
                  window.hdmEngine.updateMetrics(true);

                  setTimeout(() => {
                    saveBtn.disabled = false;
                    saveBtn.style.color = 'var(--md-sys-color-primary)';
                    saveBtn.innerHTML = origSaveIcon;
                    saveBtn.title = 'Save Document';
                    window.hdmEngine.updateMetrics(false);
                  }, 1800);
                });
              };
            })
            .catch(err => {
              if (loader) loader.style.display = 'none';
              this.toast('Failed to load file: ' + err.message);
              this.closeModals();
            });
        }

        async loadStarredSet() {
          try {
            const res = await fetch('?action=starred_list');
            const data = await res.json();
            this.starredSet = new Set(data.starred_paths || []);
          } catch(e) {}
        }

        switchDriveSection(section, updateHash = true) {
          if (updateHash) {
            const targetHash = section === 'home' ? '#/' : `#/@${section}`;
            if (window.location.hash !== targetHash) {
              window.location.hash = targetHash;
              return;
            }
          }

          this.currentSection = section;
          this.sidebar.classList.remove('open');
          this.sidebarBackdrop.classList.remove('active');
          document.querySelectorAll('#nav-home, #nav-recents, #nav-starred, #nav-activity, #nav-trash, #nav-gallery, #nav-videos, #nav-audio, #nav-documents').forEach(el => el.classList.remove('active'));
          document.getElementById(`nav-${section}`)?.classList.add('active');

          this.filter = 'all';
          document.querySelectorAll('.filter-item[data-filter]').forEach(p => {
            if (p.dataset.filter === 'all') p.classList.add('active');
            else p.classList.remove('active');
          });

          if (section === 'home') {
            this.currentPath = null;
            this.loadDir('');
          } else if (section === 'recents') {
            this.loadRecents();
          } else if (section === 'starred') {
            this.loadStarred();
          } else if (section === 'activity') {
            this.loadActivity();
          } else if (section === 'trash') {
            this.loadTrash();
          } else if (section === 'gallery') {
            this.loadGallery();
          } else if (section === 'videos') {
            this.loadVideos();
          } else if (section === 'audio') {
            this.loadAudio();
          } else if (section === 'documents') {
            this.loadDocuments();
          }
        }

        async loadGallery() {
          const seq = ++this.navSeq;
          this.currentSection = 'gallery';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.renderLimit = 25;
          this.isSearching = false;
          this.dirTitle.innerText = 'Gallery';
          this.dirStats.innerText = 'All photos across your drive';
          this.container.style.opacity = '1';
          this.container.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg><div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading gallery...</div></div>';

          try {
            const res = await fetch('?action=gallery_list');
            if (seq !== this.navSeq) return;
            const data = await res.json();
            if (seq !== this.navSeq) return;
            this.data = {
              folders: [],
              files: data.files || [],
              stats: data.stats || { total_size: '', files: (data.files || []).length, folders: 0 },
              path: ''
            };
            this.renderGallery();
            this.updateBreadcrumbs();
            this.updateBadges();
            this.updateDocTitle('Gallery', this.data.files.length);
          } catch (e) {
            if (seq !== this.navSeq) return;
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        async loadVideos() {
          const seq = ++this.navSeq;
          this.currentSection = 'videos';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.renderLimit = 25;
          this.isSearching = false;
          this.dirTitle.innerText = 'Videos';
          this.dirStats.innerText = 'All videos across your drive';
          this.container.style.opacity = '1';
          this.container.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg><div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading videos...</div></div>';

          try {
            const res = await fetch('?action=video_list');
            if (seq !== this.navSeq) return;
            const data = await res.json();
            if (seq !== this.navSeq) return;
            this.data = {
              folders: [],
              files: data.files || [],
              stats: data.stats || { total_size: '', files: (data.files || []).length, folders: 0 },
              path: ''
            };
            this.renderGallery();
            this.updateBreadcrumbs();
            this.updateBadges();
            this.updateDocTitle('Videos', this.data.files.length);
          } catch (e) {
            if (seq !== this.navSeq) return;
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        async loadAudio() {
          const seq = ++this.navSeq;
          this.currentSection = 'audio';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.renderLimit = 25;
          this.isSearching = false;
          this.dirTitle.innerText = 'Audio';
          this.dirStats.innerText = 'All audio tracks across your drive';
          this.container.style.opacity = '1';
          this.container.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg><div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading audio...</div></div>';

          try {
            const res = await fetch('?action=audio_list');
            if (seq !== this.navSeq) return;
            const data = await res.json();
            if (seq !== this.navSeq) return;
            this.data = {
              folders: [],
              files: data.files || [],
              stats: data.stats || { total_size: '', files: (data.files || []).length, folders: 0 },
              path: ''
            };
            this.renderGallery();
            this.updateBreadcrumbs();
            this.updateBadges();
            this.updateDocTitle('Audio', this.data.files.length);
          } catch (e) {
            if (seq !== this.navSeq) return;
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        async loadDocuments() {
          const seq = ++this.navSeq;
          this.currentSection = 'documents';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.renderLimit = 25;
          this.isSearching = false;
          this.dirTitle.innerText = 'Documents & Archives';
          this.dirStats.innerText = 'All text, documents, code, and compressed files';
          this.container.style.opacity = '1';
          this.container.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg><div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading documents...</div></div>';

          try {
            const res = await fetch('?action=document_list');
            if (seq !== this.navSeq) return;
            const data = await res.json();
            if (seq !== this.navSeq) return;
            this.data = {
              folders: [],
              files: data.files || [],
              stats: data.stats || { total_size: '', files: (data.files || []).length, folders: 0 },
              path: ''
            };
            this.renderGallery();
            this.updateBreadcrumbs();
            this.updateBadges();
            this.updateDocTitle('Documents', this.data.files.length);
          } catch (e) {
            if (seq !== this.navSeq) return;
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        async loadActivity() {
          const seq = ++this.navSeq;
          this.currentSection = 'activity';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.isSearching = false;
          this.dirTitle.innerText = 'File Activity';
          this.dirStats.innerText = 'Tracking modified, edited, created, and uploaded files';
          this.updateBreadcrumbs();
          this.updateDocTitle('File Activity');

          this.container.className = 'gallery-container';
          this.container.removeAttribute('data-cols');
          this.container.style.opacity = '1';
          this.container.innerHTML = `
            <div class="center-state">
              <svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
              <div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading activity...</div>
            </div>
          `;

          try {
            const res = await fetch('?action=activity_list');
            if (seq !== this.navSeq) return;
            const data = await res.json();
            if (seq !== this.navSeq) return;
            this.activityStats = data.stats || {};
            this.rawActivities = data.activities || [];

            // Scan and categorize unique files in activity for sidebar category badges
            const seenPaths = new Set();
            const activityFiles = [];
            this.rawActivities.forEach(act => {
              const p = act.path || act.name || '';
              if (p && !seenPaths.has(p)) {
                seenPaths.add(p);
                const ext = (p.split('.').pop() || '').toLowerCase();
                activityFiles.push({ path: p, name: act.name, type: this.getFileTypeByExt(ext) });
              }
            });

            this.data = { folders: [], files: activityFiles, stats: {} };
            this.updateBadges();

            this.renderActivityView();
          } catch (e) {
            if (seq !== this.navSeq) return;
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        renderActivityView() {
          this.container.style.opacity = '1';
          const stats = this.activityStats || {};
          let activities = this.rawActivities || [];

          if (this.searchQuery) {
            const q = this.searchQuery.toLowerCase();
            activities = activities.filter(act => 
              (act.name || '').toLowerCase().includes(q) || 
              (act.path || '').toLowerCase().includes(q) ||
              (act.details || '').toLowerCase().includes(q) ||
              (act.action || '').toLowerCase().includes(q)
            );
          }

          if (this.filter && this.filter !== 'all') {
            activities = activities.filter(act => {
              const p = act.path || act.name || '';
              const ext = (p.split('.').pop() || '').toLowerCase();
              return this.getFileTypeByExt(ext) === this.filter;
            });
          }

          // Apply selected sort criteria to activities
          activities.sort((a, b) => {
            const nameA = a.name || a.path || '';
            const nameB = b.name || b.path || '';
            if (this.sortBy === 'name_asc') {
              return nameA.localeCompare(nameB, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'name_desc') {
              return nameB.localeCompare(nameA, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'date_asc') {
              return (a.timestamp || 0) - (b.timestamp || 0);
            }
            if (this.sortBy === 'ext_asc') {
              const extA = (nameA.split('.').pop() || '').toLowerCase();
              const extB = (nameB.split('.').pop() || '').toLowerCase();
              return extA.localeCompare(extB, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'ext_desc') {
              const extA = (nameA.split('.').pop() || '').toLowerCase();
              const extB = (nameB.split('.').pop() || '').toLowerCase();
              return extB.localeCompare(extA, undefined, { numeric: true, sensitivity: 'base' });
            }
            return (b.timestamp || 0) - (a.timestamp || 0);
          });

          let statsHtml = `
            <div class="activity-stats-grid">
              <div class="activity-stat-card">
                <div class="activity-stat-num">${stats.modified_today || 0}</div>
                <div class="activity-stat-label">Files Modified Today</div>
              </div>
              <div class="activity-stat-card">
                <div class="activity-stat-num">${stats.today_count || 0}</div>
                <div class="activity-stat-label">Total Actions Today</div>
              </div>
              <div class="activity-stat-card">
                <div class="activity-stat-num">${stats.week_count || 0}</div>
                <div class="activity-stat-label">Changes Past 7 Days</div>
              </div>
              <div class="activity-stat-card">
                <div class="activity-stat-num">${stats.unique_files || 0}</div>
                <div class="activity-stat-label">Unique Files Changed</div>
              </div>
            </div>
          `;

          if (!activities.length) {
            const emptyMsg = this.searchQuery 
              ? `No activity matching "${this.escapeHtml(this.searchQuery)}"`
              : (this.filter !== 'all' ? `No activity found for category: ${this.filter}` : 'No recorded activity yet');
            this.container.innerHTML = `<div class="activity-view-wrapper">${statsHtml}<div class="center-state"><p>${emptyMsg}</p></div></div>`;
            return;
          }

          const toRender = activities.slice(0, this.renderLimit);
          let rowsHtml = toRender.map(act => {
            const d = act.timestamp ? new Date(act.timestamp * 1000).toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
            const actType = act.action || 'modified';
            return `
              <div class="activity-row" onclick="app.openFile('${act.path.replace(/'/g, "\\'")}', true)">
                <div style="display:flex; align-items:center; gap:0.6rem; min-width:0; flex:1;">
                  <span class="activity-badge ${actType}">${actType}</span>
                  <div style="display:flex; flex-direction:column; gap:0.1rem; min-width:0; overflow:hidden;">
                    <span style="font-weight:600; font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${act.name || act.path}</span>
                    <span style="font-size:0.72rem; color:var(--md-sys-color-on-surface-variant); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${act.path}${act.details ? ' • ' + act.details : ''}</span>
                  </div>
                </div>
                <span class="activity-row-time" style="font-size:0.75rem; color:var(--md-sys-color-outline); white-space:nowrap; flex-shrink:0;">${d}</span>
              </div>
            `;
          }).join('');

          this.container.innerHTML = `
            <div class="activity-view-wrapper">
              ${statsHtml}
              <div style="display:flex; justify-content:space-between; align-items:center; padding:0.2rem 0.4rem;">
                <span style="font-size:0.8rem; font-weight:700; color:var(--md-sys-color-primary); text-transform:uppercase; letter-spacing:0.5px;">Activity History (${activities.length})</span>
                <button class="btn-primary" style="height:32px; padding:0 0.85rem; font-size:0.78rem; background:var(--md-sys-color-surface-container-low); color:var(--md-sys-color-on-surface); border:1px solid var(--md-sys-color-outline-variant);" onclick="app.openSortDropdown(event)">
                  <svg viewBox="0 0 24 24" style="width:15px;height:15px;margin-right:4px;"><path d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/></svg>
                  <span>Sort: ${this.getSortLabel()}</span>
                </button>
              </div>
              <div class="activity-list-container">
                ${rowsHtml}
              </div>
            </div>
          `;
        }

        async loadRecents() {
          const seq = ++this.navSeq;
          this.currentSection = 'recents';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.renderLimit = 25;
          this.isSearching = false;
          this.dirTitle.innerText = 'Recents';
          this.dirStats.innerText = 'Chronologically sorted from newest to oldest';
          this.container.style.opacity = '1';
          this.container.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg><div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading recents...</div></div>';

          try {
            const res = await fetch('?action=recents_list');
            if (seq !== this.navSeq) return;
            const data = await res.json();
            if (seq !== this.navSeq) return;

            const dedupe = (list) => {
              const seen = new Set();
              return (list || []).filter(item => {
                const k = (item.path || '').replace(/\\/g, '/').replace(/^\/+/, '');
                if (seen.has(k)) return false;
                seen.add(k);
                return true;
              });
            };

            const folders = dedupe(data.folders);
            const files = dedupe(data.files);

            this.data = {
              folders: folders,
              files: files,
              stats: { total_size: '', files: files.length, folders: folders.length },
              path: ''
            };
            this.renderGallery();
            this.updateBreadcrumbs();
            this.updateBadges();
            this.updateDocTitle('Recents', folders.length + files.length);
          } catch (e) {
            if (seq !== this.navSeq) return;
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        async loadStarred() {
          const seq = ++this.navSeq;
          this.currentSection = 'starred';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.renderLimit = 25;
          this.isSearching = false;
          this.dirTitle.innerText = 'Starred Items';
          this.dirStats.innerText = 'Quick access to your favorite files and folders';
          this.container.style.opacity = '1';
          this.container.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg><div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading starred items...</div></div>';

          try {
            const res = await fetch('?action=starred_list');
            if (seq !== this.navSeq) return;
            const data = await res.json();
            if (seq !== this.navSeq) return;
            this.starredSet = new Set(data.starred_paths || []);
            this.data = {
              folders: data.folders || [],
              files: data.files || [],
              stats: data.stats || { total_size: '0 B', files: (data.files || []).length, folders: (data.folders || []).length },
              path: ''
            };
            this.renderGallery();
            this.updateBreadcrumbs();
            this.updateBadges();
            this.updateDocTitle('Starred Items');
          } catch(e) {
            if (seq !== this.navSeq) return;
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        toggleStarDirect(e, path) {
          e.stopPropagation();
          this.api('star_toggle', { path }, (res) => {
            if (res.is_starred) {
              this.starredSet.add(path);
            } else {
              this.starredSet.delete(path);
            }
            this.toast(res.is_starred ? 'Starred' : 'Unstarred');
            if (this.currentSection === 'starred') {
              const scrollEl = document.getElementById('main-content');
              this.savedScrollTop = scrollEl ? scrollEl.scrollTop : 0;
              this.loadStarred();
            } else {
              // Update only the star icon on the specific card in DOM without reloading
              const starBtns = this.container.querySelectorAll(`.file-card[data-path="${CSS.escape(path)}"] .file-star-btn`);
              const starSvg = res.is_starred
                ? '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'
                : '<svg viewBox="0 0 24 24"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>';
              starBtns.forEach(btn => {
                btn.className = `file-star-btn ${res.is_starred ? 'active' : ''}`;
                btn.title = res.is_starred ? 'Unstar' : 'Star';
                btn.innerHTML = starSvg;
              });
            }
          });
        }

        promptUploadUrl() {
          const urlInput = document.getElementById('remote-url-input');
          const nameInput = document.getElementById('remote-name-input');
          if (urlInput) urlInput.value = '';
          if (nameInput) nameInput.value = '';

          this.showModal('modal-remote-download');
          if (urlInput) urlInput.focus();

          document.getElementById('remote-download-confirm').onclick = () => {
            const url = urlInput ? urlInput.value.trim() : '';
            const customName = nameInput ? nameInput.value.trim() : '';
            if (!url) {
              this.toast('Please enter a valid URL');
              return;
            }
            this.closeModals();
            uploadManager.enqueueRemoteDownload(url, customName, this.currentPath || '');
          };
        }

        async loadTrash() {
          const seq = ++this.navSeq;
          this.currentSection = 'trash';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.isSearching = false;
          this.dirTitle.innerText = 'Trash Bin';
          this.dirStats.innerText = 'Items are permanently deleted after 30 days';
          this.updateBreadcrumbs();
          this.updateDocTitle('Trash Bin');

          this.container.className = 'gallery-container';
          this.container.removeAttribute('data-cols');
          this.container.style.opacity = '1';
          this.container.innerHTML = `
            <div class="center-state">
              <svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
              <div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading trash...</div>
            </div>
          `;

          try {
            const res = await fetch('?action=trash_list');
            if (seq !== this.navSeq) return;
            const data = await res.json();
            if (seq !== this.navSeq) return;
            this.rawTrash = data.trash || [];

            const trashFiles = this.rawTrash.filter(i => !i.is_dir).map(i => {
              const ext = (i.original_name.split('.').pop() || '').toLowerCase();
              return { type: this.getFileTypeByExt(ext) };
            });
            this.data = { folders: this.rawTrash.filter(i => i.is_dir), files: trashFiles, stats: {} };
            this.updateBadges();

            this.renderTrashView();
          } catch (e) {
            this.container.style.opacity = '1';
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        renderTrashView() {
          this.container.style.opacity = '1';
          let items = this.rawTrash || [];

          if (this.searchQuery) {
            const q = this.searchQuery.toLowerCase();
            items = items.filter(i => (i.original_name || '').toLowerCase().includes(q) || (i.original_rel || '').toLowerCase().includes(q));
          }

          if (this.filter && this.filter !== 'all') {
            items = items.filter(i => {
              if (i.is_dir) return false;
              const ext = (i.original_name.split('.').pop() || '').toLowerCase();
              return this.getFileTypeByExt(ext) === this.filter;
            });
          }

          // Apply selected sort criteria to trash items
          items.sort((a, b) => {
            const nameA = a.original_name || '';
            const nameB = b.original_name || '';
            if (this.sortBy === 'name_asc') {
              return nameA.localeCompare(nameB, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'name_desc') {
              return nameB.localeCompare(nameA, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'date_asc') {
              return (a.trashed_at || 0) - (b.trashed_at || 0);
            }
            if (this.sortBy === 'ext_asc') {
              const extA = (nameA.split('.').pop() || '').toLowerCase();
              const extB = (nameB.split('.').pop() || '').toLowerCase();
              return extA.localeCompare(extB, undefined, { numeric: true, sensitivity: 'base' });
            }
            if (this.sortBy === 'ext_desc') {
              const extA = (nameA.split('.').pop() || '').toLowerCase();
              const extB = (nameB.split('.').pop() || '').toLowerCase();
              return extB.localeCompare(extA, undefined, { numeric: true, sensitivity: 'base' });
            }
            return (b.trashed_at || 0) - (a.trashed_at || 0);
          });

          if (!items.length) {
            if (this.searchQuery) {
              this.container.innerHTML = `
                <div class="center-state" style="grid-column: 1 / -1; padding: 3.5rem 0;">
                  <svg viewBox="0 0 24 24" style="width:48px; height:48px; opacity:0.4; color:var(--md-sys-color-outline); margin-bottom:0.6rem;"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                  <div style="font-weight:700; font-size:1.1rem; color:var(--md-sys-color-on-surface);">No matching trash items</div>
                  <div style="font-size:0.82rem; color:var(--md-sys-color-on-surface-variant);">No items matching "${this.escapeHtml(this.searchQuery)}" found in trash.</div>
                </div>
              `;
            } else {
              this.container.innerHTML = `
                <div class="center-state" style="grid-column: 1 / -1; padding: 3.5rem 0;">
                  <svg viewBox="0 0 24 24" style="width:64px; height:64px; opacity:0.3; color:var(--md-sys-color-outline); margin-bottom:0.6rem;"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  <div style="font-weight:700; font-size:1.1rem; color:var(--md-sys-color-on-surface);">Trash is Empty</div>
                  <div style="font-size:0.82rem; color:var(--md-sys-color-on-surface-variant);">Deleted files and folders will appear here.</div>
                </div>
              `;
            }
            return;
          }

          let html = `
            <div class="trash-view-wrapper">
              <div style="display:flex; justify-content:space-between; align-items:center; background:var(--md-sys-color-surface-container-low); border:1px solid var(--md-sys-color-outline-variant); border-radius:16px; padding:0.75rem 1.1rem; gap:0.6rem; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:var(--md-sys-color-on-surface-variant);">
                  <span style="font-weight:700; color:var(--md-sys-color-on-surface); font-size:0.95rem;">${items.length}</span> item(s) in trash
                </div>
                <div style="display:flex; align-items:center; gap:0.4rem;">
                  <button class="btn-primary" style="height:34px; padding:0 0.85rem; font-size:0.8rem; background:var(--md-sys-color-surface-container-high); color:var(--md-sys-color-on-surface); border:1px solid var(--md-sys-color-outline-variant);" onclick="app.openSortDropdown(event)">
                    <svg viewBox="0 0 24 24" style="width:15px;height:15px;margin-right:4px;"><path d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/></svg>
                    <span>Sort: ${this.getSortLabel()}</span>
                  </button>
                  <button class="btn-primary" style="background:#dc2626; height:34px; padding:0 0.95rem; font-size:0.8rem; gap:0.4rem;" onclick="app.emptyTrash()">
                    <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    Empty Trash
                  </button>
                </div>
              </div>
              <div style="display:flex; flex-direction:column; gap:0.55rem; width:100%;">
          `;

          const toRender = items.slice(0, this.renderLimit);
          toRender.forEach(t => {
            const d = t.trashed_at ? new Date(t.trashed_at * 1000).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
            const icon = t.is_dir
              ? '<svg viewBox="0 0 16 16" style="width:22px;height:22px;color:#f59e0b;flex-shrink:0;"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg>'
              : '<svg viewBox="0 0 24 24" style="width:22px;height:22px;color:var(--md-sys-color-primary);flex-shrink:0;"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>';

            const safeName = this.escapeHtml(t.original_name);
            const safeRel = this.escapeHtml(t.original_rel);
            const paramName = safeName.replace(/'/g, "\\'");

            html += `
              <div style="background:var(--md-sys-color-surface-container-low); border:1px solid var(--md-sys-color-outline-variant); border-radius:14px; padding:0.75rem 1rem; display:flex; align-items:center; justify-content:space-between; gap:0.85rem;">
                <div style="display:flex; align-items:center; gap:0.75rem; min-width:0; flex:1;">
                  ${icon}
                  <div style="display:flex; flex-direction:column; gap:0.15rem; min-width:0; overflow:hidden;">
                    <span style="font-weight:600; font-size:0.88rem; color:var(--md-sys-color-on-surface); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${safeName}</span>
                    <span style="font-size:0.72rem; color:var(--md-sys-color-on-surface-variant); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Original: ${safeRel} ${d ? '• Trashed ' + d : ''}</span>
                  </div>
                </div>
                <div style="display:flex; align-items:center; gap:0.4rem; flex-shrink:0;">
                  <button class="btn-primary" style="height:32px; padding:0 0.75rem; font-size:0.78rem; gap:0.35rem;" onclick="app.restoreTrashItem('${t.trash_name}', '${paramName}')">
                    <svg viewBox="0 0 24 24" style="width:14px; height:14px;"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
                    Restore
                  </button>
                  <button class="btn-icon" style="width:32px; height:32px; border-radius:8px; color:var(--md-sys-color-error);" title="Delete Permanently" onclick="app.deleteTrashItemPermanently('${t.trash_name}', '${paramName}')">
                    <svg viewBox="0 0 24 24" style="width:16px; height:16px;"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  </button>
                </div>
              </div>
            `;
          });

          html += `</div></div>`;
          this.container.innerHTML = html;
        }

        restoreTrashItem(trashId, originalName) {
          if (confirm(`Restore "${originalName}" to its original location?`)) {
            this.api('trash_restore', { trash_id: trashId }, () => {
              this.toast('Item restored');
              this.loadTrash();
            });
          }
        }

        deleteTrashItemPermanently(trashId, originalName) {
          if (confirm(`Permanently delete "${originalName}"? This action cannot be undone.`)) {
            this.api('trash_delete', { trash_id: trashId }, () => {
              this.toast('Item deleted permanently');
              this.loadTrash();
            });
          }
        }

        emptyTrash() {
          if (confirm('Permanently delete all items in trash? This cannot be undone.')) {
            this.api('trash_empty', {}, () => {
              this.toast('Trash emptied');
              this.loadTrash();
            });
          }
        }

        toggleStar(path) {
          this.api('star_toggle', { path }, (res) => {
            this.toast(res.is_starred ? 'Starred' : 'Unstarred');
            this.refresh();
          });
        }

        openVersionHistory(path) {
          const fileName = path.split('/').pop();
          const subTitle = document.getElementById('versions-title-sub');
          if (subTitle) subTitle.innerText = fileName;

          fetch(`?action=versions_list&f=${encodeURIComponent(path)}`)
            .then(r => r.json())
            .then(res => {
              const list = document.getElementById('versions-content');
              if (!res.versions || !res.versions.length) {
                list.innerHTML = `
                  <div class="center-state" style="min-height:220px; padding:1.5rem 0;">
                    <svg viewBox="0 0 24 24" style="width:48px; height:48px; opacity:0.35; color:var(--md-sys-color-outline);"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
                    <div style="font-weight:600; font-size:0.95rem; color:var(--md-sys-color-on-surface);">No prior versions recorded</div>
                    <div style="font-size:0.8rem; color:var(--md-sys-color-on-surface-variant); max-width:280px; text-align:center;">Snapshots are created automatically every time you edit or save changes to this file.</div>
                  </div>
                `;
              } else {
                const total = res.versions.length;
                list.innerHTML = `
                  <div class="version-timeline">
                    ${res.versions.map((v, idx) => {
                      const revNum = total - idx;
                      return `
                        <div class="version-item">
                          <div class="version-info">
                            <div class="version-date">
                              <svg viewBox="0 0 24 24" style="width:15px; height:15px; color:var(--md-sys-color-primary);"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                              <span>${v.date}</span>
                            </div>
                            <div class="version-meta">
                              <span class="version-badge">Rev #${revNum}</span>
                              <span>${v.size}</span>
                            </div>
                          </div>
                          <div class="version-actions">
                            <button class="btn-primary" style="height:32px; padding:0 0.65rem; font-size:0.78rem; background:var(--md-sys-color-surface-container-high); color:var(--md-sys-color-on-surface); border:1px solid var(--md-sys-color-outline-variant);" onclick="app.previewDiff('${path}', '${v.filename}', '${v.date}')">
                              <svg viewBox="0 0 24 24" style="width:14px; height:14px;"><path d="M10 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h5v2h2V1h-2v2zm-5 4h5v2H5V7zm0 4h5v2H5v-2zm0 4h5v2H5v-2zm14-8h-5v2h5v10h-5v2h5c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2z"/></svg>
                              <span>Diff</span>
                            </button>
                            <button class="btn-primary" style="height:32px; padding:0 0.75rem; font-size:0.78rem; gap:0.35rem;" onclick="app.restoreVersion('${path}', '${v.filename}')">
                              <svg viewBox="0 0 24 24" style="width:14px; height:14px;"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
                              <span>Rollback</span>
                            </button>
                          </div>
                        </div>
                      `;
                    }).join('')}
                  </div>
                `;
              }
              this.showModal('modal-versions');
            });
        }

        async previewDiff(path, versionFilename, versionDate) {
          try {
            const res = await fetch(`?action=version_read&f=${encodeURIComponent(path)}&version=${encodeURIComponent(versionFilename)}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Failed to read version data');

            document.getElementById('diff-modal-title').innerText = `Diff: ${path.split('/').pop()}`;
            document.getElementById('diff-modal-subtitle').innerText = `Comparing snapshot (${versionDate}) with current version`;

            const diffLines = this.computeLineDiff(data.current || '', data.version || '', 1000);
            let adds = 0, dels = 0;

            const container = document.getElementById('diff-content');
            let html = diffLines.map(d => {
              if (d.type === 'add') adds++;
              if (d.type === 'del') dels++;
              const sign = d.type === 'add' ? '+' : (d.type === 'del' ? '-' : ' ');
              const cls = d.type === 'add' ? 'diff-add' : (d.type === 'del' ? 'diff-del' : 'diff-same');
              return `
                <div class="diff-line ${cls}">
                  <span class="diff-num">${d.oldNum || ''}</span>
                  <span class="diff-num">${d.newNum || ''}</span>
                  <span class="diff-sign">${sign}</span>
                  <span class="diff-text">${this.escapeHtml(d.text)}</span>
                </div>
              `;
            }).join('');

            if (diffLines.truncated) {
              html += `
                <div style="padding:0.75rem; text-align:center; background:var(--md-sys-color-surface-container-high); color:var(--md-sys-color-primary); font-size:0.75rem; font-weight:600;">
                  Large file optimized: previewing first modified section (${diffLines.length} lines shown).
                </div>
              `;
            }

            container.innerHTML = html;

            document.getElementById('diff-stats').innerHTML = `
              <span style="color:#7ee787;">+${adds} lines to restore</span>
              <span style="color:#ff7b72;">-${dels} current lines replaced</span>
            `;

            document.getElementById('diff-rollback-btn').onclick = () => {
              this.restoreVersion(path, versionFilename);
            };

            this.showModal('modal-diff');
          } catch (e) {
            this.toast(e.message);
          }
        }

        computeLineDiff(oldText, newText, maxRender = 1000) {
          const oldLines = oldText.split('\n');
          const newLines = newText.split('\n');
          const M = oldLines.length;
          const N = newLines.length;

          // 1. Fast Prefix Trimming
          let start = 0;
          while (start < M && start < N && oldLines[start] === newLines[start]) {
            start++;
          }

          // 2. Fast Suffix Trimming
          let oldEnd = M - 1;
          let newEnd = N - 1;
          while (oldEnd >= start && newEnd >= start && oldLines[oldEnd] === newLines[newEnd]) {
            oldEnd--;
            newEnd--;
          }

          const diff = [];

          // Retain small context prefix
          const prefixCtx = Math.min(start, 2);
          for (let k = start - prefixCtx; k < start; k++) {
            if (k >= 0) diff.push({ type: 'same', text: oldLines[k], oldNum: k + 1, newNum: k + 1 });
          }

          const subM = oldEnd - start + 1;
          const subN = newEnd - start + 1;

          // Bounded Myers/LCS on modified slice only; linear block compare if slice > 1200 lines
          if (subM > 1200 || subN > 1200 || (subM * subN > 800000)) {
            let i = start, j = start;
            let count = 0;
            while ((i <= oldEnd || j <= newEnd) && count < maxRender) {
              if (i <= oldEnd && j <= newEnd && oldLines[i] === newLines[j]) {
                diff.push({ type: 'same', text: oldLines[i], oldNum: i + 1, newNum: j + 1 });
                i++; j++;
              } else {
                if (i <= oldEnd) {
                  diff.push({ type: 'del', text: oldLines[i], oldNum: i + 1 });
                  i++; count++;
                }
                if (j <= newEnd && count < maxRender) {
                  diff.push({ type: 'add', text: newLines[j], newNum: j + 1 });
                  j++; count++;
                }
              }
            }
            if (i <= oldEnd || j <= newEnd) {
              diff.truncated = true;
            }
          } else {
            const dp = Array.from({ length: subM + 1 }, () => new Int32Array(subN + 1));
            for (let i = 0; i < subM; i++) {
              for (let j = 0; j < subN; j++) {
                if (oldLines[start + i] === newLines[start + j]) {
                  dp[i + 1][j + 1] = dp[i][j] + 1;
                } else {
                  dp[i + 1][j + 1] = Math.max(dp[i + 1][j], dp[i][j + 1]);
                }
              }
            }

            let i = subM, j = subN;
            const midDiff = [];
            while (i > 0 || j > 0) {
              if (i > 0 && j > 0 && oldLines[start + i - 1] === newLines[start + j - 1]) {
                midDiff.unshift({ type: 'same', text: oldLines[start + i - 1], oldNum: start + i, newNum: start + j });
                i--; j--;
              } else if (j > 0 && (i === 0 || dp[i][j - 1] >= dp[i - 1][j])) {
                midDiff.unshift({ type: 'add', text: newLines[start + j - 1], newNum: start + j });
                j--;
              } else if (i > 0 && (j === 0 || dp[i][j - 1] < dp[i - 1][j])) {
                midDiff.unshift({ type: 'del', text: oldLines[start + i - 1], oldNum: start + i });
                i--;
              }
            }
            diff.push(...midDiff);
          }

          // Retain small context suffix
          const suffixStart = oldEnd + 1;
          const suffixEnd = Math.min(M, suffixStart + 2);
          for (let k = suffixStart; k < suffixEnd; k++) {
            const newK = newEnd + 1 + (k - suffixStart);
            diff.push({ type: 'same', text: oldLines[k], oldNum: k + 1, newNum: newK + 1 });
          }

          return diff;
        }

        escapeHtml(str) {
          return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        restoreVersion(path, version) {
          if (confirm(`Roll back to version from ${version}? Current state will be backed up.`)) {
            this.api('version_restore', { f: path, version }, () => {
              this.toast('Rolled back to version');
              this.closeModals();
              this.openEditor(path, path.split('/').pop());
            });
          }
        }

        encryptDecryptFile(path, isEncrypted) {
          const act = isEncrypted ? 'decrypt_file' : 'encrypt_file';
          this.showInputModal(isEncrypted ? 'Decrypt File' : 'Encrypt File', 'Password (leave blank for default)', '', (pass) => {
            this.api(act, { f: path, password: pass }, () => {
              this.toast(isEncrypted ? 'File Decrypted' : 'File Encrypted');
              this.refresh();
            });
          });
        }

        showShareModal(url, filename) {
          const titleEl = document.getElementById('share-modal-filename');
          const inputEl = document.getElementById('share-link-input');
          const copyBtn = document.getElementById('share-copy-btn');
          const nativeContainer = document.getElementById('share-native-container');
          const nativeBtn = document.getElementById('share-native-btn');

          if (titleEl) titleEl.innerText = filename;
          if (inputEl) inputEl.value = url;

          if (copyBtn) {
            copyBtn.onclick = () => {
              if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url);
                this.toast('Copied to clipboard!');
              } else {
                inputEl.select();
                document.execCommand('copy');
                this.toast('Copied to clipboard!');
              }
              copyBtn.innerHTML = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;margin-right:0.3rem;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Copied';
              setTimeout(() => copyBtn.innerHTML = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;margin-right:0.3rem;"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg> Copy', 2000);
            };
          }

          if (navigator.share) {
            nativeContainer.style.display = 'flex';
            nativeBtn.onclick = () => {
              navigator.share({
                title: filename,
                url: url
              }).catch(console.error);
            };
          } else {
            nativeContainer.style.display = 'none';
          }

          this.showModal('modal-share');
        }

        createShareLink(path) {
          this.api('share_create', { f: path }, (res) => {
            const url = `${window.location.origin}${window.location.pathname}?share=${res.token}`;
            this.showShareModal(url, path.split('/').pop());
          });
        }

        updateClipboardUI() {
          const bar = document.getElementById('drive-clipboard-bar');
          const txt = document.getElementById('drive-clipboard-txt');
          const pasteBtn = document.getElementById('btn-drive-clipboard-paste');
          if (pasteBtn) {
            pasteBtn.disabled = false;
            pasteBtn.innerHTML = `<svg viewBox="0 0 24 24" style="width:15px;height:15px;margin-right:4px;"><path d="M19 2h-4.18C14.4.84 13.3 0 12 0c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm7 18H5V4h2v3h10V4h2v16z"/></svg> Paste Here`;
          }
          if (!bar) return;
          if (this.clipboard && this.clipboard.items && this.clipboard.items.length > 0) {
            const count = this.clipboard.items.length;
            const op = this.clipboard.operation === 'cut' ? 'Cut (Move)' : 'Copied';
            if (txt) txt.innerText = `${count} item(s) ${op}`;
            bar.classList.add('active');

            this.container.querySelectorAll('.file-card').forEach(card => {
              const p = card.dataset.path;
              if (this.clipboard.operation === 'cut' && this.clipboard.items.includes(p)) {
                card.style.opacity = '0.45';
              } else {
                card.style.opacity = '';
              }
            });
          } else {
            bar.classList.remove('active');
            this.container.querySelectorAll('.file-card').forEach(card => card.style.opacity = '');
          }
        }

        setClipboard(operation) {
          const items = this.selectedItems.size ? Array.from(this.selectedItems) : [];
          if (!items.length) return;
          this.clipboard = { operation, items };
          this.updateClipboardUI();
          const opLabel = operation === 'cut' ? 'Cut (Move)' : 'Copied';
          this.toast(`${items.length} item(s) ${opLabel.toLowerCase()} to clipboard`);
        }

        setClipboardSingle(operation, path) {
          this.clipboard = { operation, items: [path] };
          this.updateClipboardUI();
          const opLabel = operation === 'cut' ? 'Cut (Move)' : 'Copied';
          this.toast(`${path.split('/').pop()} marked to ${opLabel.toLowerCase()}`);
        }

        clearClipboard() {
          this.clipboard = null;
          this.updateClipboardUI();
          this.toast('Clipboard cleared');
        }

        pasteClipboardInto(targetPath) {
          if (!this.clipboard || !this.clipboard.items || !this.clipboard.items.length) {
            this.toast('Clipboard is empty');
            return;
          }

          const count = this.clipboard.items.length;
          const op = this.clipboard.operation === 'cut' ? 'cut' : 'copy';
          const opLabel = op === 'cut' ? 'Moving' : 'Copying';
          const targetFolder = targetPath ? targetPath.split('/').pop() : 'Root';

          const pasteBtn = document.getElementById('btn-drive-clipboard-paste');
          if (pasteBtn) {
            pasteBtn.disabled = true;
            pasteBtn.textContent = `${opLabel}...`;
          }

          this.runServerTaskWithProgress(`${opLabel} ${count} item(s) to "${targetFolder}"`, 'clipboard_paste', {
            target_dir: targetPath || '',
            operation: op,
            items: this.clipboard.items
          }, (res) => {
            this.toast(`${op === 'cut' ? 'Moved' : 'Copied'} ${res.processed || count} item(s) successfully`);
            if (op === 'cut') this.clipboard = null;
            this.updateClipboardUI();
            this.refresh();
            if (pasteBtn) pasteBtn.disabled = false;
          });
        }

        pasteClipboard() {
          this.pasteClipboardInto(this.currentPath || '');
        }

        copyDirectUrl(path) {
          const directUrl = new URL('?action=raw&f=' + encodeURIComponent(path), window.location.href).href;
          navigator.clipboard.writeText(directUrl);
          this.toast('Direct URL copied to clipboard');
        }

        openInNewTab(path, type) {
          if (type === 'folder') {
            window.open('#/' + ltrim(path, '/'), '_blank');
          } else {
            window.open('?action=raw&f=' + encodeURIComponent(path), '_blank');
          }
        }
  
        showContextMenu(e, type, path, name, fileType) {
          e.preventDefault();
          e.stopPropagation();

          this.activeContextItem = { type, path, name, fileType };
          this.contextMenu.innerHTML = '';

          const isEnc = (name || '').endsWith('.enc');
          const isStarred = this.starredSet.has(path);

          const addItem = (iconSvg, text, action, isDanger = false) => {
            const div = document.createElement('div');
            div.className = `dm-item ${isDanger ? 'danger' : ''}`;
            div.innerHTML = `${iconSvg} <span>${text}</span>`;
            div.onclick = (ev) => {
              ev.stopPropagation();
              this.contextMenu.classList.remove('active');
              action();
            };
            this.contextMenu.appendChild(div);
          };

          const starSvg = isStarred
            ? '<svg viewBox="0 0 24 24" style="color:#f59e0b;"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'
            : '<svg viewBox="0 0 24 24"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>';

          if (type === 'folder') {
            if (this.clipboard && this.clipboard.items && this.clipboard.items.length > 0) {
              const op = this.clipboard.operation === 'cut' ? 'Move' : 'Paste';
              addItem('<svg viewBox="0 0 24 24"><path d="M19 2h-4.18C14.4.84 13.3 0 12 0c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm7 18H5V4h2v3h10V4h2v16z"/></svg>', `${op} into this folder`, () => {
                this.pasteClipboardInto(path);
              });
              const sep = document.createElement('div');
              sep.className = 'dm-sep';
              this.contextMenu.appendChild(sep);
            }
            addItem('<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>', 'Preview / Open', () => this.navigate(path));
            addItem('<svg viewBox="0 0 24 24"><path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg>', 'Open in a new tab', () => this.openInNewTab(path, 'folder'));
            addItem('<svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>', 'Download', () => this.downloadZipWithProgress(`?action=download_zip&dir=${encodeURIComponent(path)}`, null, `${name}.zip`));
            addItem('<svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>', 'Compress to ZIP', () => {
              this.showInputModal('Compress Folder', 'Archive Filename (.zip)', `${name}.zip`, (zipName) => {
                this.runServerTaskWithProgress('Compressing folder', 'zip', { dir: this.currentPath || '', items: [path], zip_name: zipName || `${name}.zip` }, () => {
                  this.toast('Folder compressed');
                  this.refresh();
                });
              });
            });
            addItem('<svg viewBox="0 0 24 24"><path d="M19 1L14 6V22L19 17V1M3 6V22L8 17H12V2H8L3 6M10 4.25C10 3.56 9.44 3 8.75 3S7.5 3.56 7.5 4.25 8.06 5.5 8.75 5.5 10 4.94 10 4.25Z"/></svg>', 'Read as Manga', () => mangaViewer.openPath(path));
            addItem(starSvg, isStarred ? 'Unstar' : 'Star', () => this.toggleStarDirect(e, path));
            addItem('<svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>', 'Rename', () => this.renameItem(path, name));
            addItem('<svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>', 'Info', () => this.showDetails(path));
            addItem('<svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>', 'Copy', () => this.setClipboardSingle('copy', path));
            addItem('<svg viewBox="0 0 24 24"><path d="M9.64 7.64c.23-.5.36-1.05.36-1.64 0-2.21-1.79-4-4-4S2 3.79 2 6s1.79 4 4 4c.59 0 1.14-.13 1.64-.36L10 12l-2.36 2.36C7.14 14.13 6.59 14 6 14c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4c0-.59-.13-1.14-.36-1.64L12 14l7 7h3v-1L9.64 7.64zM6 8c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm0 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6-7.5c-.28 0-.5-.22-.5-.5s.22-.5.5-.5.5.22.5.5-.22.5-.5.5zM19 3l-6 6 2 2 7-7V3h-3z"/></svg>', 'Cut (Move)', () => this.setClipboardSingle('cut', path));
            addItem('<svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>', 'Move to Trash', () => this.api('trash', { items: [path] }, () => { this.toast('Moved to Trash'); this.refresh(); }), true);
          } else {
            if (fileType === 'archive' || ['zip', 'rar', 'tar', 'gz', 'tgz', '7z'].includes((name.split('.').pop() || '').toLowerCase())) {
              addItem('<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>', 'Inspect Archive', () => this.previewArchive(path, name));
              addItem('<svg viewBox="0 0 24 24"><path d="M20 6h-8l-2-2H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm0 12H4V8h16v10z"/></svg>', 'Extract to Folder', () => this.unzipItem(path, true));
              addItem('<svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>', 'Extract Here', () => this.unzipItem(path, false));
            } else {
              addItem('<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>', 'Preview / Play', () => this.openFile(path, true));
            }
            addItem('<svg viewBox="0 0 24 24"><path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg>', 'Open in a new tab', () => this.openInNewTab(path, 'file'));
            addItem('<svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>', 'Download', () => { window.location.href = `?action=download&f=${encodeURIComponent(path)}`; });
            if (fileType === 'image') {
              addItem('<svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14zm2.5-4h-2v2H9v-2H7V9h2V7h1v2h2v1z"/></svg>', 'Search on Google', () => this.searchImageOnGoogle(path));
              addItem('<svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>', 'Edit Image', () => this.openImageEditor(path, name));
            }
            if (fileType !== 'archive') {
              addItem('<svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>', 'Compress to ZIP', () => {
                const baseName = name.replace(/\.[^/.]+$/, '');
                this.showInputModal('Compress File', 'Archive Filename (.zip)', `${baseName}.zip`, (zipName) => {
                  this.runServerTaskWithProgress('Compressing file', 'zip', { dir: this.currentPath || '', items: [path], zip_name: zipName || `${baseName}.zip` }, () => {
                    this.toast('File compressed');
                    this.refresh();
                  });
                });
              });
            }
            addItem('<svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg>', 'Public File Link', () => this.createShareLink(path));
            addItem(starSvg, isStarred ? 'Unstar' : 'Star', () => this.toggleStarDirect(e, path));
            addItem('<svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>', isEnc ? 'Decrypt File' : 'Encrypt File', () => this.encryptDecryptFile(path, isEnc));
            addItem('<svg viewBox="0 0 24 24"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>', 'Version History', () => this.openVersionHistory(path));
            addItem('<svg viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>', 'Copy Direct URL', () => this.copyDirectUrl(path));
            addItem('<svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>', 'Rename', () => this.renameItem(path, name));
            addItem('<svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>', 'Info', () => this.showDetails(path));
            addItem('<svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>', 'Copy', () => this.setClipboardSingle('copy', path));
            addItem('<svg viewBox="0 0 24 24"><path d="M9.64 7.64c.23-.5.36-1.05.36-1.64 0-2.21-1.79-4-4-4S2 3.79 2 6s1.79 4 4 4c.59 0 1.14-.13 1.64-.36L10 12l-2.36 2.36C7.14 14.13 6.59 14 6 14c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4c0-.59-.13-1.14-.36-1.64L12 14l7 7h3v-1L9.64 7.64zM6 8c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm0 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6-7.5c-.28 0-.5-.22-.5-.5s.22-.5.5-.5.5.22.5.5-.22.5-.5.5zM19 3l-6 6 2 2 7-7V3h-3z"/></svg>', 'Cut (Move)', () => this.setClipboardSingle('cut', path));
            addItem('<svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>', 'Move to Trash', () => this.api('trash', { items: [path] }, () => { this.toast('Moved to Trash'); this.refresh(); }), true);
          }

          this.contextMenu.style.left = '-9999px';
          this.contextMenu.style.top = '-9999px';
          this.contextMenu.classList.add('active');

          const menuWidth = this.contextMenu.offsetWidth || 230;
          const menuHeight = this.contextMenu.offsetHeight || 420;
          const padding = 12;

          let posX = e.clientX;
          let posY = e.clientY;

          if (posX + menuWidth > window.innerWidth - padding) {
            posX = Math.max(padding, window.innerWidth - menuWidth - padding);
          }
          if (posY + menuHeight > window.innerHeight - padding) {
            posY = Math.max(padding, window.innerHeight - menuHeight - padding);
          }

          this.contextMenu.style.left = `${posX}px`;
          this.contextMenu.style.top = `${posY}px`;
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
  
        async searchImageOnGoogle(path) {
          const directUrl = new URL('?action=raw&f=' + encodeURIComponent(path), window.location.href).href;
          const hostname = window.location.hostname.toLowerCase();
          const isLocal = hostname === 'localhost' ||
                          hostname === '127.0.0.1' ||
                          hostname === '0.0.0.0' ||
                          hostname.endsWith('.local') ||
                          hostname.endsWith('.test') ||
                          /^192\.168\./.test(hostname) ||
                          /^10\./.test(hostname) ||
                          /^172\.(1[6-9]|2[0-9]|3[0-1])\./.test(hostname);

          if (!isLocal) {
            this.toast('Opening Google Lens...');
            window.open(`https://lens.google.com/uploadbyurl?url=${encodeURIComponent(directUrl)}`, '_blank');
            return;
          }

          this.toast('Copying image for Google Search...');
          try {
            const rawUrl = '?action=raw&f=' + encodeURIComponent(path);
            const img = new Image();
            img.crossOrigin = 'anonymous';

            await new Promise((resolve, reject) => {
              img.onload = resolve;
              img.onerror = reject;
              img.src = rawUrl;
            });

            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            const pngBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            if (pngBlob && navigator.clipboard && window.ClipboardItem) {
              await navigator.clipboard.write([
                new ClipboardItem({ 'image/png': pngBlob })
              ]);
              this.toast('Image copied! Press Ctrl+V (Paste) on Google to search.');
            }
          } catch (err) {
            this.toast('Opening Google Lens...');
          }

          window.open('https://images.google.com/', '_blank');
        }

        previewArchive(path, name) {
          document.getElementById('archive-preview-title').innerText = name || 'Archive Contents';
          const body = document.getElementById('archive-preview-body');
          const stats = document.getElementById('archive-preview-stats');
          stats.innerText = 'Scanning archive...';
          body.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg></div>';
          this.showModal('modal-archive-preview');

          const btnExtractHere = document.getElementById('archive-extract-btn');
          if (btnExtractHere) {
            btnExtractHere.onclick = () => {
              this.unzipItem(path, false);
              this.closeModals();
            };
          }

          const btnExtractFolder = document.getElementById('archive-extract-folder-btn');
          if (btnExtractFolder) {
            btnExtractFolder.onclick = () => {
              this.unzipItem(path, true);
              this.closeModals();
            };
          }

          fetch(`?action=archive_preview&f=${encodeURIComponent(path)}`)
            .then(r => r.json())
            .then(res => {
              if (!res.success || !res.entries) throw new Error(res.error || 'Failed to inspect archive');
              stats.innerText = `${res.total_elements} item(s) • Total unpacked: ${res.total_size}`;

              if (!res.entries.length) {
                body.innerHTML = '<div class="center-state"><p>Archive is empty or unsupported format</p></div>';
                return;
              }

              let html = `
                <table class="archive-table">
                  <thead>
                    <tr>
                      <th>File Name / Path</th>
                      <th>Size</th>
                      <th>Compressed</th>
                      <th>Modified Date</th>
                    </tr>
                  </thead>
                  <tbody>
              `;

              res.entries.forEach(e => {
                const icon = e.is_dir
                  ? '<svg viewBox="0 0 16 16" style="width:16px;height:16px;color:#f59e0b;vertical-align:middle;margin-right:6px;"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14z"/></svg>'
                  : '<svg viewBox="0 0 24 24" style="width:16px;height:16px;color:var(--md-sys-color-primary);vertical-align:middle;margin-right:6px;"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>';

                html += `
                  <tr>
                    <td style="font-weight:500; font-family:'JetBrains Mono', monospace; font-size:0.78rem;">${icon}${this.escapeHtml(e.name)}</td>
                    <td>${e.size_fmt || '-'}</td>
                    <td>${e.comp_size || '-'}</td>
                    <td style="color:var(--md-sys-color-outline); font-size:0.75rem;">${e.mtime || '-'}</td>
                  </tr>
                `;
              });

              html += '</tbody></table>';
              body.innerHTML = html;
            })
            .catch(err => {
              body.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${err.message}</p></div>`;
            });
        }

        unzipItem(path, toFolder = false) {
          const scrollEl = document.getElementById('main-content');
          this.savedScrollTop = scrollEl ? scrollEl.scrollTop : 0;
          const baseName = path.split('/').pop().replace(/\.[^/.]+$/, '');
          const label = toFolder ? `Extracting into "${baseName}/"` : 'Extracting archive';
          this.runServerTaskWithProgress(label, 'unzip', { f: path, to_folder: toFolder ? '1' : '0' }, () => {
            this.toast(toFolder ? `Extracted into "${baseName}/"` : 'Archive extracted successfully');
            this.refresh(true);
          });
        }
  
        showDetails(path) {
          const targetPath = (path !== undefined && path !== null) ? path : (this.currentPath || '');
          const folderName = targetPath ? targetPath.split('/').pop() : 'Root Directory';

          document.getElementById('details-modal-title').innerText = folderName;
          document.getElementById('details-content').innerHTML = `
            <div class="center-state" style="min-height:180px;">
              <svg class="m3-spinner" style="margin:0;" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
              <div style="font-size:0.85rem; font-weight:500; margin-top:0.8rem;">Calculating folder size and contents...</div>
            </div>
          `;
          this.showModal('modal-details');

          fetch(`?action=details&f=${encodeURIComponent(targetPath)}`)
            .then(r => {
              if (!r.ok) throw new Error(`HTTP Error ${r.status}`);
              return r.json();
            })
            .then(res => {
              if (res.error) throw new Error(res.error);
              this.renderDetailsModal(res);
            })
            .catch(err => {
              document.getElementById('details-content').innerHTML = `
                <div class="center-state" style="color:var(--md-sys-color-error); min-height:160px;">
                  <p>${err.message || 'Failed to calculate folder statistics'}</p>
                </div>
              `;
            });
        }
  
        renderDetailsModal(res) {
          document.getElementById('details-modal-title').innerText = res.title || 'Information';
          let html = '';

          if (res.media && res.media.cover_art) {
            html += `
              <div style="display:flex; justify-content:center; align-items:center; margin-bottom:1.2rem;">
                <img src="${res.media.cover_art}" alt="Album Art" style="width:160px; height:160px; border-radius:16px; object-fit:cover; box-shadow:var(--md-elevation-2); border:1px solid var(--md-sys-color-outline-variant);">
              </div>
            `;
          }

          if (res.media && res.media.tags && Object.keys(res.media.tags).length) {
            const hasVideo = !!(res.media.tags['Resolution'] || res.media.tags['Video Codec'] || res.media.tags['Frame Rate']);
            const mediaIcon = hasVideo
              ? '<svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>'
              : '<svg viewBox="0 0 24 24"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>';

            html += `
              <div class="details-section">
                <div class="details-title">
                  ${mediaIcon}
                  ${hasVideo ? 'Video & Audio Stream Info' : 'Audio & Track Metadata'}
                </div>
                <div class="details-grid">
            `;
            for (let k in res.media.tags) {
              html += `<div class="details-row"><span class="details-label">${this.escapeHtml(k)}</span><span class="details-value">${this.escapeHtml(String(res.media.tags[k]))}</span></div>`;
            }
            html += `</div></div>`;
          }

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
                  Camera EXIF & GPS Location
                </div>
                <div class="details-grid">
            `;
            let osmEmbedUrl = res.exif['OSM_Embed'] || null;
            for (let k in res.exif) {
              if (k === 'OSM_Embed') continue;
              let val = res.exif[k];
              if (k === 'OpenStreetMap') {
                val = `<a href="${res.exif[k]}" target="_blank" style="color:var(--md-sys-color-primary); font-weight:600; text-decoration:underline;">View on OpenStreetMap</a>`;
              } else if (k === 'Maps') {
                val = `<a href="${res.exif[k]}" target="_blank" style="color:var(--md-sys-color-primary); font-weight:600; text-decoration:underline;">Google Maps</a>`;
              }
              html += `<div class="details-row"><span class="details-label">${k}</span><span class="details-value">${val}</span></div>`;
            }
            if (osmEmbedUrl) {
              html += `
                <div style="padding-top:0.6rem;">
                  <iframe class="osm-map-frame" src="${osmEmbedUrl}" loading="lazy"></iframe>
                </div>
              `;
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
  
        refresh(preserveScroll = true) {
          const scrollEl = document.getElementById('main-content');
          this.savedScrollTop = preserveScroll && scrollEl ? scrollEl.scrollTop : 0;
          if (this.isSearching && this.searchQuery) {
            this.performSearch(this.searchQuery);
          } else if (this.currentSection === 'starred') {
            this.loadStarred();
          } else if (this.currentSection === 'recents') {
            this.loadRecents();
          } else if (this.currentSection === 'activity') {
            this.loadActivity();
          } else if (this.currentSection === 'trash') {
            this.loadTrash();
          } else if (this.currentSection === 'gallery') {
            this.loadGallery();
          } else {
            this.loadDir(this.currentPath);
          }
          this.loadTree();
        }
  
        showInputModal(title, label, defaultValue, callback) {
        document.getElementById('modal-input-title').innerText = title;
        document.getElementById('modal-input-label').innerText = label;
        const input = document.getElementById('modal-input-val');
        input.value = defaultValue || '';
        this.showModal('modal-input');

        setTimeout(() => {
          input.focus();
          input.select();
        }, 50);

        const confirmBtn = document.getElementById('modal-input-confirm');
        const handleConfirm = () => {
          const val = input.value.trim();
          this.closeModals();
          if (callback) callback(val);
        };

        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
        newBtn.addEventListener('click', handleConfirm);

        input.onkeydown = (e) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            handleConfirm();
          }
        };
      }
  
        showModal(id) {
          document.getElementById('modal-backdrop').classList.add('active');
          const currentVisible = Array.from(document.querySelectorAll('.modal-box')).find(m => m.style.display === 'flex' && m.id !== id);

          // Preserve editor/version history state on stack when nesting dialogs
          if (currentVisible && (id === 'modal-versions' || id === 'modal-diff' || (currentVisible.id === 'modal-versions' && id === 'modal-diff'))) {
            if (!this.modalStack.includes(currentVisible.id)) {
              this.modalStack.push(currentVisible.id);
            }
          } else if (!this.modalStack.includes(currentVisible?.id)) {
            this.modalStack = [];
          }

          document.querySelectorAll('.modal-box').forEach(m => m.style.display = 'none');
          document.getElementById(id).style.display = 'flex';
        }
  
        closeModals() {
          this.applyTheme(this.theme);

          // Reset and cleanup Image Editor state & memory cache on close
          if (typeof window.resetImageEditorSession === 'function') {
            window.resetImageEditorSession();
          }

          // If Media Lightbox is currently open behind the details modal, keep lightbox open without navigating away!
          const isLightboxActive = document.getElementById('lightbox')?.classList.contains('active');
          if (isLightboxActive) {
            this.modalStack = [];
            const mb = document.getElementById('modal-backdrop');
            if (mb) mb.classList.remove('active');
            document.querySelectorAll('.modal-box').forEach(m => m.style.display = 'none');
            return;
          }

          if (this.modalStack && this.modalStack.length > 0) {
            const prevModalId = this.modalStack.pop();
            document.querySelectorAll('.modal-box').forEach(m => m.style.display = 'none');
            const prevEl = document.getElementById(prevModalId);
            if (prevEl) {
              prevEl.style.display = 'flex';
              if (prevModalId === 'modal-editor' && window.hdmEngine && window.hdmEngine.editor) {
                setTimeout(() => window.hdmEngine.editor.refresh(), 50);
              }
              return;
            }
          }

          this.modalStack = [];
          const mb = document.getElementById('modal-backdrop');
          if (mb) mb.classList.remove('active');
          document.querySelectorAll('.modal-box').forEach(m => m.style.display = 'none');
          this.container.style.opacity = '1';

          const docContainer = document.getElementById('doc-viewer-container');
          if (docContainer) docContainer.innerHTML = '';

          const prevPane = document.getElementById('hdm-preview-pane');
          if (prevPane) prevPane.innerHTML = '';

          const activeTarget = this.activeModalPath || (window.hdmEngine ? window.hdmEngine.activePath : '');
          this.activeModalPath = '';
          if (window.hdmEngine) window.hdmEngine.activePath = '';

          // If search was active before or during modal view, keep it active and do not wipe search
          if (this.isSearching && (this.searchQuery || this.hasActiveAdvFilters())) {
            this.performSearch(this.searchQuery);
            return;
          }

          // If opened from a dedicated section (recents, starred, activity, trash, gallery, videos, audio), return to it
          const specialSections = ['recents', 'starred', 'activity', 'trash', 'gallery', 'videos', 'audio', 'documents'];
          const returnSection = (this.originSection && specialSections.includes(this.originSection))
            ? this.originSection
            : (this.currentSection && specialSections.includes(this.currentSection) ? this.currentSection : null);

          if (returnSection) {
            this.originSection = null;
            this.currentSection = returnSection;
            const targetHash = `#/@${returnSection}`;
            if (window.location.hash !== targetHash) {
              window.location.hash = targetHash;
            } else {
              this.switchDriveSection(returnSection, false);
            }
            return;
          }

          let parentDir = '';
          if (activeTarget && activeTarget.includes('/')) {
            const parts = activeTarget.split('/');
            parts.pop();
            parentDir = parts.join('/');
          } else if (this.currentPath !== null && this.currentPath !== undefined) {
            parentDir = this.currentPath;
          }

          const scrollEl = document.getElementById('main-content');
          if (scrollEl && !this.savedScrollTop) {
            this.savedScrollTop = scrollEl.scrollTop;
          }

          const currentHashClean = window.location.hash.replace(/^#\/?/, '').replace(/^\/+|\/+$/g, '');
          const targetClean = parentDir.replace(/^\/+|\/+$/g, '');

          if (currentHashClean !== targetClean && activeTarget) {
            const targetHash = parentDir ? '#/' + ltrim(parentDir, '/') : '#/';
            window.location.hash = targetHash;
          } else {
            this.updateDocTitle(parentDir ? parentDir.split('/').pop() : '', (this.data.folders?.length || 0) + (this.data.files?.length || 0));
          }
        }
  
        showLoginModal() {
          const passInput = document.getElementById('admin-login-pass');
          if (passInput) passInput.value = '';
          this.showModal('modal-login');
          if (passInput) passInput.focus();
        }

        initAuthEvents() {
          const form = document.getElementById('admin-login-form');
          if (form) {
            form.addEventListener('submit', async (e) => {
              e.preventDefault();
              const pass = document.getElementById('admin-login-pass').value;
              const fd = new FormData();
              fd.append('action', 'login');
              fd.append('password', pass);
              try {
                const res = await fetch('', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                  this.toast('Logged in as Admin');
                  window.location.reload();
                } else {
                  this.toast(data.error || 'Invalid password');
                }
              } catch (err) {
                this.toast('Login request failed');
              }
            });
          }
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
  
      class HDMarkDownEngine {
        constructor() {
          this.editor = null;
          this.activePath = '';
          this.activeType = 'markdown';
          this.mode = 'split'; // 'edit', 'split', 'preview'
          this.slides = [];
          this.slideIdx = 0;
          this.isWrap = true;
          this.renderTimer = null;
          this.initCodeMirror();
          this.bindEvents();
        }

        initCodeMirror() {
          const target = document.getElementById('hdm-raw-textarea');
          this.editor = CodeMirror.fromTextArea(target, {
            lineNumbers: true,
            theme: 'nord',
            mode: 'markdown',
            lineWrapping: true,
            viewportMargin: 30,
            extraKeys: {
              "Ctrl-S": () => document.getElementById('editor-save-btn').click(),
              "Cmd-S": () => document.getElementById('editor-save-btn').click(),
              "Ctrl-B": () => this.insertSyntax('bold'),
              "Cmd-B": () => this.insertSyntax('bold'),
              "Ctrl-I": () => this.insertSyntax('italic'),
              "Cmd-I": () => this.insertSyntax('italic'),
              "Ctrl-F": () => this.toggleFind(),
              "Cmd-F": () => this.toggleFind()
            }
          });

          this.editor.on('change', () => {
            this.updateMetrics();
            if ((this.activeType === 'markdown' || this.activeType === 'html') && this.mode !== 'edit') {
              clearTimeout(this.renderTimer);
              this.renderTimer = setTimeout(() => this.renderPreview(), 250);
            }
          });

          this.initSyncScroll();
        }

        initSyncScroll() {
          const prevPane = document.getElementById('hdm-preview-pane');
          let isSyncingEditor = false;
          let isSyncingPreview = false;

          this.editor.on('scroll', () => {
            if (this.mode !== 'split' || isSyncingEditor) return;
            isSyncingPreview = true;
            const info = this.editor.getScrollInfo();
            const maxEditor = info.height - info.clientHeight;
            if (maxEditor > 0 && prevPane) {
              const pct = info.top / maxEditor;
              const maxPrev = prevPane.scrollHeight - prevPane.clientHeight;
              prevPane.scrollTop = pct * maxPrev;
            }
            setTimeout(() => { isSyncingPreview = false; }, 50);
          });

          if (prevPane) {
            prevPane.addEventListener('scroll', () => {
              if (this.mode !== 'split' || isSyncingPreview) return;
              isSyncingEditor = true;
              const maxPrev = prevPane.scrollHeight - prevPane.clientHeight;
              if (maxPrev > 0) {
                const pct = prevPane.scrollTop / maxPrev;
                const info = this.editor.getScrollInfo();
                const maxEditor = info.height - info.clientHeight;
                this.editor.scrollTo(null, pct * maxEditor);
              }
              setTimeout(() => { isSyncingEditor = false; }, 50);
            }, { passive: true });
          }
        }

        bindEvents() {
          // Three-dots menu toggle
          const btnMore = document.getElementById('btn-editor-more');
          const menuMore = document.getElementById('dropdown-editor-more');
          if (btnMore && menuMore) {
            btnMore.onclick = (e) => {
              e.stopPropagation();
              const rect = btnMore.getBoundingClientRect();
              menuMore.style.top = `${rect.bottom + 6}px`;
              menuMore.style.right = `${window.innerWidth - rect.right}px`;
              menuMore.classList.toggle('active');
            };
            window.addEventListener('click', () => menuMore.classList.remove('active'));
          }

          document.getElementById('dem-wrap')?.addEventListener('click', () => { this.toggleWrap(); menuMore?.classList.remove('active'); });
          document.getElementById('dem-versions')?.addEventListener('click', () => { app.openVersionHistory(this.activePath); menuMore?.classList.remove('active'); });
          document.getElementById('dem-mode-edit')?.addEventListener('click', () => { this.setMode('edit'); menuMore?.classList.remove('active'); });
          document.getElementById('dem-mode-split')?.addEventListener('click', () => { this.setMode('split'); menuMore?.classList.remove('active'); });
          document.getElementById('dem-mode-preview')?.addEventListener('click', () => { this.setMode('preview'); menuMore?.classList.remove('active'); });
          document.getElementById('dem-present')?.addEventListener('click', () => { this.openPresentation(); menuMore?.classList.remove('active'); });

          const findBtn = document.getElementById('hdm-btn-find-header');
          if (findBtn) findBtn.onclick = () => this.toggleFind();

          const undoBtn = document.getElementById('hdm-btn-undo');
          if (undoBtn) undoBtn.onclick = () => {
            if (this.editor) { this.editor.undo(); this.editor.focus(); }
          };

          const redoBtn = document.getElementById('hdm-btn-redo');
          if (redoBtn) redoBtn.onclick = () => {
            if (this.editor) { this.editor.redo(); this.editor.focus(); }
          };

          // Find Card Controls
          document.getElementById('hdm-btn-find-prev')?.addEventListener('click', () => this.findPrev());
          document.getElementById('hdm-btn-find-next')?.addEventListener('click', () => this.findNext());
          document.getElementById('hdm-btn-find-close')?.addEventListener('click', () => this.toggleFind(false));
          document.getElementById('hdm-btn-replace-one')?.addEventListener('click', () => this.replaceOne());
          document.getElementById('hdm-btn-replace-all')?.addEventListener('click', () => this.replaceAll());

          const findInput = document.getElementById('hdm-find-input');
          if (findInput) {
            findInput.addEventListener('input', () => this.updateFindMatches());
            findInput.addEventListener('keydown', (e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                e.shiftKey ? this.findPrev() : this.findNext();
              } else if (e.key === 'Escape') {
                this.toggleFind(false);
              }
            });
          }

          const replaceInput = document.getElementById('hdm-replace-input');
          if (replaceInput) {
            replaceInput.addEventListener('keydown', (e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                this.replaceOne();
              }
            });
          }

          // Resizer
          const resizer = document.getElementById('hdm-resizer');
          const editPane = document.getElementById('hdm-editor-pane');
          let isResize = false;
          resizer.onmousedown = () => { isResize = true; };
          window.addEventListener('mousemove', (e) => {
            if (!isResize) return;
            const container = document.getElementById('hdm-panes').getBoundingClientRect();
            const pct = ((e.clientX - container.left) / container.width) * 100;
            if (pct > 15 && pct < 85) editPane.style.width = `${pct}%`;
          });
          window.addEventListener('mouseup', () => { isResize = false; });
        }

        setMode(mode) {
          this.mode = mode;
          const editPane = document.getElementById('hdm-editor-pane');
          const prevPane = document.getElementById('hdm-preview-pane');
          const resizer = document.getElementById('hdm-resizer');

          ['edit', 'split', 'preview'].forEach(m => document.getElementById(`hdm-btn-${m}`)?.classList.remove('active'));
          document.getElementById(`hdm-btn-${mode}`)?.classList.add('active');

          if (mode === 'edit') {
            editPane.style.display = 'flex';
            editPane.style.width = '100%';
            editPane.style.flex = '1';
            prevPane.style.display = 'none';
            if (resizer) resizer.style.display = 'none';
          } else if (mode === 'preview') {
            editPane.style.display = 'none';
            prevPane.style.display = 'block';
            prevPane.style.width = '100%';
            prevPane.style.flex = '1';
            if (resizer) resizer.style.display = 'none';
          } else {
            editPane.style.display = 'flex';
            editPane.style.width = '50%';
            editPane.style.flex = 'none';
            prevPane.style.display = 'block';
            prevPane.style.width = 'auto';
            prevPane.style.flex = '1';
            if (resizer) resizer.style.display = 'block';
          }
          setTimeout(() => {
            if (this.editor) this.editor.refresh();
          }, 60);
        }

        open(path, name, content) {
          this.activePath = path;
          const ext = name.split('.').pop().toLowerCase();
          const titleEl = document.getElementById('editor-title');
          if (titleEl) titleEl.innerText = name;
          if (this.editor) this.editor.setValue(content || '');

          const isLarge = (content && content.length > 250000);
          const toolbar = document.getElementById('hdm-toolbar');
          const viewModes = document.getElementById('hdm-view-modes');
          const presentBtn = document.getElementById('hdm-btn-present');

          if (ext === 'md' || ext === 'markdown' || ext === 'html' || ext === 'htm') {
            this.activeType = ext.startsWith('m') ? 'markdown' : 'html';
            if (this.editor) this.editor.setOption('mode', this.activeType === 'markdown' ? 'markdown' : 'htmlmixed');
            if (toolbar) toolbar.style.display = 'flex';
            if (viewModes) viewModes.style.display = 'flex';
            if (presentBtn) presentBtn.style.display = (window.innerWidth <= 768 || isLarge) ? 'none' : 'block';

            // Auto-switch large files or mobile screens to edit-only mode for maximum speed
            this.setMode(window.innerWidth <= 768 || isLarge ? 'edit' : 'split');
            if (!isLarge && this.mode !== 'edit') {
              this.renderPreview();
            }
          } else {
            this.activeType = 'code';
            if (toolbar) toolbar.style.display = 'none';
            if (viewModes) viewModes.style.display = 'none';
            if (presentBtn) presentBtn.style.display = 'none';
            this.setMode('edit');
            let cmMode = 'javascript';
            if (['php'].includes(ext)) cmMode = 'php';
            if (['py'].includes(ext)) cmMode = 'python';
            if (['sql'].includes(ext)) cmMode = 'sql';
            if (['xml', 'svg'].includes(ext)) cmMode = 'xml';
            if (['c', 'cpp'].includes(ext)) cmMode = 'clike';
            if (this.editor) this.editor.setOption('mode', cmMode);
          }

          if (window.app) app.showModal('modal-editor');
          setTimeout(() => {
            if (this.editor) this.editor.refresh();
          }, 100);
          this.updateMetrics();
        }

        async renderPreview() {
          const prevPane = document.getElementById('hdm-preview-pane');
          if (!prevPane || this.mode === 'edit') return;
          const raw = this.editor.getValue();

          const sanitizeConfig = {
            ADD_TAGS: ['iframe', 'video', 'source', 'details', 'summary', 'mark', 'u', 'div', 'span'],
            ADD_ATTR: ['allow', 'allowfullscreen', 'frameborder', 'src', 'controls', 'type', 'width', 'height', 'align', 'open'],
            FORBID_TAGS: ['style', 'link', 'script', 'base']
          };

          if (this.activeType === 'markdown') {
            const rawHtml = marked.parse(raw);
            const clean = DOMPurify.sanitize(rawHtml, sanitizeConfig);
            prevPane.innerHTML = clean;

            // Resolve relative image paths to local server raw files
            const docDir = this.activePath.includes('/') ? this.activePath.split('/').slice(0, -1).join('/') : '';
            prevPane.querySelectorAll('img').forEach(img => {
              const src = img.getAttribute('src');
              if (src && !src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('data:') && !src.startsWith('?action=')) {
                const cleanRel = src.replace(/^\.\//, '');
                const fullRel = docDir ? `${docDir}/${cleanRel}` : cleanRel;
                img.src = `?action=raw&f=${encodeURIComponent(fullRel)}`;
              }
            });

            // Highlight syntax
            if (raw.length < 350000) {
              prevPane.querySelectorAll('pre code').forEach(el => {
                if (!el.classList.contains('language-mermaid')) hljs.highlightElement(el);
              });
            }

            // Render Mermaid Diagrams
            if (window.mermaid && raw.length < 200000) {
              const blocks = prevPane.querySelectorAll('code.language-mermaid');
              for (let i = 0; i < blocks.length; i++) {
                const b = blocks[i];
                const code = b.textContent;
                const id = `mermaid_div_${Date.now()}_${i}`;
                try {
                  const { svg } = await window.mermaid.render(id, code);
                  const container = document.createElement('div');
                  container.className = 'mermaid-container';
                  container.innerHTML = svg;
                  b.parentElement.replaceWith(container);
                  this.attachMermaidPanZoom(container);
                } catch(e) {}
              }
            }
          } else if (this.activeType === 'html') {
            prevPane.innerHTML = DOMPurify.sanitize(raw, sanitizeConfig);
          }
        }

        attachMermaidPanZoom(container) {
          const svg = container.querySelector('svg');
          if (!svg) return;

          let vb = svg.getAttribute('viewBox');
          let [x, y, w, h] = vb ? vb.split(' ').map(Number) : [0, 0, container.clientWidth || 800, 400];
          svg.style.maxWidth = 'none';

          const update = () => svg.setAttribute('viewBox', `${x} ${y} ${w} ${h}`);

          let isDrag = false, startX, startY;
          svg.onpointerdown = (e) => {
            isDrag = true;
            startX = e.clientX; startY = e.clientY;
            svg.setPointerCapture(e.pointerId);
          };
          svg.onpointermove = (e) => {
            if (!isDrag) return;
            const dx = (startX - e.clientX) * (w / container.clientWidth);
            const dy = (startY - e.clientY) * (h / container.clientHeight);
            x += dx; y += dy;
            startX = e.clientX; startY = e.clientY;
            update();
          };
          svg.onpointerup = (e) => { isDrag = false; svg.releasePointerCapture(e.pointerId); };
          container.onwheel = (e) => {
            e.preventDefault();
            const factor = e.deltaY > 0 ? 1.1 : 0.9;
            w *= factor; h *= factor;
            update();
          };
        }

        updateMetrics() {
          const val = this.editor.getValue();
          const words = val.trim() ? val.trim().split(/\s+/).length : 0;
          const el = document.getElementById('editor-metrics');
          if (el) {
            el.innerText = `${val.length.toLocaleString()} chars • ${words.toLocaleString()} words`;
          }
        }

        insertSyntax(type) {
          const doc = this.editor.getDoc();
          const sel = doc.getSelection();
          let before = '', after = '', ph = '';

          switch (type) {
            case 'bold': before = '**'; after = '**'; ph = 'bold text'; break;
            case 'italic': before = '*'; after = '*'; ph = 'italic text'; break;
            case 'underline': before = '<u>'; after = '</u>'; ph = 'underlined text'; break;
            case 'strikethrough': before = '~~'; after = '~~'; ph = 'strikethrough text'; break;
            case 'mark': before = '<mark>'; after = '</mark>'; ph = 'highlighted text'; break;
            case 'h1': before = '# '; ph = 'Heading 1'; break;
            case 'h2': before = '## '; ph = 'Heading 2'; break;
            case 'h3': before = '### '; ph = 'Heading 3'; break;
            case 'align-left': before = '<div align="left">\n\n'; after = '\n\n</div>'; ph = 'Left aligned text'; break;
            case 'align-center': before = '<div align="center">\n\n'; after = '\n\n</div>'; ph = 'Centered text or image'; break;
            case 'align-right': before = '<div align="right">\n\n'; after = '\n\n</div>'; ph = 'Right aligned text'; break;
            case 'quote': before = '> '; ph = 'Blockquote'; break;
            case 'ul': before = '- '; ph = 'List item'; break;
            case 'ol': before = '1. '; ph = 'Numbered item'; break;
            case 'task': before = '- [ ] '; ph = 'Task to do'; break;
            case 'codeblock': before = '```javascript\n'; after = '\n```'; ph = '// your code here'; break;
            case 'link': before = '['; after = '](https://example.com)'; ph = 'Link text'; break;
            case 'image': before = '!['; after = '](./image.png)'; ph = 'Image Alt Text'; break;
            case 'table':
              before = '\n| Header 1 | Header 2 | Header 3 |\n| :--- | :---: | ---: |\n| Left | Center | Right |\n| Row 2 | Row 2 | Row 2 |\n';
              break;
            case 'hr': before = '\n\n---\n\n'; break;
            case 'details': before = '<details>\n<summary>Click to view</summary>\n\n'; after = '\n\n</details>'; ph = 'Hidden content here'; break;
            case 'mermaid': before = '```mermaid\ngraph TD;\n  A[Start]-->B[Process];\n  B-->C[End];\n'; after = '```'; break;
            case 'youtube': before = '![youtube]('; after = ')'; ph = 'dQw4w9WgXcQ'; break;
          }

          doc.replaceSelection(before + (sel || ph) + after);
          this.editor.focus();
        }

        toggleFind(forceState) {
          const card = document.getElementById('hdm-find-bar');
          if (!card) return;
          const show = forceState !== undefined ? forceState : (card.style.display === 'none');
          card.style.display = show ? 'flex' : 'none';
          if (show) {
            const input = document.getElementById('hdm-find-input');
            const sel = this.editor?.getSelection();
            if (sel && input) input.value = sel;
            input?.focus();
            input?.select();
            this.updateFindMatches();
          } else if (this.editor) {
            this.editor.focus();
          }
        }

        toggleWrap() {
          this.isWrap = !this.isWrap;
          this.editor.setOption('lineWrapping', this.isWrap);
          const wrapText = document.getElementById('dem-wrap-text');
          if (wrapText) {
            wrapText.innerText = `Word Wrap: ${this.isWrap ? 'On' : 'Off'}`;
          }
          if (window.app) window.app.toast(`Word Wrap: ${this.isWrap ? 'Enabled' : 'Disabled'}`);
        }

        updateFindMatches() {
          const q = document.getElementById('hdm-find-input')?.value;
          const counter = document.getElementById('hdm-find-count');
          if (!q || !this.editor) {
            if (counter) counter.innerText = '0/0';
            return;
          }

          let count = 0;
          let current = 0;
          const curPos = this.editor.getDoc().getCursor();
          let cursor = this.editor.getSearchCursor(q, { line: 0, ch: 0 }, { caseFold: true });

          while (cursor.findNext()) {
            count++;
            const from = cursor.from();
            if (from.line < curPos.line || (from.line === curPos.line && from.ch <= curPos.ch)) {
              current = count;
            }
          }

          if (current === 0 && count > 0) current = 1;
          if (counter) counter.innerText = `${count > 0 ? current : 0}/${count}`;
        }

        findNext() {
          const q = document.getElementById('hdm-find-input')?.value;
          if (!q || !this.editor) return;
          let cursor = this.editor.getSearchCursor(q, this.editor.getCursor('to'), { caseFold: true });
          if (!cursor.findNext()) {
            cursor = this.editor.getSearchCursor(q, { line: 0, ch: 0 }, { caseFold: true });
            if (!cursor.findNext()) return;
          }
          this.editor.setSelection(cursor.from(), cursor.to());
          this.editor.scrollIntoView({ from: cursor.from(), to: cursor.to() }, 30);
          this.updateFindMatches();
        }

        findPrev() {
          const q = document.getElementById('hdm-find-input')?.value;
          if (!q || !this.editor) return;
          let cursor = this.editor.getSearchCursor(q, this.editor.getCursor('from'), { caseFold: true });
          if (!cursor.findPrevious()) {
            const lastLine = this.editor.lineCount() - 1;
            cursor = this.editor.getSearchCursor(q, { line: lastLine, ch: this.editor.getLine(lastLine).length }, { caseFold: true });
            if (!cursor.findPrevious()) return;
          }
          this.editor.setSelection(cursor.from(), cursor.to());
          this.editor.scrollIntoView({ from: cursor.from(), to: cursor.to() }, 30);
          this.updateFindMatches();
        }

        replaceOne() {
          const q = document.getElementById('hdm-find-input')?.value;
          const rep = document.getElementById('hdm-replace-input')?.value || '';
          if (!q || !this.editor) return;
          const sel = this.editor.getSelection();
          if (sel.toLowerCase() === q.toLowerCase()) {
            this.editor.replaceSelection(rep, 'around');
          }
          this.findNext();
        }

        replaceAll() {
          const q = document.getElementById('hdm-find-input')?.value;
          const rep = document.getElementById('hdm-replace-input')?.value || '';
          if (!q || !this.editor) return;
          let cursor = this.editor.getSearchCursor(q, { line: 0, ch: 0 }, { caseFold: true });
          this.editor.operation(() => {
            while (cursor.findNext()) {
              cursor.replace(rep);
            }
          });
          this.updateFindMatches();
        }

        openPresentation() {
          const raw = this.editor.getValue();
          this.slides = raw.split(/^_{3,}\s*$|^\*{3,}\s*$|^-{3,}\s*$/gm).filter(s => s.trim());
          if (!this.slides.length) this.slides = [raw || '# Empty Slide'];
          this.slideIdx = 0;
          document.getElementById('presentation-overlay').classList.add('active');
          this.renderSlide();
        }

        renderSlide() {
          const text = this.slides[this.slideIdx];
          const box = document.getElementById('presentation-slide-box');
          box.innerHTML = DOMPurify.sanitize(marked.parse(text));
          document.getElementById('presentation-indicator').innerText = `${this.slideIdx + 1} / ${this.slides.length}`;
        }

        nextSlide() {
          if (this.slideIdx < this.slides.length - 1) { this.slideIdx++; this.renderSlide(); }
        }

        prevSlide() {
          if (this.slideIdx > 0) { this.slideIdx--; this.renderSlide(); }
        }

        closePresentation() {
          document.getElementById('presentation-overlay').classList.remove('active');
        }
      }

      window.hdmEngine = new HDMarkDownEngine();
      window.opfsCache = new OPFSCacheManager();
      window.uploadManager = new UploadManager();
      window.mangaViewer = new MangaViewer();
      window.lightbox = new LightboxViewer();
      window.app = new GalleryApp();
  
      document.querySelectorAll('.modal-close').forEach(b => b.addEventListener('click', () => window.app.closeModals()));

      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('?pwa=sw').catch(() => {});
        });
      }
    </script>
  </body>
</html>