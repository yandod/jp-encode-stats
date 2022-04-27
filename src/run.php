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

$idx = 1;

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
        usleep(500000); // wait for 0.5 second for safe
        $sub_dom->loadFromUrl($wiki_url, $options);

        $info_lines = $sub_dom->find('.infobox')->find('tr');
        foreach ($info_lines as $info_line) {
            if (is_null($info_line->find('th')[0]) ||$info_line->find('th')[0]->innerText !== '外部リンク') {
                continue;
            }
            //$site_url = $info_line->find('td')[0]->innerText;
            $site_url = $info_line->find('td')[0]->find('a')[0]->href;
        }

        $headers = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept-Language' => 'ja-JP,ja;q=0.9,en-US;q=0.8,en;q=0.7',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
        ]);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($ch,CURLOPT_MAXREDIRS,10);
        curl_setopt($ch,CURLOPT_AUTOREFERER,true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);        
        curl_setopt($ch, CURLOPT_URL, $site_url);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$headers)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        $response = curl_exec($ch);
        curl_close($ch);
        $encode = mb_detect_encoding($response, "UTF-8, ASCII, JIS, eucjp-win, sjis-win");
        file_put_contents("output/{$code}.txt",$response);

        $row = [
            'code' => $code,
            'name' => $name,
            'wiki' => $wiki_url,
            'site' => $site_url,
            'header' => $headers,
            'encode' => $encode,
        ];

        var_dump($row);
        echo $idx . " / 225 sites\n";
        $data[] = $row;
        $idx++;
    }
}

file_put_contents('output/output.json',json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

//echo $element->innerHtml . "\n";