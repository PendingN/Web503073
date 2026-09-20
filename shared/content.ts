export type Post = {
  slug: string;
  tag: string;
  date: string;
  title: string;
  excerpt: string;
  body: string[];
  illustration: "sprout" | "pot" | "leaf" | "water" | "grass" | "sun";
  tone: "mint" | "cream" | "sage" | "blue" | "sand" | "sun";
  time: string;
};

export type Product = {
  id: string;
  name: string;
  category: string;
  price: number;
  oldPrice?: number;
  rating: string;
  description: string;
  illustration: "leaf" | "pot" | "sprout" | "tree";
};

export type DemoCartItem = {
  productId: string;
  quantity: number;
};

export const posts: Post[] = [
  {
    slug: "5-dau-hieu-cay-can-quan-tam",
    tag: "Chăm cây",
    date: "12.09.2026",
    title: "5 dấu hiệu cây đang cần bạn quan tâm",
    excerpt: "Đọc màu lá, độ ẩm của đất và nhịp phát triển để hiểu cây hơn mỗi ngày.",
    body: [
      "Một cái cây thường lên tiếng bằng những thay đổi rất nhỏ: màu lá nhạt đi, đất lâu khô hơn hoặc mầm non chậm xuất hiện.",
      "Hãy bắt đầu bằng việc quan sát ánh sáng, kiểm tra độ ẩm ở lớp đất trên cùng và lau nhẹ bụi trên lá. Những bước nhỏ này giúp bạn hiểu nhịp sống của cây trước khi thay đổi lịch chăm sóc.",
      "Nếu dấu hiệu kéo dài, hãy thay đổi từng yếu tố một để biết cây phản hồi ra sao. Chăm cây tốt không cần thật nhiều thao tác, chỉ cần đều đặn và vừa đủ."
    ],
    illustration: "sprout",
    tone: "mint",
    time: "5 phút đọc"
  },
  {
    slug: "chon-cay-cho-can-phong-it-nang",
    tag: "Không gian",
    date: "08.09.2026",
    title: "Chọn cây xanh cho căn phòng ít nắng",
    excerpt: "Những giống cây bền bỉ, dịu mắt và phù hợp với góc nhỏ trong nhà.",
    body: [
      "Căn phòng ít nắng vẫn có thể có một góc xanh dễ chịu nếu bạn chọn cây theo lượng sáng thật sự thay vì vị trí bạn muốn đặt chậu.",
      "Ưu tiên những loại cây chịu bóng, đặt chúng gần nguồn sáng tán xạ và xoay chậu mỗi tuần để tán phát triển cân đối.",
      "Đừng tưới theo lịch cố định. Với phòng ít sáng, đất thường khô chậm hơn và cây cần ít nước hơn bạn nghĩ."
    ],
    illustration: "pot",
    tone: "cream",
    time: "4 phút đọc"
  },
  {
    slug: "ban-cong-nho-thanh-khu-vuon",
    tag: "Cảm hứng",
    date: "01.09.2026",
    title: "Một ban công nhỏ cũng có thể thành khu vườn",
    excerpt: "Bắt đầu từ vài chiếc chậu, một góc nắng và thói quen chăm chút đều đặn.",
    body: [
      "Một ban công nhỏ không cần quá nhiều chậu để trở thành nơi bạn muốn ghé qua mỗi sáng.",
      "Hãy chia không gian thành ba lớp: cây cao ở phía sau, cây tán vừa ở giữa và một vài chậu nhỏ ở gần tay với.",
      "Khi mọi thứ có vị trí rõ ràng, việc tưới và dọn lá trở thành một nhịp nghỉ nhẹ nhàng thay vì một danh sách việc cần làm."
    ],
    illustration: "leaf",
    tone: "sage",
    time: "6 phút đọc"
  },
  {
    slug: "tuoi-cay-dung-cach-mua-mua",
    tag: "Mẹo hay",
    date: "26.08.2026",
    title: "Tưới cây đúng cách trong mùa mưa",
    excerpt: "Giảm lượng nước, tăng khả năng thoát ẩm và giữ bộ rễ luôn khỏe mạnh.",
    body: [
      "Mùa mưa không đồng nghĩa với việc mọi chậu cây đều đã đủ nước. Điều quan trọng là kiểm tra độ ẩm trước khi tưới.",
      "Nếu đất còn ẩm sâu, hãy chờ thêm một hoặc hai ngày. Đặt chậu ở nơi thoáng và đảm bảo lỗ thoát nước không bị che kín.",
      "Một lịch chăm sóc linh hoạt theo thời tiết sẽ an toàn hơn việc tưới theo ngày cố định."
    ],
    illustration: "water",
    tone: "blue",
    time: "3 phút đọc"
  },
  {
    slug: "thay-chau-khi-nao-va-the-nao",
    tag: "Chăm cây",
    date: "20.08.2026",
    title: "Thay chậu: khi nào và làm thế nào?",
    excerpt: "Một hướng dẫn ngắn để cây có không gian mới mà không bị sốc.",
    body: [
      "Khi rễ bắt đầu cuộn quanh đáy chậu hoặc nước thoát quá nhanh, cây có thể đã cần một không gian mới.",
      "Chọn chậu lớn hơn một đến hai kích cỡ, giữ lại phần đất quanh rễ và tránh bón phân ngay sau khi thay.",
      "Cho cây một vài ngày ở nơi có ánh sáng dịu để cây thích nghi với môi trường mới."
    ],
    illustration: "grass",
    tone: "sand",
    time: "5 phút đọc"
  },
  {
    slug: "mot-nhip-song-cham-cung-cay",
    tag: "Sống xanh",
    date: "14.08.2026",
    title: "Tạo một nhịp sống chậm cùng cây",
    excerpt: "Năm phút chăm cây mỗi sáng có thể trở thành khoảng nghỉ dễ chịu nhất ngày.",
    body: [
      "Chăm cây có thể là một nghi thức nhỏ giúp bạn bắt đầu ngày mới chậm hơn một chút.",
      "Bạn chỉ cần quan sát một chiếc lá mới, gom lá khô và kiểm tra mặt đất. Năm phút đều đặn thường có giá trị hơn một lần chăm thật lâu rồi bỏ quên.",
      "Khi đặt cây vào nhịp sinh hoạt, bạn cũng dễ nhận ra những thay đổi nhỏ trước khi chúng trở thành vấn đề lớn."
    ],
    illustration: "sun",
    tone: "sun",
    time: "4 phút đọc"
  }
];

