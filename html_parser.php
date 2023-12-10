<?php

/// Read HTML files
$htmlFiles = [
    'Luật Cán bộ, công chức và Luật Viên chức sửa đổi 2019 số 52_2019_QH14 ban hành ngày 25_11_2019.html',
    'Luật Phòng, chống rửa tiền 2022 số 14_2022_QH15 ban hành ngày 15_11_2022.html',
    'Nghị định 44_2022_NĐ-CP về xây dựng, quản lý và sử dụng hệ thống thông tin về nhà ở và thị trường bất động sản.html',
];

$documents = [];

foreach ($htmlFiles as $file) {
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8">' . file_get_contents($file), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Initialize document details
    $documentDetails = [
        'attributes' => [
            'Số hiệu' => '',
            'Loại văn bản' => '',
            'Ngày ban hành' => '',
            'Nơi ban hành' => '',
            'Người ký' => '',
            'Ngày công báo' => '',
            'Số công báo' => '',
            'Ngày hiệu lực' => '',
            'Tình trạng hiệu lực' => '',
        ],
        'content' => []
    ];

    // Extract attribute details
    foreach ($documentDetails['attributes'] as $attribute => $value) {
        $liElements = $doc->getElementsByTagName("li");
        foreach ($liElements as $element) {
            if (strpos($element->nodeValue, $attribute) !== false) {
                $documentDetails['attributes'][$attribute] = trim(str_replace($attribute . ":", "", $element->nodeValue));
            }
        }
    }

    $elements = $doc->getElementsByTagName('a');
    $output = [];

    foreach ($elements as $element) {
        $content = trim(preg_replace('/\s\s+/', ' ', $element->firstChild->nodeValue));
        $parent = $element->parentNode;

        $pattern = '/\bĐiều\s*\d+\./u';
        if (preg_match($pattern, $content)) {
            $sibling = $parent->nextSibling;
            $children = [];

            while ($sibling) {
                if ($sibling->nodeName === 'p') {
                    $children[] = [
                        'content' => trim(preg_replace('/\s\s+/', ' ', $sibling->nodeValue)),
                        'styles' => $sibling->getAttribute('style'),
                    ];
                }
                $sibling = $sibling->nextSibling;
            }

            $tagName = $element->firstChild->tagName ? $element->firstChild->tagName : $element->tagName;

            // Clean up content
            $pattern = '/.*?\bĐiều/u';
            $replacement = 'Điều';
            $content = preg_replace($pattern, $replacement, $content);

            $output[] = [
                'content' => "<{$tagName}>$content</{$tagName}>",
                'styles' => $parent->getAttribute('style'),
                'children' => $children,
            ];
        }
    }
    $documentDetails['content'] = $output;

    // Store document details
    $documents[] = $documentDetails;
}

// Convert to JSON
$jsonData = json_encode($documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents('output.json', $jsonData);

print_r($jsonData); // Output JSON data
