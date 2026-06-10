<?php

/**
 * PHPUnit JUnit XML → HTML Report Generator
 * 
 * Parses the JUnit XML output from PHPUnit and generates
 * a styled HTML test report.
 * 
 * Usage: php tests/generate_report.php
 */

$junitFile = __DIR__ . '/../build/logs/logfile.xml';
$outputFile = __DIR__ . '/../public/reports/test-report.html';

if (!file_exists($junitFile)) {
    echo "ERROR: JUnit XML file not found at: $junitFile\n";
    echo "Run PHPUnit first: php vendor/bin/phpunit --log-junit build/logs/logfile.xml\n";
    exit(1);
}

// Create output directory
$outputDir = dirname($outputFile);
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$xml = simplexml_load_file($junitFile);

$totalTests = 0;
$totalFailures = 0;
$totalErrors = 0;
$totalSkipped = 0;
$totalTime = 0;
$suites = [];

foreach ($xml->testsuite as $suite) {
    $suiteName = (string) $suite['name'];
    $suiteData = [
        'name'      => $suiteName,
        'tests'     => (int) $suite['tests'],
        'failures'  => (int) $suite['failures'],
        'errors'    => (int) $suite['errors'],
        'skipped'   => (int) ($suite['skipped'] ?? 0),
        'time'      => (float) $suite['time'],
        'testcases' => [],
    ];

    // Recursively collect test cases
    collectTestCases($suite, $suiteData['testcases']);

    $suites[] = $suiteData;
    $totalTests += $suiteData['tests'];
    $totalFailures += $suiteData['failures'];
    $totalErrors += $suiteData['errors'];
    $totalSkipped += $suiteData['skipped'];
    $totalTime += $suiteData['time'];
}

function collectTestCases($node, &$cases) {
    foreach ($node->testcase as $tc) {
        $status = 'passed';
        $message = '';

        if (isset($tc->failure)) {
            $status = 'failed';
            $message = (string) $tc->failure;
        } elseif (isset($tc->error)) {
            $status = 'error';
            $message = (string) $tc->error;
        } elseif (isset($tc->skipped)) {
            $status = 'skipped';
            $message = (string) ($tc->skipped ?? '');
        }

        $cases[] = [
            'name'   => (string) $tc['name'],
            'class'  => (string) $tc['class'],
            'file'   => (string) ($tc['file'] ?? ''),
            'line'   => (string) ($tc['line'] ?? ''),
            'time'   => (float) $tc['time'],
            'status' => $status,
            'message'=> $message,
        ];
    }

    // Recurse into nested testsuites
    foreach ($node->testsuite as $child) {
        collectTestCases($child, $cases);
    }
}

$passed = $totalTests - $totalFailures - $totalErrors - $totalSkipped;
$passRate = $totalTests > 0 ? round(($passed / $totalTests) * 100, 1) : 0;
$generatedAt = date('Y-m-d H:i:s');

