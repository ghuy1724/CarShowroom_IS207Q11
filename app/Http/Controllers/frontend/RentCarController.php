<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CarDetails;
use App\Models\RentalCars;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\RentalReceipt;
use App\Models\RentalRenewal;
use App\Models\Account;
use App\Models\RentalPayment;

class RentCarController extends Controller
{
    public function carRent(){
        $rental_car = CarDetails::with('rentalCars')->get();
        return view("frontend.car_rent.car_rent", compact('rental_car'));
    }

    public function show($id)
    {
        // Lấy thông tin chi tiết xe cùng thông tin thuê
        $car = CarDetails::with('rentalCars')->where('car_id', $id)->first();

        // Trường hợp không tìm thấy xe
        if (!$car) {
            return response()->json(['error' => 'Car not found'], 404);
        }

        // Kiểm tra nếu không có thông tin thuê
        $rentalCar = $car->rentalCars->first();
        if (!$rentalCar) {
            return response()->json([
                'name' => $car->name,
                'brand' => $car->brand,
                'model' => $car->model,
                'year' => $car->year,
                'seat_capacity' => $car->seat_capacity,
                'max_speed' => $car->max_speed,
                'image_url' => $car->image_url,
                'rental_price_per_day' => null,
                'rental_conditions' => 'No rental conditions available.',
                'license_plate_number' => 'N/A',
            ]);
        }

        // Trả về thông tin chi tiết xe và thông tin thuê
        return response()->json([
            'name' => $car->name,
            'brand' => $car->brand,
            'model' => $car->model,
            'year' => $car->year,
            'seat_capacity' => $car->seat_capacity,
            'max_speed' => $car->max_speed,
            'image_url' => $car->image_url,
            'rental_price_per_day' => $rentalCar->rental_price_per_day,
            'rental_conditions' => $rentalCar->rental_conditions,
            'license_plate_number' => $rentalCar->license_plate_number,
        ]);
    }

    public function showRentForm($id)
    {
        // Lấy thông tin chi tiết xe kèm thông tin thuê
        $car = CarDetails::with('rentalCars')->where('car_id', $id)->first();

        // Kiểm tra nếu xe không tồn tại
        if (!$car) {
            return redirect()->route('rent.car')->with('error', 'Car not found.');
        }

        // Lấy thông tin cho thuê đầu tiên
        $rentalCar = $car->rentalCars->first();

        // Kiểm tra nếu không có thông tin thuê
        if (!$rentalCar) {
            return redirect()->route('rent.car')->with('error', 'No rental information available for this car.');
        }

        // Trả về thông tin chi tiết xe và thông tin thuê
        return view('frontend.car_rent.rentForm', compact('car', 'rentalCar'));
    }

