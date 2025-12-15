<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RentalReceipt;
use App\Models\RentalRenewal;

class RentalReceiptController extends Controller
{
    public function index()
    {
        // Lấy tất cả hóa đơn thuê xe
        $rentalReceipts = RentalReceipt::with(['renewals', 'rentalCar.carDetails', 'rentalOrder'])->orderBy('created_at', 'desc')->get();

        // Lấy tất cả yêu cầu gia hạn đang chờ xử lý
        $rentalRenewals = RentalRenewal::where('status', 'Pending')->with('rentalReceipt')->orderBy('request_date', 'desc')->get();

        return view('Backend.rentalOrder.renewalReceipt', compact('rentalReceipts', 'rentalRenewals'));
    }

}
