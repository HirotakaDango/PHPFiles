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
    if (strpos($check, $realBase) === 0) return $check;
    return false;
  }
  if (strpos($realTarget, $realBase) !== 0) return $realBase;
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
  $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'fstat_' . md5($dirPath) . '.dat';
  $currentMtime = @filemtime($dirPath) ?: 0;

  if (file_exists($cacheFile)) {
    $fp = @fopen($cacheFile, 'rb');
    if ($fp) {
      $header = fread($fp, 128);
      fclose($fp);
      if ($header) {
        $lines = explode("\n", trim($header));
        if (count($lines) >= 4 && intval($lines[0]) >= $currentMtime) {
          return [
            'size'    => floatval($lines[1]),
            'files'   => intval($lines[2]),
            'folders' => intval($lines[3])
          ];
        }
      }
    }
  }

  $size = 0.0;
  $files = 0;
  $folders = 0;
  $ignoreDirs = ['.gallery_cache', '.drive_trash_bin', '.file_version', '.git'];

  $queue = [$dirPath];
  while (!empty($queue)) {
    $currentDir = array_shift($queue);
    $dh = @opendir($currentDir);
    if (!$dh) continue;

    while (($entry = @readdir($dh)) !== false) {
      if ($entry === '.' || $entry === '..' || in_array($entry, $ignoreDirs) || $entry[0] === '.') {
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

  $outFp = @fopen($cacheFile, 'wb');
  if ($outFp) {
    fprintf($outFp, "%d\n%.0f\n%d\n%d\n", $currentMtime, $size, $files, $folders);
    fclose($outFp);
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
          $tagMap = ['TIT2' => 'Track Title', 'TPE1' => 'Artist', 'TALB' => 'Album', 'TYER' => 'Year', 'TDRC' => 'Year', 'TCON' => 'Genre', 'TRCK' => 'Track #'];

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
            $tagMap = ["\xa9nam" => 'Track Title', "\xa9ART" => 'Artist', "\xa9alb" => 'Album', "\xa9day" => 'Year', "\xa9gen" => 'Genre'];
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
            $fMap = ['TITLE' => 'Track Title', 'ARTIST' => 'Artist', 'ALBUM' => 'Album', 'DATE' => 'Year', 'GENRE' => 'Genre'];
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
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
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
  header('Content-Disposition: inline');
  header('Cache-Control: public, max-age=86400');
  header('X-Content-Type-Options: nosniff');

  $fp = @fopen($path, 'rb');
  if (!$fp) exit;
  if ($start > 0) {
    fseek($fp, (int)$start, SEEK_SET);
  }

  while (!feof($fp) && $length > 0 && connection_status() === CONNECTION_NORMAL) {
    $read = (int)min(65536, $length);
    $buff = fread($fp, $read);
    if ($buff === false || $buff === '') break;
    echo $buff;
    $length -= strlen($buff);
    @flush();
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
    'upload_chunk', 'create', 'rename', 'delete', 'save_text',
    'trash', 'trash_restore', 'trash_delete', 'trash_empty', 'version_restore',
    'star_toggle', 'clipboard_paste', 'fetch_url', 'encrypt_file',
    'decrypt_file', 'zip', 'unzip'
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
        $subCount = count(array_diff(@scandir($itemPath) ?: [], ['.', '..', '.gallery_cache']));
        $folders[] = [
          'name'        => $item,
          'path'        => $itemRel,
          'mtime'       => $mtime,
          'items_count' => $subCount,
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
    $fullPath = safePath($config['root_dir'], $file);
    if (!$fullPath || !is_file($fullPath)) {
      header('HTTP/1.0 404 Not Found');
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

    if (!file_exists($cachePath)) {
      $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
      if (in_array($ext, ['mp3', 'm4a', 'flac', 'mp4', 'mov'])) {
        $mediaMeta = getMediaMetadata($fullPath);
        if (!empty($mediaMeta['raw_cover'])) {
          $tmpCover = tempnam(sys_get_temp_dir(), 'cov_');
          @file_put_contents($tmpCover, $mediaMeta['raw_cover']);
          createThumbnail($tmpCover, $cachePath, $config['thumb_size'], $config['thumb_quality']);
          @unlink($tmpCover);
        }
      }
      if (!file_exists($cachePath)) {
        createThumbnail($fullPath, $cachePath, $config['thumb_size'], $config['thumb_quality']);
      }
    }

    if (file_exists($cachePath)) {
      header('Content-Type: image/jpeg');
      header('ETag: ' . $etag);
      header('Cache-Control: public, max-age=31536000, immutable');
      header('Content-Length: ' . filesize($cachePath));
      readfile($cachePath);
    } else {
      $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
      header('Content-Type: ' . $mime);
      header('ETag: ' . $etag);
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

      $uploadedRel = ltrim(str_replace(['\\', '//'], '/', substr(realpath($finalDest), strlen(realpath($config['root_dir'])))), '/');
      logDriveActivity($config['meta_file'], 'uploaded', $uploadedRel, 'Uploaded file');

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

    $renamed = @rename($item, $dest);
    if ($renamed) {
      logDriveActivity($config['meta_file'], 'renamed', $newName, 'Renamed from ' . basename($item));
    }
    jsonResponse(['success' => $renamed]);
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

      if (file_exists($dest) && $src !== $dest) {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $counter = 1;
        while (file_exists($dest)) {
          $dest = $targetDir . DIRECTORY_SEPARATOR . "{$baseName}_({$counter})" . ($ext ? ".{$ext}" : '');
          $counter++;
        }
      }

      if ($op === 'cut') {
        if (@rename($src, $dest)) $processed++;
      } else {
        if (is_dir($src)) {
          // Recursive copy
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
        } else {
          if (@copy($src, $dest)) $processed++;
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
    logDriveActivity($config['meta_file'], 'uploaded', $destPath, 'Created ZIP archive');
    jsonResponse(['success' => true, 'archive' => basename($destPath)]);
  }

  if ($action === 'unzip') {
    if (!$config['allow_zip']) jsonResponse(['error' => 'Extraction disabled'], 403);
    $file = findRealFile($config['root_dir'], $_POST['f'] ?? '');
    if (!$file || !is_file($file)) jsonResponse(['error' => 'Archive not found'], 404);

    $destDir = dirname($file);
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext === 'zip' && class_exists('ZipArchive')) {
      $zip = new ZipArchive();
      if ($zip->open($file) === true) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
          $entryName = $zip->getNameIndex($i);
          $cleanEntry = ltrim(str_replace(['\\', '..'], ['/', ''], $entryName), '/');
          if ($cleanEntry === '') continue;

          $isDir = substr($entryName, -1) === '/';
          $target = $destDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanEntry);

          if ($isDir) {
            if (!is_dir($target)) @mkdir($target, 0777, true);
          } else {
            $targetParent = dirname($target);
            if (!is_dir($targetParent)) @mkdir($targetParent, 0777, true);

            // Auto-increment duplicate name if file exists
            if (file_exists($target)) {
              $fExt = pathinfo($target, PATHINFO_EXTENSION);
              $fBase = pathinfo($target, PATHINFO_FILENAME);
              $cnt = 1;
              while (file_exists($target)) {
                $target = $targetParent . DIRECTORY_SEPARATOR . "{$fBase}_({$cnt})" . ($fExt ? ".{$fExt}" : '');
                $cnt++;
              }
            }

            $stream = $zip->getStream($entryName);
            if ($stream) {
              $out = @fopen($target, 'wb');
              if ($out) {
                while (!feof($stream)) {
                  fwrite($out, fread($stream, 65536));
                }
                fclose($out);
              }
              fclose($stream);
            }
          }
        }
        $zip->close();
        logDriveActivity($config['meta_file'], 'modified', $destDir, 'Extracted ZIP archive');
        jsonResponse(['success' => true]);
      }
    } elseif (in_array($ext, ['tar', 'gz', 'tgz']) && class_exists('PharData')) {
      try {
        $phar = new PharData($file);
        foreach (new RecursiveIteratorIterator($phar, RecursiveIteratorIterator::SELF_FIRST) as $item) {
          $subPath = ltrim(str_replace(['\\', '..'], ['/', ''], $item->getPathname()), '/');
          $rel = substr($subPath, strlen(realpath($file)));
          $cleanRel = ltrim(str_replace(['\\', '..'], ['/', ''], $rel), '/');
          if ($cleanRel === '') continue;

          $target = $destDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRel);

          if ($item->isDir()) {
            if (!is_dir($target)) @mkdir($target, 0777, true);
          } else {
            $targetParent = dirname($target);
            if (!is_dir($targetParent)) @mkdir($targetParent, 0777, true);

            if (file_exists($target)) {
              $fExt = pathinfo($target, PATHINFO_EXTENSION);
              $fBase = pathinfo($target, PATHINFO_FILENAME);
              $cnt = 1;
              while (file_exists($target)) {
                $target = $targetParent . DIRECTORY_SEPARATOR . "{$fBase}_({$cnt})" . ($fExt ? ".{$fExt}" : '');
                $cnt++;
              }
            }
            @copy($item->getPathname(), $target);
          }
        }
        logDriveActivity($config['meta_file'], 'modified', $destDir, 'Extracted TAR archive');
        jsonResponse(['success' => true]);
      } catch (Exception $e) {}
    } elseif ($ext === 'rar' && class_exists('RarArchive')) {
      $rar = @RarArchive::open($file);
      if ($rar) {
        $entries = @$rar->getEntries();
        if ($entries) {
          foreach ($entries as $entry) {
            $cleanEntry = ltrim(str_replace(['\\', '..'], ['/', ''], $entry->getName()), '/');
            if ($cleanEntry === '') continue;

            $target = $destDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanEntry);

            if ($entry->isDirectory()) {
              if (!is_dir($target)) @mkdir($target, 0777, true);
            } else {
              $targetParent = dirname($target);
              if (!is_dir($targetParent)) @mkdir($targetParent, 0777, true);

              if (file_exists($target)) {
                $fExt = pathinfo($target, PATHINFO_EXTENSION);
                $fBase = pathinfo($target, PATHINFO_FILENAME);
                $cnt = 1;
                while (file_exists($target)) {
                  $target = $targetParent . DIRECTORY_SEPARATOR . "{$fBase}_({$cnt})" . ($fExt ? ".{$fExt}" : '');
                  $cnt++;
                }
              }

              $stream = @$entry->getStream();
              if ($stream) {
                $out = @fopen($target, 'wb');
                if ($out) {
                  while (!feof($stream)) {
                    fwrite($out, fread($stream, 65536));
                  }
                  fclose($out);
                }
                fclose($stream);
              }
            }
          }
        }
        $rar->close();
        logDriveActivity($config['meta_file'], 'modified', $destDir, 'Extracted RAR archive');
        jsonResponse(['success' => true]);
      }
    }

    jsonResponse(['error' => 'Failed to extract archive format'], 500);
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
        flex: 1;
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
  
      .layout-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
        grid-auto-rows: min-content;
        align-content: start;
        gap: 0.75rem;
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
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
        background: rgba(8, 7, 12, 0.88);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        z-index: 1500;
        display: none;
        flex-direction: column;
        touch-action: pan-y;
      }
      .lightbox.active { display: flex; }
  
      .lightbox-header {
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.2rem;
        background: rgba(18, 16, 22, 0.65);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        color: #fff;
        position: absolute;
        top: 0; left: 0; right: 0;
        z-index: 1550;
        opacity: 0;
        transform: translateY(-100%);
        transition: all 0.3s cubic-bezier(0.2, 0, 0, 1);
      }
      .lightbox-header.active {
        opacity: 1;
        transform: translateY(0);
      }
      .lightbox-title {
        font-weight: 600;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 60vw;
        letter-spacing: 0.2px;
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
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        border-radius: 16px;
        opacity: 1;
        transform: translateZ(0);
        will-change: transform, opacity;
        background: transparent;
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
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        position: relative;
        z-index: 1515;
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
        width: 46px;
        height: 46px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-radius: 50%;
        z-index: 1520;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
        transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
      }
      .lightbox-nav:hover {
        background: rgba(255, 255, 255, 0.18);
        border-color: rgba(255, 255, 255, 0.25);
        transform: translateY(-50%) scale(1.08);
      }
      .lightbox-nav.prev { left: 1.2rem; }
      .lightbox-nav.next { right: 1.2rem; }
      @media (max-width: 520px) {
        .lightbox-nav.prev { left: 0.6rem; }
        .lightbox-nav.next { right: 0.6rem; }
      }
  
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
      .modal-box.large {
        max-width: 95vw;
        width: 95vw;
        height: 92dvh;
        border-radius: 20px;
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
          <div id="desk-cols-container" style="display:flex; align-items:center; gap:0.4rem; padding:0 0.6rem; background:var(--md-sys-color-surface-container-high); border-radius:20px; height:40px;">
            <span style="font-size:0.75rem; font-weight:600; color:var(--md-sys-color-on-surface-variant);">Cols:</span>
            <input type="range" id="slider-cols-desk" class="slider-input" min="0" max="8" value="0" style="width:70px;">
            <span id="slider-cols-desk-val" style="font-size:0.75rem; font-weight:600; min-width:28px;">Auto</span>
          </div>
          <button class="btn-icon" id="btn-clear-cache-desk" title="Clear Cache">
            <svg viewBox="0 0 24 24"><path d="M15 16h4v2h-4zm0-8h7v2h-7zm0 4h6v2h-6zM3 18c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V8H3v10zM14 5h-3l-1-1H6L5 5H2v2h12V5z"/></svg>
          </button>
          <button class="btn-icon" id="btn-manga-desk" title="Manga Mode">
            <svg viewBox="0 0 24 24"><path d="M19 1L14 6V22L19 17V1M3 6V22L8 17H12V2H8L3 6M10 4.25C10 3.56 9.44 3 8.75 3S7.5 3.56 7.5 4.25 8.06 5.5 8.75 5.5 10 4.94 10 4.25Z"/></svg>
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
        <div class="sidebar-section">
          <div class="sidebar-title">Drive Navigation</div>
          <div class="filter-group">
            <div class="filter-item active" id="nav-home" onclick="app.switchDriveSection('home')">
              <span style="display:flex;align-items:center;gap:0.5rem;"><svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> Home</span>
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
  
    <!-- Desktop Upload Options Dropdown -->
    <div class="dropdown-menu" id="dropdown-upload">
      <div class="dm-item" id="du-upload-files"><svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg> Upload Files</div>
      <div class="dm-item" id="du-upload-folder"><svg viewBox="0 0 24 24"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z"/></svg> Upload Folder</div>
      <div class="dm-item" id="du-upload-url"><svg viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg> Upload from URL</div>
    </div>

    <div class="dropdown-menu" id="dropdown-more">
      <div class="dm-item" id="dm-upload-files"><svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg> Upload Files</div>
      <div class="dm-item" id="dm-upload-folder"><svg viewBox="0 0 24 24"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z"/></svg> Upload Folder</div>
      <div class="dm-item" id="dm-upload-url"><svg viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg> Upload from URL</div>
      <div class="dm-item" id="dm-manga"><svg viewBox="0 0 24 24"><path d="M19 1L14 6V22L19 17V1M3 6V22L8 17H12V2H8L3 6M10 4.25C10 3.56 9.44 3 8.75 3S7.5 3.56 7.5 4.25 8.06 5.5 8.75 5.5 10 4.94 10 4.25Z"/></svg> Manga Mode</div>
      <div class="dm-item" id="dm-theme"><svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/></svg> Toggle Theme</div>
      <div class="dm-item desktop-only" id="dm-shortcuts"><svg viewBox="0 0 24 24"><path d="M20 5H4c-1.1 0-1.99.9-1.99 2L2 17c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm-9 3h2v2h-2V8zm0 3h2v2h-2v-2zM8 8h2v2H8V8zm0 3h2v2H8v-2zm-1 2H5v-2h2v2zm0-3H5V8h2v2zm9 7H8v-2h8v2zm0-4h-2v-2h2v2zm0-3h-2V8h2v2zm3 3h-2v-2h2v2zm0-3h-2V8h2v2z"/></svg> Keyboard Shortcuts</div>
      <div class="dm-item" id="dm-clear-cache"><svg viewBox="0 0 24 24"><path d="M15 16h4v2h-4zm0-8h7v2h-7zm0 4h6v2h-6zM3 18c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V8H3v10zM14 5h-3l-1-1H6L5 5H2v2h12V5z"/></svg> Clear Cache</div>
      <?php if ($config['auth_enabled']): ?>
        <?php if ($isAdmin): ?>
        <div class="dm-item danger" onclick="window.location.href='?action=logout'"><svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg> Logout (Admin)</div>
        <?php else: ?>
        <div class="dm-item" id="dm-login"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Login as Admin</div>
        <?php endif; ?>
      <?php endif; ?>
      <div class="dm-sep" id="mobile-cols-sep"></div>
      <div class="slider-container" id="mobile-cols-container">
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
      <div style="width:1px; height:20px; background:var(--md-sys-color-outline-variant); margin:0 0.15rem;"></div>
      <button class="btn-icon" id="btn-batch-info" title="Information">
        <svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>
      </button>
      <button class="btn-icon" id="btn-batch-download" title="Download ZIP">
        <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
      </button>
      <button class="btn-icon" id="btn-batch-compress" title="Compress to ZIP (Server)">
        <svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
      </button>
      <button class="btn-icon" id="btn-batch-delete" title="Delete Items">
        <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
      </button>
      <div style="width:1px; height:20px; background:var(--md-sys-color-outline-variant); margin:0 0.15rem;"></div>
      <button class="btn-icon" id="btn-batch-clear" title="Clear selection">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
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
        <div style="padding:0.6rem 1.4rem; background:var(--md-sys-color-surface-container-low); display:flex; justify-content:space-between; align-items:center; font-size:0.8rem;">
          <span id="archive-preview-stats" style="color:var(--md-sys-color-on-surface-variant);">0 files found</span>
          <button class="btn-primary" id="archive-extract-btn" style="height:32px; padding:0 0.8rem; gap:0.35rem;">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Extract Archive
          </button>
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
          this.images = [];

          // Always fetch a clean list for the specific directory to prevent mixing images from Recents or Search
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

        async openPath(path) {
          this.currentDirPath = path || '';
          this.images = [];

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
          this.images = [];
          this.currentDirPath = '';
          if (document.fullscreenElement) document.exitFullscreen().catch(() => {});
        }
      }
  
      class LightboxViewer {
        constructor() {
          this.el = document.getElementById('lightbox');
          this.title = document.getElementById('lb-title');
          this.body = document.getElementById('lb-body');
          this.header = document.querySelector('.lightbox-header');
          this.currentIndex = 0;
          this.mediaList = [];
          this.touchStartX = 0;
          this.touchStartY = 0;
          this.uiTimer = null;
          this.bindEvents();
        }

        showUI() {
          if (this.header) this.header.classList.add('active');
          clearTimeout(this.uiTimer);
          this.uiTimer = setTimeout(() => {
            if (this.header) this.header.classList.remove('active');
          }, 3000);
        }

        bindEvents() {
          this.el.addEventListener('mousemove', () => this.showUI());
          this.el.addEventListener('click', () => this.showUI());
          this.el.addEventListener('touchstart', () => this.showUI(), { passive: true });
          
          if (this.header) {
            this.header.addEventListener('mouseenter', () => clearTimeout(this.uiTimer));
            this.header.addEventListener('mouseleave', () => this.showUI());
          }

          document.getElementById('btn-lb-close').addEventListener('click', () => this.close());

          document.getElementById('btn-lb-download').addEventListener('click', () => {
            const item = this.mediaList[this.currentIndex];
            if (item) window.location.href = `?action=download&f=${encodeURIComponent(item.path)}`;
          });

          document.getElementById('btn-lb-details').addEventListener('click', () => {
            const item = this.mediaList[this.currentIndex];
            if (item && window.app) app.showDetails(item.path);
          });

          window.addEventListener('keydown', (e) => {
            if (!this.el.classList.contains('active')) return;
            if (e.key === 'Escape') this.close();
            if (e.key === 'ArrowLeft') this.nav(-1);
            if (e.key === 'ArrowRight') this.nav(1);
            if (e.key === ' ' && (e.target === document.body || e.target === this.el)) {
              const vid = this.body.querySelector('video, audio');
              if (vid) {
                e.preventDefault();
                if (vid.paused) vid.play(); else vid.pause();
              }
            }
          });

          this.body.addEventListener('touchstart', (e) => {
            // Prevent media seeking/scrubbing from triggering slide gestures
            if (e.target.closest('video, audio, .lightbox-audio-card, .lightbox-nav')) {
              this.touchStartX = null;
              return;
            }
            this.touchStartX = e.changedTouches[0].screenX;
            this.touchStartY = e.changedTouches[0].screenY;
          }, { passive: true });

          this.body.addEventListener('touchend', (e) => {
            if (this.touchStartX === null) return;
            const diffX = e.changedTouches[0].screenX - this.touchStartX;
            const diffY = e.changedTouches[0].screenY - this.touchStartY;
            if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY) * 1.3) {
              this.nav(diffX > 0 ? -1 : 1);
            }
          }, { passive: true });
        }

        open(mediaList, startIndex) {
          this.mediaList = mediaList || [];
          this.currentIndex = startIndex || 0;
          this.el.classList.add('active');
          this.showUI();
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

        loadCurrent() {
          const item = this.mediaList[this.currentIndex];
          if (!item) return;

          this.cleanupMedia();

          const targetRel = ltrim(item.path, '/');
          let currentDecoded = '';
          try { currentDecoded = decodeURIComponent(window.location.hash.replace(/^#\/?/, '')); } catch (e) { currentDecoded = window.location.hash.replace(/^#\/?/, ''); }

          if (currentDecoded !== targetRel) {
            window.location.hash = '#/' + encodeURI(targetRel);
          }

          const fileName = item.name || item.path.split('/').pop() || '';
          this.title.innerText = `${fileName} (${this.currentIndex + 1}/${this.mediaList.length})`;
          if (window.app) app.updateDocTitle(fileName);

          const rawUrl = `?action=raw&f=${encodeURIComponent(item.path)}`;
          const navPrev = `<div class="lightbox-nav prev" id="lb-prev"><svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg></div>`;
          const navNext = `<div class="lightbox-nav next" id="lb-next"><svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></div>`;

          const ext = (fileName.split('.').pop() || '').toLowerCase();
          const isAudio = item.type === 'audio' || ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma', 'm4r', 'mid', 'midi'].includes(ext);
          const isVideo = item.type === 'video' || ['mp4', 'webm', 'mov', 'm4v', 'ogv', 'mkv', 'avi', 'ts', '3gp', 'wmv', 'flv'].includes(ext);

          if (isVideo) {
            this.body.innerHTML = `
              <video class="lightbox-media" src="${rawUrl}" controls autoplay playsinline preload="auto" style="max-height:82dvh; max-width:95%; background:#000;">
                Your browser does not support HTML5 video.
              </video>
              ${navPrev}
              ${navNext}
            `;
            const vid = this.body.querySelector('video');
            if (vid) {
              vid.load();
              vid.play().catch(() => { vid.controls = true; });
            }
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
              ${navPrev}
              ${navNext}
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
            this.body.innerHTML = `
              <svg class="m3-spinner lb-spinner active" id="lb-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
              <img class="lightbox-media" id="lb-img" src="" alt="${fileName}" style="opacity:0;">
              ${navPrev}
              ${navNext}
            `;

            const img = document.getElementById('lb-img');
            const spinner = document.getElementById('lb-spinner');
            const preloader = new Image();
            preloader.onload = () => {
              if (!this.mediaList[this.currentIndex] || this.mediaList[this.currentIndex].path !== item.path) return;
              if (spinner) spinner.classList.remove('active');
              if (img) {
                img.src = rawUrl;
                img.style.opacity = '1';
              }
            };
            preloader.onerror = () => {
              if (spinner) spinner.classList.remove('active');
              if (img) {
                img.src = rawUrl;
                img.style.opacity = '1';
              }
            };
            preloader.src = rawUrl;
          }

          const btnPrev = document.getElementById('lb-prev');
          if (btnPrev) btnPrev.onclick = (e) => { e.stopPropagation(); this.nav(-1); };

          const btnNext = document.getElementById('lb-next');
          if (btnNext) btnNext.onclick = (e) => { e.stopPropagation(); this.nav(1); };
        }

        nav(dir) {
          if (!this.mediaList || this.mediaList.length <= 1) return;
          this.currentIndex = (this.currentIndex + dir + this.mediaList.length) % this.mediaList.length;
          this.loadCurrent();
        }

        close(updateHash = true) {
          this.cleanupMedia();
          this.el.classList.remove('active');
          this.body.innerHTML = '';

          if (updateHash && window.app) {
            const returnSection = app.originSection || (app.currentSection !== 'home' ? app.currentSection : null);
            if (returnSection) {
              app.originSection = null;
              const targetHash = `#/${returnSection}`;
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
          this.renderLimit = 25;
          this.filteredList = [];
          this.searchDebounceTimer = null;
          this.isSearching = false;
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
                this.filteredList.forEach(item => this.selectedItems.add(item.path));
                this.renderGallery(true);
                this.updateBatchBar();
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
            const targetDir = this.currentPath || '';
            const zipName = (targetDir.split('/').pop() || 'gallery') + '.zip';
            this.downloadZipWithProgress(`?action=download_zip&dir=${encodeURIComponent(targetDir)}`, null, zipName);
          });
  
          document.getElementById('btn-batch-clear').addEventListener('click', () => this.clearSelection());
          document.getElementById('btn-batch-download').addEventListener('click', () => this.batchDownload());
          document.getElementById('btn-batch-delete').addEventListener('click', () => this.batchDelete());
          document.getElementById('btn-batch-compress')?.addEventListener('click', () => this.batchCompress());
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
            const du = document.getElementById('dropdown-upload');
            if (du) du.classList.remove('active');
          });
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
          document.querySelectorAll('[data-layout]').forEach(b => b.classList.remove('active'));
          document.querySelector(`[data-layout="${layout}"]`)?.classList.add('active');
          this.container.className = `gallery-container layout-${layout}`;
          this.applyGridSizing();
          this.renderLimit = 25;
          this.renderGallery();
        }
  
        updateControlsVisibility() {
          const isStarred = this.currentSection === 'starred';
          const isTrash = this.currentSection === 'trash';
          const isActivity = this.currentSection === 'activity';
          const hideUpload = isStarred || isTrash || isActivity;
          const hideNewItems = isStarred || isTrash || isActivity;
    
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
          const showCols = !isTrash && !isActivity;
          const deskCols = document.getElementById('desk-cols-container');
          const mobCols = document.getElementById('mobile-cols-container');
          const mobSep = document.getElementById('mobile-cols-sep');
          if (deskCols) deskCols.style.display = showCols ? 'flex' : 'none';
          if (mobCols) mobCols.style.display = showCols ? 'flex' : 'none';
          if (mobSep) mobSep.style.display = showCols ? 'block' : 'none';
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

          // Do not re-render or overwrite views for activity or trash
          if (this.currentSection === 'activity' || this.currentSection === 'trash') {
            return;
          }

          if (this.layout === 'columns') {
            this.renderLimit = 25;
            this.renderGallery(true);
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

          const specialSections = ['recents', 'starred', 'activity', 'trash'];
          const lowerDecoded = decoded.toLowerCase();

          if (specialSections.includes(lowerDecoded)) {
            if (window.lightbox && lightbox.el && lightbox.el.classList.contains('active')) {
              lightbox.close(false);
            }
            this.switchDriveSection(lowerDecoded, false);
            return;
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

            const specialSections = ['recents', 'starred', 'activity', 'trash'];
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
  
        async loadDir(path) {
          this.currentSection = 'home';
          this.updateControlsVisibility();
          const toolbar = document.querySelector('.toolbar-actions');
          if (toolbar) toolbar.style.display = 'flex';
          document.querySelectorAll('#nav-home, #nav-recents, #nav-starred, #nav-activity, #nav-trash').forEach(el => el.classList.remove('active'));
          document.getElementById('nav-home')?.classList.add('active');
          this.currentPath = path;
          this.selectedItems.clear();
          this.updateBatchBar();
          this.renderLimit = 25;
          this.isSearching = false;
          const searchInput = document.getElementById('search-input');
          if (searchInput) searchInput.value = '';
          this.searchQuery = '';
          this.sidebar.classList.remove('open');
          this.sidebarBackdrop.classList.remove('active');
          this.updateTreeActive();

          const cacheKey = 'dir_list_' + path;
          let hasValidCache = false;

          if (window.opfsCache) {
            try {
              const cachedData = await window.opfsCache.getJSON(cacheKey);
              if (cachedData && ((cachedData.folders && cachedData.folders.length > 0) || (cachedData.files && cachedData.files.length > 0))) {
                this.data = cachedData;
                this.renderGallery();
                this.updateBreadcrumbs();
                this.updateBadges();
                hasValidCache = true;
              }
            } catch (e) {}
          }

          if (!hasValidCache) {
            this.container.innerHTML = `
              <div class="center-state">
                <svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
                <div style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant); font-weight:500;">Loading files...</div>
              </div>
            `;
          }

          try {
            const res = await fetch(`?action=list&dir=${encodeURIComponent(path || '')}`);
            if (!res.ok) {
              // If a requested subfolder doesn't exist, auto-fallback to root directory
              if (path && path !== '') {
                this.toast(`Folder "${path}" not found. Redirecting to Home...`);
                this.navigate('');
                return;
              }
              throw new Error('Failed to load directory');
            }
            const freshData = await res.json();
            this.data = freshData;
            if (window.opfsCache) window.opfsCache.setJSON(cacheKey, freshData);
            this.renderGallery();
            this.updateBreadcrumbs();
            this.updateBadges();
            const totalItems = (freshData.folders ? freshData.folders.length : 0) + (freshData.files ? freshData.files.length : 0);
            this.updateDocTitle(this.data.path ? this.data.path.split('/').pop() : '', totalItems);
          } catch (e) {
            if (!hasValidCache) {
              this.container.innerHTML = `
                <div class="center-state" style="color:var(--md-sys-color-error);">
                  <p>${e.message}</p>
                  <button class="btn-primary" style="margin-top:0.6rem;" onclick="app.navigate('')">Back to Home</button>
                </div>
              `;
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
  
        renderGallery(preserveScroll = false) {
          this.container.className = `gallery-container layout-${this.layout}`;
          const toolbar = document.querySelector('.toolbar-actions');
          if (toolbar && this.currentSection !== 'trash' && this.currentSection !== 'activity') {
            toolbar.style.display = 'flex';
          }

          const scrollEl = document.getElementById('main-content');
          const savedScroll = (preserveScroll || this.savedScrollTop) ? (this.savedScrollTop || (scrollEl ? scrollEl.scrollTop : 0)) : 0;
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

          if (savedScroll && scrollEl) {
            requestAnimationFrame(() => {
              scrollEl.scrollTop = savedScroll;
              this.savedScrollTop = 0;
            });
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

            const formattedDate = item.mtime ? new Date(item.mtime * 1000).toLocaleDateString(undefined, { year: '2-digit', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }) : '';
            const isStarred = this.starredSet.has(item.path);
            const starSvg = isStarred
              ? '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'
              : '<svg viewBox="0 0 24 24"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>';

            if (item.isDir) {
              const folderRatio = (this.layout === 'columns' || this.layout === 'list') ? '' : 'style="aspect-ratio: 1 / 1; min-height: 140px;"';
              card.onclick = (e) => app.handleItemClick(e, 'folder', item.path);
              card.oncontextmenu = (e) => app.showContextMenu(e, 'folder', item.path, item.name);

              if (item.thumb_image) card.classList.add('has-image');

              let folderThumbHtml = item.thumb_image
                ? `<img src="?action=thumb&f=${encodeURIComponent(item.thumb_image)}" alt="" loading="lazy" decoding="async">`
                : `<div class="type-icon type-folder"><svg viewBox="0 0 16 16"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.37 3.328 5.742 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg></div>`;

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
              `;
            } else {
              let thumbHtml = '';
              let thumbRatio = this.layout === 'list' ? '' : (this.layout === 'columns' ? '' : 'style="aspect-ratio: 1 / 1; min-height: 140px;"');
              const ext = item.ext || '';

              if (item.type === 'image') {
                card.classList.add('has-image');
                thumbHtml = `<img src="?action=thumb&f=${encodeURIComponent(item.path)}" alt="" loading="lazy" decoding="async" onload="this.style.opacity='1';">`;
                if (this.layout === 'columns') {
                  thumbRatio = 'style="min-height:140px; height:auto;"';
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
          const scrollEl = document.getElementById('main-content');
          const st = scrollEl ? scrollEl.scrollTop : 0;
          if (this.selectedItems.has(path)) {
            this.selectedItems.delete(path);
          } else {
            this.selectedItems.add(path);
          }
          if (this.isSearching) {
            this.appendBatch();
          } else {
            this.renderGallery(true);
          }
          if (scrollEl) scrollEl.scrollTop = st;
        }
  
        clearSelection() {
          const scrollEl = document.getElementById('main-content');
          const st = scrollEl ? scrollEl.scrollTop : 0;
          this.selectedItems.clear();
          this.updateBatchBar();
          if (this.isSearching) {
            this.appendBatch();
          } else {
            this.renderGallery(true);
          }
          if (scrollEl) scrollEl.scrollTop = st;
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
  
        batchCompress() {
          const items = Array.from(this.selectedItems);
          if (!items.length) return;
          const defaultName = (items.length === 1 ? items[0].split('/').pop() : 'archive') + '.zip';
          this.showInputModal('Compress Items', 'Archive Filename (.zip)', defaultName, (zipName) => {
            this.toast('Creating archive...');
            this.api('zip', {
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
            html += `<span class="bc-sep">/</span><a href="#/recents" class="bc-item active">Recents</a>`;
          } else if (this.currentSection === 'starred') {
            html += `<span class="bc-sep">/</span><a href="#/starred" class="bc-item active">Starred Items</a>`;
          } else if (this.currentSection === 'activity') {
            html += `<span class="bc-sep">/</span><a href="#/activity" class="bc-item active">File Activity</a>`;
          } else if (this.currentSection === 'trash') {
            html += `<span class="bc-sep">/</span><a href="#/trash" class="bc-item active">Trash Bin</a>`;
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
            window.location.href = `?action=download&f=${encodeURIComponent(file.path)}`;
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
                <div class="center-state" style="min-height:300px;">
                  <div style="font-weight:600; font-size:1rem; margin-bottom:0.4rem;">Unsupported Preview Format</div>
                  <div style="font-size:0.8rem; color:var(--md-sys-color-on-surface-variant); margin-bottom:1rem;">This format can be opened in an external application.</div>
                  <a href="${directUrl}" class="btn-primary" download style="text-decoration:none;">Download File</a>
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
            const targetHash = section === 'home' ? '#/' : `#/${section}`;
            if (window.location.hash !== targetHash) {
              window.location.hash = targetHash;
              return;
            }
          }

          this.currentSection = section;
          this.sidebar.classList.remove('open');
          this.sidebarBackdrop.classList.remove('active');
          document.querySelectorAll('#nav-home, #nav-recents, #nav-starred, #nav-activity, #nav-trash').forEach(el => el.classList.remove('active'));
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
          }
        }

        async loadActivity() {
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

          const toolbar = document.querySelector('.toolbar-actions');
          if (toolbar) toolbar.style.display = 'none';

          this.container.innerHTML = `
            <div class="center-state">
              <svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
            </div>
          `;

          try {
            const res = await fetch('?action=activity_list');
            const data = await res.json();
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
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        renderActivityView() {
          const stats = this.activityStats || {};
          let activities = this.rawActivities || [];

          if (this.filter && this.filter !== 'all') {
            activities = activities.filter(act => {
              const p = act.path || act.name || '';
              const ext = (p.split('.').pop() || '').toLowerCase();
              return this.getFileTypeByExt(ext) === this.filter;
            });
          }

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
            this.container.innerHTML = `<div class="activity-view-wrapper">${statsHtml}<div class="center-state"><p>${this.filter !== 'all' ? 'No activity found for category: ' + this.filter : 'No recorded activity yet'}</p></div></div>`;
            return;
          }

          let rowsHtml = activities.map(act => {
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
              <div class="activity-list-container">
                ${rowsHtml}
              </div>
            </div>
          `;
        }

        async loadRecents() {
          this.currentSection = 'recents';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.renderLimit = 25;
          this.isSearching = false;
          this.dirTitle.innerText = 'Recents';
          this.dirStats.innerText = 'Chronologically sorted from newest to oldest';
          this.container.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg></div>';

          try {
            const res = await fetch('?action=recents_list');
            const data = await res.json();

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
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        async loadStarred() {
          this.currentSection = 'starred';
          this.updateControlsVisibility();
          this.currentPath = '';
          this.selectedItems.clear();
          this.updateBatchBar();
          this.renderLimit = 25;
          this.isSearching = false;
          this.dirTitle.innerText = 'Starred Items';
          this.dirStats.innerText = 'Quick access to your favorite files and folders';
          this.container.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg></div>';

          try {
            const res = await fetch('?action=starred_list');
            const data = await res.json();
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
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
        }

        toggleStarDirect(e, path) {
          e.stopPropagation();
          const scrollEl = document.getElementById('main-content');
          const savedScroll = scrollEl ? scrollEl.scrollTop : 0;
          this.api('star_toggle', { path }, (res) => {
            if (res.is_starred) {
              this.starredSet.add(path);
            } else {
              this.starredSet.delete(path);
            }
            this.toast(res.is_starred ? 'Starred' : 'Unstarred');
            if (this.currentSection === 'starred') {
              this.loadStarred();
            } else {
              this.renderGallery(true);
            }
            if (scrollEl) scrollEl.scrollTop = savedScroll;
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

          const toolbar = document.querySelector('.toolbar-actions');
          if (toolbar) toolbar.style.display = 'none';

          // Reset container layout so it doesn't inherit masonry flex columns
          this.container.className = 'gallery-container layout-grid';
          this.container.removeAttribute('data-cols');
          this.container.innerHTML = `
            <div class="center-state">
              <svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg>
            </div>
          `;

          try {
            const res = await fetch('?action=trash_list');
            const data = await res.json();
            const items = data.trash || [];

            // Calculate live category badge breakdown for items in trash
            const trashFiles = items.filter(i => !i.is_dir).map(i => {
              const ext = (i.original_name.split('.').pop() || '').toLowerCase();
              return { type: this.getFileTypeByExt(ext) };
            });
            this.data = { folders: items.filter(i => i.is_dir), files: trashFiles, stats: {} };
            this.updateBadges();

            if (this.filter && this.filter !== 'all') {
              items = items.filter(i => {
                if (i.is_dir) return false;
                const ext = (i.original_name.split('.').pop() || '').toLowerCase();
                return this.getFileTypeByExt(ext) === this.filter;
              });
            }

            if (!items.length) {
              this.container.innerHTML = `
                <div class="center-state" style="grid-column: 1 / -1; padding: 3.5rem 0;">
                  <svg viewBox="0 0 24 24" style="width:64px; height:64px; opacity:0.3; color:var(--md-sys-color-outline); margin-bottom:0.6rem;"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  <div style="font-weight:700; font-size:1.1rem; color:var(--md-sys-color-on-surface);">Trash is Empty</div>
                  <div style="font-size:0.82rem; color:var(--md-sys-color-on-surface-variant);">Deleted files and folders will appear here.</div>
                </div>
              `;
              return;
            }

            let html = `
              <div style="grid-column: 1 / -1; display:flex; justify-content:space-between; align-items:center; background:var(--md-sys-color-surface-container-low); border:1px solid var(--md-sys-color-outline-variant); border-radius:16px; padding:0.75rem 1.1rem; margin-bottom:0.6rem;">
                <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:var(--md-sys-color-on-surface-variant);">
                  <span style="font-weight:700; color:var(--md-sys-color-on-surface); font-size:0.95rem;">${items.length}</span> item(s) in trash
                </div>
                <button class="btn-primary" style="background:#dc2626; height:34px; padding:0 0.95rem; font-size:0.8rem; gap:0.4rem;" onclick="app.emptyTrash()">
                  <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  Empty Trash
                </button>
              </div>
              <div style="grid-column: 1 / -1; display:flex; flex-direction:column; gap:0.55rem; width:100%;">
            `;

            items.forEach(t => {
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

            html += `</div>`;
            this.container.innerHTML = html;
          } catch (e) {
            this.container.innerHTML = `<div class="center-state" style="color:var(--md-sys-color-error);"><p>${e.message}</p></div>`;
          }
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

        createShareLink(path) {
          this.api('share_create', { f: path }, (res) => {
            const url = `${window.location.origin}${window.location.pathname}?share=${res.token}`;
            navigator.clipboard.writeText(url);
            this.toast('Share link copied to clipboard!');
          });
        }

        setClipboard(operation) {
          const items = this.selectedItems.size ? Array.from(this.selectedItems) : [];
          if (!items.length) return;
          this.clipboard = { operation, items };
          this.toast(`${items.length} item(s) marked to ${operation}`);
        }

        setClipboardSingle(operation, path) {
          this.clipboard = { operation, items: [path] };
          this.toast(`Marked to ${operation}`);
        }

        pasteClipboard() {
          if (!this.clipboard || !this.clipboard.items.length) {
            this.toast('Clipboard is empty');
            return;
          }
          this.api('clipboard_paste', {
            target_dir: this.currentPath || '',
            operation: this.clipboard.operation,
            items: this.clipboard.items
          }, () => {
            this.toast('Pasted successfully');
            if (this.clipboard.operation === 'cut') this.clipboard = null;
            this.refresh();
          });
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
            addItem('<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>', 'Preview / Open', () => this.navigate(path));
            addItem('<svg viewBox="0 0 24 24"><path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg>', 'Open in a new tab', () => this.openInNewTab(path, 'folder'));
            addItem('<svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>', 'Download', () => this.downloadZipWithProgress(`?action=download_zip&dir=${encodeURIComponent(path)}`, null, `${name}.zip`));
            addItem('<svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>', 'Compress to ZIP', () => {
              this.showInputModal('Compress Folder', 'Archive Filename (.zip)', `${name}.zip`, (zipName) => {
                this.api('zip', { dir: this.currentPath || '', items: [path], zip_name: zipName || `${name}.zip` }, () => {
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
              addItem('<svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>', 'Extract Here', () => this.unzipItem(path));
            } else {
              addItem('<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>', 'Preview / Play', () => this.openFile(path, true));
            }
            addItem('<svg viewBox="0 0 24 24"><path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg>', 'Open in a new tab', () => this.openInNewTab(path, 'file'));
            addItem('<svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>', 'Download', () => { window.location.href = `?action=download&f=${encodeURIComponent(path)}`; });
            if (fileType !== 'archive') {
              addItem('<svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>', 'Compress to ZIP', () => {
                const baseName = name.replace(/\.[^/.]+$/, '');
                this.showInputModal('Compress File', 'Archive Filename (.zip)', `${baseName}.zip`, (zipName) => {
                  this.api('zip', { dir: this.currentPath || '', items: [path], zip_name: zipName || `${baseName}.zip` }, () => {
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
  
        previewArchive(path, name) {
          document.getElementById('archive-preview-title').innerText = name || 'Archive Contents';
          const body = document.getElementById('archive-preview-body');
          const stats = document.getElementById('archive-preview-stats');
          stats.innerText = 'Scanning archive...';
          body.innerHTML = '<div class="center-state"><svg class="m3-spinner" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle></svg></div>';
          this.showModal('modal-archive-preview');

          document.getElementById('archive-extract-btn').onclick = () => {
            this.unzipItem(path);
            this.closeModals();
          };

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

        unzipItem(path) {
          this.toast('Extracting archive...');
          this.api('unzip', { f: path }, () => {
            this.toast('Archive extracted successfully');
            this.refresh();
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
            html += `
              <div class="details-section">
                <div class="details-title">
                  <svg viewBox="0 0 24 24"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>
                  Media & Track Info
                </div>
                <div class="details-grid">
            `;
            for (let k in res.media.tags) {
              html += `<div class="details-row"><span class="details-label">${k}</span><span class="details-value">${res.media.tags[k]}</span></div>`;
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
          document.getElementById('modal-backdrop').classList.remove('active');
          document.querySelectorAll('.modal-box').forEach(m => m.style.display = 'none');
          const docContainer = document.getElementById('doc-viewer-container');
          if (docContainer) docContainer.innerHTML = '';

          const prevPane = document.getElementById('hdm-preview-pane');
          if (prevPane) prevPane.innerHTML = '';

          const activeTarget = this.activeModalPath || (window.hdmEngine ? window.hdmEngine.activePath : '');
          this.activeModalPath = '';
          if (window.hdmEngine) window.hdmEngine.activePath = '';

          // If opened from a dedicated section (recents, starred, activity, trash), return to it
          const specialSections = ['recents', 'starred', 'activity', 'trash'];
          const returnSection = (this.originSection && specialSections.includes(this.originSection))
            ? this.originSection
            : (this.currentSection && specialSections.includes(this.currentSection) ? this.currentSection : null);

          if (returnSection) {
            this.originSection = null;
            this.currentSection = returnSection;
            const targetHash = `#/${returnSection}`;
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

          const targetHash = parentDir ? '#/' + ltrim(parentDir, '/') : '#/';
          if (window.location.hash !== targetHash) {
            window.location.hash = targetHash;
          } else {
            this.loadDir(parentDir);
          }
          this.updateDocTitle(parentDir ? parentDir.split('/').pop() : '', (this.data.folders?.length || 0) + (this.data.files?.length || 0));
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
    </script>
  </body>
</html>