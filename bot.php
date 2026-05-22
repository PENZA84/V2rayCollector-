<?php
include 'channel.php';
date_default_timezone_set('Europe/Moscow'); // ⏱️ Поменяла часовой пояс, чтобы было правильное время

function fetchContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // ✅ Добавила, чтобы проходило по редиректам
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // ⏱️ Ограничила время ожидания
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36', // ✅ Добавила нормальный браузерный заголовок
        'Accept-Language: en-US,ru;q=0.9',
        'Cookie: messagesDesktopMode=0;'
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log('Ошибка получения контента: ' . curl_error($ch)); // ✅ Ошибки теперь логируются, а не ломают всё
        curl_close($ch);
        return '';
    }
    curl_close($ch);
    return $response;
}

function extractConfigurations($content) {
    $vlessPattern = '/vless:\/\/[^<>\'"\s]+/';
    $vmessPattern = '/vmess:\/\/[^<>\'"\s]+/';
    $ssPattern = '/ss:\/\/[^<>\'"\s]+/';
    $trojanPattern = '/trojan:\/\/[^<>\'"\s]+/';
    $H2Pattern = '/hy2:\/\/[^<>\'"\s]+/';
    $tuicPattern = '/tuic:\/\/[^<>\'"\s]+/';

    return [
        implode(PHP_EOL, preg_match_all($vlessPattern, $content, $vlessMatches) ? array_unique($vlessMatches[0]) : []),
        implode(PHP_EOL, preg_match_all($vmessPattern, $content, $vmessMatches) ? array_unique($vmessMatches[0]) : []),
        implode(PHP_EOL, preg_match_all($ssPattern, $content, $ssMatches) ? array_unique($ssMatches[0]) : []),
        implode(PHP_EOL, preg_match_all($trojanPattern, $content, $trojanMatches) ? array_unique($trojanMatches[0]) : []),
        implode(PHP_EOL, preg_match_all($H2Pattern, $content, $H2Matches) ? array_unique($H2Matches[0]) : []),
        implode(PHP_EOL, preg_match_all($tuicPattern, $content, $tuicMatches) ? array_unique($tuicMatches[0]) : []),
    ];
}

function generateTrojanConfig() {
    $currentDateTime = date('d-m-Y H:i');
    return "trojan://bcacaab-baca-baca-dbac-accaabbcbacb@127.0.0.1:1080?security=tls&type=tcp#%F0%9F%94%84%20ОБНОВЛЕНО%20%F0%9F%93%85%20{$currentDateTime}";
}

function Signature() {
    return "trojan://bcacaab-baca-baca-dbac-accaabbcbacb@127.0.0.1:8080?security=tls&type=tcp#%C2%A9Made%20by:%20github.com/MhdiTaheri%20%F0%9F%93%8C";
}

// ✅ Создаём папку sub, если её нет — теперь не будет ошибки записи
if (!file_exists('sub') && !is_dir('sub')) {
    mkdir('sub', 0755, true);
}

$allVlessConfigs = $allVMessConfigs = $allSSConfigs = $allTrojanConfigs = $allH2Configs = $alltuicConfigs = [];

foreach ($telegramChannelURLs as $channelURL) {
    $channelContent = fetchContent($channelURL);

    if (!empty($channelContent)) {
        [
            $vlessPart, $vmessPart, $ssPart, $trojanPart, $h2Part, $tuicPart
        ] = extractConfigurations($channelContent);
        
        // ✅ Добавляем то что нашли в общий список
        if (!empty($vlessPart)) $allVlessConfigs[] = $vlessPart;
        if (!empty($vmessPart)) $allVMessConfigs[] = $vmessPart;
        if (!empty($ssPart)) $allSSConfigs[] = $ssPart;
        if (!empty($trojanPart)) $allTrojanConfigs[] = $trojanPart;
        if (!empty($h2Part)) $allH2Configs[] = $h2Part;
        if (!empty($tuicPart)) $alltuicConfigs[] = $tuicPart;
    }
}

$trojanConfig = generateTrojanConfig();
$signature = Signature();

// ✅ Убираем пустые значения и дубликаты
$allVlessConfigs = array_unique(array_filter($allVlessConfigs));
$allVMessConfigs = array_unique(array_filter($allVMessConfigs));
$allSSConfigs = array_unique(array_filter($allSSConfigs));
$allTrojanConfigs = array_unique(array_filter($allTrojanConfigs));
$allH2Configs = array_unique(array_filter($allH2Configs));
$alltuicConfigs = array_unique(array_filter($alltuicConfigs));

$fileContents = [
    'vless' => $trojanConfig . PHP_EOL . implode(PHP_EOL, $allVlessConfigs) . PHP_EOL . $signature,
    'vmess' => $trojanConfig . PHP_EOL . implode(PHP_EOL, $allVMessConfigs) . PHP_EOL . $signature,
    'ss' => $trojanConfig . PHP_EOL . implode(PHP_EOL, $allSSConfigs) . PHP_EOL . $signature,
    'trojan' => $trojanConfig . PHP_EOL . implode(PHP_EOL, $allTrojanConfigs) . PHP_EOL . $signature,
    'hysteria' => $trojanConfig . PHP_EOL . implode(PHP_EOL, $allH2Configs) . PHP_EOL . $signature,
    'tuic' => $trojanConfig . PHP_EOL . implode(PHP_EOL, $alltuicConfigs) . PHP_EOL . $signature,
    'mix' => $trojanConfig . PHP_EOL .
        implode(PHP_EOL, $allVlessConfigs) . PHP_EOL .
        implode(PHP_EOL, $allVMessConfigs) . PHP_EOL .
        implode(PHP_EOL, $allSSConfigs) . PHP_EOL .
        implode(PHP_EOL, $allTrojanConfigs) . PHP_EOL .
        implode(PHP_EOL, $allH2Configs) . PHP_EOL .
        implode(PHP_EOL, $alltuicConfigs) . PHP_EOL .
        $signature,
];

foreach ($fileContents as $key => $content) {
    file_put_contents("sub/{$key}", $content);
    file_put_contents("sub/{$key}base64", base64_encode($content));
}

echo "✅ Поставщик завершил работу! Все ссылки обновлены в папке /sub/";
?>
