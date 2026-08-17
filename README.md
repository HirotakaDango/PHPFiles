# PHPFiles - Modern Single-File PHP File Manager & Media Gallery

<img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/9b13a45e-2e3c-480f-958b-a0f84421bffc" />

**PHPFiles** is a powerful, lightweight, single-file PHP file manager and gallery application designed for fast, seamless file management and media browsing. It features a modern Google Material Design 3 UI, rich media viewers, chunked file uploading, client-side caching (OPFS), and zero external database dependencies.

---

## Key Features

- **Single-File Architecture:** Fully functional single-file script incorporating both backend PHP endpoints and front-end HTML/CSS/JS interface.
- **Material Design 3 Interface:** Responsive layout supporting light/dark themes, customizable grid columns, masonry/column views, and list views.
- **Drive Navigation & Sections:**
  - **Home & Folder Tree:** Interactive sidebar folder navigation tree with expand/collapse state memory.
  - **Recents:** Instant chronological view of recently updated or uploaded files and folders.
  - **Starred Items:** Bookmark favorite files and directories for quick access.
  - **File Activity Log:** In-depth audit log tracking created, modified, renamed, uploaded, and trashed file activities.
  - **Trash Bin & Auto-Purge:** Soft-delete system with manual restore/empty controls and automatic purging of items older than 30 days.
- **Chunked & Remote Uploads:**
  - Resumable/chunked uploading of large files and entire folder structures via drag-and-drop or file pickers.
  - **Remote URL Downloader:** Direct server-side file downloading from remote URLs with optional custom naming and header auto-detection.
- **Rich Media Lightbox & Gallery:** Modal lightbox with touch gestures for viewing photos, video streaming (supports range requests for seekable playback), and audio playback with spinning album art discs.
- **Dedicated Manga Reader Mode:** Continuous vertical scroll viewer for reading comic/manga image folders with customizable widths and an offline HTML exporter option.
- **Document & PDF Viewers:** In-browser rendering for PDF files, Microsoft Word (`.docx`) documents, and Excel (`.xlsx`/`.csv`) spreadsheets.
- **HDMarkDown & Code Editor:**
  - Full-featured syntax-friendly text editor powered by CodeMirror.
  - Live split-view or full preview mode for Markdown files with support for code syntax highlighting, Mermaid diagrams, and presentation mode.
  - In-editor Find & Replace, undo/redo history, word wrap, and metrics counter.
- **Version Control & Diff Viewer:** Automatic file versioning on edit with rollback capabilities and inline visual diff comparison (`Current` vs. `Rollback` version).
- **File Encryption:** AES-256-CBC client/server file encryption (`.enc`) and decryption protected by configurable or custom passwords.
- **Metadata Viewer (EXIF, IPTC & ID3):** Extract detailed image metadata (EXIF/IPTC with Google Maps coordinates) and audio/video ID3 tags (track titles, artists, duration, resolution, and album art).
- **Archive Operations & Inspector:**
  - Create ZIP or TAR/TAR.GZ archives from selected items.
  - Extract ZIP, TAR, and RAR archives directly on the server.
  - **Archive Inspector:** Interactive modal preview to view inner entries, compressed/uncompressed sizes, and modification dates without extracting.
- **Clipboard & Batch Operations:** Select multiple items for batch inspection, ZIP downloading, server compression, moving (cut), copying, or deletion.
- **Public Share Links:** Generate direct download or sharing links protected by tokenized URLs.
- **OPFS (Origin Private File System) Caching:** High-performance client-side directory and tree caching to deliver instantaneous navigation.
- **Authentication & Security:** Optional password authentication (with `password_verify` support), demo/read-only mode for unauthenticated users, and path normalization to prevent directory traversal attacks (`safePath`).

---

## File Type Support

