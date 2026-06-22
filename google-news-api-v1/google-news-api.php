<?php
/*
 * Google News API — PHP backend
 * Fetches Google News RSS feeds and returns JSON.
 * Supports: search, top headlines, topic, geo/location, breaking news.
 * Optional data_depth=true enriches each item with full article content via cURL.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: X-Api-Key, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../app/Database.php';

// ── Auth ──────────────────────────────────────────────────────────────────────
$headers   = getallheaders();
$headerKey = $headers['X-Api-Key'] ?? $headers['x-api-key'] ?? '';
$apiKey    = $_REQUEST['api_key'] ?? $headerKey;

$db   = Database::getInstance();
$stmt = $db->query("SELECT value FROM settings WHERE key = 'api_key'");
$row  = $stmt->fetch();
$validKey = $row ? $row['value'] : '';

if (!$apiKey || $apiKey !== $validKey) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'error'  => 'Invalid or missing API key',
        'hint'   => 'Pass via ?api_key= query param or X-Api-Key header',
    ]);
    exit;
}

// ── Parameters ────────────────────────────────────────────────────────────────
$endpoint  = strtolower(trim($_REQUEST['endpoint'] ?? 'top'));
$q         = trim($_REQUEST['q'] ?? '');
$num       = max(1, min((int)($_REQUEST['num'] ?? 10), 100));
$hl        = preg_replace('/[^a-zA-Z\-]/', '', $_REQUEST['hl'] ?? 'en-US');
$gl        = preg_replace('/[^a-zA-Z]/', '', $_REQUEST['gl'] ?? 'US');
$ceidDef   = strtoupper($gl) . ':' . strtolower(explode('-', $hl)[0]);
$ceid      = $_REQUEST['ceid'] ?? $ceidDef;
$topic     = strtoupper(trim($_REQUEST['topic'] ?? ''));
$location  = trim($_REQUEST['location'] ?? '');
$dataDepth = in_array($_REQUEST['data_depth'] ?? '', ['true', '1', 'yes'], true);
$within    = max(0.25, min((float)($_REQUEST['within'] ?? 2), 24));
$when      = trim($_REQUEST['when'] ?? '');
$before    = trim($_REQUEST['before'] ?? '');
$after     = trim($_REQUEST['after'] ?? '');
$site      = trim($_REQUEST['site'] ?? '');
$intitle   = trim($_REQUEST['intitle'] ?? '');

const VALID_TOPICS = ['WORLD', 'NATION', 'BUSINESS', 'TECHNOLOGY', 'ENTERTAINMENT', 'SCIENCE', 'SPORTS', 'HEALTH'];

// ── Helpers ───────────────────────────────────────────────────────────────────
function localeStr(string $hl, string $gl, string $ceid): string {
    return "hl={$hl}&gl={$gl}&ceid=" . rawurlencode($ceid);
}

function buildQuery(string $q, string $when, string $before, string $after, string $site, string $intitle): string {
    if ($when)    $q .= " when:{$when}";
    if ($after)   $q .= " after:{$after}";
    if ($before)  $q .= " before:{$before}";
    if ($site)    $q .= " site:{$site}";
    if ($intitle) $q .= " intitle:{$intitle}";
    return trim($q);
}

function httpGet(string $url, int $timeout = 10): ?string {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; GoogleNewsRSSBot/2.0)',
        CURLOPT_HTTPHEADER     => ['Accept: application/rss+xml, application/xml, text/xml, */*'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_ENCODING       => '',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return $body ?: null;
}

function httpGetArticle(string $url, int $timeout = 12): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 6,
        CURLOPT_ENCODING       => '',
    ]);
    $body    = curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['url' => $finalUrl ?: $url, 'html' => ($body && strlen($body) > 200) ? $body : null];
}

// ── RSS parser ────────────────────────────────────────────────────────────────
function parseRSS(?string $xml): array {
    $empty = ['feedTitle' => '', 'feedLink' => '', 'items' => []];
    if (!$xml) return $empty;

    libxml_use_internal_errors(true);
    $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$doc || !isset($doc->channel)) return $empty;

    $channel   = $doc->channel;
    $feedTitle = (string)($channel->title ?? '');
    $feedLink  = (string)($channel->link ?? '');
    $items     = [];

    foreach ($channel->item ?? [] as $item) {
        $source    = '';
        $sourceUrl = '';
        if (isset($item->source)) {
            $source = (string)$item->source;
            $attrs  = $item->source->attributes();
            $sourceUrl = (string)($attrs['url'] ?? '');
        }
        $items[] = [
            'title'       => (string)($item->title ?? ''),
            'link'        => (string)($item->link ?? ''),
            'pubDate'     => (string)($item->pubDate ?? ''),
            'source'      => $source,
            'sourceUrl'   => $sourceUrl,
            'description' => strip_tags((string)($item->description ?? '')),
        ];
    }

    return compact('feedTitle', 'feedLink', 'items');
}

