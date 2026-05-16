<?php
/**
 * Sitemap — generovaná dynamicky, vždy se správným XML Content-Type.
 * Google Search Console: https://rezervly.eu/sitemap.php
 */
header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$base    = rtrim(PLATFORM_URL, '/');
$today   = date('Y-m-d');
$db      = getDB();

// Aktivní firmy s veřejnou rezervační stránkou
$stmt = $db->query(
    "SELECT slug, created_at FROM users
     WHERE status = 'active'
     ORDER BY created_at DESC
     LIMIT 5000"
);
$businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

  <!-- ── Hlavní stránka ── -->
  <url>
    <loc><?= $base ?>/</loc>
    <xhtml:link rel="alternate" hreflang="cs"        href="<?= $base ?>/?setlang=cs"/>
    <xhtml:link rel="alternate" hreflang="en"        href="<?= $base ?>/?setlang=en"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= $base ?>/"/>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- ── Registrace ── -->
  <url>
    <loc><?= $base ?>/register.php</loc>
    <xhtml:link rel="alternate" hreflang="cs"        href="<?= $base ?>/register.php?setlang=cs"/>
    <xhtml:link rel="alternate" hreflang="en"        href="<?= $base ?>/register.php?setlang=en"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= $base ?>/register.php"/>
    <lastmod><?= $today ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.9</priority>
  </url>

<?php foreach ($businesses as $biz):
    $loc     = htmlspecialchars($base . '/rezervace/' . $biz['slug'], ENT_XML1);
    $lastmod = date('Y-m-d', strtotime($biz['created_at']));
?>
  <!-- ── Rezervační stránka firmy ── -->
  <url>
    <loc><?= $loc ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach; ?>

</urlset>
