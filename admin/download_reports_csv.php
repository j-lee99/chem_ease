<?php
session_start();
require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || $role !== 'super_admin') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

function table_exists(mysqli $conn, string $table): bool
{
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    if (!$res) return false;
    $ok = ($res->num_rows > 0);
    $res->free();
    return $ok;
}

function col_exists(mysqli $conn, string $table, string $col): bool
{
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($col);
    $res = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    if (!$res) return false;
    $ok = ($res->num_rows > 0);
    $res->free();
    return $ok;
}

function fetch_scalar(mysqli $conn, string $sql, $default = 0)
{
    $res = $conn->query($sql);
    if (!$res) return $default;
    $row = $res->fetch_row();
    $value = $row[0] ?? $default;
    $res->free();
    return $value;
}

function safe_percent($part, $whole, int $precision = 2): float
{
    $whole = (float) $whole;
    if ($whole <= 0) return 0;
    return round((((float) $part) / $whole) * 100, $precision);
}

function xlsx_cell($value, string $type = 's', int $style = 0): array
{
    return ['v' => $value, 't' => $type, 's' => $style];
}

class SimpleXLSXWriter
{
    private array $sheets = [];

    public function addSheet(string $name, array $rows): void
    {
        $safeName = trim($name) !== '' ? $name : 'Sheet' . (count($this->sheets) + 1);
        $safeName = preg_replace('/[\\\/*?:\[\]]/', '-', $safeName);
        $safeName = mb_substr($safeName, 0, 31);
        $this->sheets[] = ['name' => $safeName, 'rows' => $rows];
    }

