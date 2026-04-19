<?php
if (!defined('LITETRACKER')) {
    die('Direct access denied.');
}

$commentDeletedMeta = lt_comment_deleted_meta((string) ($arr['text'] ?? ''));
$commentDeleted = !empty($commentDeletedMeta['is_deleted']);
$commentDateLabel = ($append_edit ? $append_edit : $date);
$commentAuthorHtml = get_user_color($user_class, htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'));
$commentCanReply = (!empty($USER) && !$commentDeleted);
$commentCanEdit = (!empty($USER['id']) && !$commentDeleted && (!empty($PRIV['comments_edit']) || ($type !== 'users' && (int) $USER['id'] === (int) $user_id)));
$commentCanDelete = (!empty($USER['id']) && !$commentDeleted && (!empty($PRIV['comments_delete']) || ($type !== 'users' && (int) $USER['id'] === (int) $user_id)));
$commentHasSideActions = ($commentCanEdit || $commentCanDelete);
$commentTextHtml = ($commentDeleted
    ? '<span class="comment-entry-deleted-label">'.htmlspecialchars($commentDeletedMeta['message'], ENT_QUOTES, 'UTF-8').'</span>'
    : $text);
?>
<article class="wall-comment comment-entry<?=($commentDeleted ? ' comment-entry-deleted' : '');?>">
    <a class="wall-comment-avatar comment-entry-avatar" href="<?=profile_href($user_id);?>"><?=$avatar;?></a>
    <div class="wall-comment-body comment-entry-body<?=($commentHasSideActions ? ' comment-entry-body-has-side-actions' : '');?>">
        <div class="wall-comment-meta comment-entry-meta">
            <a class="wall-comment-author comment-entry-author" href="<?=profile_href($user_id);?>"><?=$commentAuthorHtml;?></a>
            <span class="wall-comment-date comment-entry-date"><?=htmlspecialchars($commentDateLabel, ENT_QUOTES, 'UTF-8');?></span>
        </div>

        <?php if ($commentHasSideActions) { ?>
        <div class="comment-side-actions">
            <?php if ($commentCanEdit) { ?>
            <a class="comment-side-button comment-side-button-edit" href="comments.take.php?type=<?=urlencode($type);?>&amp;object_id=<?=(int)$object_id;?>&amp;id_comment=<?=(int)$id;?>&amp;act=edit&amp;file=<?=htmlspecialchars($file, ENT_QUOTES, 'UTF-8');?>"><?=$language['comments_4'];?></a>
            <?php } ?>
            <?php if ($commentCanDelete) { ?>
            <a class="comment-side-button comment-side-button-delete" href="comments.take.php?type=<?=urlencode($type);?>&amp;object_id=<?=(int)$object_id;?>&amp;id_comment=<?=(int)$id;?>&amp;act=delete&amp;file=<?=htmlspecialchars($file, ENT_QUOTES, 'UTF-8');?>"><?=$language['comments_5'];?></a>
            <?php } ?>
        </div>
        <?php } ?>

        <div class="wall-comment-text comment-entry-text<?=($commentDeleted ? ' comment-entry-text-deleted' : '');?>"><?=$commentTextHtml;?></div>

        <?php if ($commentCanReply) { ?>
        <div class="wall-comment-actions comment-entry-actions">
            <button class="wall-comment-button comment-reply-button" type="button" onclick="return replyWallComment('<?=htmlspecialchars(addslashes($user_name), ENT_QUOTES, 'UTF-8');?>');">Ответить</button>
        </div>
        <?php } ?>
    </div>
</article>
