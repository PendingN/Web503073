(function () {
    'use strict';

    function init() {
        var menuToggle = document.querySelector('[data-menu-toggle]');
        var siteMenu = document.getElementById('site-menu');
        if (menuToggle && siteMenu) {
            menuToggle.addEventListener('click', function () {
                var isOpen = siteMenu.classList.toggle('is-open');
                menuToggle.setAttribute('aria-expanded', String(isOpen));
                menuToggle.setAttribute('aria-label', isOpen ? 'Đóng menu' : 'Mở menu');
            });
        }

        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.getAttribute('aria-controls'));
                if (!input) return;
                var visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.textContent = visible ? 'Hiện' : 'Ẩn';
                button.setAttribute('aria-label', visible ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
            });
        });

        document.querySelectorAll('[data-newsletter-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                form.hidden = true;
                var success = form.parentElement.querySelector('[data-newsletter-success]');
                if (success) success.hidden = false;
            });
        });

        var composerForm = document.querySelector('[data-demo-post-form]');
        var feedColumn = document.querySelector('.feed-column');
        if (composerForm && feedColumn) {
            var composerToggles = document.querySelectorAll('[data-composer-toggle]');
            var titleInput = composerForm.querySelector('input[name="title"]');

            function setComposerOpen(isOpen) {
                composerForm.hidden = !isOpen;
                composerToggles.forEach(function (toggle) {
                    toggle.setAttribute('aria-expanded', String(isOpen));
                });
                if (isOpen && titleInput) titleInput.focus();
            }

            composerToggles.forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    setComposerOpen(composerForm.hidden);
                });
            });

            var cancelButton = composerForm.querySelector('[data-composer-cancel]');
            if (cancelButton) {
                cancelButton.addEventListener('click', function () {
                    composerForm.reset();
                    setComposerOpen(false);
                });
            }

            composerForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var title = (composerForm.querySelector('input[name="title"]') || {}).value || '';
                var content = (composerForm.querySelector('textarea[name="content"]') || {}).value || '';
                title = title.trim();
                content = content.trim();
                if (!title || !content) return;

                var feedList = feedColumn.querySelector('.feed-list');
                if (!feedList) return;
                var article = document.createElement('article');
                article.className = 'feed-post feed-post-demo';
                article.dataset.feedScore = '1';
                article.dataset.feedOrder = '-1';
                article.innerHTML = '<header class="feed-post-head"><div class="feed-author"><span class="feed-avatar feed-avatar-user" aria-hidden="true">bạn</span><div><p><strong>r/góc của bạn</strong><span aria-hidden="true"> · </span><span>vừa xong</span></p><small>Bài viết thử nghiệm</small></div></div><div class="feed-post-controls"><button class="feed-more" type="button" aria-label="Tuỳ chọn bài viết">•••</button></div></header><div class="feed-post-content"><div class="feed-post-copy"><span class="feed-topic">Góc của bạn</span><h3><a href="#write-post" data-demo-title></a></h3><p data-demo-content></p><div class="feed-post-actions"><div class="feed-vote-group" data-vote-group><button class="feed-action feed-vote" type="button" data-feed-action="up" aria-label="Thích bài viết" aria-pressed="false"><span aria-hidden="true">↑</span><span data-count>1</span></button><button class="feed-action feed-vote feed-vote-down" type="button" data-feed-action="down" aria-label="Không thích bài viết" aria-pressed="false"><span aria-hidden="true">↓</span></button></div><button class="feed-action" type="button" data-feed-action="comment" aria-label="Xem 0 bình luận"><span aria-hidden="true">◯</span><span>0</span></button><button class="feed-action" type="button" data-feed-action="save" aria-label="Lưu bài viết" aria-pressed="false"><span aria-hidden="true">♡</span><span class="feed-action-text">Lưu</span></button><a class="feed-read-link" href="#write-post">Đọc tiếp <span aria-hidden="true">↗</span></a></div></div><div class="feed-post-art feed-art-mint" aria-hidden="true"><span class="feed-demo-art">✦</span></div></div>';
                article.querySelector('[data-demo-title]').textContent = title;
                article.querySelector('[data-demo-content]').textContent = content;
                feedList.prepend(article);
                composerForm.reset();
                setComposerOpen(false);

                var status = document.createElement('p');
                status.className = 'demo-post-status';
                status.setAttribute('role', 'status');
                status.textContent = 'Đã thêm bài viết demo vào đầu bảng tin.';
                composerForm.parentElement.appendChild(status);
                window.setTimeout(function () { status.remove(); }, 4500);
            });

            feedColumn.addEventListener('click', function (event) {
                var target = event.target;
                if (!(target instanceof Element)) return;

                var filterButton = target.closest('[data-feed-filter]');
                if (filterButton) {
                    feedColumn.querySelectorAll('[data-feed-filter]').forEach(function (button) {
                        button.classList.toggle('is-active', button === filterButton);
                        button.setAttribute('aria-pressed', String(button === filterButton));
                    });
                    var filter = filterButton.getAttribute('data-feed-filter');
                    var feedList = feedColumn.querySelector('.feed-list');
                    if (feedList) {
                        var posts = Array.prototype.slice.call(feedList.querySelectorAll('.feed-post'));
                        posts.sort(function (first, second) {
                            if (filter === 'popular') {
                                return (parseInt(second.dataset.feedScore, 10) || 0) - (parseInt(first.dataset.feedScore, 10) || 0);
                            }
                            return (parseInt(first.dataset.feedOrder, 10) || 0) - (parseInt(second.dataset.feedOrder, 10) || 0);
                        });
                        posts.forEach(function (post) { feedList.appendChild(post); });
                    }
                    return;
                }

                var actionButton = target.closest('[data-feed-action]');
                if (!actionButton) return;
                var action = actionButton.getAttribute('data-feed-action');

                if (action === 'save') {
                    var isSaved = actionButton.classList.toggle('is-active');
                    actionButton.setAttribute('aria-pressed', String(isSaved));
                    var saveIcon = actionButton.querySelector('span:first-child');
                    var saveLabel = actionButton.querySelector('.feed-action-text');
                    if (saveIcon) saveIcon.textContent = isSaved ? '♥' : '♡';
                    if (saveLabel) saveLabel.textContent = isSaved ? 'Đã lưu' : 'Lưu';
                    return;
                }

                if (action !== 'up' && action !== 'down') return;
                var voteGroup = actionButton.closest('[data-vote-group]');
                if (!voteGroup) return;
                var count = voteGroup.querySelector('[data-count]');
                var oppositeAction = voteGroup.querySelector('[data-feed-action="' + (action === 'up' ? 'down' : 'up') + '"]');
                if (!count || !oppositeAction) return;
                var currentCount = parseInt(count.textContent, 10) || 0;
                var wasActive = actionButton.classList.contains('is-active');
                var oppositeActive = oppositeAction.classList.contains('is-active');
                actionButton.classList.toggle('is-active', !wasActive);
                actionButton.setAttribute('aria-pressed', String(!wasActive));
                if (oppositeActive) {
                    oppositeAction.classList.remove('is-active');
                    oppositeAction.setAttribute('aria-pressed', 'false');
                }
                if (wasActive) {
                    currentCount += action === 'up' ? -1 : 1;
                } else {
                    currentCount += action === 'up' ? 1 : -1;
                    if (oppositeActive) currentCount += action === 'up' ? 1 : -1;
                }
                count.textContent = String(Math.max(0, currentCount));
            });
        }

        document.querySelectorAll('[data-auto-dismiss]').forEach(function (note) {
            window.setTimeout(function () { note.remove(); }, 5000);
        });

    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
