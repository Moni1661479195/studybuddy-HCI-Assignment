<?php
function extract_tags_from_text(string $text, int $limit = 6): array {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $stop = array_flip(['the','and','for','with','that','this','from','are','you','your','not','but','have','has','a','an','of','in','on','to','by','is','it','as','or','we','be','our','study','session']);
    $freq = [];
    foreach ($words as $w) {
        if (mb_strlen($w) < 2) continue;
        if (isset($stop[$w])) continue;
        $freq[$w] = ($freq[$w] ?? 0) + 1;
    }
    arsort($freq);
    return array_slice(array_keys($freq), 0, $limit);
}
?>