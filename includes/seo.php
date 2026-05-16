<?php
/**
 * SEO helpers — loaded automatically via config.php after lang.php.
 * Call renderSeoHead() from every public page <head>.
 */

function renderSeoHead(array $o = []): void {
    $lang     = currentLang();
    $base     = rtrim(PLATFORM_URL, '/');
    $siteName = PLATFORM_NAME;
    $defImg   = $base . '/assets/img/og-image.jpg';

    $title   = $o['title']    ?? PLATFORM_TITLE;
    $desc    = $o['desc']     ?? '';
    $kw      = $o['keywords'] ?? ($lang === 'en'
        ? 'booking system, online booking, reservation system, appointment scheduling, Rezervly, SaaS booking'
        : 'rezervační systém, online rezervace, booking systém, objednávkový systém, Rezervly, rezervace online');
    $img     = $o['image']    ?? $defImg;
    $noindex = !empty($o['noindex']);
    $ogType  = $o['ogType']   ?? 'website';
    $locale  = $lang === 'en' ? 'en_US' : 'cs_CZ';
    $localeA = $lang === 'en' ? 'cs_CZ' : 'en_US';

    $path   = strtok($_SERVER['REQUEST_URI'], '?');
    $canon  = $o['canonical'] ?? $base . $path;
    $csUrl  = $base . $path . '?setlang=cs';
    $enUrl  = $base . $path . '?setlang=en';

    $esc = function(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $robots = $noindex
        ? 'noindex,nofollow'
        : 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1';

    echo '<meta name="google-site-verification" content="_Vfb2w2PT_5nuHcJwblkaUHz4yIWXitCdOqiQOJInAY">' . "\n";
    echo '<link rel="icon" type="image/svg+xml" href="/favicon.svg">' . "\n";
    echo '<link rel="shortcut icon" href="/favicon.svg">' . "\n";
    echo '<meta name="robots" content="' . $robots . '">' . "\n";
    if ($desc !== '') {
        echo '<meta name="description" content="' . $esc($desc) . '">' . "\n";
    }
    echo '<meta name="keywords" content="' . $esc($kw) . '">' . "\n";
    echo '<meta name="author" content="' . $esc($siteName) . '">' . "\n";
    echo '<link rel="canonical" href="' . $esc($canon) . '">' . "\n";

    if (!$noindex) {
        echo '<link rel="alternate" hreflang="cs" href="' . $esc($csUrl) . '">' . "\n";
        echo '<link rel="alternate" hreflang="en" href="' . $esc($enUrl) . '">' . "\n";
        echo '<link rel="alternate" hreflang="x-default" href="' . $esc($base . $path) . '">' . "\n";
    }

    // Open Graph
    echo '<meta property="og:type" content="' . $esc($ogType) . '">' . "\n";
    echo '<meta property="og:url" content="' . $esc($canon) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . $esc($siteName) . '">' . "\n";
    echo '<meta property="og:locale" content="' . $locale . '">' . "\n";
    echo '<meta property="og:locale:alternate" content="' . $localeA . '">' . "\n";
    echo '<meta property="og:title" content="' . $esc($title) . '">' . "\n";
    if ($desc !== '') {
        echo '<meta property="og:description" content="' . $esc($desc) . '">' . "\n";
    }
    echo '<meta property="og:image" content="' . $esc($img) . '">' . "\n";
    echo '<meta property="og:image:width" content="1200">' . "\n";
    echo '<meta property="og:image:height" content="630">' . "\n";
    echo '<meta property="og:image:alt" content="' . $esc($siteName) . '">' . "\n";

    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . $esc($title) . '">' . "\n";
    if ($desc !== '') {
        echo '<meta name="twitter:description" content="' . $esc($desc) . '">' . "\n";
    }
    echo '<meta name="twitter:image" content="' . $esc($img) . '">' . "\n";
}
