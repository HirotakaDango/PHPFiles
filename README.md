# PHPFiles - Modern Single-File PHP File Manager & Media Gallery

<img width="2048" height="1152" alt="screenshot" src="https://github.com/user-attachments/assets/12ecea53-bcb7-4a54-adbf-9896778fd05e" />

**PHPFiles** is a high-performance, single-file PHP file manager, document workspace, in-app image studio, and media gallery. Built on Google Material Design 3 principles, it combines desktop-class file management with rich media streaming, client-side OPFS caching, and zero external database dependencies.

---

## ✨ Key Features

- **Single-File Architecture:** The entire application—backend REST endpoints, frontend SPA routing, canvas engines, and UI—is self-contained in a single `.php` file.
- **Material Design 3 Interface & 4 Distinct Layout Modes:**
  - **Square Grid (1:1):** Classic uniform square tiles with customizable grid density (1 to 8 columns).
  - **Column Masonry (Waterfall):** Pinterest-style vertical multi-column flow.
  - **Justified Row Masonry (Dynamic Aspect Ratio):** PicHome / Google Photos-style layout where images maintain natural aspect ratios with fixed row heights, dynamically scalable via the density slider (125px to 380px).
  - **Compact List View:** High-density file list with metadata, file sizes, and modification dates.
- **Built-In In-App Image Studio & Canvas Editor:**
  - **Interactive 8-Point Crop Engine:** Draggable & resizable boundary handles with clear visual masks and aspect ratio presets (*Freeform, 1:1 Square, 16:9 Landscape, 4:3 Standard, 9:16 Story/Reel*).
  - **Transforms:** 90° Clockwise/Counter-Clockwise rotations, Flip Horizontal, and Flip Vertical.
  - **Live Filter Adjustments:** Sliders for Brightness, Contrast, and Saturation, plus instant Grayscale, Sepia, and Invert toggles.
  - **Freeform Draggable Text & Header Banners:** Click or drag text anywhere on the canvas with custom font size, text color picker, and full-width top/bottom header banner stamping.
  - **Undo & Redo System:** Full historical step stack (`Ctrl + Z` / `Ctrl + Y`) for all filters, crops, transforms, and text overlays.
  - **Dual Save Engine:** Option to **Permanently Overwrite** (with automatic version snapshot backup) or **Save as Copy / Duplicate** (`filename_edited_(n).jpg`).
- **Low-Spec Hardware & Media Streaming Optimizations:**
  - **Zero Memory Accumulation:** Bypasses PHP `zlib.output_compression` and Apache `gzip` on streaming endpoints, preventing low-RAM devices (e.g., 2GB laptops) from crashing due to memory buffering.
  - **High-Throughput 512KB Chunking:** Optimized byte-range streaming (`HTTP 206 Partial Content`) with shared non-blocking file locks (`flock`) and connection abort listeners.
  - **GPU-Friendly Compositing:** Streamlined CSS rendering without heavy blur filters for smooth 60fps playback on legacy integrated graphics (e.g., Intel HD 3000/4000).
  - **External / Native Player Link:** Instant "Native Tab / External Player" toggle to watch videos without DOM overhead.
- **Advanced Batch Rename Engine:**
  - Rename dozens of files simultaneously using substring patterns or Regular Expressions (Regex).
  - Match case toggles, prefix/suffix additions, and target scoping (*Name only, Extension only, or Full filename*).
  - Live preview table with duplicate destination collision detection.
- **HDMarkDown & Programming Workspace:**
  - Built-in text and code editor powered by CodeMirror.
  - Live split-view or full preview mode for Markdown and HTML files with syntax highlighting (PHP, JS, Python, SQL, C/C++, HTML, CSS, XML).
  - Interactive **Mermaid Diagrams** with zoom and drag-pan navigation.
  - Fullscreen Presentation Mode for slideshows (`---` delimiter slide separation).
  - In-editor Find & Replace (with match counters), Undo/Redo stack, and word wrap toggle.
