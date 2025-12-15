@extends('frontend.profilepage.viewprofile')
@section('main')
<div style="background-color: #f3f4f6">
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div class="container mt-4">
            <!-- Tabs -->
            <div class="d-flex border-bottom mb-3">
                <button class="tablinks active px-4 py-2" onclick="openTab(event, 'carsContainer')"
                    style="border: none; font-size: 18px; color: #007bff; font-weight: bold; border-bottom: 3px solid #007bff; background-color: transparent;">
                    Xe ô tô - Sản phẩm
                </button>
                <button class="tablinks px-4 py-2" onclick="openTab(event, 'rentalContainer')"
                    style="border: none; font-size: 18px; color: #6c757d; font-weight: normal; border-bottom: 3px solid transparent; background-color: transparent;">
                    Thuê xe
                </button>

            </div>

            <!-- Danh sách giao dịch Xe ô tô và Sản phẩm -->
            <div id="carsContainer" class="tabcontent d-flex flex-column">
                @foreach ($transactions as $transaction)
                            @if ($transaction->order)
                                        @php
                                            // Trạng thái đặt cọc
                                            $statusText = '';
                                            $statusColor = '#e3e3e3';
                                            $colorText = '#1e1e1e';

                                            // Trạng thái thanh toán
                                            $paymentStatusText = '';
                                            $paymentStatusColor = '#e3e3e3';
                                            $paymentColorText = '#1e1e1e';

                                            switch ($transaction->status_deposit) {
                                                case 0:
                                                    $statusText = 'Chờ đặt cọc';
                                                    $statusColor = '#e3e3e3';
                                                    break;
                                                case 1:
                                                    $statusText = 'Đã đặt cọc';
                                                    $statusColor = '#28a745';
                                                    $colorText = '#fff';
                                                    break;
                                                case 2:
                                                    $statusText = 'Không đặt cọc';
                                                    $statusColor = '#dc3545';
                                                    $colorText = '#fff';
                                                    break;
                                            }

                                            switch ($transaction->status_payment_all) {
                                                case 0:
                                                    $paymentStatusText = 'Chưa thanh toán hết';
                                                    $paymentStatusColor = '#ffc107';
                                                    break;
                                                case 1:
                                                    $paymentStatusText = 'Đã thanh toán hết';
                                                    $paymentStatusColor = '#28a745';
                                                    $paymentColorText = '#fff';
                                                    break;

                                                case 2:
                                                    $paymentStatusText = 'Không thanh toán hết';
                                                    $paymentStatusColor = '#dc3545';
                                                    $paymentColorText = '#fff';
                                                    break;
                                            }

                                            // Kiểm tra loại đơn hàng
                                            $orderType = '';
                                            $carDetails = $transaction->order->salesCar->carDetails ?? null;
                                            $accessories = $transaction->order->accessories;

                                            if ($carDetails && $accessories->count() > 0) {
                                                $orderType = 'Xe và Sản phẩm';
                                            } elseif ($carDetails) {
                                                $orderType = 'Xe';
                                            } elseif ($accessories->count() > 0) {
                                                $orderType = 'Sản phẩm';
                                            }

                                            // Ảnh hiển thị
                                            $imageUrl = $carDetails ? $carDetails->image_url : ($accessories->count() > 0 ? $accessories->first()->image_url : 'default-image.jpg');
                                        @endphp

                                        <div
                                            style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #ddd; padding: 16px; border-radius: 12px; margin-bottom: 16px; background-color: #ffffff; width: 95%; margin-left: auto; margin-right: auto;">
                                            <!-- Ảnh và thông tin -->
                                            <a href="{{ route('transactionHistory.details', ['orderId' => $transaction->order->order_id]) }}">
                                                <div style="display: flex; align-items: center;">
                                                    <!-- Ảnh xe hoặc phụ kiện -->
                                                    <img src="{{ $imageUrl ?? 'default-image.jpg' }}" alt="Image"
                                                        style="width: 70px; height: auto; border-radius: 8px; margin-right: 15px;">
                                                    <!-- Thông tin -->
                                                    <div>
                                                        <h4 style="margin: 0; font-weight: bold;">
                                                            {{ $carDetails->name ?? ($accessories->count() > 0 ? 'Sản phẩm' : 'Không xác định') }}
                                                        </h4>
                                                        <p style="margin: 0; color: #6c757d;">Đại loại: <strong>{{ $orderType }}</strong></p>
                                                        <p style="margin: 0; color: #6c757d;">Đơn hàng: {{ $transaction->order->order_id }}</p>

                                                        @if ($accessories->count() > 0)
                                                            <p style="margin: 0; color: #6c757d;">
                                                                Phụ kiện:
                                                                @foreach ($accessories as $accessory)
                                                                    {{ $accessory->name }} (x{{ $accessory->pivot->quantity }}),
                                                                @endforeach
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>

                                            <!-- Trạng thái -->
                                            <div>
                                                <span class="px-3 py-1 rounded-full text-sm font-medium"
                                                    style="background-color: {{ $statusColor }}; color: {{ $colorText }}; margin-right: 10px;">
                                                    {{ $statusText }}
                                                </span>
                                                <span class="px-3 py-1 rounded-full text-sm font-medium"
                                                    style="background-color: {{ $paymentStatusColor }}; color: {{ $paymentColorText }};">
                                                    {{ $paymentStatusText }}
                                                </span>
                                            </div>
                                        </div>
                            @endif
                @endforeach
            </div>

            <!-- Danh sách giao dịch Thuê xe -->
            <div id="rentalContainer" class="tabcontent d-flex flex-column" style="display: none;">
                @foreach ($rentalTransactions as $transaction)
                        @php
                            // Determine status from payment
                            $payment = $transaction->rentalPayments->first(); 
                            
                            $depositStatus = 'Chưa đặt cọc';
                            $depositColor = '#e3e3e3'; 
                            $depositTextColor = '#1e1e1e';

                            $fullPaymentStatus = 'Chưa thanh toán hết';
                            $fullPaymentColor = '#ffc107'; // yellow
                            $fullPaymentTextColor = '#1e1e1e';

                            if ($payment) {
                                // Deposit Status Logic
                                if ($payment->status_deposit === 'Successful') {
                                    $depositStatus = 'Đã đặt cọc';
                                    $depositColor = '#28a745'; // green
                                    $depositTextColor = '#fff';
                                } elseif ($payment->status_deposit === 'Failed' || $payment->status_deposit === 'Canceled') {
                                     $depositStatus = 'Không đặt cọc';
                                     $depositColor = '#dc3545'; // red
                                     $depositTextColor = '#fff';
                                } else {
                                    // Pending or other
                                    $depositStatus = 'Chờ đặt cọc';
                                }

                                // Full Payment Status Logic
                                if ($payment->full_payment_status === 'Successful' || $payment->remaining_amount == 0) {
                                    $fullPaymentStatus = 'Đã thanh toán hết';
                                    $fullPaymentColor = '#28a745'; // green
                                    $fullPaymentTextColor = '#fff';
                                } elseif ($payment->status_deposit === 'Successful') {
                                     // Deposited but remaining > 0
                                     $fullPaymentStatus = 'Không thanh toán hết'; 
                                     $fullPaymentColor = '#ffc107'; // yellow
                                     $fullPaymentTextColor = '#1e1e1e';
                                } else {
                                    // Not deposited or failed
                                    $fullPaymentStatus = 'Không thanh toán hết';
                                    $fullPaymentColor = '#dc3545'; // red
                                    $fullPaymentTextColor = '#fff';
                                }
                            } else {
                                $depositStatus = 'Không đặt cọc';
                                $depositColor = '#dc3545';
                                $depositTextColor = '#fff';
                                $fullPaymentStatus = 'Không thanh toán hết';
                                $fullPaymentColor = '#dc3545';
                                $fullPaymentTextColor = '#fff';
                            }

                            // If user explicitly asks for 'không đặt cọc' and 'không thanh toán hết' specifically map them:
                            if ($depositStatus == 'Chờ đặt cọc') $depositStatus = 'Không đặt cọc'; 

                            $imageUrl = $transaction->rentalCar->carDetails->image_url ?? 'default-image.jpg';
                        @endphp

                        <div
                            style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #ddd; padding: 16px; border-radius: 12px; margin-bottom: 16px; background-color: #ffffff; width: 95%; margin-left: auto; margin-right: auto;">
                            <!-- Ảnh và thông tin -->
                            <a href="{{ route('rentalHistory.details', ['orderId' => $transaction->order_id]) }}">
                                <div style="display: flex; align-items: center;">
                                    <!-- Ảnh xe -->
                                    <img src="{{ $imageUrl }}" alt="Image"
                                        style="width: 70px; height: auto; border-radius: 8px; margin-right: 15px;">
                                    <!-- Thông tin -->
                                    <div>
                                        <h4 style="margin: 0; font-weight: bold;">
                                            {{ $transaction->rentalCar->carDetails->name ?? 'Xe thuê' }}
                                        </h4>
                                        <p style="margin: 0; color: #6c757d;">Phân loại: <strong>Thuê xe</strong></p>
                                        <p style="margin: 0; color: #6c757d;">Mã đơn: {{ $transaction->order_id }}</p>
                                    </div>
                                </div>
                            </a>

                            <!-- Trạng thái -->
                            <div>
                                <span class="px-3 py-1 rounded-full text-sm font-medium"
                                    style="background-color: {{ $depositColor }}; color: {{ $depositTextColor }}; margin-right: 10px;">
                                    {{ $depositStatus }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-sm font-medium"
                                    style="background-color: {{ $fullPaymentColor }}; color: {{ $fullPaymentTextColor }};">
                                    {{ $fullPaymentStatus }}
                                </span>
                            </div>
                        </div>
                @endforeach
            </div>


        </div>
    </div>
</div>

<script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
            tablinks[i].style.color = "#6c757d";
            tablinks[i].style.borderBottom = "3px solid transparent";
            tablinks[i].style.fontWeight = "normal";
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
        evt.currentTarget.style.color = "#007bff";
        evt.currentTarget.style.borderBottom = "3px solid #007bff";
        evt.currentTarget.style.fontWeight = "bold";
    }

    document.getElementById("carsContainer").style.display = "block";
</script>
@endsection