<?php

$SOURCE = 'https://www.solanavolumebotpro.com/solana-volume-data/data.json';
$ROOT   = __DIR__;

$ch = curl_init($SOURCE);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
    CURLOPT_USERAGENT      => 'solana-dex-reliability-index/1.0',
]);
$raw  = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($code !== 200) exit("source returned HTTP $code\n");

$data = json_decode((string) $raw, true);
if (!is_array($data) || empty($data['latest']['venues'])) exit("unexpected payload\n");

$latest = $data['latest'];
$date   = substr((string) ($latest['measured_at'] ?? ''), 0, 10);
if ($date === '') exit("no measurement date\n");

@mkdir($ROOT . '/data/history', 0775, true);

file_put_contents($ROOT . '/data/latest.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
file_put_contents($ROOT . '/data/history/' . $date . '.json', json_encode($latest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

$rows = [['measured_at', 'venue', 'transactions', 'failed', 'failure_rate_pct', 'median_fee_lamports', 'share_pct']];
foreach ($latest['venues'] as $v) {
    $rows[] = [
        $latest['measured_at'],
        $v['venue'],
        $v['transactions'],
        $v['failed'],
        $v['failure_rate'],
        $v['median_fee_lamports'],
        $v['share'],
    ];
}
$rows[] = [
    $latest['measured_at'],
    'Network-wide',
    $latest['blocks']['transactions'],
    $latest['blocks']['failed'],
    $latest['blocks']['failure_rate'],
    $latest['blocks']['median_fee_lamports'],
    100,
];

$fh = fopen($ROOT . '/data/latest.csv', 'w');
foreach ($rows as $r) fputcsv($fh, $r);
fclose($fh);

printf("saved %s - %d venues, network failure rate %s%%\n", $date, count($latest['venues']), $latest['blocks']['failure_rate']);