- **File Version Control & LCS Diff Viewer:**
  - Automatic incremental version backup snapshots created before every file save, image overwrite, or restore.
  - Bounded Longest Common Subsequence (LCS) line diff algorithm with fast prefix/suffix trimming for large files.
  - Visual side-by-side/inline comparison of additions (`+`) and deletions (`-`) with one-click rollback.
- **Document & Office Suite Rendering:**
  - Client-side PDF rendering using `pdf.js` with Retina / High-DPI canvas scaling.
  - Microsoft Word (`.docx`) document rendering via `docx-preview`.
  - Excel (`.xlsx`, `.xls`, `.csv`) spreadsheet parsing and interactive table generation via `SheetJS`.
- **Archive Operations & Live Inspector:**
  - Create ZIP or TAR/TAR.GZ archives from multiple selected files or folders.
  - Server-side extraction for ZIP, TAR, GZ, and RAR archives.
  - **Archive Inspector:** Preview archive contents, file sizes, and modification dates without extracting to disk.
- **Manga Reader Mode & Offline HTML Exporter:**
  - Continuous vertical reading mode for comic/manga image folders with customizable width options (*Fit Width, Fit Height, Fit Screen*).
  - **Offline Exporter:** One-click export that packages an entire image folder into a single standalone `.html` file with embedded base64 images.
- **Chunked Uploads & Remote URL Downloader:**
  - Resumable 2MB chunked upload engine supporting drag-and-drop of entire folder hierarchies.
  - **Remote URL Downloader:** Fetch files directly to the server from remote HTTP/HTTPS links with automated filename detection via Content-Disposition headers.
- **Drive Navigation & Sections:**
  - **Home & Folder Tree:** Interactive sidebar folder navigation tree with expand/collapse state memory.
  - **Gallery Mode:** Consolidated view of all images across subdirectories.
  - **Recents:** Chronological view of recently updated or uploaded files and folders.
  - **Starred Items:** Bookmark favorite files and directories for quick access.
  - **File Activity Log:** Audit log tracking created, modified, renamed, uploaded, and trashed file activities with daily/weekly analytics.
  - **Trash Bin & Auto-Purge:** Soft-delete recycling bin with individual item restoration, empty bin option, and automatic 30-day purge cleanup.
- **Security & Privacy:**
  - **AES-256-CBC File Encryption:** Encrypt and decrypt sensitive files (`.enc`) on-the-fly with custom passwords.
  - **Metadata Inspector:** Extract EXIF/IPTC image metadata (with Google Maps coordinate links) and audio/video ID3 tags (duration, bitrate, resolution, album art).
  - **OPFS (Origin Private File System) Caching:** Client-side directory tree and metadata caching for instant folder navigation.
  - **Access Control:** Optional password protection with bcrypt hashing and read-only Demo Mode for unauthenticated visitors.

---

## 📁 File Type Support

| Category | Supported Extensions |
| :--- | :--- |
| **Images** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `avif`, `bmp`, `svg` *(In-app editing supported for raster formats)* |
| **Videos** | `mp4`, `webm`, `mov`, `m4v`, `ogv`, `mkv`, `avi`, `ts`, `3gp`, `wmv`, `flv` |
| **Audio** | `mp3`, `wav`, `ogg`, `flac`, `m4a`, `aac`, `opus`, `wma`, `m4r`, `mid`, `midi` |
| **Documents / Code** | `txt`, `md`, `markdown`, `json`, `js`, `css`, `html`, `htm`, `php`, `py`, `c`, `cpp`, `sh`, `log`, `xml`, `yaml`, `yml`, `ini`, `env`, `sql`, `csv`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `enc` |
| **Archives** | `zip`, `tar`, `gz`, `tgz`, `7z`, `rar` |

---

## 📋 Requirements

