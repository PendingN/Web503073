<?php
declare(strict_types=1);

// Star ratings and comments under each plant. Included by tests/run.php (disposable database and web server).

$reviewPlant = 22;
$reviewPage = 'plant-detail.php?id=' . $reviewPlant;
$reviewBack = $prefix . '/plant-detail.php?id=' . $reviewPlant . '#reviews';
create_user('Rater One', 'rater1@example.test', 'RaterOne123!', 'user', true);
create_user('Rater Two', 'rater2@example.test', 'RaterTwo123!', 'user', true);
create_user('Review Admin', 'review-admin@example.test', 'ReviewAdmin123!', 'admin', true);
$raterOne = new TestBrowser($base);
$raterTwo = new TestBrowser($base);
$reviewAdmin = new TestBrowser($base);
check($raterOne->login('rater1@example.test', 'RaterOne123!')['status'] === 303
    && $raterTwo->login('rater2@example.test', 'RaterTwo123!')['status'] === 303
    && $reviewAdmin->login('review-admin@example.test', 'ReviewAdmin123!')['status'] === 303, 'Review test accounts must sign in.');

function test_review(TestBrowser $browser, string $page, array $data): array
{
    return $browser->request('actions/plant-review-action.php', ['csrf_token' => $browser->token($page)] + $data);
}

function test_rating(int $plantId, string $email): ?int
{
    $statement = db()->prepare('SELECT r.rating FROM plant_ratings r JOIN users u ON u.id = r.user_id WHERE r.plant_id = ? AND u.email = ?');
    $statement->execute([$plantId, $email]);
    $rating = $statement->fetchColumn();
    return $rating === false ? null : (int) $rating;
}

// Guests see the section but must log in to take part.
$reviewGuest = new TestBrowser($base);
$page = $reviewGuest->request($reviewPage)['body'];
check(str_contains($page, 'id="reviews"') && str_contains($page, 'Chưa có ai đánh giá cây này.') && str_contains($page, 'Đăng nhập để chấm sao và bình luận'), 'Guests must see the empty review section and a login prompt.');
check(!str_contains($page, 'name="rating"'), 'Guests must not get the rating form.');
$response = test_review($reviewGuest, $reviewPage, ['operation' => 'rate', 'plant_id' => $reviewPlant, 'rating' => '5']);
check($response['status'] === 303 && str_starts_with($response['headers']['location'] ?? '', $prefix . '/login.php?return_to='), 'Guests must be sent to log in before rating.');
check($raterOne->request('actions/plant-review-action.php', ['operation' => 'rate', 'plant_id' => $reviewPlant, 'rating' => '5'])['status'] === 403, 'Rating requires a CSRF token.');
check($raterOne->request('actions/plant-review-action.php')['status'] === 405, 'The review handler only accepts POST.');
check(test_review($raterOne, $reviewPage, ['operation' => 'rate', 'plant_id' => 99999, 'rating' => '5'])['status'] === 404, 'Rating a missing plant must return 404.');
check(test_review($raterOne, $reviewPage, ['operation' => 'explode', 'plant_id' => $reviewPlant])['status'] === 400, 'Unknown review operations must be refused.');

// Rating: create, validate, change, average, distribution.
$response = test_review($raterOne, $reviewPage, ['operation' => 'rate', 'plant_id' => $reviewPlant, 'rating' => '4', 'return_to' => $prefix . '/' . $reviewPage]);
check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $reviewBack, 'Rating must return to the plant reviews.');
check(test_rating($reviewPlant, 'rater1@example.test') === 4, 'The rating must be saved.');
foreach (['0', '6', 'abc', ''] as $badRating) {
    test_review($raterOne, $reviewPage, ['operation' => 'rate', 'plant_id' => $reviewPlant, 'rating' => $badRating]);
}
check(test_rating($reviewPlant, 'rater1@example.test') === 4, 'Ratings outside 1-5 must be refused.');
check(str_contains($raterOne->request($reviewPage)['body'], 'Hãy chọn từ 1 đến 5 sao'), 'An invalid rating must show an error.');
test_review($raterOne, $reviewPage, ['operation' => 'rate', 'plant_id' => $reviewPlant, 'rating' => '5']);
test_review($raterTwo, $reviewPage, ['operation' => 'rate', 'plant_id' => $reviewPlant, 'rating' => '2']);
check(test_rating($reviewPlant, 'rater1@example.test') === 5 && (int) db()->query('SELECT COUNT(*) FROM plant_ratings WHERE plant_id = ' . $reviewPlant)->fetchColumn() === 2, 'Rating again must update the same row.');
$page = $raterOne->request($reviewPage)['body'];
check(str_contains($page, '<strong>3,5</strong>') && str_contains($page, '2 lượt đánh giá') && str_contains($page, 'Bạn đã chấm 5 sao'), 'The page must show the average, the count, and my rating.');
check(preg_match('/id="rating-5" value="5" checked/', $page) === 1, 'My saved rating must be preselected.');
check(preg_match('#<dt>5 sao</dt>\s*<dd><span class="reviews-bar" aria-hidden="true"><span style="--share: 50%"></span></span><span class="reviews-bar-count">1</span>#', $page) === 1, 'The distribution must show each star share.');
check(str_contains($reviewGuest->request('collection.php')['body'], 'class="plant-card-rating"'), 'Plant cards must show the average rating.');

