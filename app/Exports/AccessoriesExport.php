<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\FromArray;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class AccessoriesExport implements WithHeadings, WithStyles, WithColumnWidths, FromArray, WithEvents
{
    /**
     * Tiêu đề của các cột
     */
    public function headings(): array
    {
        return [
            'name',              // Tên phụ kiện
            'category',          // Danh mục
            'price',             // Giá
            'quantity',          // Số lượng
            'description',       // Mô tả
            'image_url',         // URL hình ảnh
        ];
    }

    /**
     * Dữ liệu mẫu
     */
    public function array(): array
    {
        return [
            ['Máy Hút Bụi Cầm Tay Không Dây Baseus A7 Cordless Car Vacuum Cleaner 6000Pa (78W - 2000 mAh)', 'Car Care', '1500000', '10', 'Giới Thiệu Máy Hút Bụi Cầm Tay Không Dây Baseus A7 Cordless Car Vacuum Cleaner 6000Pa (78W - 2000 mAh) - Máy hút bụi cầm tay cao cấp Baseus A7 Cordless sở hữu thiết kế nhỏ gọn, tinh tế, dễ dàng thao tác ngay cả trong những không gian hẹp. Sản phẩm không chỉ tiện lợi cho việc dọn dẹp nội thất xe hơi mà còn phù hợp với nhiều môi trường khác nhau.', 'https://product.hstatic.net/1000152881/product/baseus_a7_cordless_car_vacuum_cleaner_006_2636d4eb46ed4878aa7361fe5b94718b_1024x1024.jpg'],
            ['Cam Hành Trình Cho Ô Tô Baseus PrimeTrip VD1 Pro 4K', 'Exterior', '2800000', '5', 'Cam Hành Trình Cho Ô Tô', 'https://example.com/image/car-cam.jpg'],
            ['Meguiar Car Wax', 'Car Care', '300000', '50', 'Sáp bóng ô tô Meguiar.', 'https://example.com/image/meguiar-wax.jpg'],
        ];
    }

    /**
     * Định dạng style cho các ô
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // In đậm tiêu đề hàng đầu tiên
            'A1:F1' => ['alignment' => ['horizontal' => 'center']], // Canh giữa hàng tiêu đề
        ];
    }

    /**
     * Độ rộng của các cột
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30, // name
            'B' => 20, // category
            'C' => 15, // price
            'D' => 15, // quantity
            'E' => 40, // description
            'F' => 50, // image_url
        ];
    }

    /**
     * Thêm dropdown vào cột category bằng Data Validation
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Tạo Data Validation cho cột B (category)
                $dataValidation = $sheet->getCell('B2')->getDataValidation();
                $dataValidation->setType(DataValidation::TYPE_LIST);
                $dataValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $dataValidation->setAllowBlank(false);
                $dataValidation->setShowDropDown(true);
                $dataValidation->setErrorTitle('Invalid Input');
                $dataValidation->setError('Please select a value from the list.');
                $dataValidation->setFormula1('"Interior,Exterior,Car Care"');

                // Áp dụng Data Validation cho các dòng từ 2 đến 100 trong cột B
                for ($row = 2; $row <= 100; $row++) {
                    $sheet->getCell("B$row")->setDataValidation(clone $dataValidation);
                }
            },
        ];
    }
}
