<?php
declare(strict_types=1);

// How to use Là Lá La, written for Sâu AI's huong_dan_su_dung tool (includes/sau-agent.php).
// Each topic links to the page it describes, so an answer can send the user straight there.
// Keep this in step with the pages: Sâu repeats it to users as fact.

const SITE_NAME = 'Là Lá La';

/** @return list<array{key: string, title: string, url: string, keywords: string, content: string}> */
function site_guide(): array
{
    return [
        ['key' => 'gioi-thieu', 'title' => 'Giới thiệu Là Lá La', 'url' => 'index.php',
            'keywords' => 'website là gì giới thiệu trang chủ tính năng làm được gì mua bán',
            'content' => 'Là Lá La là website tra cứu cây cảnh trong nhà, cây để bàn và sổ tay chăm cây của cộng đồng. '
                . 'Trên thanh menu có: Trang chủ, Bộ sưu tập (danh mục cây), Blog (sổ tay thực vật), Trang cá nhân. '
                . 'Website chỉ để tra cứu và chia sẻ kinh nghiệm, không bán cây hay giao hàng.'],
        ['key' => 'tai-khoan', 'title' => 'Đăng ký, đăng nhập và kích hoạt tài khoản', 'url' => 'register.php',
            'keywords' => 'đăng ký tạo tài khoản đăng nhập đăng xuất kích hoạt xác minh email chưa xác minh',
            'content' => 'Đăng ký chỉ cần email, họ tên và mật khẩu (nhập 2 lần). Đăng ký xong website gửi email kích hoạt (kiểm tra cả thư rác); mở link trong email rồi đăng nhập. '
                . 'Nếu chưa nhận được, đăng nhập bằng email và mật khẩu đã đăng ký rồi bấm Gửi lại email kích hoạt. '
                . 'Nút Đăng nhập nằm góc phải thanh menu, nút Đăng xuất hiện ở đó khi đã đăng nhập.'],
        ['key' => 'quen-mat-khau', 'title' => 'Quên mật khẩu', 'url' => 'forgot-password.php',
            'keywords' => 'quên mật khẩu lấy lại khôi phục đặt lại reset mã otp 6 số',
            'content' => 'Ở trang đăng nhập bấm Quên mật khẩu?, nhập email. Bạn nhận email có link đặt lại và mã 6 số: bấm link hoặc nhập mã để sang bước đặt mật khẩu mới. '
                . 'Đặt xong bạn cần đăng nhập lại bằng mật khẩu mới.'],
        ['key' => 'ca-nhan', 'title' => 'Trang cá nhân, ảnh đại diện và cài đặt tài khoản', 'url' => 'account-settings.php',
            'keywords' => 'trang cá nhân hồ sơ ảnh đại diện avatar đổi tên đổi email đổi mật khẩu cài đặt tài khoản thống kê',
            'content' => 'Trang cá nhân (menu Trang cá nhân) hiện ảnh đại diện, thống kê (cây yêu thích, bài đã viết, bài đã lưu, bình luận, bài được chia sẻ với bạn) và danh sách bài của bạn có tìm kiếm, sắp xếp. '
                . 'Nút Cài đặt tài khoản ở đó cho phép đổi họ tên, email, chọn hoặc xoá ảnh đại diện, và Đổi mật khẩu (nhập mật khẩu hiện tại rồi mật khẩu mới 2 lần).'],
        ['key' => 'bo-suu-tap', 'title' => 'Bộ sưu tập cây', 'url' => 'collection.php',
            'keywords' => 'bộ sưu tập danh mục cây tìm cây xem cây chi tiết cây sắp xếp loại cây để bàn trong nhà',
            'content' => 'Menu Bộ sưu tập liệt kê các cây của website (cây trong nhà và cây để bàn). Gõ vào ô tìm kiếm để thấy gợi ý ngay, sắp xếp theo tên. '
                . 'Bấm vào một cây để xem trang chi tiết: mô tả, cách chăm, đánh giá sao và bình luận.'],
        ['key' => 'yeu-thich', 'title' => 'Cây yêu thích, đánh giá và bình luận cây', 'url' => 'favorites.php',
            'keywords' => 'yêu thích trái tim lưu cây danh sách yêu thích đánh giá sao chấm điểm bình luận cây',
            'content' => 'Bấm biểu tượng trái tim trên thẻ cây để thêm vào Cây yêu thích; xem lại ở trang Cây yêu thích hoặc từ Trang cá nhân. '
                . 'Ở trang chi tiết cây, bạn chấm 1 đến 5 sao (đổi hoặc xoá được) và viết bình luận. Cần đăng nhập.'],
        ['key' => 'goi-y-cay', 'title' => 'Gợi ý chọn cây', 'url' => 'plant-advisor.php',
            'keywords' => 'gợi ý chọn cây tư vấn phong thủy mệnh ngũ hành cung hoàng đạo năm sinh lắc quẻ hợp tuổi',
            'content' => 'Trang Gợi ý chọn cây hỏi từng bước: ngày sinh (theo cung hoàng đạo hoặc mệnh ngũ hành), điều bạn mong muốn và góc nhà đặt cây, rồi gợi ý cây hợp trong bộ sưu tập. '
                . 'Có thêm chế độ lắc quẻ cây xanh cho vui.'],
        ['key' => 'blog', 'title' => 'Đọc và tìm bài trên Blog', 'url' => 'blog.php',
            'keywords' => 'blog sổ tay thực vật đọc bài tìm bài tìm kiếm chủ đề lọc tag nhãn lưới danh sách bài của tôi đã lưu sắp xếp',
            'content' => 'Menu Blog (Sổ Tay Thực Vật) có các tab Tất cả, Bài của tôi, Đã lưu. Gõ vào ô tìm kiếm là danh sách lọc ngay theo tiêu đề, nội dung và chủ đề, không cần nhấn nút. '
                . 'Bấm các chủ đề nằm ngay dưới ô tìm kiếm để lọc (chọn được nhiều chủ đề), đổi Mới nhất hoặc Được yêu thích, và chuyển giữa dạng lưới và danh sách bằng hai nút bên phải.'],
        ['key' => 'tuong-tac-bai', 'title' => 'Thích, lưu và bình luận bài viết', 'url' => 'blog.php',
            'keywords' => 'thích bình chọn vote lưu bài đã lưu bình luận xoá bình luận',
            'content' => 'Trong bài viết bạn bình chọn, bấm Lưu để đưa bài vào tab Đã lưu, và viết bình luận ở cuối bài (xoá được bình luận của mình). Cần đăng nhập.'],
        ['key' => 'tom-tat-ai', 'title' => 'Tóm tắt bài bằng AI', 'url' => 'blog.php',
            'keywords' => 'tóm tắt ai tóm tắt bài ý chính tạo lại',
            'content' => 'Mỗi bài viết có khung Tóm tắt bằng AI: tạo 1 đến 2 câu tóm tắt và vài ý chính, bấm tạo lại khi muốn. Cần đăng nhập.'],
        ['key' => 'viet-bai', 'title' => 'Viết và sửa bài', 'url' => 'blog-editor.php',
            'keywords' => 'viết bài đăng bài sửa bài xoá bài soạn thảo chèn ảnh ảnh bìa chủ đề nháp riêng tư hoàn tác xem trước',
            'content' => 'Bấm Viết bài mới (trên Blog) hoặc Viết bài (Trang cá nhân). Chỉ bắt buộc tiêu đề và nội dung; có thể thêm khối, chèn ảnh, liên kết, ảnh bìa, chọn chủ đề và bật Riêng tư. '
                . 'Nội dung tự lưu nháp trên máy, có Hoàn tác, Làm lại và Xem trước. Sửa bài dùng cùng màn hình đó (nút Sửa bài); xoá bài luôn có hộp xác nhận.'],
        ['key' => 'khoa-bai', 'title' => 'Khoá bài bằng mật khẩu', 'url' => 'blog.php',
            'keywords' => 'khoá bài mật khẩu bài bảo vệ bật khóa tắt khóa đổi mật khẩu khóa mở khóa',
            'content' => 'Mở bài của bạn, ở khung Bảo mật và chia sẻ chọn Khóa bằng mật khẩu: nhập mật khẩu 2 lần rồi Bật khóa. '
                . 'Sau đó xem, sửa hay xoá bài đều phải nhập mật khẩu. Đổi mật khẩu khóa cần mật khẩu hiện tại và mật khẩu mới 2 lần; Tắt khóa cần nhập lại mật khẩu hiện tại. Mỗi bài có mật khẩu riêng.'],
        ['key' => 'chia-se', 'title' => 'Chia sẻ bài và bài được chia sẻ với bạn', 'url' => 'shared.php',
            'keywords' => 'chia sẻ bài gửi bài email quyền chỉ xem được sửa thu hồi được chia sẻ với tôi thông báo',
            'content' => 'Ở khung Bảo mật và chia sẻ của bài, nhập email (một hoặc nhiều, phải là tài khoản đã đăng ký) và chọn quyền Chỉ xem hoặc Được sửa. '
                . 'Danh sách người đã được chia sẻ cho phép đổi quyền hoặc Thu hồi. Người nhận thấy thông báo khi đăng nhập và xem các bài ở mục Được chia sẻ với tôi (dòng trong Trang cá nhân), kèm người chia sẻ, thời gian và quyền.'],
        ['key' => 'cung-sua', 'title' => 'Cùng sửa bài theo thời gian thực', 'url' => 'shared.php',
            'keywords' => 'cùng sửa cộng tác thời gian thực realtime nhiều người sửa cùng lúc',
            'content' => 'Khi bài được chia sẻ quyền Được sửa, chủ bài và người nhận mở trình soạn thảo cùng lúc sẽ thấy thay đổi của nhau ngay lập tức.'],
        ['key' => 'sau-ai', 'title' => 'Hỏi Sâu AI', 'url' => 'ask.php',
            'keywords' => 'sâu ai hỏi đáp trợ lý chatbot hỏi về sổ tay',
            'content' => 'Bấm chú sâu góc phải dưới màn hình (hoặc trang Hỏi AI) để hỏi về sổ tay của bạn, cây yêu thích, bộ sưu tập cây, blog và cách dùng website. '
                . 'Câu trả lời kèm link tới bài hoặc trang liên quan.'],
    ];
}

/** Guide topics that best match the question, most relevant first. */
function site_guide_matches(string $question, int $limit = 3): array
{
    $terms = array_unique(notes_qa_terms($question));
    $scored = [];
    foreach (site_guide() as $index => $topic) {
        $head = array_count_values(notes_qa_terms($topic['title'] . ' ' . $topic['keywords']));
        $body = array_count_values(notes_qa_terms($topic['content']));
        $score = 0;
        foreach ($terms as $term) {
            $score += 3 * min(2, $head[$term] ?? 0) + min(2, $body[$term] ?? 0);
        }
        if ($score > 0) {
            $scored[] = ['score' => $score, 'index' => $index, 'topic' => $topic];
        }
    }
    usort($scored, static fn(array $a, array $b): int => [$b['score'], $a['index']] <=> [$a['score'], $b['index']]);
    return array_column(array_slice($scored, 0, $limit), 'topic');
}