// The plant's own "back to" link survives; foreign return URLs fall back to the plant.
$withReturn = 'plant-detail.php?id=' . $reviewPlant . '&return_to=' . rawurlencode($prefix . '/favorites.php');
$response = test_review($raterTwo, $withReturn, ['operation' => 'rate', 'plant_id' => $reviewPlant, 'rating' => '3', 'return_to' => $prefix . '/' . $withReturn]);
check(($response['headers']['location'] ?? '') === $prefix . '/' . $withReturn . '#reviews', 'The return URL of the plant page must be kept.');
$response = test_review($raterTwo, $reviewPage, ['operation' => 'rate', 'plant_id' => $reviewPlant, 'rating' => '2', 'return_to' => 'https://evil.example/']);
check(($response['headers']['location'] ?? '') === $reviewBack, 'External return URLs must be ignored.');
$response = test_review($raterTwo, $reviewPage, ['operation' => 'rate', 'plant_id' => $reviewPlant, 'rating' => '2', 'return_to' => $prefix . '/profile.php']);
check(($response['headers']['location'] ?? '') === $reviewBack, 'Reviews must always return to the plant page.');

// Comments: validation, escaping, author rating, deletion rights.
$before = (int) db()->query('SELECT COUNT(*) FROM plant_comments')->fetchColumn();
test_review($raterOne, $reviewPage, ['operation' => 'comment', 'plant_id' => $reviewPlant, 'content' => "   \n  "]);
check(str_contains($raterOne->request($reviewPage)['body'], 'Bình luận không được để trống.'), 'Empty comments must be refused.');
$longComment = str_repeat('á', PLANT_COMMENT_MAX_LENGTH + 1);
test_review($raterOne, $reviewPage, ['operation' => 'comment', 'plant_id' => $reviewPlant, 'content' => $longComment]);
$page = $raterOne->request($reviewPage)['body'];
check(str_contains($page, 'Bình luận tối đa') && str_contains($page, str_repeat('á', PLANT_COMMENT_MAX_LENGTH) . '</textarea>'), 'Long comments must be refused and the draft kept.');
check((int) db()->query('SELECT COUNT(*) FROM plant_comments')->fetchColumn() === $before, 'Invalid comments must not be saved.');
$response = test_review($raterOne, $reviewPage, ['operation' => 'comment', 'plant_id' => $reviewPlant, 'content' => "Cây rất dễ chăm <script>alert(1)</script>\nTưới 10 ngày một lần."]);
check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $reviewBack, 'A comment must return to the plant reviews.');
test_review($raterTwo, $reviewPage, ['operation' => 'comment', 'plant_id' => $reviewPlant, 'content' => 'Lá bị vàng khi để gần cửa sổ.']);
$page = $reviewGuest->request($reviewPage)['body'];
check(str_contains($page, '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($page, '<script>alert(1)</script>'), 'Comments must be escaped.');
check(str_contains($page, 'Rater One') && str_contains($page, 'Bình luận <span>(2)</span>') && str_contains($page, 'aria-label="5,0 trên 5 sao"'), 'Comments must show the author, total, and the author rating.');
check(!str_contains($page, 'Xóa bình luận'), 'Guests must not see delete buttons.');
$commentOne = (int) db()->query("SELECT c.id FROM plant_comments c JOIN users u ON u.id = c.user_id WHERE u.email = 'rater1@example.test'")->fetchColumn();
$commentTwo = (int) db()->query("SELECT c.id FROM plant_comments c JOIN users u ON u.id = c.user_id WHERE u.email = 'rater2@example.test'")->fetchColumn();
check(test_review($raterTwo, $reviewPage, ['operation' => 'delete-comment', 'plant_id' => $reviewPlant, 'comment_id' => $commentOne])['status'] === 403, 'Users must not delete other people\'s comments.');
check(test_review($raterOne, $reviewPage, ['operation' => 'delete-comment', 'plant_id' => 21, 'comment_id' => $commentOne])['status'] === 404, 'A comment must belong to the posted plant.');
check(test_review($raterOne, $reviewPage, ['operation' => 'delete-comment', 'plant_id' => $reviewPlant, 'comment_id' => $commentOne])['status'] === 303, 'Authors must delete their own comments.');
check(test_review($reviewAdmin, $reviewPage, ['operation' => 'delete-comment', 'plant_id' => $reviewPlant, 'comment_id' => $commentTwo])['status'] === 303, 'Admins must delete any comment.');
check((int) db()->query('SELECT COUNT(*) FROM plant_comments WHERE id IN (' . $commentOne . ', ' . $commentTwo . ')')->fetchColumn() === 0, 'Deleted comments must be removed.');

// Pagination keeps ten comments per page.
$raterOneId = (int) find_user_by_email('rater1@example.test')['id'];
$insert = db()->prepare('INSERT INTO plant_comments (plant_id, user_id, content) VALUES (?, ?, ?)');
for ($index = 1; $index <= PLANT_COMMENTS_PER_PAGE + 1; $index++) {
    $insert->execute([$reviewPlant, $raterOneId, 'Bình luận số ' . $index]);
}
$page = $reviewGuest->request($reviewPage)['body'];
check(str_contains($page, 'Bình luận số ' . (PLANT_COMMENTS_PER_PAGE + 1)) && !str_contains($page, 'Bình luận số 1<') && str_contains($page, 'Trang 1 / 2'), 'The first page must show the newest ten comments.');
check(str_contains($reviewGuest->request($reviewPage . '&page=2')['body'], 'Bình luận số 1<'), 'The second page must show older comments.');
check($reviewGuest->request($reviewPage . '&page=999')['status'] === 200, 'Out-of-range comment pages must clamp.');

// Removing a rating.
check(test_review($raterOne, $reviewPage, ['operation' => 'unrate', 'plant_id' => $reviewPlant])['status'] === 303 && test_rating($reviewPlant, 'rater1@example.test') === null, 'Users must remove their rating.');
check(str_contains($raterOne->request($reviewPage)['body'], '1 lượt đánh giá'), 'The count must drop after removing a rating.');