// ── Article content extractor ─────────────────────────────────────────────────
function extractArticleContent(string $html, string $url): array {
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($doc);

    $title       = '';
    $description = '';
    $image       = null;
    $author      = null;
    $published   = null;
    $modified    = null;
    $keywords    = null;
    $section     = null;

    // JSON-LD structured data
    $scripts = $xpath->query("//script[@type='application/ld+json']");
    foreach ($scripts as $script) {
        $data = json_decode($script->textContent, true);
        if (!$data) continue;
        $candidates = isset($data[0]) ? $data : [$data];
        foreach ($candidates as $c) {
            $type = $c['@type'] ?? '';
            if (in_array($type, ['NewsArticle', 'Article', 'WebPage', 'ReportageNewsArticle'], true)) {
                if (!$title)     $title       = $c['headline'] ?? '';
                if (!$description) $description = $c['description'] ?? '';
                if (!$published) $published   = $c['datePublished'] ?? null;
                if (!$modified)  $modified    = $c['dateModified'] ?? null;
                if (!$keywords)  $keywords    = $c['keywords'] ?? null;
                if (!$section)   $section     = $c['articleSection'] ?? null;
                if (!$author) {
                    $auth = $c['author'] ?? null;
                    if (is_array($auth)) {
                        $author = isset($auth['name']) ? $auth['name'] : ($auth[0]['name'] ?? null);
                    }
                }
                if (!$image && isset($c['image'])) {
                    $img = $c['image'];
                    $image = is_string($img) ? $img : ($img['url'] ?? null);
                }
                break;
            }
        }
    }

    // Open Graph / meta fallbacks
    $getMeta = function(string $attr, string $val) use ($xpath): string {
        $node = $xpath->query("//meta[@{$attr}='{$val}']")->item(0);
        return $node ? $node->getAttribute('content') : '';
    };

    if (!$title)       $title       = $getMeta('property', 'og:title') ?: $getMeta('name', 'twitter:title');
    if (!$description) $description = $getMeta('property', 'og:description') ?: $getMeta('name', 'description');
    if (!$image)       $image       = $getMeta('property', 'og:image') ?: $getMeta('name', 'twitter:image') ?: null;
    if (!$published)   $published   = $getMeta('property', 'article:published_time') ?: null;
    if (!$modified)    $modified    = $getMeta('property', 'article:modified_time') ?: null;
    if (!$keywords)    $keywords    = $getMeta('name', 'keywords') ?: null;
    if (!$author) {
        $relAuth = $xpath->query("//*[@rel='author']")->item(0);
        $author  = $relAuth ? trim($relAuth->textContent) : null;
    }

    // H1 fallback for title
    if (!$title) {
        $h1Node = $xpath->query("//h1")->item(0);
        if ($h1Node) $title = trim($h1Node->textContent);
    }
    if (!$title) {
        $titleNode = $xpath->query("//title")->item(0);
        if ($titleNode) $title = trim($titleNode->textContent);
    }

    // Body text extraction — try article/main first, then fall back to paragraphs
    $bodyText = '';
    $bodySelectors = [
        "//article",
        "//*[contains(@class,'article-body')]",
        "//*[contains(@class,'article-content')]",
        "//*[contains(@class,'story-body')]",
        "//*[contains(@class,'post-body')]",
        "//*[contains(@class,'entry-content')]",
        "//main",
        "//*[@role='main']",
    ];
    foreach ($bodySelectors as $sel) {
        $nodes = $xpath->query($sel);
        if ($nodes && $nodes->length > 0) {
            $text = preg_replace('/\s+/', ' ', trim($nodes->item(0)->textContent));
            if (strlen($text) > 300) { $bodyText = $text; break; }
        }
    }

    // Paragraph fallback
    if (strlen($bodyText) < 200) {
        $paras = $xpath->query("//p");
        $parts = [];
        foreach ($paras as $p) {
            $t = trim($p->textContent);
            if (strlen($t) > 40) $parts[] = $t;
        }
        if ($parts) $bodyText = implode(' ', $parts);
    }

    $bodyText  = substr(trim($bodyText), 0, 8000);
    $wordCount = $bodyText ? count(array_filter(explode(' ', $bodyText))) : 0;

    return [
        'actualUrl'     => $url,
        'articleTitle'  => $title ?: null,
        'author'        => $author ?: null,
        'publishedDate' => $published ?: null,
        'modifiedDate'  => $modified ?: null,
        'section'       => $section ?: null,
        'description'   => $description ?: null,
        'image'         => $image,
        'keywords'      => is_string($keywords) ? $keywords : null,
        'video'         => null,
        'wordCount'     => $wordCount,
        'body'          => $bodyText ?: null,
        'screenshotUrl' => null,
    ];
}

