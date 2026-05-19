<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Последние комментарии
===================================================================
*/

require dirname(__DIR__, 3) . '/app/system/init.php';
require_once dirname(__DIR__, 2) . '/system/functions/functions.comments.php';
is_login();

$res = $db->query("SELECT COUNT(*) AS cnt FROM comments_torrents");
$countRow = $db->get_row($res);
$count = (int) ($countRow['cnt'] ?? 0);

list($pagertop, $pagerbottom, $limit) = pager('20', $count, 'comments.last.php?');

$sql = $db->query(
    "SELECT ct.*, ct.id AS comment_id, ct.id_user AS comment_user_id,
            t.name AS torrent_name, t.id AS torrent_id
     FROM comments_torrents ct
     LEFT JOIN torrents t ON t.id = ct.id_torrents
     ORDER BY ct.date DESC
     " . $limit
);

if (!$db->num_rows($sql)) {
    head('Последние комментарии');
    begin_frame('Последние комментарии');
    msg('Ошибка', 'Последних отзывов не было найдено', 1);
    end_frame();
    foot();
    die();
}

head('Последние комментарии');
begin_frame('Последние комментарии');
echo $pagertop;

echo '<div class="last-comments-list">';
while ($arr = $db->get_row($sql)) {
    $user       = get_user_info((int) $arr['comment_user_id']);
    $userId     = (int) $user['id'];
    $userName   = htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8');
    $userClass  = (int) $user['class'];
    $commentDate = convent_date($arr['date']);
    $editDate   = lt_comment_has_real_edit($arr)
                  ? htmlspecialchars($language['comments_3'] . ' ' . convent_date($arr['date_edit']), ENT_QUOTES, 'UTF-8')
                  : '';
    $dateLabel  = ($editDate ?: $commentDate);

    $avatarSrc  = (!empty($user['avatar']) && is_file('public/avatars/small/' . $user['avatar']))
                  ? 'public/avatars/small/' . $user['avatar']
                  : 'public/images/default_avatar.gif';

    $textHtml   = cleanhtml((string) $arr['text']);

    $torrentId   = (int) $arr['torrent_id'];
    $torrentName = htmlspecialchars((string) $arr['torrent_name'], ENT_QUOTES, 'UTF-8');

    echo '<article class="last-comment-item">';
    echo '<a class="last-comment-avatar" href="' . profile_href($userId) . '">'
       . '<img src="' . htmlspecialchars($avatarSrc, ENT_QUOTES, 'UTF-8') . '" alt="' . $userName . '" width="40" height="40">'
       . '</a>';
    echo '<div class="last-comment-body">';
    echo '<div class="last-comment-meta">';
    echo '<a class="last-comment-author" href="' . profile_href($userId) . '">'
       . get_user_color($userClass, $userName) . '</a>';
    echo '<span class="last-comment-sep">→</span>';
    echo '<a class="last-comment-torrent" href="details.php?id=' . $torrentId . '">' . $torrentName . '</a>';
    echo '<span class="last-comment-date">' . htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') . '</span>';
    echo '</div>';
    echo '<div class="last-comment-text">' . $textHtml . '</div>';
    echo '</div>';
    echo '</article>';
}
echo '</div>';

echo $pagerbottom;
end_frame();
foot();