// Generate HTML
$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sparkleen Laundry — Test Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { font-size: 2rem; margin-bottom: 0.5rem; background: linear-gradient(135deg, #38bdf8, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .subtitle { color: #94a3b8; margin-bottom: 2rem; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .card { background: #1e293b; border-radius: 12px; padding: 1.5rem; text-align: center; border: 1px solid #334155; }
        .card .number { font-size: 2.5rem; font-weight: 700; }
        .card .label { color: #94a3b8; font-size: 0.875rem; margin-top: 0.25rem; }
        .card.passed .number { color: #4ade80; }
        .card.failed .number { color: #f87171; }
        .card.errors .number { color: #fb923c; }
        .card.skipped .number { color: #facc15; }
        .card.total .number { color: #38bdf8; }
        .card.time .number { color: #c084fc; font-size: 1.5rem; }
        .progress-bar { background: #334155; border-radius: 999px; height: 8px; margin-bottom: 2rem; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #4ade80, #38bdf8); transition: width 0.5s; }
        .suite { background: #1e293b; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid #334155; overflow: hidden; }
        .suite-header { padding: 1rem 1.5rem; background: #1e293b; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .suite-header:hover { background: #263044; }
        .suite-name { font-weight: 600; font-size: 1.1rem; }
        .suite-stats { display: flex; gap: 1rem; font-size: 0.875rem; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-pass { background: rgba(74, 222, 128, 0.15); color: #4ade80; }
        .badge-fail { background: rgba(248, 113, 113, 0.15); color: #f87171; }
        .badge-error { background: rgba(251, 146, 60, 0.15); color: #fb923c; }
        .test-list { padding: 0; }
        .test-item { padding: 0.75rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; font-size: 0.9rem; }
        .test-item:last-child { border-bottom: none; }
        .test-item:hover { background: #1a2436; }
        .test-name { flex: 1; }
        .test-time { color: #64748b; margin-right: 1rem; font-size: 0.8rem; }
        .status-icon { width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold; margin-right: 0.75rem; flex-shrink: 0; }
        .status-passed { background: rgba(74, 222, 128, 0.2); color: #4ade80; }
        .status-failed { background: rgba(248, 113, 113, 0.2); color: #f87171; }
        .status-error { background: rgba(251, 146, 60, 0.2); color: #fb923c; }
        .status-skipped { background: rgba(250, 204, 21, 0.2); color: #facc15; }
        .failure-msg { background: #1a1625; border-left: 3px solid #f87171; padding: 0.75rem 1rem; margin: 0.5rem 1.5rem; border-radius: 0 8px 8px 0; font-family: 'Cascadia Code', monospace; font-size: 0.8rem; white-space: pre-wrap; color: #fca5a5; max-height: 200px; overflow-y: auto; }
        footer { text-align: center; color: #475569; margin-top: 3rem; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Sparkleen Laundry — Test Report</h1>
        <p class="subtitle">Generated: {$generatedAt} | Pass Rate: {$passRate}%</p>

        <div class="summary">
            <div class="card total"><div class="number">{$totalTests}</div><div class="label">Total Tests</div></div>
            <div class="card passed"><div class="number">{$passed}</div><div class="label">Passed</div></div>
            <div class="card failed"><div class="number">{$totalFailures}</div><div class="label">Failed</div></div>
            <div class="card errors"><div class="number">{$totalErrors}</div><div class="label">Errors</div></div>
            <div class="card skipped"><div class="number">{$totalSkipped}</div><div class="label">Skipped</div></div>
            <div class="card time"><div class="number">{$totalTime}s</div><div class="label">Duration</div></div>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" style="width: {$passRate}%"></div>
        </div>
HTML;

foreach ($suites as $suite) {
    $suitePassed = $suite['tests'] - $suite['failures'] - $suite['errors'] - $suite['skipped'];
    $badgeClass = $suite['failures'] > 0 || $suite['errors'] > 0 ? 'badge-fail' : 'badge-pass';
    $badgeText = $suite['failures'] > 0 || $suite['errors'] > 0
        ? "{$suite['failures']}F / {$suite['errors']}E"
        : "✓ All Passed";

    $html .= <<<SUITE
        <div class="suite">
            <div class="suite-header">
                <span class="suite-name">{$suite['name']}</span>
                <div class="suite-stats">
                    <span class="badge {$badgeClass}">{$badgeText}</span>
                    <span>{$suitePassed}/{$suite['tests']} passed</span>
                    <span>{$suite['time']}s</span>
                </div>
            </div>
            <div class="test-list">
SUITE;

    foreach ($suite['testcases'] as $tc) {
        $icon = match($tc['status']) {
            'passed'  => '<span class="status-icon status-passed">✓</span>',
            'failed'  => '<span class="status-icon status-failed">✗</span>',
            'error'   => '<span class="status-icon status-error">!</span>',
            'skipped' => '<span class="status-icon status-skipped">⊘</span>',
        };
        $timeFormatted = round($tc['time'], 4);
        $displayName = preg_replace('/^test/', '', $tc['name']);
        $displayName = preg_replace('/([A-Z])/', ' $1', $displayName);
        $displayName = trim($displayName);

        $html .= "<div class=\"test-item\">{$icon}<span class=\"test-name\">{$displayName}</span><span class=\"test-time\">{$timeFormatted}s</span></div>\n";

        if ($tc['message']) {
            $escapedMsg = htmlspecialchars($tc['message']);
            $html .= "<div class=\"failure-msg\">{$escapedMsg}</div>\n";
        }
    }

    $html .= "</div></div>\n";
}

$html .= <<<FOOTER
        <footer>
            <p>Sparkleen Laundry Test Suite — Powered by PHPUnit + CodeIgniter 4</p>
        </footer>
    </div>
</body>
</html>
FOOTER;

file_put_contents($outputFile, $html);
echo "✅ Test report generated: {$outputFile}\n";
echo "   Total: {$totalTests} | Passed: {$passed} | Failed: {$totalFailures} | Errors: {$totalErrors} | Skipped: {$totalSkipped}\n";
echo "   Pass Rate: {$passRate}% | Duration: {$totalTime}s\n";
