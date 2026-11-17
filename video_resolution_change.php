<?php
/**
 * Video Resolution Converter (16:9 to 9:16)
 * Supports 3840x2160 4K UHD landscape to 2160x3840 9:16 vertical video conversion
 * CLI & Web dual-mode script with Chunked Upload support
 */

@set_time_limit(0);
@ini_set('memory_limit', '512M');

// Class to handle video resolution and aspect ratio conversion logic
class VideoResolutionConverter {
    /**
     * Locate the FFmpeg executable on the host system
     */
    public static function findFfmpeg(): ?string {
        $commonPaths = [
            '/opt/homebrew/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/usr/bin/ffmpeg',
            '/bin/ffmpeg'
        ];

        foreach ($commonPaths as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        $which = trim(@shell_exec('which ffmpeg 2>/dev/null') ?? '');
        if (!empty($which) && is_file($which) && is_executable($which)) {
            return $which;
        }

        return null;
    }

    /**
     * Locate the FFprobe executable on the host system
     */
    public static function findFfprobe(): ?string {
        $commonPaths = [
            '/opt/homebrew/bin/ffprobe',
            '/usr/local/bin/ffprobe',
            '/usr/bin/ffprobe',
            '/bin/ffprobe'
        ];

        foreach ($commonPaths as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        $which = trim(@shell_exec('which ffprobe 2>/dev/null') ?? '');
        if (!empty($which) && is_file($which) && is_executable($which)) {
            return $which;
        }

        return null;
    }

    /**
     * Inspect video dimensions using ffprobe
     */
    public static function getVideoDimensions(string $filePath): ?array {
        $ffprobe = self::findFfprobe();
        if (!$ffprobe || !is_file($filePath)) {
            return null;
        }

        $cmd = escapeshellcmd($ffprobe) . ' -v error -select_streams v:0 -show_entries stream=width,height -of json ' . escapeshellarg($filePath);
        $output = @shell_exec($cmd);
        if (!$output) {
            return null;
        }

        $json = json_decode($output, true);
        if (isset($json['streams'][0]['width'], $json['streams'][0]['height'])) {
            return [
                'width' => (int)$json['streams'][0]['width'],
                'height' => (int)$json['streams'][0]['height']
            ];
        }

        return null;
    }

    /**
     * Generate the FFmpeg command string and arguments
     *
     * @param string $inputPath Path to source video (e.g. 3840x2160)
     * @param string $outputPath Path for target vertical video
     * @param string $mode 'blur' (blurred background), 'crop' (center crop), 'fit' (pillarbox), or 'stretch'
     * @param int $targetWidth Target width (default 2160)
     * @param int $targetHeight Target height (default 3840)
     * @return array [ 'command' => string, 'filter_type' => string, 'filter' => string ]
     */
    public static function buildCommand(
        string $inputPath,
        string $outputPath,
        string $mode = 'blur',
        int $targetWidth = 2160,
        int $targetHeight = 3840
    ): array {
        $ffmpeg = self::findFfmpeg() ?? 'ffmpeg';
        $w = max(2, $targetWidth - ($targetWidth % 2));
        $h = max(2, $targetHeight - ($targetHeight % 2));

        switch ($mode) {
            case 'crop':
                // Center crop 16:9 to 9:16 and scale to target resolution
                $filter = "crop=ih*9/16:ih:(iw-ih*9/16)/2:0,scale={$w}:{$h},setsar=1";
                $filterArg = "-vf " . escapeshellarg($filter);
                $filterType = '-vf';
                break;

            case 'fit':
                // Fit original 16:9 inside 9:16 canvas with black padding bars on top & bottom
                $filter = "scale={$w}:-2,pad={$w}:{$h}:(ow-iw)/2:(oh-ih)/2:black,setsar=1";
                $filterArg = "-vf " . escapeshellarg($filter);
                $filterType = '-vf';
                break;

            case 'stretch':
                // Directly stretch to target resolution
                $filter = "scale={$w}:{$h},setsar=1";
                $filterArg = "-vf " . escapeshellarg($filter);
                $filterType = '-vf';
                break;

            case 'blur':
            default:
                // Blurred background: upscale & blur video for background, overlay original 16:9 video centered
                $filter = "[0:v]scale={$w}:{$h}:force_original_aspect_ratio=increase,crop={$w}:{$h},boxblur=25:8[bg];[0:v]scale={$w}:-2,setsar=1[fg];[bg][fg]overlay=(W-w)/2:(H-h)/2,setsar=1";
                $filterArg = "-filter_complex " . escapeshellarg($filter);
                $filterType = '-filter_complex';
                break;
        }

        $command = sprintf(
            '%s -i %s %s -c:v libx264 -preset veryfast -crf 22 -c:a aac -b:a 192k -movflags +faststart -y %s',
            escapeshellcmd($ffmpeg),
            escapeshellarg($inputPath),
            $filterArg,
            escapeshellarg($outputPath)
        );

        return [
            'command' => $command,
            'filter_type' => $filterType,
            'filter' => $filter,
            'target_width' => $w,
            'target_height' => $h,
            'mode' => $mode
        ];
    }

    /**
     * Execute the conversion
     */
    public static function convert(
        string $inputPath,
        string $outputPath,
        string $mode = 'blur',
        int $targetWidth = 2160,
        int $targetHeight = 3840
    ): array {
        if (!file_exists($inputPath)) {
            return ['success' => false, 'error' => "Input file does not exist: {$inputPath}"];
        }

        $ffmpeg = self::findFfmpeg();
        if (!$ffmpeg) {
            return ['success' => false, 'error' => 'FFmpeg binary not found on the system. Please ensure FFmpeg is installed.'];
        }

        $cmdData = self::buildCommand($inputPath, $outputPath, $mode, $targetWidth, $targetHeight);
        $fullCmd = $cmdData['command'] . ' 2>&1';

        $startTime = microtime(true);
        $outputLines = [];
        $returnVar = 0;
        exec($fullCmd, $outputLines, $returnVar);
        $duration = round(microtime(true) - $startTime, 2);

        if ($returnVar !== 0 || !file_exists($outputPath) || filesize($outputPath) === 0) {
            return [
                'success' => false,
                'error' => 'FFmpeg conversion failed: ' . implode("\n", array_slice($outputLines, -10)),
                'command' => $cmdData['command'],
                'duration' => $duration
            ];
        }

        return [
            'success' => true,
            'duration' => $duration,
            'output_file' => $outputPath,
            'output_size' => filesize($outputPath),
            'command' => $cmdData['command'],
            'mode' => $mode,
            'dimensions' => "{$targetWidth}x{$targetHeight}"
        ];
    }
}

// ==========================================
// CLI MODE HANDLER
// ==========================================
if (php_sapi_name() === 'cli') {
    $shortopts = "i:o:m:r:ch";
    $longopts = [
        "input:",
        "output:",
        "mode:",
        "res:",
        "command-only",
        "help"
    ];
    $options = getopt($shortopts, $longopts);

    if (isset($options['h']) || isset($options['help'])) {
        echo "\n=== Video Resolution Converter (16:9 to 9:16) ===\n";
        echo "Convert 3840x2160 (4K UHD 16:9) to 2160x3840 (4K UHD 9:16) vertical video.\n\n";
        echo "Usage: php video_resolution_change.php [options]\n\n";
        echo "Options:\n";
        echo "  -i, --input <file>         Input video file path\n";
        echo "  -o, --output <file>        Output video file path (default: output_2160x3840_9_16.mp4)\n";
        echo "  -m, --mode <mode>          Conversion mode:\n";
        echo "                               blur    : Blurred background with original 16:9 centered (default)\n";
        echo "                               crop    : Center-crop to 9:16 (full vertical screen)\n";
        echo "                               fit     : Pillarbox (black bars top/bottom, no crop)\n";
        echo "                               stretch : Direct scaling to target dimensions\n";
        echo "  -r, --res <resolution>     Target resolution:\n";
        echo "                               2160x3840 (default - 4K Ultra HD vertical)\n";
        echo "                               1080x1920 (Full HD Reels/Shorts/TikTok)\n";
        echo "                               720x1280  (HD vertical)\n";
        echo "  -c, --command-only         Print the exact FFmpeg command without executing\n";
        echo "  -h, --help                 Show this help message\n\n";
        echo "Examples:\n";
        echo "  php video_resolution_change.php -i input_4k.mp4 -o out_vertical_4k.mp4 -m blur -r 2160x3840\n";
        echo "  php video_resolution_change.php -i input_4k.mp4 -o out_crop_4k.mp4 -m crop -r 2160x3840\n";
        echo "  php video_resolution_change.php -i 3840x2160.mp4 -c\n\n";
        exit(0);
    }

    $inputFile = $options['i'] ?? $options['input'] ?? null;
    $outputFile = $options['o'] ?? $options['output'] ?? null;

    if (!$inputFile) {
        for ($idx = 1; $idx < count($argv); $idx++) {
            $arg = $argv[$idx];
            if ($arg !== '' && $arg[0] === '-') {
                if (in_array($arg, ['-i', '-o', '-m', '-r', '--input', '--output', '--mode', '--res'])) {
                    $idx++;
                }
                continue;
            }
            if (!$inputFile) {
                $inputFile = $arg;
            } elseif (!$outputFile) {
                $outputFile = $arg;
            }
        }
    }
    if (!$outputFile) {
        $outputFile = 'output_2160x3840_9_16.mp4';
    }
    $mode = strtolower($options['m'] ?? $options['mode'] ?? 'blur');
    $res = $options['r'] ?? $options['res'] ?? '2160x3840';
    $commandOnly = isset($options['c']) || isset($options['command-only']);

    if (!$inputFile) {
        echo "Video Resolution Converter (16:9 to 9:16)\n";
        echo "No input file specified. Run with --help for full usage instructions.\n\n";
        echo "Default 3840x2160 -> 2160x3840 (9:16) command:\n";
        $cmdData = VideoResolutionConverter::buildCommand('input_3840x2160.mp4', 'output_2160x3840_9_16.mp4', 'blur', 2160, 3840);
        echo "Command:\n" . $cmdData['command'] . "\n\n";
        exit(1);
    }

    $resParts = explode('x', strtolower($res));
    $targetW = isset($resParts[0]) ? (int)$resParts[0] : 2160;
    $targetH = isset($resParts[1]) ? (int)$resParts[1] : 3840;

    $cmdData = VideoResolutionConverter::buildCommand($inputFile, $outputFile, $mode, $targetW, $targetH);

    if ($commandOnly) {
        echo $cmdData['command'] . PHP_EOL;
        exit(0);
    }

    echo "Converting video...\n";
    echo "  Input      : {$inputFile}\n";
    echo "  Output     : {$outputFile}\n";
    echo "  Mode       : {$mode}\n";
    echo "  Resolution : {$targetW}x{$targetH} (9:16)\n";

    $result = VideoResolutionConverter::convert($inputFile, $outputFile, $mode, $targetW, $targetH);
    if ($result['success']) {
        echo "Successfully converted in {$result['duration']}s!\n";
        echo "Output saved to: {$outputFile} (" . round($result['output_size'] / (1024 * 1024), 2) . " MB)\n";
        exit(0);
    } else {
        echo "Error: " . $result['error'] . PHP_EOL;
        exit(1);
    }
}

// ==========================================
// HTTP REQUEST & UPLOAD HANDLER
// ==========================================
$tempStorageDir = sys_get_temp_dir() . '/video_converter';
if (!is_dir($tempStorageDir)) {
    @mkdir($tempStorageDir, 0777, true);
}

// Clean up files older than 2 hours in tempStorageDir
if (is_dir($tempStorageDir)) {
    $files = @glob($tempStorageDir . '/*');
    if ($files) {
        $now = time();
        foreach ($files as $f) {
            if (is_file($f) && ($now - filemtime($f)) > 7200) {
                @unlink($f);
            }
        }
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. Download Converted Video
if ($action === 'download') {
    $fileId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id'] ?? '');
    $filePath = $tempStorageDir . '/' . $fileId . '.mp4';
    if ($fileId && file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: video/mp4');
        header('Content-Disposition: attachment; filename="converted_2160x3840_9_16.mp4"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        echo 'File not found or expired. Please upload and convert again.';
        exit;
    }
}

// 2. Stream / Play Video Preview
if ($action === 'preview') {
    $fileId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id'] ?? '');
    $filePath = $tempStorageDir . '/' . $fileId . '.mp4';
    if ($fileId && file_exists($filePath)) {
        header('Content-Type: video/mp4');
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        exit;
    }
}

// 3. Chunked Upload Slice Handler
if ($action === 'upload_chunk') {
    header('Content-Type: application/json');
    $uploadId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['upload_id'] ?? '');
    $chunkIndex = (int)($_POST['chunk_index'] ?? 0);
    $totalChunks = (int)($_POST['total_chunks'] ?? 1);

    if (empty($uploadId)) {
        echo json_encode(['success' => false, 'error' => 'Missing upload ID']);
        exit;
    }

    if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Chunk upload failed: ' . ($_FILES['chunk']['error'] ?? 'missing')]);
        exit;
    }

    $chunkFile = $tempStorageDir . '/' . $uploadId . '.part';
    $in = fopen($_FILES['chunk']['tmp_name'], 'rb');
    $out = fopen($chunkFile, $chunkIndex === 0 ? 'wb' : 'ab');
    if ($in && $out) {
        while (!feof($in)) {
            fwrite($out, fread($in, 8192));
        }
        fclose($in);
        fclose($out);
        echo json_encode(['success' => true, 'chunk' => $chunkIndex, 'total' => $totalChunks]);
    } else {
        if ($in) fclose($in);
        if ($out) fclose($out);
        echo json_encode(['success' => false, 'error' => 'Unable to write chunk to temporary disk']);
    }
    exit;
}

// 4. Chunked Upload Finish & Convert Handler
if ($action === 'convert_chunked') {
    header('Content-Type: application/json');
    $uploadId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['upload_id'] ?? '');
    $mode = $_POST['mode'] ?? 'blur';
    $resolution = $_POST['resolution'] ?? '2160x3840';

    if (!in_array($mode, ['blur', 'crop', 'fit', 'stretch'])) {
        $mode = 'blur';
    }

    $resParts = explode('x', strtolower($resolution));
    $targetW = isset($resParts[0]) ? (int)$resParts[0] : 2160;
    $targetH = isset($resParts[1]) ? (int)$resParts[1] : 3840;

    $partFile = $tempStorageDir . '/' . $uploadId . '.part';
    if (!file_exists($partFile) || filesize($partFile) === 0) {
        echo json_encode(['success' => false, 'error' => 'Uploaded file chunks missing or empty']);
        exit;
    }

    $inputFile = $tempStorageDir . '/' . $uploadId . '_in.mp4';
    rename($partFile, $inputFile);

    $uniqueId = 'converted_' . bin2hex(random_bytes(8));
    $outputFile = $tempStorageDir . '/' . $uniqueId . '.mp4';

    $result = VideoResolutionConverter::convert($inputFile, $outputFile, $mode, $targetW, $targetH);
    @unlink($inputFile);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'download_url' => '?action=download&id=' . $uniqueId,
            'preview_url' => '?action=preview&id=' . $uniqueId,
            'file_size' => round(filesize($outputFile) / (1024 * 1024), 2) . ' MB',
            'duration' => $result['duration'] . 's',
            'mode' => $mode,
            'resolution' => "{$targetW}x{$targetH}"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'command' => $result['command'] ?? ''
        ]);
    }
    exit;
}