    public function rentCar(Request $request)
    {
        $request->validate([
            'rental_id' => 'required|exists:rental_cars,rental_id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|min:10|max:15',
            'start_date' => 'required|date|after_or_equal:today',
            'rental_days' => 'required|integer|min:1', // Số ngày thuê
            'total_cost' => 'required|numeric|min:0',
            'deposit_amount' => 'required|numeric|min:0',
            'rental_price_per_day' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Tạo đơn hàng (rental_order)
            $orderId = DB::table('rental_orders')->insertGetId([
                'user_id' => auth('account')->id(),
                'rental_id' => $request->rental_id, // Lấy rental_id từ form
                'status' => 'Pending', // Trạng thái ban đầu là 'Pending'
                'order_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Tính ngày kết thúc thuê
            $rental_start_date = Carbon::parse($request->start_date); // Ngày bắt đầu do người dùng chọn
            // Logic: start_date + (rental_days - 1) days.
            $rental_end_date = $rental_start_date->copy()->addDays($request->rental_days - 1)->endOfDay();

            // Lưu dữ liệu vào bảng rental_receipt
            DB::table('rental_receipt')->insert([
                'order_id' => $orderId, // Lấy ID của đơn hàng vừa tạo
                'rental_id' => $request->rental_id,
                'rental_start_date' => $request->start_date,
                'rental_end_date' => $rental_end_date, // Use calculated end date
                'rental_price_per_day' => $request->rental_price_per_day,
                'total_cost' => $request->total_cost,
                'status' => 'Active', 
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // --- Payment Logic Start ---
            
            // Lấy thông tin thanh toán
            // Clean amount: remove all non-numeric characters (assuming VND is integer)
            $paymentDepositAmount = preg_replace('/[^0-9]/', '', $request->deposit_amount); 
            $totalAmount = preg_replace('/[^0-9]/', '', $request->total_cost);
            $remainingAmount = $totalAmount - $paymentDepositAmount; // Số dư còn lại

            // Tạo mã giao dịch duy nhất
            $vnp_TxnRef = uniqid();

            // Tạo bản ghi trong bảng rental_payments
            RentalPayment::create([
                'order_id' => $orderId,
                'status_deposit' => 'Pending',
                'full_payment_status' => 'Pending',
                'deposit_amount' => $paymentDepositAmount,
                'total_amount' => $totalAmount,
                'remaining_amount' => $remainingAmount,
                'due_date' => now()->addMinutes(5),
                'payment_date' => now(),
                'transaction_code' => $vnp_TxnRef,
            ]);

            // Cấu hình VNPAY
            $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
            $vnp_Returnurl = route('rental.payment.vnpay_return'); // Đường dẫn trả về sau khi thanh toán
            $vnp_TmnCode = env('VNPAY_TMN_CODE'); // Mã website tại VNPAY
            $vnp_HashSecret = env('VNPAY_HASH_SECRET'); // Chuỗi bí mật
            $vnp_BankCode = ''; // Để trống để VNPay hiển thị tất cả phương thức

            $vnp_Amount = (int)$paymentDepositAmount * 100; // Đơn vị VND * 100, ensure integer
            $vnp_IpAddr = $request->ip();
            $vnp_OrderInfo = 'Thanh toán hóa đơn thuê xe #ORDER-' . $orderId;
            $vnp_OrderType = 'billpayment';
            $vnp_Locale = 'vn';

            // Tạo dữ liệu cho VNPAY
            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
            );

            if (isset($vnp_BankCode) && $vnp_BankCode != "") {
                $inputData['vnp_BankCode'] = $vnp_BankCode;
            }

            // Ký hash dữ liệu
            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            
            DB::commit();

            // Redirect người dùng tới URL thanh toán
            return redirect()->away($vnp_Url);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rental order creation failed: ' . $e->getMessage());

            toastr()->error('Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.');
            return redirect()->back()->withInput();
        }
    }

    public function handleExtend(Request $request)
    {
        $validated = $request->validate([
            'receipt_id' => 'required|exists:rental_receipt,receipt_id',
            'extend_days' => 'required|integer|min:1',
        ]);

        $receipt = RentalReceipt::findOrFail($validated['receipt_id']);
        $extendDays = $validated['extend_days'];

        // Kiểm tra trạng thái hiện tại của biên lai
        if (!in_array($receipt->status, ['Active', 'Completed'])) {
            toastr()->error('Không thể gia hạn cho biên lai này.');
            return redirect()->back();
        }
        
        // Xử lý logic gia hạn dựa trên trạng thái
        if ($receipt->status === 'Active') {
            // Ép kiểu và kiểm tra dữ liệu đầu vào
            $extendDays = (int) $validated['extend_days']; // Đảm bảo $extendDays là số nguyên
            $rentalEndDate = Carbon::parse($receipt->rental_end_date); // Đảm bảo là Carbon instance
        
            // Nới ngày kết thúc thêm số ngày gia hạn
            $newEndDate = $rentalEndDate->addDays($extendDays);
            
        } elseif ($receipt->status === 'Completed') {
            // Đặt ngày bắt đầu mới là ngày sau ngày kết thúc
            $newStartDate = Carbon::parse($receipt->rental_end_date)->addDay();
            $newEndDate = Carbon::parse($newStartDate)->addDays($extendDays - 1);
        }

        // Tính chi phí gia hạn
        $renewalCost = $receipt->rental_price_per_day * $extendDays;

        // Gửi yêu cầu gia hạn tới admin bằng cách thêm bản ghi mới vào bảng rental_renewals
        $renewal = RentalRenewal::create([
            'receipt_id' => $receipt->receipt_id,
            'request_date' => now(),
            'new_end_date' => $newEndDate,
            'renewal_cost' => $renewalCost,
            'status' => 'Pending',
        ]);

        // Trả về thông báo cho khách hàng
        toastr()->success('Yêu cầu gia hạn đã được gửi. Vui lòng chờ xác nhận!');
        return redirect()->back();
    }



}
