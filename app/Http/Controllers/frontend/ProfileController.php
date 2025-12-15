<?php

namespace App\Http\Controllers\frontend;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\OrderAccessory;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    function viewprofile(Request $request)
    {
        return view("frontend.profilepage.informationuser");
    }
    function showResetPass(Request $request)
    {
        return view("frontend.profilepage.resetpass");
    }

    function invoiceInfo(Request $request)
    {
         return view("frontend.profilepage.invoiceInfo");
    }

    public function update(Request $request)
    {
        // Lấy thông tin người dùng từ session
        $user = session('login_account');

        // Xác thực dữ liệu
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:accounts,email,' . $user->id,
            'phone' => 'required|string|max:15|unique:accounts,phone,' . $user->id,
            'address' => 'nullable|string|max:255',
        ]);

        // Kiểm tra thông tin có thay đổi không
        if (
            $user->name === $request->name &&
            $user->email === $request->email &&
            $user->phone === $request->phone &&
            $user->address === $request->address
        ) {
            toastr()->warning("Không có thông tin thay đổi");
            return back();
        }

        // Cập nhật thông tin trong cơ sở dữ liệu
        $account = Account::find($user->id);
        $account->name = $request->name;
        $account->email = $request->email;
        $account->phone = $request->phone;
        $account->address = $request->address;
        $account->save();

        // Cập nhật session sau khi thay đổi
        session(['login_account' => $account]);

        // Trả về thông báo thành công
        toastr()->success("Cập nhật thông tin thành công");

        return back();
    }
    public function customer_car()
    {
        $user = Auth::guard('account')->user();
        $currentUserId = $user->id;

        // Lấy danh sách xe của khách hàng
        $customerCars = Payment::with([
            'order.salesCar.carDetails', // Nạp quan hệ từ Order -> SalesCar -> CarDetails
            'order.account',             // Nạp thông tin tài khoản của người đặt hàng
        ])
            ->whereHas('order', function ($query) use ($currentUserId) {
                $query->where('account_id', $currentUserId) // Lọc các order của khách hàng đang đăng nhập
                    ->where('status_order', 1);          // Thêm điều kiện lọc status_order = 1
            })
            ->orderBy('payment_deposit_date', 'desc') // Sắp xếp theo ngày thanh toán
            ->get();

        // Kiểm tra nếu xe trống
        if ($customerCars->isEmpty()) {
            return view('frontend.profilepage.customerCar', [
                'customerCars' => null,
                'message' => 'Không tìm thấy xe nào thuộc về bạn. Vui lòng kiểm tra lại đơn hàng.',
            ]);
        }

        // Trả về giao diện với dữ liệu
        return view('frontend.profilepage.customerCar', compact('customerCars'));
    }

    public function customer_accessories()
    {
        $user = Auth::guard('account')->user();
        $currentUserId = $user->id;

        $customerAccessories = OrderAccessory::with(['accessory', 'order'])
            ->whereHas('order', function ($query) use ($currentUserId) {
                $query->where('account_id', $currentUserId)
                      ->where('status_order', 1); // Only completed orders
            })
            ->get()
            ->groupBy('accessory_id')
            ->map(function ($group) {
                $quantity = $group->sum('quantity');
                $accessory = $group->first()->accessory;
                return (object) [
                    'accessory' => $accessory,
                    'quantity' => $quantity,
                ];
            });

        if ($customerAccessories->isEmpty()) {
            return view('frontend.profilepage.customer_accessories', [
                'customerAccessories' => null,
                'message' => 'You have not purchased any accessories yet.',
            ]);
        }

        return view('frontend.profilepage.customer_accessories', compact('customerAccessories'));
    }

    public function customer_car_detail($id)
    {
        // Find the order by ID
        $order = Order::with(['salesCar.carDetails'])
            ->where('order_id', $id)
            ->first();

        // Check if the order exists
        if (!$order) {
            return redirect()->back()->with('error', 'Order information not found.');
        }

        // Return the view to display order details
        return view('frontend.profilepage.customerCarDetails', compact('order'));
    }
}
