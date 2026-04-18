<?
/*
===================================================================
-------------------------------------------------------------------
Назначение: Новые релизы
===================================================================
*/

global $memcached, $db, $language, $config, $PRIV;

$news_releases = array();

if (isset($memcached) && is_object($memcached)) {
    $cached_news_releases = $memcached->get('news_releases');

    if ($cached_news_releases !== false && is_array($cached_news_releases)) {
        $news_releases = $cached_news_releases;
    }
}

if (!is_array($news_releases) || empty($news_releases)) {
    $news_releases = array();

    $releases_days = isset($config['releases_news']) ? (int)$config['releases_news'] : 0;

    $sql = $db->query("
        SELECT
            t.*,
            SUM(tr.seeders) AS seeders,
            SUM(tr.leechers) AS leechers
        FROM torrents AS t
        LEFT JOIN trackers AS tr ON tr.torrent = t.id
        WHERE t.banned = '0'
          AND t.news = '1'
          AND (t.infohash <> '' AND t.image <> '')
          AND ADDDATE(t.added, INTERVAL {$releases_days} DAY) > NOW()
        GROUP BY t.id
        ORDER BY t.added DESC
        LIMIT 25
    ");

    while ($row = $db->get_row($sql)) {
        if (!is_array($row)) {
            continue;
        }

        $news_releases[] = $row;
    }

    if (isset($memcached) && is_object($memcached)) {
        $memcached->set('news_releases', $news_releases, 15 * 60);
    }
}

if (!empty($news_releases) && is_array($news_releases)) {

    echo '<script type="text/javascript" src="public/js/wz_tooltip.js"></script>';
    ?>

    <table align="center">
    <?
    $rows = 0;
    $count = 0;

    foreach ($news_releases as $row) {
        if (!is_array($row)) {
            continue;
        }

        $count++;
        $class = ($rows++ % 2) ? 'row1' : 'row2';

        // Номер релиза
        $id = isset($row['id']) ? (int)$row['id'] : 0;

        // Размер файла
        $size_value = isset($row['size']) ? (float)$row['size'] : 0;
        $size = mksize($size_value);

        // Описание релиза
        $descr = array();

        // Год
        if (isset($row['year']) && $row['year'] !== '' && (int)$row['year'] !== 0) {
            $descr[] = htmlspecialchars((string)$row['year'], ENT_QUOTES, 'UTF-8');
        }

        // Язык
        if (isset($row['language']) && trim((string)$row['language']) !== '') {
            $descr[] = htmlspecialchars((string)$row['language'], ENT_QUOTES, 'UTF-8');
        }

        // Качество
        if (isset($row['quality']) && trim((string)$row['quality']) !== '') {
            $descr[] = htmlspecialchars((string)$row['quality'], ENT_QUOTES, 'UTF-8');
        }

        $descr = !empty($descr) ? implode(' / ', $descr) : '';

        // Обложка
        $image = isset($row['image']) ? (string)$row['image'] : '';

        // Информация о категории
        $category = array();
        if (isset($row['id_category'])) {
            $category = categories_array((int)$row['id_category']);
        }
        if (!is_array($category)) {
            $category = array();
        }

        // Имя категории
        $cat_name = isset($category['name']) ? htmlspecialchars((string)$category['name'], ENT_QUOTES, 'UTF-8') : '';

        // ID категории
        $id_category = isset($category['id']) ? (int)$category['id'] : 0;

        // Картинка категории
        $cat_image = isset($category['image']) ? (string)$category['image'] : '';

        // Теги
        $row_tags = isset($row['tags']) ? (string)$row['tags'] : '';
        $tags = '';
        if ($row_tags !== '') {
            $tags = tags_echo($row_tags);
        }

        /////////////////////////////////////////////////////////
        // Пользователь
        /////////////////////////////////////////////////////////
        $user = array();
        if (isset($row['id_user'])) {
            $user = get_user_info((int)$row['id_user']);
        }
        if (!is_array($user)) {
            $user = array();
        }

        // ID пользователя
        $id_user = isset($user['id']) ? (int)$user['id'] : 0;

        // Имя пользователя
        $user_name = isset($user['name']) ? $user['name'] : '';

        // Класс пользователя
        $user_class = isset($user['class']) ? $user['class'] : 0;

        // Раздают
        $seeders = number_format(isset($row['seeders']) ? (int)$row['seeders'] : 0);

        // Качают
        $leechers = number_format(isset($row['leechers']) ? (int)$row['leechers'] : 0);

        require 'templates/' . $config['template'] . '/blocks/block.releases.news.php';
    }

    echo '</table>';
}
?>
