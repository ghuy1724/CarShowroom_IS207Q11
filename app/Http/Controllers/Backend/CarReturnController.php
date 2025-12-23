<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RentalReceipt;
use App\Models\RentalCars;
use App\Models\RentalRenewal;
use App\Models\RentalPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CarReturnController extends Controller
{
    /**
     * Display all rental receipts with filtering options
     */
    public function index()
    {
        $receipts = RentalReceipt::with(['rentalCar.carDetails', 'rentalOrder.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('Backend.rentalOrder.carReturn', compact('receipts'));
    }

    /**
     * Filter rental receipts by status and search query
     */
    public function filter(Request $request)
    {
        $query = RentalReceipt::with(['rentalCar.carDetails', 'rentalOrder.user']);

        // Search by receipt ID or customer name
        if ($request->filled('search')) {
            $query->where('receipt_id', 'like', '%' . $request->search . '%')
                ->orWhereHas('rentalOrder.user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $receipts = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['receipts' => $receipts]);
    }

    /**
     * Process car return
     */
    public function returnCar($receipt_id)
    {
        DB::beginTransaction();
        try {
            $receipt = RentalReceipt::with(['rentalCar', 'rentalOrder'])->findOrFail($receipt_id);

            // Check if receipt is eligible for return (Active or Overdue)
            if (!in_array($receipt->status, ['Active', 'Overdue'])) {
                toastr()->error('Xe này không thể trả. Trạng thái không hợp lệ.');
                return redirect()->back();
            }

            // Update receipt status to Completed
            $receipt->update(['status' => 'Completed']);

            // Update car availability status to Available
            if ($receipt->rentalCar) {
                $receipt->rentalCar->update(['availability_status' => 'Available']);
            }

            // Cancel any pending renewal requests for this receipt
            RentalRenewal::where('receipt_id', $receipt_id)
                ->whereIn('status', ['Pending', 'Approved'])
                ->update(['status' => 'Canceled']);

            DB::commit();

            toastr()->success('Xe đã được trả thành công.');
            return redirect()->route('carReturn');
        } catch (\Exception $e) {
            DB::rollBack();
            toastr()->error('Có lỗi xảy ra khi trả xe. Vui lòng thử lại.');
            return redirect()->back();
        }
    }

    /**
     * Process manual payment for overdue rental (admin only)
     */
    public function processManualPayment($receipt_id)
    {
        DB::beginTransaction();
        try {
            $receipt = RentalReceipt::with(['rentalCar', 'rentalOrder'])->findOrFail($receipt_id);

            // Check if receipt is overdue
            if ($receipt->status !== 'Overdue') {
                toastr()->error('Chỉ có thể thanh toán thủ công cho xe quá hạn.');
                return redirect()->back();
            }

            // Calculate overdue fee
            $endDate = Carbon::parse($receipt->rental_end_date);
            $today = Carbon::today();
            $overdueDays = $endDate->diffInDays($today) + 1;
            $overdueFee = $receipt->rental_price_per_day * $overdueDays;

            // Update receipt status to Completed
            $receipt->update(['status' => 'Completed']);

            // Update car availability status to Available
            if ($receipt->rentalCar) {
                $receipt->rentalCar->update(['availability_status' => 'Available']);
            }

            // Cancel any pending renewal requests for this receipt
            RentalRenewal::where('receipt_id', $receipt_id)
                ->whereIn('status', ['Pending', 'Approved'])
                ->update(['status' => 'Canceled']);

            // Create payment record for overdue fee
            RentalPayment::create([
                'order_id' => $receipt->order_id,
                'status_deposit' => 'Successful',
                'full_payment_status' => 'Successful',
                'deposit_amount' => 0,
                'total_amount' => $overdueFee,
                'remaining_amount' => 0,
                'due_date' => now(),
                'payment_date' => now(),
                'transaction_code' => 'MANUAL_OVERDUE_' . $receipt_id . '_' . time(),
            ]);

            DB::commit();

            toastr()->success("Thanh toán thủ công thành công. Phí quá hạn: " . number_format($overdueFee, 0, ',', '.') . " VNĐ");
            return redirect()->route('carReturn');
        } catch (\Exception $e) {
            DB::rollBack();
            toastr()->error('Có lỗi xảy ra khi thanh toán. Vui lòng thử lại.');
            return redirect()->back();
        }
    }
}