- **PHP Version:** PHP 7.4 or higher (PHP 8.x recommended).
- **PHP Extensions:**
  - `gd` (Required for image thumbnail generation, WebP/AVIF processing, and canvas image manipulation).
  - `fileinfo` (Standard, for MIME type detection).
  - `exif` *(Optional — for extracting camera EXIF metadata)*.
  - `zip` *(Optional — for ZIP archive creation, inspection, and extraction)*.
  - `curl` *(Optional — for Remote URL downloads)*.
  - `openssl` *(Optional — for AES-256 file encryption and decryption)*.
  - `phar` / `rar` *(Optional — for TAR creation and RAR extraction)*.
- **Web Server:** Apache, Nginx, IIS, or PHP Built-in CLI Server.
- Read and write permissions in the directory where the script resides.

---

## 🚀 Installation & Quick Start

1. **Upload:** Place `phpfiles.php` (or rename it to `index.php`) in any web-accessible folder on your server.
2. **Permissions:** Ensure the parent folder has write permissions so PHPFiles can create cache directories (`.gallery_cache/`, `.drive_trash_bin/`, `.file_version/`).
3. **Open:** Navigate to the URL in your web browser (e.g., `http://your-server.com/phpfiles.php`).

### Local Development / Testing

To run locally without setting up Apache or Nginx:

```bash
php -S localhost:8000 phpfiles.php
```

Then open `http://localhost:8000` in your web browser.

---

## ⚙️ Configuration

All configuration settings are defined in the `$config` array at the top of the file:

```php
$config = [
  'root_dir'           => __DIR__,
  'cache_dir'          => __DIR__ . '/.gallery_cache',
  'trash_dir'          => __DIR__ . '/.drive_trash_bin',
  'version_dir'        => __DIR__ . '/.file_version',
  'meta_file'          => __DIR__ . '/.gallery_cache/.drive_meta.json',
  'app_title'          => 'PHPFiles',
  'auth_enabled'       => false, // Set to true to require login
  'password'           => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // Default password: admin
  'encryption_key'     => 'Default_Secret_2026', // Secret key for AES-256 encryption
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
  // Extension arrays...
];
```

### Password Protection & Demo Mode

1. Set `'auth_enabled' => true`.
2. Generate a bcrypt hash using `password_hash('your_password', PASSWORD_BCRYPT)` and assign it to `'password'`.
3. When authenticated, full read/write permissions are unlocked. Unauthenticated users access the workspace in a read-only **Demo Mode**.

---

## ⌨️ Keyboard Shortcuts

| Shortcut | Action | Scope |
| :--- | :--- | :--- |
| `?` / `F1` | Open Keyboard Shortcuts Cheat Sheet | Global |
| `/` or `Ctrl + F` | Focus Search Bar | File Manager |
| `Ctrl + F` | Find & Replace Card | Text / Code Editor |
| `Ctrl + A` | Select all items | File Manager |
| `Ctrl + Shift + N` | Create a new folder | File Manager |
| `Ctrl + Shift + F` | Create a new text file | File Manager |
| `Ctrl + C` / `Ctrl + X` | Copy / Cut selected items | File Manager |
| `Ctrl + V` | Paste copied/cut items | File Manager |
| `F2` | Rename selected item | File Manager |
| `Delete` | Move selected item(s) to Trash | File Manager |
| `Ctrl + S` | Save active document | Text / Code Editor |
| `Ctrl + B` / `Ctrl + I` | Bold / Italic syntax | Text / Code Editor |
| `Ctrl + Z` / `Ctrl + Y` | Undo / Redo changes | Code & Image Editor |
| `←` / `→` | Previous / Next media item | Lightbox |
| `Space` | Play / Pause media playback | Lightbox |
| `Esc` | Close active modal, Lightbox, or Manga viewer | Global |

## 📄 License

This project is open-source software provided under the [MIT License](LICENSE).
