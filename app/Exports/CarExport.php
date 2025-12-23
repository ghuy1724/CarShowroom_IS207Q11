<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\FromArray;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CarExport implements WithHeadings, WithStyles, WithColumnWidths, FromArray
{
    /**
     * Tiêu đề của các cột
     */
    public function headings(): array
    {
        return [
            'brand',
            'name',
            'model',
            'year',
            'engine_type',
            'seat_capacity',
            'engine_power',
            'max_speed',
            'trunk_capacity',
            'length',
            'width',
            'height',
            'description',
            'sale_price',
            'availability_status',
            'warranty_period',
            'sale_conditions',
            'image_url',
            'quantity',
            'acceleration_time',
            'fuel_efficiency',
            'torque',
        ];
    }

    /**
     * Dữ liệu mẫu
     */
    public function array(): array
    {
        return [
            [
                'Lamborghini',
                'Aventador S',
                'Coupe',
                '2021',
                'Gasoline',
                '2',
                '740 HP',
                '350 km/h',
                '19 cubic feet',
                '4797',
                '2265',
                '1136',
                'Mô tả chi tiết',
                '190000',
                'Available',
                '24',
                'Brand new, full warranty',
                'https://png.pngtree.com/png-clipart/20240810/original/pngtree-lamborghini-log-png-image_15743898.png',
                '3',
                2.5, // acceleration_time
                16.9, // fuel_efficiency
                720, // torque
            ],
            [
                'Porsche',
                '911 Carrera S',
                'Coupe',
                '2023',
                'Gasoline',
                '4',
                '443 HP',
                '308 km/h',
                '20 cubic feet',
                '4519',
                '1850',
                '1300',
                'Mô tả chi tiết',
                '2000000',
                'Available',
                '16',
                'Certified pre-owned',
                'https://porsche-vietnam.vn/wp-content/uploads/2025/02/992-2nd-c2-gts-modelimage-sideshot-840x473.png',
                '2',
                3.3,  // acceleration_time
                10.2, // fuel_efficiency
                530,   // torque
            ],
            [
                'Toyota',
                'Corolla',
                'Altis',
                '2022',
                'Hybrid',
                '5',
                '180 HP',
                '200 km/h',
                '13 cubic feet',
                '4650',
                '1775',
                '1435',
                'Mô tả chi tiết',
                '30000',
                'Available',
                '36',
                'Certified with warranty',
                'https://example.com/toyota-corolla.jpg',
                '4',
                9.0, // acceleration_time
                20.0, // fuel_efficiency
                400, // torque
            ],
        ];
    }

    /**
     * Định dạng style cho các ô
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // In đậm tiêu đề hàng đầu tiên
            'A1:V1' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    /**
     * Độ rộng của các cột
     */
    public function columnWidths(): array
    { 
        return [
            'A' => 15, // brand
            'B' => 20, // name
            'C' => 15, // model
            'D' => 10, // year
            'E' => 15, // engine_type
            'F' => 15, // seat_capacity
            'G' => 15, // engine_power
            'H' => 15, // max_speed
            'I' => 20, // trunk_capacity
            'J' => 10, // length
            'K' => 10, // width
            'L' => 10, // height
            'M' => 30, // description
            'N' => 15, // sale_price
            'O' => 20, // availability_status
            'P' => 15, // warranty_period
            'Q' => 30, // sale_conditions
            'R' => 50, // image_url
            'S' => 10, // quantity
            'T' => 20, // acceleration_time
            'U' => 20, // fuel_efficiency
            'V' => 20, // torque
        ];
    }
}
