<?php
// Owner-only panel on blog-post.php: password lock and sharing. Expects $post (unlocked) and $user.
$isLocked = post_is_locked($post);
$shares = list_post_shares((int) $post['id']);
$manageOpen = $_SESSION['post_manage_open'] ?? '';
unset($_SESSION['post_manage_open']);
$csrf = csrf_token();
$permissionLabels = ['read' => 'Chỉ xem', 'edit' => 'Được sửa'];
?>
<section class="post-manage" id="post-manage" aria-labelledby="post-manage-title">
    <h2 id="post-manage-title">Bảo mật &amp; chia sẻ</h2>
    <p class="post-manage-status">
        <?php if ($isLocked): ?><span class="post-badge is-locked">🔒 Đang khóa bằng mật khẩu</span><?php endif; ?>
        <?php if ((int) $post['is_private'] === 1): ?><span class="post-badge is-private">Riêng tư</span><?php else: ?><span class="post-badge">Công khai</span><?php endif; ?>
        <?php if ($shares !== []): ?><span class="post-badge is-shared">👥 Chia sẻ với <?= count($shares) ?> người</span><?php endif; ?>
    </p>

    <details class="post-manage-row"<?= $manageOpen === 'lock' ? ' open' : '' ?>>
        <summary>
            <span><strong>Khóa bằng mật khẩu</strong><small><?= $isLocked ? 'Đổi mật khẩu hoặc tắt khóa' : 'Mọi người, kể cả bạn, phải nhập mật khẩu mới xem, sửa hoặc xóa được' ?></small></span>
        </summary>
        <?php if (!$isLocked): ?>
            <form class="post-manage-form" method="post" action="actions/post-lock-action.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="operation" value="enable">
                <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
                <label class="form-label" for="lock-new">Mật khẩu khóa</label>
                <input class="form-control" id="lock-new" name="new_password" type="password" autocomplete="new-password" minlength="<?= POST_LOCK_MIN_LENGTH ?>" maxlength="72" required>
                <label class="form-label" for="lock-confirm">Nhập lại mật khẩu khóa</label>
                <input class="form-control" id="lock-confirm" name="password_confirmation" type="password" autocomplete="new-password" minlength="<?= POST_LOCK_MIN_LENGTH ?>" maxlength="72" required>
                <p class="post-manage-hint">Mật khẩu này chỉ dùng cho bài này, khác mật khẩu đăng nhập. Ít nhất <?= POST_LOCK_MIN_LENGTH ?> ký tự.</p>
                <button class="btn-brand" type="submit">Bật khóa</button>
            </form>
        <?php else: ?>
            <form class="post-manage-form" method="post" action="actions/post-lock-action.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="operation" value="change">
                <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
                <h3>Đổi mật khẩu khóa</h3>
                <label class="form-label" for="lock-current">Mật khẩu khóa hiện tại</label>
                <input class="form-control" id="lock-current" name="current_password" type="password" autocomplete="off" maxlength="72" required>
                <label class="form-label" for="lock-change-new">Mật khẩu khóa mới</label>
                <input class="form-control" id="lock-change-new" name="new_password" type="password" autocomplete="new-password" minlength="<?= POST_LOCK_MIN_LENGTH ?>" maxlength="72" required>
                <label class="form-label" for="lock-change-confirm">Nhập lại mật khẩu khóa mới</label>
                <input class="form-control" id="lock-change-confirm" name="password_confirmation" type="password" autocomplete="new-password" minlength="<?= POST_LOCK_MIN_LENGTH ?>" maxlength="72" required>
                <button class="btn-brand" type="submit">Đổi mật khẩu</button>
            </form>
            <form class="post-manage-form post-manage-danger" method="post" action="actions/post-lock-action.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="operation" value="disable">
                <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
                <h3>Tắt khóa</h3>
                <label class="form-label" for="lock-disable">Nhập mật khẩu khóa hiện tại để xác nhận</label>
                <input class="form-control" id="lock-disable" name="current_password" type="password" autocomplete="off" maxlength="72" required>
                <button class="btn-outline-brand" type="submit">Tắt khóa</button>
            </form>
        <?php endif; ?>
    </details>

    <details class="post-manage-row"<?= $manageOpen === 'share' ? ' open' : '' ?>>
        <summary>
            <span><strong>Chia sẻ</strong><small><?= $shares === [] ? 'Chưa chia sẻ với ai' : 'Đang chia sẻ với ' . count($shares) . ' người' ?></small></span>
        </summary>
        <form class="post-manage-form" method="post" action="actions/post-share-action.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="operation" value="add">
            <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
            <label class="form-label" for="share-emails">Email người nhận</label>
            <textarea class="form-control" id="share-emails" name="emails" rows="2" maxlength="2000" placeholder="ban@example.com, nguoikhac@example.com" required></textarea>
            <p class="post-manage-hint">Nhập một hoặc nhiều email đã đăng ký, cách nhau bằng dấu phẩy hoặc xuống dòng.</p>
            <label class="form-label" for="share-permission">Quyền</label>
            <select class="form-control" id="share-permission" name="permission">
                <option value="read">Chỉ xem</option>
                <option value="edit">Được sửa (sửa cùng lúc theo thời gian thực)</option>
            </select>
            <button class="btn-brand" type="submit">Chia sẻ</button>
        </form>

        <?php if ($shares !== []): ?>
            <h3 class="post-share-heading">Người đã được chia sẻ</h3>
            <ul class="post-share-list">
                <?php foreach ($shares as $share): ?>
                    <li>
                        <span class="post-share-who"><strong><?= e($share['name']) ?></strong><small><?= e($share['email']) ?> · từ <?= e(date('d/m/Y H:i', strtotime($share['created_at']))) ?></small></span>
                        <form method="post" action="actions/post-share-action.php" class="post-share-inline">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="operation" value="update">
                            <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $share['user_id'] ?>">
                            <label class="sr-only" for="share-perm-<?= (int) $share['user_id'] ?>">Quyền của <?= e($share['email']) ?></label>
                            <select id="share-perm-<?= (int) $share['user_id'] ?>" name="permission" data-auto-submit>
                                <?php foreach ($permissionLabels as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= $share['permission'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button type="submit">Lưu</button></noscript>
                        </form>
                        <form method="post" action="actions/post-share-action.php" class="post-share-inline">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="operation" value="revoke">
                            <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $share['user_id'] ?>">
                            <button class="post-share-revoke" type="submit">Thu hồi<span class="sr-only"> quyền của <?= e($share['email']) ?></span></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </details>
</section>
