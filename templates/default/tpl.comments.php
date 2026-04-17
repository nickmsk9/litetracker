<?php
if (!defined('LITETRACKER')) {
    die('Direct access denied.');
}

if ($type == 'users') {
    ?>
    <article class="wall-comment">
        <a class="wall-comment-avatar" href="<?=profile_href($user_id);?>"><?=$avatar;?></a>
        <div class="wall-comment-body">
            <div class="wall-comment-meta">
                <a class="wall-comment-author" href="<?=profile_href($user_id);?>"><?=get_user_color($user_class, htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'));?></a>
                <span class="wall-comment-date"><?=($append_edit ? htmlspecialchars($append_edit, ENT_QUOTES, 'UTF-8') : htmlspecialchars($date, ENT_QUOTES, 'UTF-8'));?></span>
            </div>
            <div class="wall-comment-text"><?=$text;?></div>
            <div class="wall-comment-actions">
                <?php if (!empty($USER)) { ?>
                    <button class="wall-comment-button" type="button" onclick="return replyWallComment('<?=htmlspecialchars(addslashes($user_name), ENT_QUOTES, 'UTF-8');?>');">Ответить</button>
                <?php } ?>

                <?php if (!empty($PRIV['comments_edit'])) { ?>
                    <a class="wall-comment-button" href="comments.take.php?type=<?=urlencode($type);?>&amp;object_id=<?=(int)$object_id;?>&amp;id_comment=<?=(int)$id;?>&amp;act=edit&amp;file=<?=htmlspecialchars($file, ENT_QUOTES, 'UTF-8');?>"><?=$language['comments_4'];?></a>
                <?php } ?>

                <?php if (!empty($PRIV['comments_delete'])) { ?>
                    <a class="wall-comment-button" href="comments.take.php?type=<?=urlencode($type);?>&amp;object_id=<?=(int)$object_id;?>&amp;id_comment=<?=(int)$id;?>&amp;act=delete&amp;file=<?=htmlspecialchars($file, ENT_QUOTES, 'UTF-8');?>"><?=$language['comments_5'];?></a>
                <?php } ?>
            </div>
        </div>
    </article>
    <?php
} else {
    begin_frame();
    ?>

    <a href="<?=profile_href($user_id);?>"><?=get_user_color($user_class, $user_name);?></a> написал<br>
    <?=($append_edit ? '<small>' . $append_edit . '</small>' : '<small>' . $date . '</small>');?>
    <hr>

    <table>
        <tbody>
            <tr>
                <td><a href="<?=profile_href($user_id);?>"><?=$avatar;?></a></td>
                <td valign="top">
                    <?=$text;?>
                    <hr>
                    <?php if (!empty($USER['id']) && ($USER['id'] == $user_id || !empty($PRIV['comments_edit']))) { ?>
                        <input type="button" value="<?=$language['comments_4'];?>" onclick="window.location.href='comments.take.php?type=<?=urlencode($type);?>&object_id=<?=(int)$object_id;?>&id_comment=<?=(int)$id;?>&act=edit&file=<?=htmlspecialchars($file, ENT_QUOTES, 'UTF-8');?>'">
                    <?php } ?>
                    <?php if (!empty($USER['id']) && ($USER['id'] == $user_id || !empty($PRIV['comments_delete']))) { ?>
                        <input type="button" value="<?=$language['comments_5'];?>" onclick="window.location.href='comments.take.php?type=<?=urlencode($type);?>&object_id=<?=(int)$object_id;?>&id_comment=<?=(int)$id;?>&act=delete&file=<?=htmlspecialchars($file, ENT_QUOTES, 'UTF-8');?>'">
                    <?php } ?>
                </td>
            </tr>
        </tbody>
    </table>

    <?php
    end_frame();
}