// ── Article enrichment ────────────────────────────────────────────────────────
function enrichItems(array $items, bool $doEnrich): array {
    if (!$doEnrich) return $items;
    $enriched = [];
    foreach ($items as $item) {
        $gnUrl = $item['link'] ?? '';
        if (!$gnUrl) {
            $enriched[] = array_merge($item, ['articleContent' => ['error' => 'No URL available']]);
            continue;
        }
        $result   = httpGetArticle($gnUrl);
        $finalUrl = $result['url'];
        $html     = $result['html'];

        if ($html) {
            $content = extractArticleContent($html, $finalUrl);
        } else {
            $content = ['actualUrl' => $finalUrl, 'error' => 'Could not fetch article content'];
        }
        $enriched[] = array_merge($item, ['articleContent' => $content]);
    }
    return $enriched;
}

// ── Main routing ──────────────────────────────────────────────────────────────
$locale = localeStr($hl, $gl, $ceid);
$data   = [];

try {
    switch ($endpoint) {

        // ── Search ──────────────────────────────────────────────────────────
        case 'search':
            $query = buildQuery($q, $when, $before, $after, $site, $intitle);
            if (!$query) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'error' => "Parameter 'q' is required for the search endpoint"]);
                exit;
            }
            $url  = 'https://news.google.com/rss/search?q=' . rawurlencode($query) . '&' . $locale;
            $data = parseRSS(httpGet($url));
            $data['query'] = $query;
            break;

        // ── Top Headlines ────────────────────────────────────────────────────
        case 'top':
            $url  = 'https://news.google.com/rss?' . $locale;
            $data = parseRSS(httpGet($url));
            break;

        // ── Topic ────────────────────────────────────────────────────────────
        case 'topic':
            if (!in_array($topic, VALID_TOPICS, true)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'error'  => 'Invalid topic. Valid values: ' . implode(', ', VALID_TOPICS),
                ]);
                exit;
            }
            $url  = "https://news.google.com/rss/headlines/section/topic/{$topic}?{$locale}";
            $data = parseRSS(httpGet($url));
            $data['topic'] = $topic;
            break;

        // ── Geo / Location ───────────────────────────────────────────────────
        case 'geo':
            if (!$location) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'error' => "Parameter 'location' is required for the geo endpoint"]);
                exit;
            }
            $url  = 'https://news.google.com/rss/headlines/section/geo/' . rawurlencode($location) . '?' . $locale;
            $data = parseRSS(httpGet($url));
            $data['location'] = $location;
            break;

        // ── Breaking News ────────────────────────────────────────────────────
        case 'breaking':
            $cutoff = new DateTime("-{$within} hours");
            $feeds  = [];

            if ($q) {
                $whenStr = $within <= 1 ? '1h' : ($within <= 4 ? '4h' : '1d');
                $bq      = "{$q} when:{$whenStr}";
                $feeds[] = parseRSS(httpGet('https://news.google.com/rss/search?q=' . rawurlencode($bq) . '&' . $locale));
            } elseif ($topic && in_array($topic, VALID_TOPICS, true)) {
                $feeds[] = parseRSS(httpGet("https://news.google.com/rss/headlines/section/topic/{$topic}?{$locale}"));
            } else {
                $feeds[] = parseRSS(httpGet("https://news.google.com/rss?{$locale}"));
                $feeds[] = parseRSS(httpGet("https://news.google.com/rss/headlines/section/topic/WORLD?{$locale}"));
            }

            $allItems   = [];
            $seenTitles = [];

            foreach ($feeds as $feed) {
                foreach ($feed['items'] as $item) {
                    $key = substr(preg_replace('/[^a-z0-9]/', '', strtolower($item['title'])), 0, 60);
                    if (in_array($key, $seenTitles, true)) continue;
                    $seenTitles[] = $key;

                    if (!$item['pubDate']) continue;
                    try {
                        $pub = new DateTime($item['pubDate']);
                    } catch (Exception $e) {
                        continue;
                    }
                    if ($pub < $cutoff) continue;

                    $ageMinutes  = (int)round((time() - $pub->getTimestamp()) / 60);
                    $allItems[]  = array_merge($item, ['ageMinutes' => $ageMinutes]);
                }
            }

            usort($allItems, fn($a, $b) => $a['ageMinutes'] - $b['ageMinutes']);

            $data = [
                'feedTitle'   => 'Breaking News',
                'feedLink'    => '',
                'items'       => $allItems,
                'withinHours' => $within,
                'topic'       => $topic ?: null,
                'query'       => $q ?: null,
            ];
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'status'    => 'error',
                'error'     => "Unknown endpoint '{$endpoint}'",
                'endpoints' => ['search', 'top', 'topic', 'geo', 'breaking'],
            ]);
            exit;
    }

    // Limit results
    $data['items'] = array_slice($data['items'], 0, $num);

    // Optionally enrich with full article content
    if ($dataDepth) {
        $data['items'] = enrichItems($data['items'], true);
    }

    echo json_encode(array_merge(
        ['status' => 'ok', 'count' => count($data['items'])],
        $data
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}
