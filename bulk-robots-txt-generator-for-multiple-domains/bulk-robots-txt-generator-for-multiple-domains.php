<?php

$domains = [
    'site1.ru',
    'site2.ru',
    'site3.ru',
    'site4.ru',
];

$common_rules = [
    'Disallow: /*?',
    'Disallow: /*.php',
    'Disallow: /*admin',
    'Disallow: /*json',
    'Disallow: /cgi',
    'Disallow: */feed/',
    'Disallow: */trackback',
    'Disallow: /archive',
    'Disallow: */page',
    'Disallow: */embed',
    'Disallow: /search',
    'Disallow: *comment',
    'Disallow: /author',
    'Disallow: *account',
];

$allow_rules = [
    'Allow: /*.js',
    'Allow: /*.css',
    'Allow: /*.png',
    'Allow: /*.jpg',
    'Allow: /*.jpeg',
    'Allow: /*.gif',
];

function setRandSeed(string $input): void
{
    $hash = md5($input);
    $seed = hexdec(substr($hash, 0, 8));
    srand($seed);
}

function shuffle_array(array $arr, string $input_seed): array
{
    setRandSeed($input_seed);
    shuffle($arr);
    return $arr;
}

function randSetBot(string $bot, string $input_seed): bool
{
    setRandSeed($input_seed . $bot);
    $rand = rand(0, 1);
    return (bool)$rand;
}

// Создание директории, если она не существует
$robotsDir = __DIR__ . '/robots';
if (!is_dir($robotsDir)) {
    mkdir($robotsDir, 0777, true);
}

foreach ($domains as $domain) {
    $result = '';

    $shuffle_common_rules = shuffle_array($common_rules, $domain);
    $shuffle_allow_rules = shuffle_array($allow_rules, $domain);

    $user_agents = [
        [
            'name' => '*',
            'isOn' => true,
        ],
        [
            'name' => 'Yandex',
            'isOn' => false,
        ],
        [
            'name' => 'Googlebot',
            'isOn' => false,
        ],
    ];

    foreach ($user_agents as $key => $user_agent) {
        $user_agents[$key]['rules'] = $shuffle_common_rules;

        if (!$user_agent['isOn']) {
            $user_agents[$key]['isOn'] = randSetBot($user_agent['name'], $domain);
        }
    }

    $common_user_agent = array_shift($user_agents);
    $user_agents = shuffle_array($user_agents, $domain);
    array_unshift($user_agents, $common_user_agent);

    $user_agents = array_filter($user_agents, function ($user_agent) use ($domain) {
        return $user_agent['isOn'];
    });
    $user_agents = array_values($user_agents);

    if (count($user_agents) > 1) {
        $index = randSetBot($user_agents[0]['name'], $domain . 'Allow'); // Исправлено
        $index = intval($index) + 1;
        $index = ($index > array_key_last($user_agents)) ? array_key_last($user_agents) : $index;
        if (isset($user_agents[$index]['rules'])) {
            $user_agents[$index]['rules'] = array_merge($user_agents[$index]['rules'], $shuffle_allow_rules);
        } else {
            $user_agents[$index]['rules'] = $shuffle_allow_rules;
        }
    } else {
        if (isset($user_agents[0]['rules'])) {
            $user_agents[0]['rules'] = array_merge($user_agents[0]['rules'], $shuffle_allow_rules);
        } else {
            $user_agents[0]['rules'] = $shuffle_allow_rules;
        }
    }

    foreach ($user_agents as $user_agent) {
        $result .= 'User-agent: ' . $user_agent['name'] . PHP_EOL;
        $result .= implode(PHP_EOL, $user_agent['rules']) . PHP_EOL . PHP_EOL;
    }

    $result = trim($result);

    $filePath = $robotsDir . '/' . $domain . '.txt';
    if (file_put_contents($filePath, $result)) {
        echo 'Robots for ' . $domain . ' is created' . PHP_EOL;
    } else {
        echo 'Error create robots for ' . $domain . ': ' . print_r(error_get_last(), true) . PHP_EOL;
    }
}
