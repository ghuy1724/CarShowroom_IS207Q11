<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SalesCars;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class paymentcontroller extends Controller
{
    /**
     * Xử lý thanh toán mua xe qua VNPAY (đặt cọc hoặc toàn phần).
     */
    public function vnpay_payment(Request $request)
    {
        // Custom validation that handles comma-formatted numbers
        $totalPrice = str_replace(',', '', $request->input('total-price', '0'));
        $depositAmount = str_replace(',', '', $request->input('payment_deposit_amount', '0'));
        
        Log::info('Payment request received', [
            'totalPrice' => $totalPrice,
            'depositAmount' => $depositAmount,
            'saleId' => $request->input('sale_id'),
            'authenticated' => Auth::guard('account')->check()
        ]);
        
        $request->validate([
            'sale_id' => 'required|exists:sales_cars,sale_id',
        ]);
        
        // Validate numeric values manually
        if (!is_numeric($totalPrice) || !is_numeric($depositAmount)) {
            Log::error('Invalid numeric values', ['totalPrice' => $totalPrice, 'depositAmount' => $depositAmount]);
            toastr()->error('Số tiền không hợp lệ.');
            return redirect()->back();
        }

        if (!Auth::guard('account')->check()) {
            Log::warning('User not authenticated for payment');
            toastr()->error('Vui lòng đăng nhập để thanh toán.');
            return redirect()->back();
        }

        DB::beginTransaction();

        try {
            $accountId = Auth::guard('account')->id();
            $saleId = $request->input('sale_id');
            $totalAmount = (float) $totalPrice;
            $depositAmount = (float) $depositAmount;
            $remainingAmount = max($totalAmount - $depositAmount, 0);

            Log::info('Processing payment', [
                'accountId' => $accountId,
                'saleId' => $saleId,
                'totalAmount' => $totalAmount,
                'depositAmount' => $depositAmount
            ]);

            // Kiểm tra xe còn số lượng
            $rawResult = DB::table('sales_cars')->where('sale_id', $saleId)->first();
            Log::info('Raw DB result', ['raw' => $rawResult]);
            
            $saleCar = SalesCars::findOrFail($saleId);
            Log::info('Car found', ['quantity' => $saleCar->quantity, 'is_deleted' => $saleCar->is_deleted, 'model' => $saleCar->toArray()]);
            
            if (is_null($saleCar->quantity) || $saleCar->quantity <= 0 || $saleCar->is_deleted) {
                Log::warning('Car not available', ['quantity' => $saleCar->quantity, 'is_deleted' => $saleCar->is_deleted]);
                toastr()->error('Xe không còn khả dụng.');
                return redirect()->back();
            }

            // Tạo đơn hàng
            $order = Order::create([
                'account_id' => $accountId,
                'sale_id' => $saleId,
                'status_order' => 0,
                'order_date' => now(),
            ]);

            Log::info('Order created', ['order_id' => $order->order_id ?? 'unknown']);

            // Gửi email xác nhận đặt cọc ngay khi tạo đơn
            try {
                $user = $order->account;
                if ($user && $user->email) {
                    Mail::send('emails.purchase_confirmation', [
                        'name' => $user->name,
                        'order_id' => $order->order_id,
                        'deposit_amount' => $depositAmount,
                        'remaining_amount' => $remainingAmount,
                        'total_amount' => $totalAmount,
                    ], function ($message) use ($user) {
                        $message->to($user->email)->subject('Xác nhận thanh toán đặt cọc mua xe');
                    });
                    Log::info('Purchase confirmation email sent to ' . $user->email);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send purchase confirmation email: ' . $e->getMessage());
            }

            // Tạo mã giao dịch VNPAY
            $vnp_TxnRef = 'SALE' . time() . rand(1000, 9999);

            Log::info('Creating payment record', [
                'order_id' => $order->order_id,
                'vnp_TxnRef' => $vnp_TxnRef,
                'depositAmount' => $depositAmount
            ]);

            // Tạo bản ghi thanh toán
            $payment = Payment::create([
                'order_id' => $order->order_id,
                'VNPAY_ID' => $vnp_TxnRef,
                'payment_deposit_date' => now(),
                'status_deposit' => 0, // Pending
                'status_payment_all' => 0, // Pending
                'deposit_amount' => $depositAmount,
                'remaining_amount' => $remainingAmount,
                'total_amount' => $totalAmount,
                'deposit_deadline' => now()->addDays(3),
                'payment_deadline' => now()->addDays(30),
            ]);

            Log::info('Payment record created', ['payment_id' => $payment->id ?? 'unknown']);

            DB::commit();

            // Cấu hình VNPAY
            $vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
            $vnp_Returnurl = route('payment.vnpay_return');
            $vnp_TmnCode = 'YNHSYV2M';
            $vnp_HashSecret = 'ATCT9RJYIMSNQ47T8J3AAM87W3NPPQS8';

            $vnp_Amount = $depositAmount * 100;
            $vnp_IpAddr = request()->ip();
            $vnp_OrderInfo = 'Thanh toan dat coc mua xe ORDER ' . $order->order_id;

            $inputData = [
                'vnp_Version' => '2.1.0',
                'vnp_TmnCode' => $vnp_TmnCode,
                'vnp_Amount' => $vnp_Amount,
                'vnp_Command' => 'pay',
                'vnp_CreateDate' => now()->format('YmdHis'),
                'vnp_CurrCode' => 'VND',
                'vnp_IpAddr' => $vnp_IpAddr,
                'vnp_Locale' => 'vn',
                'vnp_OrderInfo' => $vnp_OrderInfo,
                'vnp_OrderType' => 'billpayment',
                'vnp_ReturnUrl' => $vnp_Returnurl,
                'vnp_TxnRef' => $vnp_TxnRef,
            ];

            ksort($inputData);
            $query = '';
            $i = 0;
            $hashdata = '';
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . '=' . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . '=' . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . '=' . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . '?' . $query;
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

            Log::info('VNPAY Redirect URL', ['url' => $vnp_Url]);
            
            return redirect()->away($vnp_Url);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('VNPAY Payment Error (sale): ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            toastr()->error('Có lỗi xảy ra khi xử lý thanh toán. Vui lòng thử lại.');
            return redirect()->back();
        }
    }

    /**
     * Kết quả trả về từ VNPAY sau khi thanh toán đặt cọc mua xe.
     */
    public function vnpay_return(Request $request)
    {
        $vnp_HashSecret = 'ATCT9RJYIMSNQ47T8J3AAM87W3NPPQS8';
        $inputData = $request->all();

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = '';
        foreach ($inputData as $key => $value) {
            $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
        }
        $hashData = trim($hashData, '&');
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash === $vnp_SecureHash) {
            $responseCode = $inputData['vnp_ResponseCode'] ?? null;
            $txnRef = $inputData['vnp_TxnRef'] ?? null;

            if ($responseCode === '00' && $txnRef) {
                $payment = Payment::where('VNPAY_ID', $txnRef)->first();
                
                if ($payment) {
                    DB::beginTransaction();
                    try {
                        $payment->update([
                            'status_deposit' => 1,
                            'payment_deposit_date' => now(),
                        ]);

                        // Đánh dấu đơn hàng đang chờ thanh toán còn lại
                        $payment->order()->update([
                            'status_order' => 0,
                        ]);

                        // Gửi email xác nhận thanh toán
                        try {
                            $order = $payment->order;
                            $user = $order->account;
                            if ($user && $user->email) {
                                Mail::send('emails.purchase_confirmation', [
                                    'name' => $user->name,
                                    'order_id' => $order->order_id,
                                    'deposit_amount' => $payment->deposit_amount,
                                    'remaining_amount' => $payment->remaining_amount,
                                    'total_amount' => $payment->total_amount,
                                ], function ($message) use ($user) {
                                    $message->to($user->email)->subject('Xác nhận thanh toán đặt cọc mua xe');
                                });
                                Log::info('Purchase confirmation email sent to ' . $user->email);
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to send payment confirmation email: ' . $e->getMessage());
                        }

                        DB::commit();
                        toastr()->success('Thanh toán đặt cọc thành công.');
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Payment update error: ' . $e->getMessage());
                        toastr()->error('Có lỗi xảy ra. Vui lòng thử lại.');
                    }
                } else {
                    toastr()->error('Không tìm thấy giao dịch.');
                }
            } else {
                toastr()->error('Thanh toán thất bại. Mã lỗi: ' . $responseCode);
            }
        } else {
            toastr()->error('Chữ ký không hợp lệ.');
        }

        return redirect()->route('CarController.index');
    }
}
