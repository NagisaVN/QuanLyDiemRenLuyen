<?php

namespace App\Support;

use App\Models\MucTieuChi;
use App\Models\TieuChi;
use Illuminate\Support\Facades\Schema;

class DrlRubric
{
    public static function syncIfMissing(): void
    {
        if (! Schema::hasColumn('muc_tieu_chis', 'ma_muc')) {
            return;
        }

        if (! MucTieuChi::query()->whereNotNull('ma_muc')->exists()) {
            self::sync();
        }
    }

    public static function sync(): void
    {
        if (! Schema::hasColumn('muc_tieu_chis', 'ma_muc')) {
            return;
        }

        $criteriaIds = [];

        foreach (self::criteria() as $index => $criterion) {
            $tieuChi = TieuChi::withTrashed()->updateOrCreate(
                ['ma_tieu_chi' => $criterion['code']],
                [
                    'ten_tieu_chi' => $criterion['name'],
                    'mo_ta' => $criterion['description'],
                    'diem_toi_da' => $criterion['max'],
                    'thu_tu' => $index + 1,
                    'is_active' => true,
                    'deleted_at' => null,
                ],
            );

            if ($tieuChi->trashed()) {
                $tieuChi->restore();
            }

            $criteriaIds[] = $tieuChi->id;

            foreach ($criterion['items'] as $itemIndex => $item) {
                MucTieuChi::query()->updateOrCreate(
                    ['ma_muc' => $item['code']],
                    [
                        'tieu_chi_id' => $tieuChi->id,
                        'ten_muc' => $item['name'],
                        'mo_ta' => $item['description'] ?? null,
                        'diem_toi_da' => $item['points'],
                        'thu_tu' => $itemIndex + 1,
                        'loai' => $item['type'],
                        'is_active' => true,
                    ],
                );
            }
        }

        MucTieuChi::query()
            ->whereIn('tieu_chi_id', $criteriaIds)
            ->whereNull('ma_muc')
            ->update(['is_active' => false]);
    }