| Category | Supported Extensions |
| :--- | :--- |
| **Images** | `jpg`, `jpeg`, `png`, `gif`, `webp`, `avif`, `bmp`, `svg` |
| **Videos** | `mp4`, `webm`, `mov`, `m4v`, `ogv`, `mkv`, `avi`, `ts`, `3gp`, `wmv`, `flv` |
| **Audio** | `mp3`, `wav`, `ogg`, `flac`, `m4a`, `aac`, `opus`, `wma`, `m4r`, `mid`, `midi` |
| **Documents / Text** | `txt`, `md`, `markdown`, `json`, `js`, `css`, `html`, `htm`, `php`, `py`, `c`, `cpp`, `sh`, `log`, `xml`, `yaml`, `yml`, `ini`, `env`, `sql`, `csv`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `enc` |
| **Archives** | `zip`, `tar`, `gz`, `tgz`, `7z`, `rar` |

---

## Requirements

- **PHP Version:** PHP 7.4 or higher (PHP 8.x recommended).
- **PHP Extensions:**
  - `gd` (Required for image thumbnail generation and WebP/AVIF support).
  - `exif` (Optional, for extracting image EXIF metadata).
  - `zip` (Optional, required for ZIP archive compression and extraction).
  - `curl` (Optional, required for Remote URL downloads).
  - `openssl` (Optional, required for AES-256 file encryption and decryption).
  - `phar` / `rar` (Optional, for TAR/TAR.GZ creation and RAR extraction).
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
  'trash_dir'          => __DIR__ . '/.drive_trash_bin',
  'version_dir'        => __DIR__ . '/.file_version',
  'meta_file'          => __DIR__ . '/.gallery_cache/.drive_meta.json',
  'app_title'          => 'PHPFiles',
  'auth_enabled'       => false, // Set to true to enable login
  'password'           => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // Default password: admin
  'encryption_key'     => 'Default_Secret_2026', // Secret key used for AES-256 file encryption
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

### Password Protection & Demo Mode

To enable password authentication:

1. Set `'auth_enabled' => true`.
2. Generate a secure password hash in PHP using `password_hash('your_password', PASSWORD_BCRYPT)` and assign it to `'password'`.
3. When authenticated, full read/write capabilities are unlocked. Unauthenticated users operate in a read-only **Demo Mode**.

---

## Usage Guide & Shortcuts

- **Navigation:** Click on directories in the main gallery, sidebar folder tree, breadcrumbs, or use drive navigation filters (Recents, Starred, Activity, Trash).
- **Selection & Batch Operations:** Click the checkbox on any file/folder card to select multiple items for batch info viewing, ZIP downloading, compression, copying/cutting, or deletion.
- **Remote Upload:** Click **Upload > Upload from URL** to fetch files directly from remote HTTP/HTTPS links.
- **Manga Mode:** Right-click a folder or click the Manga icon in the top toolbar to load all folder images into a continuous vertical reader.
- **Offline Manga Export:** Inside Manga Mode, click the download icon to save the entire image folder as a standalone self-contained `.html` file with embedded base64 images.
- **Text & Markdown Editing:** Right-click or click on supported text/code/markdown files to open the built-in CodeMirror editor with live preview, diff viewer, and version history.
- **Keyboard Shortcuts:**

| Shortcut | Action |
| --- | --- |
| `?` / `F1` | Open Keyboard Shortcuts guide modal |
| `/` or `Ctrl + F` | Focus search bar (File Manager) / Find & Replace (Editor) |
| `Ctrl + A` | Select all items in current folder |
| `Ctrl + Shift + N` | Create a new folder |
| `Ctrl + Shift + F` | Create a new text file |
| `Ctrl + C` / `Ctrl + X` | Copy / Cut selected items |
| `Ctrl + V` | Paste copied/cut items to current directory |
| `F2` | Rename selected item |
| `Delete` | Move selected item(s) to Trash |
| `Ctrl + S` | Save active document in Editor |
| `Esc` | Close active modal, Lightbox, or Manga view |

---

## License

This project is open-source software provided under the MIT License.
