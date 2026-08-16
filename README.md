# PHPFiles - Modern Single-File PHP File Manager & Media Gallery

<img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/ef99a382-2423-4505-acd8-08cf2c5c50ac" />

**PHPFiles** is a powerful, lightweight, single-file PHP file manager and gallery application designed for fast, seamless file management and media browsing. It features a modern Google Material Design 3 UI, rich media viewers, chunked file uploading, client-side caching (OPFS), and zero external database dependencies.

---

## Key Features

- **Single-File Architecture:** Fully functional single-file script incorporating both backend PHP endpoints and front-end HTML/CSS/JS interface.
- **Material Design 3 Interface:** Responsive layout supporting light/dark themes, customizable grid columns, masonry/column views, and list views.
- **Chunked File Uploads:** Supports resumable/chunked uploading of large files and entire folder structures via drag-and-drop or file pickers.
- **Media Lightbox & Gallery:** Built-in modal lightbox with touch gesture support for viewing photos, video streaming, and audio playback.
- **Dedicated Manga Reader Mode:** Vertical continuous-scroll viewer for reading comic/manga image folders with customizable widths and an offline HTML exporter option.
- **Built-in Text & Code Editor:** View and edit plain text, code, JSON, and configuration files directly in the browser.
- **Metadata Viewer (EXIF & IPTC):** Extract detailed image metadata including camera model, ISO, aperture, shutter speed, date taken, GPS coordinates (with Google Maps integration), and IPTC tags.
- **ZIP Operations:** Create ZIP archives from selected files/folders, extract existing archives inline, or download entire directories as ZIP files.
- **OPFS (Origin Private File System) Caching:** High-performance client-side directory and tree caching to deliver instantaneous navigation.
- **Authentication & Security:** Optional password authentication (with `password_verify` support) and path normalization to prevent directory traversal attacks (`safePath`).

---

## File Type Support

| Category | Supported Extensions |
| :--- | :--- |
| **Images** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `avif`, `bmp`, `svg` |
| **Videos** | `mp4`, `webm`, `mov`, `m4v`, `ogv`, `mkv` |
| **Audio** | `mp3`, `wav`, `ogg`, `flac`, `m4a`, `aac` |
| **Documents / Text** | `txt`, `md`, `json`, `js`, `css`, `html`, `php`, `py`, `c`, `cpp`, `sh`, `log`, `xml`, `yaml`, `yml`, `ini`, `env`, `sql`, `csv`, `pdf` |
| **Archives** | `zip`, `tar`, `gz`, `7z`, `rar` |

---

## Requirements

- **PHP Version:** PHP 7.4 or higher (PHP 8.x recommended).
- **PHP Extensions:**
  - `gd` (Required for image thumbnail generation and WebP/AVIF support).
  - `exif` (Optional, for extracting image EXIF metadata).
  - `zip` (Optional, required for ZIP archive compression and extraction).
- **Web Server:** Apache, Nginx, IIS, or PHP Built-in Web Server.

---

## Quick Start & Installation

1. **Download:** Save the single PHP script as `index.php` (or `files.php`) inside your target directory.
2. **Permissions:** Ensure the PHP script has read and write permissions in its parent directory (for uploading files, caching thumbnails in `.gallery_cache`, and executing modifications).
3. **Run:** Point your web browser to the file location (e.g., `http://localhost/index.php`).

### PHP Built-in Web Server

To test locally without setting up Apache or Nginx:

```bash
php -S localhost:8000 index.php
```

Then open `http://localhost:8000` in your web browser.

---

## Configuration

All application configuration options are located at the top of the PHP script in the `$config` array:

```php
$config = [
  'root_dir'           => __DIR__,
  'cache_dir'          => __DIR__ . '/.gallery_cache',
  'app_title'          => 'PHPFiles',
  'auth_enabled'       => false, // Set to true to enable login
  'password'           => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // Default password: admin
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
  // Extension definitions...
];
```

### Password Protection
To enable password authentication:
1. Set `'auth_enabled' => true`.
2. Generate a secure password hash in PHP using `password_hash('your_password', PASSWORD_BCRYPT)` and assign it to `'password'`.

---

## Usage Guide

- **Navigation:** Click on directories in the main gallery, sidebar folder tree, or breadcrumbs.
- **Selection & Batch Operations:** Click the checkbox on any file/folder card to select multiple items for batch info viewing, ZIP downloading, or deletion.
- **Manga Mode:** Right-click a folder or click the Manga icon in the top toolbar to load all folder images into a continuous vertical reader.
- **Offline Manga Export:** Inside Manga Mode, click the download icon to save the entire image folder as a standalone self-contained `.html` file with embedded base64 images.
- **Text Editing:** Right-click or click on supported text files to open the built-in syntax-friendly text editor.

---

## License

This project is open-source software provided under the MIT License.