// 5. Standard Direct Upload & Convert Handler
if ($action === 'convert') {
    header('Content-Type: application/json');

    $mode = $_POST['mode'] ?? 'blur';
    $resolution = $_POST['resolution'] ?? '2160x3840';

    if (!in_array($mode, ['blur', 'crop', 'fit', 'stretch'])) {
        $mode = 'blur';
    }

    $resParts = explode('x', strtolower($resolution));
    $targetW = isset($resParts[0]) ? (int)$resParts[0] : 2160;
    $targetH = isset($resParts[1]) ? (int)$resParts[1] : 3840;

    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['video']['error'] ?? 'No file uploaded';
        echo json_encode(['success' => false, 'error' => 'Upload error: ' . $errorCode]);
        exit;
    }

    $uploadedTmp = $_FILES['video']['tmp_name'];
    $uniqueId = 'converted_' . bin2hex(random_bytes(8));
    $outputFile = $tempStorageDir . '/' . $uniqueId . '.mp4';

    $result = VideoResolutionConverter::convert($uploadedTmp, $outputFile, $mode, $targetW, $targetH);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'download_url' => '?action=download&id=' . $uniqueId,
            'preview_url' => '?action=preview&id=' . $uniqueId,
            'file_size' => round(filesize($outputFile) / (1024 * 1024), 2) . ' MB',
            'duration' => $result['duration'] . 's',
            'mode' => $mode,
            'resolution' => "{$targetW}x{$targetH}"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'command' => $result['command'] ?? ''
        ]);
    }
    exit;
}

