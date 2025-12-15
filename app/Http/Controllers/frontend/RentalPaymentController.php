<?php

namespace App\Http\Controllers\frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\RentalPayment;
use App\Models\RentalOrder;
use App\Models\RentalReceipt;
use App\Models\RentalCars; // Or RentalCar depending on model name, check Use
use App\Models\RentalRenewal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RentalPaymentController extends Controller
{
    // Handle VNPAY Return for Rental Creation
    public function vnpay_return(Request $request)
    {
        $vnp_HashSecret = env('VNPAY_HASH_SECRET'); 
        $inputData = $request->all();

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = "";
        foreach ($inputData as $key => $value) {
            $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
        }
        $hashData = trim($hashData, '&');
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash === $vnp_SecureHash) {
            $transactionCode = $inputData['vnp_TxnRef'] ?? null;
            $payment = RentalPayment::where('transaction_code', $transactionCode)->first();

            if (!$payment) {
                toastr()->error("Không tìm thấy giao dịch liên kết!");
                return redirect()->route('rent.car');
            }

            $order = $payment->rentalOrder;
            $receipt = $order->rentalReceipts->first(); // Assuming one receipt per order
            $rentalCar = $receipt ? $receipt->rentalCar : null;

            if ($inputData['vnp_ResponseCode'] == '00') {
                // SUCCESS
                DB::beginTransaction();
                try {
                    $payment->update([
                        'status_deposit' => 'Successful',
                        'payment_date' => now(),
                    ]);

                    $order->update(['status' => 'Deposit Paid']);
                    
                    // Receipt is already 'Active' from creation, but we can confirm/leave it
                    // Maybe update to 'Active' if logic requires confirmation
                    
                    // Note: If full payment was made directly (future feature), handle here. 
                    // For now assume deposit.

                    DB::commit();
                    toastr()->success("Thanh toán cọc thành công!");
                    return redirect()->route('rentalHistory'); // Redirect to history
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Rental Payment Update Failed: " . $e->getMessage());
                    toastr()->error("Lỗi cập nhật trạng thái đơn hàng.");
                    return redirect()->route('rent.car');
                }
            } else {
                // FAILED / CANCELED
                DB::beginTransaction();
                try {
                    $payment->update(['status_deposit' => 'Failed']);
                    $order->update(['status' => 'Canceled']);
                    
                    if ($receipt) {
                        $receipt->update(['status' => 'Canceled']);
                    }

                    // Release Car if needed - depends on if we marked it busy on create
                    // RentCarController just inserted receipt. 
                    // Usually we might want to ensure car availability is reset?
                    // But availability is often checked against Active receipts.
                    // Since verification requires "Existing Blockers" to be solved, and Step 1100 explicitly mentioned:
                    // "RentalCars' availability_status to 'Available'"
                    
                    if ($rentalCar) {
                        $rentalCar->update(['availability_status' => 'Available']);
                    }

                    DB::commit();
                    toastr()->error("Giao dịch thanh toán thất bại hoặc bị hủy.");
                    return redirect()->route('rent.car');
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Rental Payment Fail Update Failed: " . $e->getMessage());
                    return redirect()->route('rent.car');
                }
            }
        } else {
            toastr()->error("Chữ ký không hợp lệ!");
            return redirect()->route('rent.car');
        }
    }

    // Handle VNPAY Return for Renewal
    public function vnpay_return_renewal(Request $request)
    {
        $vnp_HashSecret = env('VNPAY_HASH_SECRET'); 
        $inputData = $request->all();

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = "";
        foreach ($inputData as $key => $value) {
            $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
        }
        $hashData = trim($hashData, '&');
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash === $vnp_SecureHash) {
            $renewalId = $inputData['vnp_TxnRef'] ?? null; 
            // Note: We used renewal_id as TxnRef in payment initiator
            
            $renewal = RentalRenewal::find($renewalId);

            if (!$renewal) {
                toastr()->error("Không tìm thấy yêu cầu gia hạn!");
                return redirect()->route('rentalHistory');
            }

            if ($inputData['vnp_ResponseCode'] == '00') {
                // SUCCESS
                DB::beginTransaction();
                try {
                    $renewal->update(['status' => 'Completed']);
                    
                    $receipt = $renewal->rentalReceipt;
                    $extendDays = $renewal->new_end_date->diffInDays($receipt->rental_end_date);
                    
                    // Create New Order for Renewal History
                    $newOrder = RentalOrder::create([
                        'user_id' => $receipt->rentalOrder->user_id,
                        'rental_id' => $receipt->rental_id,
                        'status' => 'Paid',
                        'order_date' => now(),
                        'renew_order' => true,
                    ]);

                     // Update Receipt or Create New Receipt (Depending on Business Logic)
                     // Here we extend the current receipt OR create a new one. 
                     // Given 'manual extend' logic created a new receipt, we should probably align.
                     // But typically 'renewal' extends the existing contract. 
                     // Let's UPDATE the receipt end date for simplicity and continuity unless specified otherwise.
                     // Wait, `manualExtend` created a NEW receipt. Let's follow that pattern to keep history clear?
                     // Actually, if we create a new receipt, the old one remains 'Active' or 'Completed'?
                     // Manual Extend Logic:
                     // if Active -> New Receipt (Active), Old Receipt (Active) -- illogical?
                     // Let's just update the existing receipt's end date and log the renewal.
                     // BUT, if we want to track payments separate, maybe just update.
                     
                     // RE-READING LOGIC: User wants "Gia hạn" (Extend).
                     // Simple approach: Update existing receipt end_date.
                     
                     $receipt->update([
                         'rental_end_date' => $renewal->new_end_date,
                         'total_cost' => $receipt->total_cost + $renewal->renewal_cost,
                     ]);
                     
                     // Create Payment Record
                     RentalPayment::create([
                        'order_id' => $receipt->order_id, // Link to original order or new? Original seems safer for now.
                        'status_deposit' => 'Successful',
                        'full_payment_status' => 'Successful',
                        'deposit_amount' => 0,
                        'total_amount' => $renewal->renewal_cost,
                        'remaining_amount' => 0,
                        'due_date' => now(),
                        'payment_date' => now(),
                        'transaction_code' => $renewalId, // Use renewal ID or VNPAY Ref
                    ]);

                    DB::commit();
                    toastr()->success("Thanh toán gia hạn thành công!");
                    return redirect()->route('rentalHistory');
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Renewal Payment Update Failed: " . $e->getMessage());
                    toastr()->error("Lỗi cập nhật gia hạn.");
                    return redirect()->route('rentalHistory');
                }
            } else {
                // FAILED
                 $renewal->update(['status' => 'Failed']);
                 toastr()->error("Thanh toán gia hạn thất bại.");
                 return redirect()->route('rentalHistory');
            }
        } else {
            toastr()->error("Chữ ký không hợp lệ!");
            return redirect()->route('rentalHistory');
        }
    }

    public function vnpay_payment_renewal(Request $request) {
        $renewalId = $request->renewal_id;
        $renewal = RentalRenewal::findOrFail($renewalId);
        
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = route('rental.payment.vnpay_return_renewal');
        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');

        $vnp_TxnRef = $renewalId; // Use Renewal ID as transaction ref
        $vnp_OrderInfo = "Thanh toan gia han hop dong #" . $renewalId;
        $vnp_OrderType = "billpayment";
        $vnp_Amount = $renewal->renewal_cost * 100;
        $vnp_Locale = "vn";
        $vnp_IpAddr = $request->ip();

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

        return redirect()->away($vnp_Url);
    }

}
