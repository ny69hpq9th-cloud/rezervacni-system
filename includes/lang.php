<?php
/**
 * i18n engine — language detection, cookie management, translation helpers.
 * Automatically included via config.php.
 */

// Handle language switch request
if (isset($_GET['setlang']) && in_array($_GET['setlang'], ['cs', 'en'])) {
    $newLang = $_GET['setlang'];
    setcookie('rezervly_lang', $newLang, time() + (365 * 86400), '/');
    $_COOKIE['rezervly_lang'] = $newLang;
    $url    = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['setlang']);
    $redirect = $url . ($params ? '?' . http_build_query($params) : '');
    header('Location: ' . $redirect);
    exit;
}

function currentLang(): string {
    $lang = $_COOKIE['rezervly_lang'] ?? 'cs';
    return in_array($lang, ['cs', 'en']) ? $lang : 'cs';
}

function htmlLang(): string {
    return currentLang() === 'en' ? 'en' : 'cs';
}

// Load translations
$_TRANSLATIONS = require __DIR__ . '/../lang/' . currentLang() . '.php';

/**
 * Translate a key (dot-notation: 'section.key').
 * Supports :placeholder substitution.
 */
function __(string $key, array $vars = []): string {
    global $_TRANSLATIONS;
    $parts = explode('.', $key);
    $val   = $_TRANSLATIONS;
    foreach ($parts as $p) {
        if (!is_array($val) || !array_key_exists($p, $val)) return $key;
        $val = $val[$p];
    }
    if (!is_string($val)) return $key;
    foreach ($vars as $k => $v) {
        $val = str_replace(':' . $k, (string)$v, $val);
    }
    return $val;
}

/**
 * Return an array translation (dot-notation).
 * Use this when the translation value is an array (e.g. dash.colors).
 */
function __array(string $key): array {
    global $_TRANSLATIONS;
    $parts = explode('.', $key);
    $val   = $_TRANSLATIONS;
    foreach ($parts as $p) {
        if (!is_array($val) || !array_key_exists($p, $val)) return [];
        $val = $val[$p];
    }
    return is_array($val) ? $val : [];
}

/** Return price for a plan in the current language's currency. */
function langPrice(string $plan): int {
    if (currentLang() === 'en') {
        return $plan === 'pro' ? PLAN_PRO_PRICE_EUR : PLAN_BASIC_PRICE_EUR;
    }
    return $plan === 'pro' ? PLAN_PRO_PRICE : PLAN_BASIC_PRICE;
}

/** Format a price with the correct currency symbol for the current language. */
function langFormatPrice(float $amount): string {
    if (currentLang() === 'en') {
        return '€' . number_format($amount, 0, '.', ',');
    }
    return number_format($amount, 0, ',', ' ') . ' Kč';
}

function langCurrencyCode(): string {
    return currentLang() === 'en' ? 'EUR' : 'CZK';
}

function langCurrencySymbol(): string {
    return currentLang() === 'en' ? '€' : 'Kč';
}

/** SVG logo icon — $variant: 'color' (blue, for light bg) or 'white' (for dark bg). */
function logoIcon(string $variant = 'color'): string {
    if ($variant === 'white') {
        return '<svg width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;display:block">'
            . '<rect x="2" y="7" width="28" height="22" rx="5" fill="rgba(255,255,255,0.15)"/>'
            . '<rect x="2" y="7" width="28" height="9" rx="5" fill="rgba(255,255,255,0.25)"/>'
            . '<rect x="2" y="13" width="28" height="3" fill="rgba(255,255,255,0.25)"/>'
            . '<rect x="9" y="2" width="4" height="9" rx="2" fill="rgba(255,255,255,0.7)"/>'
            . '<rect x="19" y="2" width="4" height="9" rx="2" fill="rgba(255,255,255,0.7)"/>'
            . '<polyline points="10,21.5 14.5,26 22,18" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
            . '</svg>';
    }
    return '<svg width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;display:block">'
        . '<rect x="2" y="7" width="28" height="22" rx="5" fill="#2563eb"/>'
        . '<rect x="2" y="7" width="28" height="9" rx="5" fill="#1d4ed8"/>'
        . '<rect x="2" y="13" width="28" height="3" fill="#1d4ed8"/>'
        . '<rect x="9" y="2" width="4" height="9" rx="2" fill="#60a5fa"/>'
        . '<rect x="19" y="2" width="4" height="9" rx="2" fill="#60a5fa"/>'
        . '<polyline points="10,21.5 14.5,26 22,18" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg>';
}

/** Render the language switcher HTML (flags). */
function langSwitcher(string $extraClass = ''): string {
    $lang   = currentLang();
    $url    = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;

    $csUrl = $url . '?' . http_build_query(array_merge($params, ['setlang' => 'cs']));
    $enUrl = $url . '?' . http_build_query(array_merge($params, ['setlang' => 'en']));

    $csActive = $lang === 'cs' ? ' lang-btn--active' : '';
    $enActive = $lang === 'en' ? ' lang-btn--active' : '';

    return '<div class="lang-switcher' . ($extraClass ? ' ' . $extraClass : '') . '">' .
        '<a href="' . htmlspecialchars($csUrl, ENT_QUOTES) . '" class="lang-btn' . $csActive . '" title="Čeština">CS</a>' .
        '<a href="' . htmlspecialchars($enUrl, ENT_QUOTES) . '" class="lang-btn' . $enActive . '" title="English">EN</a>' .
        '</div>';
}

/**
 * Inline <script> tag for <head> — anti-FOUC + toggleTheme().
 * Call as the FIRST thing inside <head> before any CSS.
 * No external file dependency.
 */
function themeHeadScript(): string {
    return '<script>'
        . '(function(){'
        .   'try{'
        .     'var t=localStorage.getItem("rezervly_theme");'
        .     'var p=t||(window.matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light");'
        .     'document.documentElement.setAttribute("data-theme",p);'
        .   '}catch(e){}'
        . '})();'
        . 'function toggleTheme(){'
        .   'var h=document.documentElement;'
        .   'var n=h.getAttribute("data-theme")==="dark"?"light":"dark";'
        .   'h.setAttribute("data-theme",n);'
        .   'try{localStorage.setItem("rezervly_theme",n);}catch(e){}'
        . '}'
        . 'window.addEventListener("load",function(){'
        .   'document.documentElement.classList.add("theme-ready");'
        . '});'
        . '</script>' . "\n";
}

/**
 * Dark/light mode toggle button.
 */
function themeToggle(): string {
    return '<button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark/light mode">'
        . '<svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
        . '<circle cx="12" cy="12" r="5"/>'
        . '<line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>'
        . '<line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>'
        . '<line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>'
        . '<line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>'
        . '</svg>'
        . '<svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
        . '<path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>'
        . '</svg>'
        . '</button>';
}
