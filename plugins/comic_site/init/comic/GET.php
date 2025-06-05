<?php
openTemplate("default");
$comicTitle = isset($oset['comic_site_title']) ? $oset['comic_site_title'] : 'Webcomic';
?>
<div class="comic-container">
<h1 class="comic-title"><?php echo htmlspecialchars($comicTitle); ?></h1>
<?php
$comics = getModelItems('comic');
if (isset($api_path[3]) && $api_path[3] !== '') {
    foreach ($comics as $c) {
        if ($c['id'] === $api_path[3]) { $comics = [$c]; break; }
    }
}
foreach ($comics as $c) {
    $img = explode(':', $c['image'])[0];
    $alt = !empty($c['alt_text']) ? htmlspecialchars($c['alt_text']) : '';
    echo "<div class='comic-entry'>";
    echo "<h2 class='comic-title'>" . htmlspecialchars($c['title']) . "</h2>";
    echo "<img class='comic-image' src='" . $img . "' alt='" . $alt . "'>";
    echo "</div>";
}
?>
</div>
<?php closeTemplate("default"); ?>
