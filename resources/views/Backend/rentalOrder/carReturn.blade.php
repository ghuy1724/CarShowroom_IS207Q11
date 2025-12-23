@extends('Backend.dashboard.layout')

@section('content')
<x-breadcrumbs breadcrumb="carReturn" />

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-bold text-primary">Quản lí trả xe</h1>
            </div>

            <div class="flex items-center space-x-4 mb-4">
                <!-- Tìm kiếm -->
                <input type="text" id="searchInput" class="rounded-lg border border-gray-300 px-4 py-2 w-64"
                    placeholder="Tìm kiếm theo mã hóa đơn hoặc tên khách hàng...">

                <!-- Lọc theo trạng thái -->
                <select id="statusFilter" class="rounded-lg border border-gray-300 px-4 py-2">
                    <option value="">Tất cả trạng thái</option>
                    <option value="Active">Đang thuê xe</option>
                    <option value="Overdue">Quá hạn</option>
                    <option value="Completed">Đã trả xe</option>
                    <option value="Canceled">Đã hủy</option>
                </select>
            </div>

            <div id="receiptsContainer" class="table-responsive shadow-sm">
                <table class="table table-striped table-hover text-center align-middle">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">Mã hóa đơn</th>
                            <th scope="col">Khách hàng</th>
                            <th scope="col">Xe thuê</th>
                            <th scope="col">Ngày bắt đầu</th>
                            <th scope="col">Ngày kết thúc</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receipts as $receipt)
                            <tr>
                                <td class="fw-bold">#{{ $receipt->receipt_id }}</td>
                                <td>{{ $receipt->rentalOrder->user->name ?? 'N/A' }}</td>
                                <td>{{ $receipt->rentalCar->carDetails->name ?? 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($receipt->rental_start_date)->format('d/m/Y H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($receipt->rental_end_date)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge rounded-pill 
                                        @if ($receipt->status === 'Active') bg-info
                                        @elseif ($receipt->status === 'Overdue') bg-danger
                                        @elseif ($receipt->status === 'Completed') bg-success
                                        @elseif ($receipt->status === 'Canceled') bg-secondary
                                        @endif">
                                        @if ($receipt->status === 'Active') Đang thuê xe
                                        @elseif ($receipt->status === 'Overdue') Quá hạn
                                        @elseif ($receipt->status === 'Completed') Đã trả xe
                                        @elseif ($receipt->status === 'Canceled') Đã hủy
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    @if (in_array($receipt->status, ['Active', 'Overdue']))
                                        <button class="btn btn-warning btn-sm" 
                                            onclick="confirmReturn({{ $receipt->receipt_id }})">
                                            <i class="bi bi-check-circle"></i> Trả xe
                                        </button>
                                        @if ($receipt->status === 'Overdue')
                                            <button class="btn btn-success btn-sm ms-1" 
                                                onclick="processManualPayment({{ $receipt->receipt_id }})">
                                                <i class="bi bi-cash"></i> Thanh toán thủ công
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $receipts->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnModalLabel">Xác nhận trả xe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xác nhận trả xe này không? Hành động này không thể hoàn tác.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="returnForm" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">Xác nhận trả xe</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Confirmation modal
    function confirmReturn(receiptId) {
        const modal = new bootstrap.Modal(document.getElementById('returnModal'));
        const form = document.getElementById('returnForm');
        form.action = `/admin/car-return/${receiptId}`;
        modal.show();
    }

    // Manual payment confirmation
    function processManualPayment(receiptId) {
        if (confirm('Xác nhận thanh toán thủ công phí quá hạn cho hóa đơn này?\n\nSau khi thanh toán, xe sẽ được trả và có thể cho thuê lại.')) {
            window.location.href = `/admin/car-return/manual-payment/${receiptId}`;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const receiptsContainer = document.getElementById('receiptsContainer');

        // AJAX filtering function
        function fetchFilteredReceipts() {
            const searchValue = searchInput.value.trim();
            const statusValue = statusFilter.value;

            fetch("{{ route('carReturn.filter') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    search: searchValue,
                    status: statusValue,
                }),
            })
                .then(response => response.json())
                .then(data => {
                    updateReceiptList(data.receipts);
                })
                .catch(error => console.error('Error:', error));
        }

        // Update receipt list
        function updateReceiptList(receipts) {
            receiptsContainer.innerHTML = '';

            if (receipts.length === 0) {
                receiptsContainer.innerHTML = '<p class="text-center">Không tìm thấy hóa đơn nào.</p>';
                return;
            }

            let html = `
                <table class="table table-striped table-hover text-center align-middle">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">Mã hóa đơn</th>
                            <th scope="col">Khách hàng</th>
                            <th scope="col">Xe thuê</th>
                            <th scope="col">Ngày bắt đầu</th>
                            <th scope="col">Ngày kết thúc</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            receipts.forEach(receipt => {
                const statusClass = receipt.status === 'Active' ? 'bg-info' :
                    receipt.status === 'Overdue' ? 'bg-danger' :
                    receipt.status === 'Completed' ? 'bg-success' : 'bg-secondary';
                
                const statusText = receipt.status === 'Active' ? 'Đang thuê xe' :
                    receipt.status === 'Overdue' ? 'Quá hạn' :
                    receipt.status === 'Completed' ? 'Đã trả xe' : 'Đã hủy';

                const customerName = receipt.rental_order?.user?.name || 'N/A';
                const carName = receipt.rental_car?.car_details?.name || 'N/A';

                html += `
                    <tr>
                        <td class="fw-bold">#${receipt.receipt_id}</td>
                        <td>${customerName}</td>
                        <td>${carName}</td>
                        <td>${new Date(receipt.rental_start_date).toLocaleString('vi-VN')}</td>
                        <td>${new Date(receipt.rental_end_date).toLocaleString('vi-VN')}</td>
                        <td>
                            <span class="badge rounded-pill ${statusClass}">${statusText}</span>
                        </td>
                        <td>
                            ${(receipt.status === 'Active' || receipt.status === 'Overdue') ? 
                                `<button class="btn btn-warning btn-sm" onclick="confirmReturn(${receipt.receipt_id})">
                                    <i class="bi bi-check-circle"></i> Trả xe
                                </button>
                                ${receipt.status === 'Overdue' ? 
                                    `<button class="btn btn-success btn-sm ms-1" onclick="processManualPayment(${receipt.receipt_id})">
                                        <i class="bi bi-cash"></i> Thanh toán thủ công
                                    </button>` : ''}` : 
                                '<span class="text-muted">-</span>'}
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            receiptsContainer.innerHTML = html;
        }

        // Event listeners
        [searchInput, statusFilter].forEach(filter => {
            filter.addEventListener('change', fetchFilteredReceipts);
            filter.addEventListener('keyup', fetchFilteredReceipts);
        });
    });
</script>
@endsection
