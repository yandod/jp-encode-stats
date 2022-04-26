<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPHtmlParser\Dom;
use PHPHtmlParser\Options;

$options = new Options();
$options->setEnforceEncoding('utf8');


$data = [];

// ページを解析
$url = 'https://ja.wikipedia.org/wiki/%E6%97%A5%E7%B5%8C%E5%B9%B3%E5%9D%87%E6%A0%AA%E4%BE%A1';
$dom = new Dom();
$dom->loadFromUrl($url, $options);

$tables = $dom->find('table');

foreach ($tables as $table) {

    if ( is_null($table->find('tr')[0]->find('th')[0]) || $table->find('tr')[0]->find('th')[0]->innerText !== '証券コード') {
        continue;
    }

    foreach ($table->find('tr') as $line) {
        $cells = $line->find('td');
        if (count($cells) == 0) {
            continue;
        }

        $code = $cells[0]->innerText;
        $name = $cells[1]->innerText;
        $wiki = $cells[1]->find('a')[0]->href;

        $site_url = '';
        $wiki_url = 'https://ja.wikipedia.org/' . $wiki;
        $sub_dom = new Dom();
        $sub_dom->loadFromUrl($wiki_url, $options);

        $info_lines = $sub_dom->find('.infobox')->find('tr');
        foreach ($info_lines as $info_line) {
            if (is_null($info_line->find('th')[0]) ||$info_line->find('th')[0]->innerText !== '外部リンク') {
                continue;
            }
            $site_url = $info_line->find('td')[0]->innerText;
        }

        echo 'code:' . $code ."\n";
        echo 'name:' . $name ."\n";
        echo 'wiki:' . $wiki . "\n";
        echo 'site:' . $site_url . "\n";

        $row = [
            'code' => $code,
            'name' => $name,
            'wiki' => $wiki,
            'site' => $site_url,
        ];
        $data[] = $row;
    }
}

file_put_contents('output.json',json_encode($data, JSON_UNESCAPED_UNICODE));

//echo $element->innerHtml . "\n";