// ==========================================
// WEB UI RENDERING
// ==========================================
$pageTitle = "Video Resolution Converter - Convert to 2160x3840 9:16 Vertical Video";
$metaDescription = "Upload your 3840x2160 (16:9 4K UHD) video and convert it directly to 2160x3840 (9:16) vertical video for TikTok, Instagram Reels, and YouTube Shorts. Download the converted video instantly.";
$metaKeywords = "convert to 2160x3840, 3840x2160 to 2160x3840, 16:9 to 9:16, 4k 9:16 converter, video resolution converter, reels 4k converter, tiktok 4k, vertical video download";

include 'includes/header.php';
$ffmpegDetected = VideoResolutionConverter::findFfmpeg();
?>

<section aria-labelledby="video-converter-title">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 8px;">
    <h1 id="video-converter-title" style="margin: 0; font-size: 1.85rem;">Convert 16:9 Video to 2160&times;3840 (9:16)</h1>
    <?php if ($ffmpegDetected): ?>
      <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; padding: 4px 12px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; border-radius: 9999px; font-weight: 600;">
        <span style="display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></span>
        FFmpeg Ready on Server
      </span>
    <?php else: ?>
      <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; padding: 4px 12px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; border-radius: 9999px; font-weight: 600;">
        <span style="display: inline-block; width: 8px; height: 8px; background: #f59e0b; border-radius: 50%;"></span>
        FFmpeg CLI Command Generator
      </span>
    <?php endif; ?>
  </div>

  <p class="tool-desc" style="color: var(--text-color, #475569); margin-top: 4px; margin-bottom: 24px; font-size: 1.05rem;">
    Upload your widescreen video (e.g., <strong>3840&times;2160 4K UHD 16:9</strong>), convert it to <strong>2160&times;3840 9:16 Vertical Video</strong>, and download the finished file ready for Instagram Reels, TikTok, and YouTube Shorts.
  </p>

  <!-- Interactive Converter Card -->
  <div class="content-card" style="padding: 24px; background: var(--card-bg, #ffffff); border-radius: 12px; border: 1px solid var(--border-color, #e2e8f0); margin-bottom: 25px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;" class="converter-grid">
      
      <!-- Left Column: Settings & Upload -->
      <div>
        <div style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color, #1e293b); font-size: 1rem;">
            1. Upload Video File:
          </label>
          <div id="dropZone" style="border: 2px dashed #3b82f6; border-radius: 12px; padding: 28px 16px; text-align: center; background: var(--input-bg, #f8fafc); cursor: pointer; transition: all 0.2s ease;">
            <svg style="width: 44px; height: 44px; margin: 0 auto 10px; display: block; color: var(--primary-color, #3b82f6);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p style="margin: 0 0 6px 0; font-weight: 700; color: var(--text-color, #1e293b); font-size: 1.05rem;">Click to choose file or drag & drop video</p>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted, #64748b);">Supports 3840&times;2160 4K UHD, 1080p (MP4, MOV, WebM, MKV)</p>
            <input type="file" id="videoFileInput" accept="video/mp4,video/quicktime,video/webm,video/x-matroska" style="display: none;">
          </div>
          
          <div id="fileInfoBadge" style="display: none; margin-top: 12px; padding: 10px 14px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; font-size: 0.9rem; color: #1e40af;">
            <div style="font-weight: 700;" id="fileNameDisplay"></div>
            <div style="margin-top: 4px; font-size: 0.82rem; color: #3b82f6;" id="fileMetaDisplay">Detecting video resolution & size...</div>
          </div>
        </div>

        <div style="margin-bottom: 20px;">
          <label for="targetResolution" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color, #1e293b); font-size: 0.95rem;">
            2. Target Vertical Resolution (9:16):
          </label>
          <select id="targetResolution" style="width: 100%; padding: 11px; border-radius: 8px; border: 1px solid var(--border-color, #cbd5e1); background: var(--input-bg, #ffffff); font-size: 0.95rem; font-weight: 600; color: var(--text-color, #1e293b);">
            <option value="2160x3840" selected>2160 &times; 3840 (4K UHD 9:16 - Ultra High Quality Vertical)</option>
            <option value="1080x1920">1080 &times; 1920 (Full HD 9:16 - Instagram Reels / Shorts / TikTok)</option>
            <option value="720x1280">720 &times; 1280 (HD 9:16 - Compact / Fast)</option>
            <option value="custom">Custom Dimensions...</option>
          </select>
          
          <div id="customResInputs" style="display: none; margin-top: 10px; gap: 10px; align-items: center;">
            <input type="number" id="customWidth" placeholder="Width" value="2160" style="flex: 1; padding: 9px; border-radius: 6px; border: 1px solid var(--border-color, #cbd5e1); font-size: 0.9rem;">
            <span>&times;</span>
            <input type="number" id="customHeight" placeholder="Height" value="3840" style="flex: 1; padding: 9px; border-radius: 6px; border: 1px solid var(--border-color, #cbd5e1); font-size: 0.9rem;">
          </div>
        </div>

        <div style="margin-bottom: 22px;">
          <label for="conversionMode" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color, #1e293b); font-size: 0.95rem;">
            3. Conversion Mode:
          </label>
          <select id="conversionMode" style="width: 100%; padding: 11px; border-radius: 8px; border: 1px solid var(--border-color, #cbd5e1); background: var(--input-bg, #ffffff); font-size: 0.95rem; color: var(--text-color, #1e293b);">
            <option value="blur" selected>Blurred Background (Most Popular for Reels/Shorts)</option>
            <option value="crop">Center Crop (Fills full 2160x3840 screen, trims sides)</option>
            <option value="fit">Fit / Pillarbox (Black bars on top & bottom, no crop)</option>
            <option value="stretch">Direct Stretch (Scale to 9:16 without crop)</option>
          </select>
          <p id="modeDescription" style="margin: 6px 0 0 0; font-size: 0.84rem; color: var(--text-muted, #64748b);">
            Keeps the full 16:9 frame centered with a stylized blurred background filling the 2160&times;3840 canvas.
          </p>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
          <button type="button" id="startConvertBtn" style="background-color: #2563eb; color: white; border: none; padding: 12px 26px; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(37,99,235,0.3);">
            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Convert to 2160&times;3840 (9:16)
          </button>
          
          <button type="button" id="copyFfmpegBtn" style="background-color: var(--card-bg, #f1f5f9); color: var(--text-color, #1e293b); border: 1px solid var(--border-color, #cbd5e1); padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
            Copy FFmpeg Command
          </button>
        </div>

        <!-- Upload & Conversion Progress Bar -->
        <div id="progressWrapper" style="display: none; margin-top: 18px;">
          <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color, #1e293b);">
            <span id="progressStatusText">Uploading video...</span>
            <span id="progressPercent">0%</span>
          </div>
          <div style="width: 100%; height: 10px; background: #e2e8f0; border-radius: 9999px; overflow: hidden;">
            <div id="progressBar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #3b82f6, #10b981); transition: width 0.2s ease;"></div>
          </div>
        </div>

        <!-- Success Result & Download Box -->
        <div id="resultBox" style="display: none; margin-top: 20px; padding: 20px; border-radius: 10px; background: #f0fdf4; border: 1px solid #86efac;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
            <svg style="width: 24px; height: 24px; color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 style="margin: 0; color: #15803d; font-size: 1.15rem;">Conversion Successful!</h3>
          </div>
          <p id="resultDetails" style="margin: 0 0 14px 0; color: #166534; font-size: 0.9rem;"></p>
          <a id="downloadLinkBtn" href="#" style="display: inline-flex; align-items: center; gap: 8px; background: #16a34a; color: white; padding: 10px 22px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 0.95rem; box-shadow: 0 2px 5px rgba(22,163,74,0.3);">
            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download 2160&times;3840 (9:16) Video
          </a>
        </div>

        <!-- Error Box -->
        <div id="errorBox" style="display: none; margin-top: 20px; padding: 16px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 0.9rem;">
          <strong id="errorMsgTitle">Error:</strong> <span id="errorMsgText"></span>
        </div>
      </div>

      <!-- Right Column: Visual Transformation Preview -->
      <div>
        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color, #1e293b); font-size: 0.95rem;">
          Visual 9:16 Aspect Ratio Preview:
        </label>
        
        <div style="background: var(--input-bg, #0f172a); border-radius: 12px; padding: 20px; color: #f8fafc; text-align: center; min-height: 320px; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
          
          <!-- Mockup Canvas (Ratio 9:16 -> 180px x 320px) -->
          <div id="visualizerCanvas" style="position: relative; width: 180px; height: 320px; background: #1e293b; border: 2px solid #38bdf8; border-radius: 14px; overflow: hidden; box-shadow: 0 12px 30px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;">
            
            <!-- Background Layer (Blur mode) -->
            <div id="visBgLayer" style="position: absolute; inset: 0; background: linear-gradient(135deg, #1e3a8a, #0369a1); filter: blur(12px); opacity: 0.9; transform: scale(1.4); transition: all 0.3s ease;"></div>
            
            <!-- Foreground Layer (16:9 Box) -->
            <div id="visFgLayer" style="position: relative; z-index: 2; width: 100%; height: 101px; background: linear-gradient(135deg, #3b82f6, #06b6d4); display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.4); border-radius: 4px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.4);">
              <span id="visFgText" style="font-size: 0.72rem; font-weight: 700; color: #ffffff; letter-spacing: 0.5px;">ORIGINAL 16:9</span>
              <span id="visFgDim" style="font-size: 0.62rem; color: #e0f2fe; margin-top: 2px;">3840 &times; 2160 (4K UHD)</span>
            </div>

            <!-- Crop outline for Crop mode -->
            <div id="visCropOverlay" style="position: absolute; inset: 0; display: none; z-index: 3; border: 2px dashed #f59e0b; pointer-events: none;">
              <span style="position: absolute; bottom: 6px; left: 6px; font-size: 0.6rem; color: #fef08a; background: rgba(0,0,0,0.7); padding: 2px 6px; border-radius: 4px;">Center Crop</span>
            </div>
          </div>

          <div style="margin-top: 14px; font-size: 0.85rem; color: #94a3b8;">
            Mode: <strong id="visModeLabel" style="color: #38bdf8;">Blurred Background</strong> &bull; Canvas: <strong id="visCanvasRes" style="color: #38bdf8;">2160&times;3840 (9:16)</strong>
          </div>
        </div>

        <!-- Converted Video Player Preview -->
        <div id="convertedVideoContainer" style="display: none; margin-top: 15px; text-align: center;">
          <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; color: #16a34a;">
            Preview Converted 2160&times;3840 Video:
          </label>
          <video id="convertedVideoPlayer" controls playsinline style="width: 100%; max-height: 260px; border-radius: 10px; background: #000; border: 1px solid #86efac;"></video>
        </div>

        <!-- Source Video Player Preview -->
        <div id="sourceVideoContainer" style="display: none; margin-top: 15px; text-align: center;">
          <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.85rem; color: var(--text-muted, #64748b);">
            Uploaded Source Video Preview:
          </label>
          <video id="sourceVideoPlayer" controls playsinline style="width: 100%; max-height: 180px; border-radius: 8px; background: #000; border: 1px solid var(--border-color, #cbd5e1);"></video>
        </div>
      </div>

    </div>

    <!-- Generated FFmpeg Command Output Box -->
    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color, #e2e8f0);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <label for="ffmpegCommandBox" style="font-weight: 600; color: var(--text-color, #1e293b); font-size: 0.95rem;">
          Generated FFmpeg Command (2160&times;3840 9:16):
        </label>
        <span id="copiedTooltip" style="font-size: 0.82rem; color: #059669; font-weight: 600; display: none;">Copied to clipboard!</span>
      </div>
      <textarea id="ffmpegCommandBox" readonly rows="3" style="width: 100%; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.85rem; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color, #cbd5e1); background: var(--input-bg, #0f172a); color: #38bdf8; box-sizing: border-box; resize: vertical;" aria-label="Generated FFmpeg Command"></textarea>
    </div>

  </div>
</section>

<!-- About & Documentation Card -->
<div class="content-card" style="margin-top: 30px; padding: 30px; background: var(--card-bg, #ffffff); border-radius: 12px; border: 1px solid var(--border-color, #e2e8f0);">
  <h2 style="color: var(--text-color, #1e293b); margin-bottom: 20px; font-size: 1.5em;">Why Convert 3840&times;2160 to 2160&times;3840 (9:16)?</h2>
  
  <p style="color: var(--text-color, #475569); line-height: 1.8; margin-bottom: 16px;">
    Standard 4K Ultra HD cameras, drones, and video cameras capture video at <strong>3840 &times; 2160 pixels</strong> in landscape <strong>16:9</strong> widescreen. When publishing to vertical mobile feeds such as <strong>Instagram Reels, TikTok, YouTube Shorts, and Facebook Reels</strong>, videos must be formatted in portrait <strong>9:16</strong>.
  </p>

  <p style="color: var(--text-color, #475569); line-height: 1.8; margin-bottom: 20px;">
    By converting to <strong>2160 &times; 3840 pixels</strong>, you maintain full native 4K vertical resolution without downscaling quality:
  </p>

  <ul style="color: var(--text-color, #475569); line-height: 1.8; margin-bottom: 20px;">
    <li><strong>Widescreen 4K UHD:</strong> 3840 &times; 2160 pixels (16:9 landscape)</li>
    <li><strong>Vertical 4K UHD:</strong> 2160 &times; 3840 pixels (9:16 portrait)</li>
    <li><strong>Full HD Vertical:</strong> 1080 &times; 1920 pixels (9:16 portrait)</li>
  </ul>

  <h3 style="color: var(--text-color, #1e293b); margin: 25px 0 15px; font-size: 1.25em;">How the Conversion Works</h3>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 25px;">
    <div style="padding: 16px; background: var(--input-bg, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px;">
      <h4 style="margin: 0 0 8px 0; color: #2563eb;">Blurred Background (Recommended)</h4>
      <p style="margin: 0; font-size: 0.9rem; color: var(--text-color, #475569); line-height: 1.6;">
        Centers the full 16:9 frame over an upscaled, dynamically blurred version of the video filling the 2160&times;3840 screen. No part of your video is cropped out.
      </p>
    </div>

    <div style="padding: 16px; background: var(--input-bg, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px;">
      <h4 style="margin: 0 0 8px 0; color: #d97706;">Center Crop</h4>
      <p style="margin: 0; font-size: 0.9rem; color: var(--text-color, #475569); line-height: 1.6;">
        Crops a 9:16 vertical window ($1215 \times 2160$) from the center of the video and scales it to 2160&times;3840. Completely fills mobile screens with zero bars.
      </p>
    </div>

    <div style="padding: 16px; background: var(--input-bg, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px;">
      <h4 style="margin: 0 0 8px 0; color: #475569;">Fit / Pillarbox</h4>
      <p style="margin: 0; font-size: 0.9rem; color: var(--text-color, #475569); line-height: 1.6;">
        Maintains 100% of the original video with clean black bars added to top and bottom to make the canvas 2160&times;3840.
      </p>
    </div>
  </div>

  <h3 style="color: var(--text-color, #1e293b); margin: 25px 0 15px; font-size: 1.25em;">CLI Command Example</h3>
  <pre style="background: #0f172a; color: #38bdf8; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 0.9rem;"><code># Convert directly to 2160x3840 4K 9:16 vertical video
php video_resolution_change.php -i my_input_4k.mp4 -o my_reel_4k.mp4 -m blur -r 2160x3840</code></pre>
</div>

<!-- Responsive Layout Styling -->
<style>
  @media (max-width: 768px) {
    .converter-grid {
      grid-template-columns: 1fr !important;
    }
  }
  #dropZone:hover {
    border-color: #2563eb !important;
    background-color: rgba(37, 99, 235, 0.05) !important;
  }
</style>

<!-- Client-side Interactive Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('videoFileInput');
  const fileInfoBadge = document.getElementById('fileInfoBadge');
  const fileNameDisplay = document.getElementById('fileNameDisplay');
  const fileMetaDisplay = document.getElementById('fileMetaDisplay');
  const conversionMode = document.getElementById('conversionMode');
  const modeDescription = document.getElementById('modeDescription');
  const targetResolution = document.getElementById('targetResolution');
  const customResInputs = document.getElementById('customResInputs');
  const customWidth = document.getElementById('customWidth');
  const customHeight = document.getElementById('customHeight');
  const ffmpegCommandBox = document.getElementById('ffmpegCommandBox');
  const copyFfmpegBtn = document.getElementById('copyFfmpegBtn');
  const copiedTooltip = document.getElementById('copiedTooltip');
  const startConvertBtn = document.getElementById('startConvertBtn');
  
  const progressWrapper = document.getElementById('progressWrapper');
  const progressStatusText = document.getElementById('progressStatusText');
  const progressPercent = document.getElementById('progressPercent');
  const progressBar = document.getElementById('progressBar');
  
  const resultBox = document.getElementById('resultBox');
  const resultDetails = document.getElementById('resultDetails');
  const downloadLinkBtn = document.getElementById('downloadLinkBtn');
  const errorBox = document.getElementById('errorBox');
  const errorMsgText = document.getElementById('errorMsgText');

  const sourceVideoContainer = document.getElementById('sourceVideoContainer');
  const sourceVideoPlayer = document.getElementById('sourceVideoPlayer');
  const convertedVideoContainer = document.getElementById('convertedVideoContainer');
  const convertedVideoPlayer = document.getElementById('convertedVideoPlayer');

  // Visualizer elements
  const visBgLayer = document.getElementById('visBgLayer');
  const visFgLayer = document.getElementById('visFgLayer');
  const visFgDim = document.getElementById('visFgDim');
  const visCropOverlay = document.getElementById('visCropOverlay');
  const visModeLabel = document.getElementById('visModeLabel');
  const visCanvasRes = document.getElementById('visCanvasRes');

  let currentFile = null;
  let detectedWidth = 3840;
  let detectedHeight = 2160;

  // File drag & drop
  dropZone.addEventListener('click', () => fileInput.click());
  dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#2563eb';
  });
  dropZone.addEventListener('dragleave', () => {
    dropZone.style.borderColor = '#3b82f6';
  });
  dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#3b82f6';
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      handleFileSelected(e.dataTransfer.files[0]);
    }
  });

  fileInput.addEventListener('change', (e) => {
    if (e.target.files && e.target.files.length > 0) {
      handleFileSelected(e.target.files[0]);
    }
  });

  function handleFileSelected(file) {
    currentFile = file;
    fileNameDisplay.textContent = file.name;
    const mbSize = (file.size / (1024 * 1024)).toFixed(2);
    fileMetaDisplay.textContent = `Size: ${mbSize} MB | Detecting resolution...`;
    fileInfoBadge.style.display = 'block';

    resultBox.style.display = 'none';
    errorBox.style.display = 'none';
    convertedVideoContainer.style.display = 'none';

    // Preview source video in browser
    const fileUrl = URL.createObjectURL(file);
    sourceVideoPlayer.src = fileUrl;
    sourceVideoContainer.style.display = 'block';

    sourceVideoPlayer.onloadedmetadata = function() {
      detectedWidth = sourceVideoPlayer.videoWidth;
      detectedHeight = sourceVideoPlayer.videoHeight;
      const is16by9 = Math.abs((detectedWidth / detectedHeight) - (16 / 9)) < 0.05;
      const tag = is16by9 ? '(16:9 Widescreen)' : '';
      fileMetaDisplay.textContent = `Resolution: ${detectedWidth} × ${detectedHeight} ${tag} | Size: ${mbSize} MB`;
      visFgDim.textContent = `${detectedWidth} × ${detectedHeight} ${tag}`;
      updateCommandAndPreview();
    };

    updateCommandAndPreview();
  }

  // Update controls
  conversionMode.addEventListener('change', updateCommandAndPreview);
  targetResolution.addEventListener('change', function() {
    if (this.value === 'custom') {
      customResInputs.style.display = 'flex';
    } else {
      customResInputs.style.display = 'none';
    }
    updateCommandAndPreview();
  });
  customWidth.addEventListener('input', updateCommandAndPreview);
  customHeight.addEventListener('input', updateCommandAndPreview);

  function getTargetDimensions() {
    const val = targetResolution.value;
    if (val === 'custom') {
      const w = parseInt(customWidth.value) || 2160;
      const h = parseInt(customHeight.value) || 3840;
      return { width: w, height: h };
    }
    const parts = val.split('x');
    return { width: parseInt(parts[0]), height: parseInt(parts[1]) };
  }

  function updateCommandAndPreview() {
    const mode = conversionMode.value;
    const { width: targetW, height: targetH } = getTargetDimensions();
    const inName = currentFile ? currentFile.name : 'input_3840x2160.mp4';
    const outName = 'output_' + targetW + 'x' + targetH + '_9_16.mp4';

    visCanvasRes.textContent = `${targetW}×${targetH} (9:16)`;

    let filter = '';
    let cmd = '';

    if (mode === 'blur') {
      modeDescription.textContent = `Keeps the full 16:9 frame centered with a stylized blurred background filling the ${targetW}×${targetH} canvas.`;
      visModeLabel.textContent = 'Blurred Background';
      visBgLayer.style.display = 'block';
      visBgLayer.style.filter = 'blur(12px)';
      visBgLayer.style.opacity = '0.9';
      visFgLayer.style.width = '100%';
      visFgLayer.style.height = '101px';
      visFgLayer.style.background = 'linear-gradient(135deg, #3b82f6, #06b6d4)';
      visCropOverlay.style.display = 'none';

      filter = `[0:v]scale=${targetW}:${targetH}:force_original_aspect_ratio=increase,crop=${targetW}:${targetH},boxblur=25:8[bg];[0:v]scale=${targetW}:-2,setsar=1[fg];[bg][fg]overlay=(W-w)/2:(H-h)/2,setsar=1`;
      cmd = `ffmpeg -i "${inName}" -filter_complex "${filter}" -c:v libx264 -preset veryfast -crf 22 -c:a aac -b:a 192k -movflags +faststart -y "${outName}"`;

    } else if (mode === 'crop') {
      modeDescription.textContent = `Crops the left and right sides of the 16:9 video to fill the full ${targetW}×${targetH} vertical canvas with zero borders.`;
      visModeLabel.textContent = 'Center Crop';
      visBgLayer.style.display = 'none';
      visFgLayer.style.width = '100%';
      visFgLayer.style.height = '100%';
      visFgLayer.style.background = 'linear-gradient(135deg, #3b82f6, #06b6d4)';
      visCropOverlay.style.display = 'block';

      filter = `crop=ih*9/16:ih:(iw-ih*9/16)/2:0,scale=${targetW}:${targetH},setsar=1`;
      cmd = `ffmpeg -i "${inName}" -vf "${filter}" -c:v libx264 -preset veryfast -crf 22 -c:a aac -b:a 192k -movflags +faststart -y "${outName}"`;

    } else if (mode === 'fit') {
      modeDescription.textContent = `Maintains 100% of the 16:9 frame with clean black pillarbox bars on top and bottom to fill ${targetW}×${targetH}.`;
      visModeLabel.textContent = 'Fit with Black Bars';
      visBgLayer.style.display = 'block';
      visBgLayer.style.filter = 'none';
      visBgLayer.style.opacity = '1';
      visBgLayer.style.background = '#000000';
      visFgLayer.style.width = '100%';
      visFgLayer.style.height = '101px';
      visFgLayer.style.background = 'linear-gradient(135deg, #3b82f6, #06b6d4)';
      visCropOverlay.style.display = 'none';

      filter = `scale=${targetW}:-2,pad=${targetW}:${targetH}:(ow-iw)/2:(oh-ih)/2:black,setsar=1`;
      cmd = `ffmpeg -i "${inName}" -vf "${filter}" -c:v libx264 -preset veryfast -crf 22 -c:a aac -b:a 192k -movflags +faststart -y "${outName}"`;

    } else if (mode === 'stretch') {
      modeDescription.textContent = `Forces direct scaling to ${targetW}×${targetH} dimensions (video will appear vertically stretched).`;
      visModeLabel.textContent = 'Direct Stretch';
      visBgLayer.style.display = 'none';
      visFgLayer.style.width = '100%';
      visFgLayer.style.height = '100%';
      visCropOverlay.style.display = 'none';

      filter = `scale=${targetW}:${targetH},setsar=1`;
      cmd = `ffmpeg -i "${inName}" -vf "${filter}" -c:v libx264 -preset veryfast -crf 22 -c:a aac -b:a 192k -movflags +faststart -y "${outName}"`;
    }

    ffmpegCommandBox.value = cmd;
  }

  // Copy FFmpeg Command
  copyFfmpegBtn.addEventListener('click', function() {
    ffmpegCommandBox.select();
    navigator.clipboard.writeText(ffmpegCommandBox.value).then(() => {
      copiedTooltip.style.display = 'inline';
      setTimeout(() => copiedTooltip.style.display = 'none', 2500);
    });
  });

  // Start Upload and Conversion
  startConvertBtn.addEventListener('click', async function() {
    if (!currentFile) {
      alert('Please select or drag & drop a video file first.');
      fileInput.click();
      return;
    }

    const mode = conversionMode.value;
    const { width: targetW, height: targetH } = getTargetDimensions();
    const resolution = `${targetW}x${targetH}`;

    // Reset UI states
    startConvertBtn.disabled = true;
    startConvertBtn.style.opacity = '0.6';
    resultBox.style.display = 'none';
    errorBox.style.display = 'none';
    convertedVideoContainer.style.display = 'none';
    progressWrapper.style.display = 'block';
    progressBar.style.width = '0%';
    progressPercent.textContent = '0%';

    const CHUNK_SIZE = 1.5 * 1024 * 1024; // 1.5MB chunks to safely stay under small upload limits
    const totalChunks = Math.ceil(currentFile.size / CHUNK_SIZE);
    const uploadId = 'up_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);

    try {
      // Upload file in chunks
      for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
        const start = chunkIndex * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, currentFile.size);
        const chunkBlob = currentFile.slice(start, end);

        const formData = new FormData();
        formData.append('action', 'upload_chunk');
        formData.append('upload_id', uploadId);
        formData.append('chunk_index', chunkIndex);
        formData.append('total_chunks', totalChunks);
        formData.append('chunk', chunkBlob, currentFile.name);

        const uploadRes = await fetch('video_resolution_change.php', {
          method: 'POST',
          body: formData
        });

        const uploadJson = await uploadRes.json();
        if (!uploadJson.success) {
          throw new Error(uploadJson.error || 'Failed uploading video chunk ' + (chunkIndex + 1));
        }

        const pct = Math.round(((chunkIndex + 1) / totalChunks) * 100);
        progressBar.style.width = pct + '%';
        progressPercent.textContent = pct + '%';
        progressStatusText.textContent = `Uploading video... (${chunkIndex + 1}/${totalChunks} chunks)`;
      }

      // Trigger Conversion
      progressStatusText.textContent = `Converting to ${targetW}×${targetH} (9:16) with FFmpeg... Please wait.`;
      progressBar.style.width = '100%';
      progressPercent.textContent = 'Processing...';

      const convertForm = new FormData();
      convertForm.append('action', 'convert_chunked');
      convertForm.append('upload_id', uploadId);
      convertForm.append('mode', mode);
      convertForm.append('resolution', resolution);

      const convertRes = await fetch('video_resolution_change.php', {
        method: 'POST',
        body: convertForm
      });

      const convertJson = await convertRes.json();
      startConvertBtn.disabled = false;
      startConvertBtn.style.opacity = '1';
      progressWrapper.style.display = 'none';

      if (convertJson.success) {
        resultBox.style.display = 'block';
        resultDetails.innerHTML = `
          <strong>Target Resolution:</strong> ${convertJson.resolution} (9:16)<br>
          <strong>Output Size:</strong> ${convertJson.file_size} &bull; <strong>Processing Time:</strong> ${convertJson.duration} &bull; <strong>Mode:</strong> ${convertJson.mode}
        `;
        downloadLinkBtn.href = convertJson.download_url;

        // Load preview of converted video
        convertedVideoPlayer.src = convertJson.preview_url;
        convertedVideoContainer.style.display = 'block';

        // Scroll to result
        resultBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } else {
        errorBox.style.display = 'block';
        errorMsgText.textContent = convertJson.error || 'FFmpeg conversion failed.';
      }

    } catch (err) {
      startConvertBtn.disabled = false;
      startConvertBtn.style.opacity = '1';
      progressWrapper.style.display = 'none';
      errorBox.style.display = 'block';
      errorMsgText.textContent = err.message + ' (You can also run the copied FFmpeg command directly on your machine).';
    }
  });

  // Initial update
  updateCommandAndPreview();
});
</script>

<?php include 'includes/right_sidebar.php'; ?>
<?php include 'includes/footer.php'; ?>