export const featuredPost: Post = {
  slug: "bat-dau-mot-goc-xanh-tu-dau",
  tag: "Bài nổi bật",
  date: "14.09.2026",
  title: "Bắt đầu một góc xanh từ đâu?",
  excerpt: "Không cần một khu vườn rộng. Chỉ cần chọn đúng loại cây, hiểu ánh sáng trong phòng và bắt đầu với một nhịp chăm sóc vừa đủ.",
  body: [
    "Một góc xanh dễ bắt đầu nhất khi nó vừa với không gian và nhịp sống của bạn.",
    "Hãy chọn một vị trí có ánh sáng ổn định, một loại cây dễ chăm và một chiếc chậu có lỗ thoát nước. Khi ba điều này đúng, việc chăm cây sẽ nhẹ nhàng hơn rất nhiều.",
    "Từ một chậu cây, bạn có thể quan sát căn phòng khác đi: nơi nào sáng hơn, nơi nào thoáng hơn và thói quen nào khiến bạn vui hơn mỗi ngày."
  ],
  illustration: "leaf",
  tone: "sage",
  time: "7 phút đọc"
};

export const products: Product[] = [
  { id: "luoi-ho", name: "Lưỡi hổ", category: "Cây trong nhà", price: 289000, rating: "4.9", description: "Dáng đứng gọn, dễ chăm và phù hợp với nhiều góc sáng trong nhà.", illustration: "leaf" },
  { id: "trau-ba", name: "Trầu bà", category: "Cây để bàn", price: 289000, rating: "4.9", description: "Tán lá mềm, tạo cảm giác xanh mát cho bàn làm việc hoặc kệ nhỏ.", illustration: "pot" },
  { id: "kim-tien", name: "Kim tiền", category: "Cây dễ chăm sóc", price: 289000, rating: "4.9", description: "Một lựa chọn bền bỉ cho người mới bắt đầu chăm cây.", illustration: "sprout" },
  { id: "trau-ba-la-xe", name: "Trầu bà lá xẻ", category: "Cây ngoài trời", price: 349000, oldPrice: 499000, rating: "4.9", description: "Tán lá nổi bật, mang cảm giác rừng xanh vào không gian sống.", illustration: "tree" }
];

export const collections = [
  { name: "Cây trong nhà", illustration: "leaf" as const },
  { name: "Cây để bàn", illustration: "pot" as const },
  { name: "Cây dễ chăm sóc", illustration: "sprout" as const },
  { name: "Cây ngoài trời", illustration: "tree" as const }
];

export function formatPrice(value: number) {
  return `${new Intl.NumberFormat("vi-VN").format(value)} ₫`;
}