    public function output(string $filename): void
    {
        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            echo 'ZipArchive is required for XLSX export.';
            exit;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            echo 'Failed to create XLSX file.';
            exit;
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('docProps/core.xml', $this->coreXml());
        $zip->addFromString('docProps/app.xml', $this->appXml());

        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheetXml($sheet['rows']));
        }

        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    private function contentTypesXml(): string
    {
        $overrides = '';
        foreach ($this->sheets as $i => $_sheet) {
            $sheetNo = $i + 1;
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $sheetNo . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . $overrides
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        $sheetsXml = '';
        foreach ($this->sheets as $i => $sheet) {
            $sheetNo = $i + 1;
            $sheetsXml .= '<sheet name="' . $this->xml($sheet['name']) . '" sheetId="' . $sheetNo . '" r:id="rId' . $sheetNo . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetsXml . '</sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        $rels = '';
        foreach ($this->sheets as $i => $_sheet) {
            $sheetNo = $i + 1;
            $rels .= '<Relationship Id="rId' . $sheetNo . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetNo . '.xml"/>';
        }

        $styleId = count($this->sheets) + 1;
        $rels .= '<Relationship Id="rId' . $styleId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF17A2B8"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF2C3E50"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF7FB"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD9DEE3"/></left><right style="thin"><color rgb="FFD9DEE3"/></right><top style="thin"><color rgb="FFD9DEE3"/></top><bottom style="thin"><color rgb="FFD9DEE3"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' // 0 normal
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>' // 1 title
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>' // 2 teal header
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>' // 3 dark header
            . '<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>' // 4 light section row
            . '<xf numFmtId="10" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>' // 5 percentage
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function coreXml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>OpenAI</dc:creator>'
            . '<cp:lastModifiedBy>OpenAI</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '<dc:title>ChemEase Reports Export</dc:title>'
            . '</cp:coreProperties>';
    }

    private function appXml(): string
    {
        $titles = '';
        foreach ($this->sheets as $sheet) {
            $titles .= '<vt:lpstr>' . $this->xml($sheet['name']) . '</vt:lpstr>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Microsoft Excel</Application>'
            . '<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>' . count($this->sheets) . '</vt:i4></vt:variant></vt:vector></HeadingPairs>'
            . '<TitlesOfParts><vt:vector size="' . count($this->sheets) . '" baseType="lpstr">' . $titles . '</vt:vector></TitlesOfParts>'
            . '</Properties>';
    }

    private function sheetXml(array $rows): string
    {
        $maxCols = 1;
        foreach ($rows as $row) {
            $maxCols = max($maxCols, count($row));
        }
        $lastCell = $this->colName($maxCols) . max(1, count($rows));
        $colsXml = $this->colsXml($rows, $maxCols);

        $sheetData = '';
        foreach ($rows as $rIndex => $row) {
            $sheetData .= '<row r="' . ($rIndex + 1) . '">';
            foreach ($row as $cIndex => $cell) {
                $ref = $this->colName($cIndex + 1) . ($rIndex + 1);
                $sheetData .= $this->cellXml($ref, $cell);
            }
            $sheetData .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . $lastCell . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $colsXml
            . '<sheetData>' . $sheetData . '</sheetData>'
            . '</worksheet>';
    }

    private function colsXml(array $rows, int $maxCols): string
    {
        $widths = array_fill(1, $maxCols, 10);
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $value = is_array($cell) ? ($cell['v'] ?? '') : $cell;
                $len = mb_strlen((string)$value);
                $widths[$i + 1] = min(45, max($widths[$i + 1], $len + 2));
            }
        }

        $xml = '<cols>';
        foreach ($widths as $idx => $width) {
            $xml .= '<col min="' . $idx . '" max="' . $idx . '" width="' . $width . '" customWidth="1"/>';
        }
        $xml .= '</cols>';
        return $xml;
    }

    private function cellXml(string $ref, $cell): string
    {
        if (!is_array($cell)) {
            $cell = ['v' => $cell, 't' => is_numeric($cell) ? 'n' : 's', 's' => 0];
        }

        $value = $cell['v'] ?? '';
        $type = $cell['t'] ?? (is_numeric($value) ? 'n' : 's');
        $style = (int)($cell['s'] ?? 0);
        $styleAttr = $style > 0 ? ' s="' . $style . '"' : '';

        if ($value === null || $value === '') {
            return '<c r="' . $ref . '"' . $styleAttr . '/>';
        }

        if ($type === 'n' || $type === 'p') {
            return '<c r="' . $ref . '"' . $styleAttr . '><v>' . $this->xml((string)$value) . '</v></c>';
        }

        return '<c r="' . $ref . '" t="inlineStr"' . $styleAttr . '><is><t>' . $this->xml((string)$value) . '</t></is></c>';
    }

    private function colName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }
        return $name;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

$rangeDays = 30;
if (isset($_GET['days'])) {
    $d = (int) $_GET['days'];
    if ($d >= 7 && $d <= 365) $rangeDays = $d;
}

$hasUsers = table_exists($conn, 'users');
$hasAttempts = table_exists($conn, 'user_exam_attempts');
$hasExams = table_exists($conn, 'exams');

if (!$hasUsers) {
    http_response_code(500);
    echo 'Required table missing: users';
    exit;
}

$progressTable = null;
$progressCandidates = ['user_progress', 'user_material_progress', 'user_study_progress', 'material_progress', 'progress'];
foreach ($progressCandidates as $cand) {
    if (table_exists($conn, $cand)) {
        $progressTable = $cand;
        break;
    }
}

$hasProgress = false;
$progUserCol = null;
$progPctCol = null;
$progUpdCol = null;

if ($progressTable) {
    foreach (['user_id', 'uid', 'student_id'] as $c) {
        if (col_exists($conn, $progressTable, $c)) {
            $progUserCol = $c;
            break;
        }
    }

    foreach (['progress', 'percentage', 'percent', 'progress_percent', 'progress_pct'] as $c) {
        if (col_exists($conn, $progressTable, $c)) {
            $progPctCol = $c;
            break;
        }
    }

    foreach (['updated_at', 'last_updated', 'modified_at'] as $c) {
        if (col_exists($conn, $progressTable, $c)) {
            $progUpdCol = $c;
            break;
        }
    }

    $hasProgress = (bool) ($progUserCol && $progPctCol);
}

$totalUsers = 0;
$newUsersInRange = 0;
$activeUsersInRange = 0;
$totalFinishedAttempts = 0;
$finishedAttemptsInRange = 0;
$avgScore = 0;
$passedAttempts = 0;
$failedAttempts = 0;
$passRate = 0;
$avgProgressOverall = 0;

if (col_exists($conn, 'users', 'role')) {
    $totalUsers = (int) fetch_scalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'user'", 0);
} else {
    $totalUsers = (int) fetch_scalar($conn, "SELECT COUNT(*) FROM users", 0);
}

if (col_exists($conn, 'users', 'created_at')) {
    if (col_exists($conn, 'users', 'role')) {
        $newUsersInRange = (int) fetch_scalar(
            $conn,
            "SELECT COUNT(*) FROM users WHERE role = 'user' AND created_at >= (NOW() - INTERVAL {$rangeDays} DAY)",
            0
        );
    } else {
        $newUsersInRange = (int) fetch_scalar(
            $conn,
            "SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL {$rangeDays} DAY)",
            0
        );
    }
}

if ($hasAttempts) {
    $roleJoin = col_exists($conn, 'users', 'role') ? " INNER JOIN users u ON u.id = uea.user_id AND u.role = 'user' " : "";

    $activeUsersInRange = (int) fetch_scalar(
        $conn,
        "SELECT COUNT(DISTINCT uea.user_id)
         FROM user_exam_attempts uea
         {$roleJoin}
         WHERE uea.finished_at IS NOT NULL
           AND uea.finished_at >= (NOW() - INTERVAL {$rangeDays} DAY)",
        0
    );

    $totalFinishedAttempts = (int) fetch_scalar(
        $conn,
        "SELECT COUNT(*) FROM user_exam_attempts WHERE finished_at IS NOT NULL",
        0
    );

    $finishedAttemptsInRange = (int) fetch_scalar(
        $conn,
        "SELECT COUNT(*) FROM user_exam_attempts WHERE finished_at IS NOT NULL AND finished_at >= (NOW() - INTERVAL {$rangeDays} DAY)",
        0
    );

    $avgScore = (float) fetch_scalar(
        $conn,
        "SELECT COALESCE(AVG(score), 0) FROM user_exam_attempts WHERE finished_at IS NOT NULL AND score IS NOT NULL",
        0
    );

    if ($hasExams && col_exists($conn, 'exams', 'passing_score')) {
        $passedAttempts = (int) fetch_scalar(
            $conn,
            "SELECT COUNT(*)
             FROM user_exam_attempts uea
             INNER JOIN exams e ON e.id = uea.exam_id
             WHERE uea.finished_at IS NOT NULL
               AND uea.score IS NOT NULL
               AND e.passing_score IS NOT NULL
               AND uea.score >= e.passing_score",
            0
        );
    } else {
        $passedAttempts = (int) fetch_scalar(
            $conn,
            "SELECT COUNT(*) FROM user_exam_attempts WHERE finished_at IS NOT NULL AND score IS NOT NULL AND score >= 75",
            0
        );
    }

    $failedAttempts = max(0, $totalFinishedAttempts - $passedAttempts);
    $passRate = safe_percent($passedAttempts, $totalFinishedAttempts, 2);
}

if ($hasProgress) {
    $avgProgressOverall = (float) fetch_scalar(
        $conn,
        "SELECT COALESCE(AVG({$progPctCol}), 0) FROM {$progressTable}",
        0
    );
}

$cols = ['u.id'];
if (col_exists($conn, 'users', 'full_name')) $cols[] = 'u.full_name';
if (col_exists($conn, 'users', 'email')) $cols[] = 'u.email';
if (col_exists($conn, 'users', 'role')) $cols[] = 'u.role';
if (col_exists($conn, 'users', 'created_at')) $cols[] = 'u.created_at';

$selectCols = implode(', ', $cols);

$attemptSelect = '';
$attemptJoin = '';
if ($hasAttempts) {
    $attemptSelect = ",
        COALESCE(t_total.total_attempts, 0) total_attempts,
        COALESCE(t_rng.attempts_rng, 0) attempts_range,
        COALESCE(t_avg.avg_score, 0) avg_score,
        t_last.last_attempt_at last_attempt_at";

    $attemptJoin = "
        LEFT JOIN (
            SELECT user_id, COUNT(*) total_attempts
            FROM user_exam_attempts
            WHERE finished_at IS NOT NULL
            GROUP BY user_id
        ) t_total ON t_total.user_id = u.id

        LEFT JOIN (
            SELECT user_id, COUNT(*) attempts_rng
            FROM user_exam_attempts
            WHERE finished_at IS NOT NULL
              AND finished_at >= (NOW() - INTERVAL {$rangeDays} DAY)
            GROUP BY user_id
        ) t_rng ON t_rng.user_id = u.id

        LEFT JOIN (
            SELECT user_id, AVG(score) avg_score
            FROM user_exam_attempts
            WHERE finished_at IS NOT NULL
              AND score IS NOT NULL
            GROUP BY user_id
        ) t_avg ON t_avg.user_id = u.id

        LEFT JOIN (
            SELECT user_id, MAX(finished_at) last_attempt_at
            FROM user_exam_attempts
            WHERE finished_at IS NOT NULL
            GROUP BY user_id
        ) t_last ON t_last.user_id = u.id
    ";
}

$progSelect = '';
$progJoin = '';
if ($hasProgress) {
    $progSelect = ",
        COALESCE(p_avg.avg_progress, 0) avg_progress,
        p_last.last_progress_at last_progress_at";

    $progJoin = "
        LEFT JOIN (
            SELECT {$progUserCol} user_id, AVG({$progPctCol}) avg_progress
            FROM {$progressTable}
            GROUP BY {$progUserCol}
        ) p_avg ON p_avg.user_id = u.id
    ";

    if ($progUpdCol) {
        $progJoin .= "
            LEFT JOIN (
                SELECT {$progUserCol} user_id, MAX({$progUpdCol}) last_progress_at
                FROM {$progressTable}
                GROUP BY {$progUserCol}
            ) p_last ON p_last.user_id = u.id
        ";
    } else {
        $progJoin .= "
            LEFT JOIN (
                SELECT NULL user_id, NULL last_progress_at
            ) p_last ON 1 = 0
        ";
    }
}

$whereUsers = col_exists($conn, 'users', 'role') ? "WHERE u.role = 'user'" : '';

$sql = "
    SELECT {$selectCols}
    {$attemptSelect}
    {$progSelect}
    FROM users u
    {$attemptJoin}
    {$progJoin}
    {$whereUsers}
    ORDER BY u.id ASC
";

$res = $conn->query($sql);
if (!$res) {
    http_response_code(500);
    echo 'Query failed: ' . $conn->error;
    exit;
}

$summaryRows = [
    [xlsx_cell('ChemEase Users Activity Report', 's', 1)],
    [xlsx_cell('Range (days)', 's', 2), xlsx_cell($rangeDays, 'n', 0)],
    [xlsx_cell('Generated at', 's', 2), xlsx_cell(date('Y-m-d H:i:s'))],
    [''],
    [xlsx_cell('Summary Metric', 's', 3), xlsx_cell('Value', 's', 3)],
    [xlsx_cell('Total Users'), xlsx_cell($totalUsers, 'n')],
    [xlsx_cell("New Users ({$rangeDays}d)"), xlsx_cell($newUsersInRange, 'n')],
    [xlsx_cell("Active Users ({$rangeDays}d)"), xlsx_cell($activeUsersInRange, 'n')],
    [xlsx_cell('Total Finished Attempts'), xlsx_cell($totalFinishedAttempts, 'n')],
    [xlsx_cell("Finished Attempts ({$rangeDays}d)"), xlsx_cell($finishedAttemptsInRange, 'n')],
    [xlsx_cell('Average Score'), xlsx_cell(round($avgScore, 2), 'n')],
    [xlsx_cell('Passed Attempts'), xlsx_cell($passedAttempts, 'n')],
    [xlsx_cell('Failed Attempts'), xlsx_cell($failedAttempts, 'n')],
    [xlsx_cell('Pass Rate (%)'), xlsx_cell(round($passRate / 100, 4), 'n', 5)],
];

if ($hasProgress) {
    $summaryRows[] = [xlsx_cell('Average Progress'), xlsx_cell(round($avgProgressOverall / 100, 4), 'n', 5)];
}

$detailHeader = [xlsx_cell('id', 's', 3)];
if (in_array('u.full_name', $cols, true)) $detailHeader[] = xlsx_cell('full_name', 's', 3);
if (in_array('u.email', $cols, true)) $detailHeader[] = xlsx_cell('email', 's', 3);
if (in_array('u.role', $cols, true)) $detailHeader[] = xlsx_cell('role', 's', 3);
if (in_array('u.created_at', $cols, true)) $detailHeader[] = xlsx_cell('created_at', 's', 3);
if ($hasAttempts) {
    $detailHeader[] = xlsx_cell('total_attempts', 's', 3);
    $detailHeader[] = xlsx_cell("attempts_{$rangeDays}d", 's', 3);
    $detailHeader[] = xlsx_cell('avg_score', 's', 3);
    $detailHeader[] = xlsx_cell('last_attempt_at', 's', 3);
}
if ($hasProgress) {
    $detailHeader[] = xlsx_cell('avg_progress', 's', 3);
    $detailHeader[] = xlsx_cell('last_progress_at', 's', 3);
}

$detailRows = [
    [xlsx_cell('User Details', 's', 1)],
    $detailHeader,
];

while ($row = $res->fetch_assoc()) {
    $line = [];
    $line[] = xlsx_cell((int)($row['id'] ?? 0), 'n');

    if (isset($row['full_name'])) $line[] = xlsx_cell($row['full_name']);
    if (isset($row['email'])) $line[] = xlsx_cell($row['email']);
    if (isset($row['role'])) $line[] = xlsx_cell($row['role']);
    if (isset($row['created_at'])) $line[] = xlsx_cell((string)$row['created_at']);

    if ($hasAttempts) {
        $line[] = xlsx_cell((int)($row['total_attempts'] ?? 0), 'n');
        $line[] = xlsx_cell((int)($row['attempts_range'] ?? 0), 'n');
        $line[] = xlsx_cell(round((float)($row['avg_score'] ?? 0) / 100, 4), 'n', 5);
        $line[] = xlsx_cell((string)($row['last_attempt_at'] ?? ''));
    }

    if ($hasProgress) {
        $line[] = xlsx_cell(round((float)($row['avg_progress'] ?? 0) / 100, 4), 'n', 5);
        $line[] = xlsx_cell((string)($row['last_progress_at'] ?? ''));
    }

    $detailRows[] = $line;
}

$res->free();

$writer = new SimpleXLSXWriter();
$writer->addSheet('Summary', $summaryRows);
$writer->addSheet('User Details', $detailRows);
$writer->output('chemease_users_activity_report.xlsx');