    public static function criteria(): array
    {
        return [
            [
                'code' => 'TC01',
                'name' => 'Trách nhiệm, tinh thần và thái độ trong học tập',
                'description' => 'Trách nhiệm, tinh thần và thái độ trong học tập (tối đa 20 điểm).',
                'max' => 20,
                'items' => [
                    self::heading('I.1', '1. Tinh thần vượt khó, phấn đấu vươn lên trong học tập'),
                    self::item('I.1.a', 'a. Có ý thức học tập, tham dự đầy đủ các giờ học', 5),
                    self::item('I.1.b', 'b. Đi học muộn 1 lần', -1),
                    self::item('I.1.c', 'c. Đi học muộn nhiều lần', -2),
                    self::item('I.1.d', 'd. Nghỉ học có phép', -2),
                    self::item('I.1.e', 'e. Nghỉ học không phép', -5),
                    self::item('I.1.f', 'f. Bỏ giờ học ra ngoài không lý do (Cúp tiết)', -2),
                    self::heading('I.2', '2. Kết quả học tập'),
                    self::item('I.2.a', 'a. Kết quả học tập trung bình Học kỳ đạt loại Xuất sắc', 5),
                    self::item('I.2.b', 'b. Kết quả học tập trung bình Học kỳ đạt loại Giỏi', 3),
                    self::item('I.2.c', 'c. Kết quả học tập trung bình Học kỳ đạt loại Khá', 2),
                    self::item('I.2.d', 'd. Đạt chứng chỉ nghề nghiệp (Tin học, Ngoại ngữ...)', 2),
                    self::heading('I.3', '3. Trách nhiệm và tinh thần tham gia các kỳ thi, cuộc thi'),
                    self::item('I.3.a', 'a. Không vi phạm quy chế kiểm tra, thi cử', 5),
                    self::item('I.3.b', 'b. Là thí sinh tham gia các cuộc thi học thuật do Khoa/Nhà trường phát động', 3),
                    self::item('I.3.c', 'c. Đạt thành tích các cuộc thi học thuật ở mục 3b.', 4),
                    self::item('I.3.d', 'd. Tham gia cổ vũ các hoạt động học thuật', 3),
                    self::heading('I.4', '4. Trách nhiệm và thái độ tham gia các hoạt động học tập, nghiên cứu khoa học, sinh hoạt ngoại khóa'),
                    self::item('I.4.a', 'a. Là thành viên thuộc các CLB Học thuật', 2),
                    self::item('I.4.b', 'b. Có tham gia các chương trình hội thảo, workshop, tọa đàm...', 5),
                    self::item('I.4.c', 'c. Tham quan thực tế doanh nghiệp/tập huấn do Nhà trường tổ chức', 3),
                    self::item('I.4.d', 'd. Không tham gia bất kỳ hoạt động nào', -5),
                ],
            ],
            [
                'code' => 'TC02',
                'name' => 'Trách nhiệm chấp hành pháp luật và nội quy, quy chế của nhà trường',
                'description' => 'Trách nhiệm chấp hành pháp luật và nội quy, quy chế của nhà trường (tối đa 25 điểm).',
                'max' => 25,
                'items' => [
                    self::heading('II.1', '1. Trách nhiệm chấp hành các quy định của pháp luật đối với công dân, các văn bản chỉ đạo của Bộ, ngành, của cơ quan quản lý thực hiện trong nhà trường'),
                    self::item('II.1.a', 'a. Không vi phạm pháp luật, chủ trương các cấp.', 5),
                    self::item('II.1.b', 'b. Không vi phạm nội quy thông báo khác của nhà trường.', 5),
                    self::heading('II.2', '2. Trách nhiệm chấp hành các nội quy, quy chế và các quy định khác của nhà trường'),
                    self::item('II.2.a', 'a. Tham gia đầy đủ sinh hoạt lớp', 5),
                    self::item('II.2.b', 'b. Tham gia đầy đủ sinh hoạt công dân HSSV, sinh hoạt đầu học kỳ/khóa', 4),
                    self::item('II.2.c', 'c. Vắng sinh hoạt công dân, sinh hoạt đầu học kỳ/khóa không lý do', -4),
                    self::item('II.2.d', 'd. Đóng học phí đúng hạn', 5),
                    self::item('II.2.e', 'e. Đóng bảo hiểm y tế - tai nạn theo quy định', 3),
                    self::item('II.2.f', 'f. Thực hiện khảo sát hoạt động giảng dạy và đánh giá học tập theo chỉ thị nhà trường', 3),
                    self::item('II.2.g', 'g. Hoàn tất hồ sơ HSSV', 3),
                    self::item('II.2.h', 'h. Vi phạm xử lý kỷ luật mức khiển trách', -5),
                    self::item('II.2.i', 'i. Vi phạm xử lý kỷ luật mức cảnh cáo', -10),
                    self::item('II.2.j', 'j. Vi phạm xử lý kỷ luật mức đình chỉ học tập', -25),
                ],
            ],
            [
                'code' => 'TC03',
                'name' => 'Trách nhiệm tham gia các hoạt động chính trị - xã hội, văn hóa, văn nghệ, thể thao',
                'description' => 'Trách nhiệm tham gia các hoạt động chính trị - xã hội, văn hóa, văn nghệ, thể thao, phòng chống tội phạm, tệ nạn xã hội (tối đa 20 điểm).',
                'max' => 20,
                'items' => [
                    self::heading('III.1', '1. Trách nhiệm và hiệu quả tham gia các hoạt động rèn luyện về chính trị, xã hội, văn hóa, văn nghệ, thể thao'),
                    self::item('III.1.a', 'a. Có tham gia hoạt động cấp Khoa, cấp Trường', 3),
                    self::item('III.1.b', 'b. Có tham gia hoạt động từ cấp Thành phố trở lên', 5),
                    self::item('III.1.c', 'c. Có tham gia các hoạt động Đoàn - Hội', 3),
                    self::item('III.1.d', 'd. Có thành tích, giải thưởng khi tham gia các hoạt động thuộc tiêu chí 1', 5),
                    self::heading('III.note', 'Lưu ý: Sinh viên là người khuyết tật liên hệ P.CTSV để được hỗ trợ'),
                    self::heading('III.2', '2. Trách nhiệm tham gia các hoạt động công ích, tình nguyện, công tác xã hội'),
                    self::item('III.2.a', 'a. Có tham gia các hoạt động: Chủ nhật xanh, thăm và chăm sóc trẻ em mồ côi, gia đình khó khăn, hiến máu nhân đạo, xuân tình nguyện, mùa hè xanh, tiếp sức mùa thi...', 5),
                    self::item('III.2.b', 'b. Tham gia các hoạt động và được khen thưởng', 10),
                    self::item('III.2.c', 'c. Là cộng tác viên hỗ trợ tích cực hoạt động/sự kiện của nhà trường', 3),
                    self::item('III.2.d', 'd. Tích cực tham gia like và share các thông tin hoạt động từ các kênh chính thống của nhà trường: itc.edu.vn, fanpage, zalo...', 3),
                    self::heading('III.3', '3. Tham gia tuyên truyền, phòng chống tội phạm và các tệ nạn xã hội'),
                    self::item('III.3.a', 'a. Tham gia tuyên truyền phòng chống tệ nạn xã hội, an ninh mạng, bảo vệ môi trường', 3),
                    self::item('III.3.b', 'b. Có những nội dung sáng tạo phù hợp phục vụ cho các công tác tuyên truyền được các cấp ghi nhận', 3),
                    self::item('III.3.c', 'c. Có ý thức like và share đồng hành trong công tác truyền thông', 3),
                ],
            ],
            [
                'code' => 'TC04',
                'name' => 'Trách nhiệm công dân trong quan hệ cộng đồng',
                'description' => 'Trách nhiệm công dân trong quan hệ cộng đồng (tối đa 15 điểm).',
                'max' => 15,
                'items' => [
                    self::heading('IV.1', '1. Tham gia tuyên truyền các chủ trương của Đảng, chính sách, pháp luật của Nhà nước trong cộng đồng'),
                    self::item('IV.1.a', 'a. Chấp hành tốt các chủ trương, chính sách, pháp luật của Đảng và Nhà nước', 5),
                    self::item('IV.1.b', 'b. Thực hiện trách nhiệm công dân số, bảo vệ dữ liệu cá nhân, an toàn thông tin', 5),
                    self::item('IV.1.c', 'c. Đăng tải thông tin sai sự thật', -5),
                    self::item('IV.1.d', 'd. Xúc phạm cá nhân, tổ chức trên mạng xã hội', -5),
                    self::item('IV.1.e', 'e. Gian lận học thuật bằng AI', -5),
                    self::item('IV.1.f', 'f. Vi phạm quy định bảo mật dữ liệu', -5),
                    self::item('IV.1.g', 'g. Vi phạm ATGT, trật tự công cộng', -5),
                    self::heading('IV.2', '2. Trách nhiệm tham gia các hoạt động xã hội được ghi nhận, biểu dương, khen thưởng'),
                    self::item('IV.2.a', 'a. Tham gia quyên góp ủng hộ quỹ hỗ trợ được phát động bởi Nhà nước, Nhà trường, hoặc các đơn vị chính thống được cấp phép', 3),
                    self::item('IV.2.b', 'b. Được biểu dương, khen thưởng trong tham gia các hoạt động xã hội (có giấy khen, giấy chứng nhận từ ban tổ chức)', 3),
                    self::item('IV.2.c', 'c. Có tinh thần giúp đỡ bạn học, xây dựng tập thể', 3),
                    self::item('IV.2.d', 'd. Tham gia các hoạt động phục vụ cộng đồng, địa phương', 3),
                ],
            ],
            [
                'code' => 'TC05',
                'name' => 'Trách nhiệm và kết quả tham gia công tác cán bộ lớp, đoàn thể, tổ chức khác',
                'description' => 'Trách nhiệm và kết quả tham gia công tác cán bộ lớp, công tác đoàn thể, các tổ chức khác hoặc có thành tích xuất sắc trong học tập, rèn luyện (tối đa 20 điểm).',
                'max' => 20,
                'items' => [
                    self::heading('V.1', '1. Trách nhiệm, tinh thần, thái độ, uy tín, kỹ năng tổ chức và hiệu quả công việc của sinh viên được phân công nhiệm vụ quản lý lớp, Đảng, Đoàn, Hội và các tổ chức khác của nhà trường'),
                    self::item('V.1.a', 'a. Là Lớp trưởng, BCH Đoàn trường, BCH Hội sinh viên trường', 5),
                    self::item('V.1.b', 'b. Là Lớp phó, BCH Đoàn khoa, BCH LCH SV; BCH CĐ, BCH chi hội lớp', 3),
                    self::item('V.1.c', 'c. Là Đảng viên/Đối tượng Đảng thuộc Đảng CS Việt Nam', 3),
                    self::item('V.1.d', 'd. Là Đoàn viên TNCS Hồ Chí Minh', 2),
                    self::item('V.1.e', 'e. Là Hội viên Hội Sinh viên Trường', 2),
                    self::item('V.1.f', 'f. Là Ban Điều hành/Ban Chủ nhiệm Câu Lạc bộ/Đội/Nhóm', 4),
                    self::item('V.1.g', 'g. Được Đoàn thanh niên, Hội sinh viên Trường biểu dương, khen thưởng', 3),
                    self::heading('V.2', '2. Hỗ trợ và tham gia tích cực vào các hoạt động chung của lớp, khoa và nhà trường'),
                    self::item('V.2.a', 'a. Có ý kiến đóng góp tích cực trong công tác xây dựng phong trào thi đua học tập tốt', 3),
                    self::item('V.2.b', 'b. Có đóng góp tích cực trong công tác tổ chức các hoạt động sinh hoạt lớp, hoạt động khoa, trường', 3),
                    self::heading('V.3', '3. Có thành tích trong nghiên cứu khoa học, tham gia các cuộc thi, sáng kiến cải tiến kỹ thuật, hoạt động khởi nghiệp và các cuộc thi, hoạt động khác dành cho sinh viên được nhà trường hoặc cơ quan có thẩm quyền khen thưởng'),
                    self::item('V.3.a', 'a. Sinh viên có hoàn cảnh gia đình đặc biệt khó khăn nhưng tích cực trong học tập, rèn luyện.', 5),
                    self::item('V.3.b', 'b. Sinh viên đạt giải thưởng nghiên cứu khoa học hoặc là thành viên đội tuyển trường đạt giải thưởng các cuộc thi, hội thi, hoạt động từ cấp Khoa/trường', 5),
                    self::item('V.3.c', 'c. Sinh viên đạt giải thưởng nghiên cứu khoa học hoặc là thành viên đội tuyển trường đạt giải thưởng các cuộc thi, hội thi, hoạt động từ cấp tỉnh, thành phố trực thuộc trung ương trở lên.', 10),
                    self::item('V.3.d', 'd. Sinh viên được biểu dương, khen thưởng từ cấp tỉnh, thành phố trực thuộc trung ương trở lên về công tác giữ gìn trật tự xã hội, bảo vệ pháp luật, cứu người; danh hiệu Sinh viên 5 tốt; học tập và làm theo tấm gương đạo đức Hồ Chí Minh.', 10),
                    self::item('V.3.e', 'e. Sinh viên nhận bằng khen cấp trung ương về công tác Đoàn Thanh niên, Hội Sinh viên, Hội Liên hiệp thanh niên.', 10),
                ],
            ],
        ];
    }

    private static function heading(string $code, string $name): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'type' => 'heading',
            'points' => null,
        ];
    }

    private static function item(string $code, string $name, int $points): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'type' => 'item',
            'points' => $points,
        ];
    }
}
