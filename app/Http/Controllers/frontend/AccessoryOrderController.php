<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderAccessory;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccessoryOrderController extends Controller
{
    public function showCheckoutForm(Request $request)
    {
        try {
            $user = Auth::guard('account')->user();

            if (!$user) {
                return redirect()->route('customer.login')->with('error', 'You need to log in first.');
            }

            $selectedItems = $request->query('items');

            if (empty($selectedItems)) {
                return redirect()->route('show.cart')->with('error', 'Please select at least one item to checkout.');
            }

            $cartItems = Cart::with(['accessory:accessory_id,price,name,image_url'])
                            ->where('account_id', $user->id)
                            ->whereIn('accessory_id', $selectedItems)
                            ->get();

            if ($cartItems->isEmpty()) {
                return redirect()->route('show.cart')->with('info', 'Your cart is empty or selected items are not valid.');
            }

            $totalPrice = $cartItems->sum(function ($item) {
                return $item->quantity * $item->accessory->price;
            });

            $cartCount = $cartItems->sum('quantity');

            return view('frontend.accessories.checkout', compact('cartItems', 'totalPrice', 'cartCount', 'user'));
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while proceeding to checkout.');
        }
    }

    public function processCheckout(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::guard('account')->user();
            if (!$user) {
                return redirect()->route('customer.login')->with('error', 'You need to log in first.');
            }

            $selectedItems = $request->input('items');

            if (empty($selectedItems)) {
                return redirect()->route('show.cart')->with('error', 'Please select at least one item to checkout.');
            }

            $cartItems = Cart::with('accessory')
                            ->where('account_id', $user->id)
                            ->whereIn('accessory_id', $selectedItems)
                            ->get();

            if ($cartItems->isEmpty()) {
                return redirect()->route('show.cart')->with('info', 'Your cart is empty or selected items are not valid.');
            }

            $totalPrice = $cartItems->sum(function ($item) {
                return $item->quantity * $item->accessory->price;
            });

            // Create Order
            $order = new Order();
            $order->account_id = $user->id;
            $order->status_order = 0; // Pending
            $order->order_date = now();
            $order->save();
            $order_id = $order->order_id;

            // Create Order Accessories and update stock
            foreach ($cartItems as $item) {
                OrderAccessory::create([
                    'order_id' => $order_id,
                    'accessory_id' => $item->accessory_id,
                    'quantity' => $item->quantity,
                    'price' => $item->accessory->price,
                ]);

                $accessory = $item->accessory;
                $accessory->quantity -= $item->quantity;
                $accessory->save();
            }

            $vnp_TxnRef = uniqid();
            // Create Payment
            $payment = new Payment();
            $payment->payment_id = 'PAY-ACCESSORY-' . time();
            $payment->order_id = $order_id;
            $payment->VNPAY_ID = $vnp_TxnRef;
            $payment->status_deposit = 0; // Pending full payment for accessories
            $payment->status_payment_all = 0; // Pending
            $payment->deposit_amount = $totalPrice; // Full amount as deposit
            $payment->remaining_amount = 0;
            $payment->total_amount = $totalPrice;
            $payment->deposit_deadline = now()->addMinutes(15);
            $payment->payment_deadline = now()->addMinutes(15);
            $payment->save();

            // Clear selected items from Cart
            Cart::where('account_id', $user->id)->whereIn('accessory_id', $selectedItems)->delete();

            // VNPay redirection
            $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
            $vnp_Returnurl = route('accessories.payment.vnpay_return');
            $vnp_TmnCode = env('VNPAY_TMN_CODE'); // Your VNPAY TmnCode
            $vnp_HashSecret = env('VNPAY_HASH_SECRET'); // Your VNPAY HashSecret

            $inputData = [
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => (int)($totalPrice * 100), // Amount in VND cents
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $request->ip(),
                "vnp_Locale" => "vn",
                "vnp_OrderInfo" => "Thanh toan don hang phu kien",
                "vnp_OrderType" => "billpayment",
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $payment->VNPAY_ID,
            ];

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

            $vnp_Url .= "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }

            DB::commit();

            return redirect($vnp_Url);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'An error occurred during checkout: ' . $e->getMessage());
        }
    }

    public function vnpay_return(Request $request)
    {
        $vnp_HashSecret = env('VNPAY_HASH_SECRET'); // Your VNPAY HashSecret
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
            if ($inputData['vnp_ResponseCode'] == '00') {
                $transactionCode = $inputData['vnp_TxnRef'] ?? null;

                if ($transactionCode) {
                    $payment = Payment::where('VNPAY_ID', $transactionCode)->first();

                    if ($payment) {
                        $payment->update([
                            'status_deposit' => 1, // Paid in full
                            'status_payment_all' => 1,
                            'payment_deposit_date' => now(),
                        ]);

                        $order = Order::find($payment->order_id);
                        if ($order) {
                            $order->update(['status_order' => 1]); // Completed
                        }

                        toastr()->success("Payment successful!");
                        return redirect()->route('customer.accessories');
                    }
                }
                toastr()->error("Transaction not found!");
                return redirect()->route('CustomerDashBoard.accsessories');
            } else {
                toastr()->error("Payment failed. Please try again.");
                return redirect()->route('CustomerDashBoard.accsessories');
            }
        } else {
            toastr()->error("Invalid signature.");
            return redirect()->route('CustomerDashBoard.accsessories');
        }
    }
}
