<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RentalReceipt;
use App\Models\OverdueNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\OverdueNotificationMail;

class CheckOverdueRentals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:overdue-rentals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue rentals and send email notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $now = Carbon::now();

        // Find all Active rentals that are past their end date
        $overdueReceipts = RentalReceipt::with(['rentalCar.carDetails', 'rentalOrder.user'])
            ->where('status', 'Active')
            ->where('rental_end_date', '<', $now)
            ->get();

        foreach ($overdueReceipts as $receipt) {
            // Update status to Overdue
            $receipt->update(['status' => 'Overdue']);

            // Calculate overdue days and fee
            $endDate = Carbon::parse($receipt->rental_end_date);
            $overdueDays = $endDate->diffInDays($today) + 1; // Include today
            $overdueFee = $receipt->rental_price_per_day * $overdueDays;

            // Check if notification already sent today
            $notificationExists = OverdueNotification::where('receipt_id', $receipt->receipt_id)
                ->where('notification_date', $today)
                ->exists();

            if (!$notificationExists && $receipt->rentalOrder && $receipt->rentalOrder->user) {
                // Create notification record
                OverdueNotification::create([
                    'receipt_id' => $receipt->receipt_id,
                    'notification_date' => $today,
                    'overdue_fee' => $overdueFee,
                    'overdue_days' => $overdueDays,
                ]);

                // Send email
                try {
                    Mail::to($receipt->rentalOrder->user->email)->send(new OverdueNotificationMail([
                        'name' => $receipt->rentalOrder->user->name,
                        'car_name' => $receipt->rentalCar->carDetails->name ?? 'N/A',
                        'rental_end_date' => $receipt->rental_end_date,
                        'overdue_days' => $overdueDays,
                        'rental_price_per_day' => $receipt->rental_price_per_day,
                        'overdue_fee' => $overdueFee,
                        'receipt_id' => $receipt->receipt_id,
                    ]));

                    $this->info("Email sent to {$receipt->rentalOrder->user->email} for receipt #{$receipt->receipt_id}");
                } catch (\Exception $e) {
                    $this->error("Failed to send email for receipt #{$receipt->receipt_id}: " . $e->getMessage());
                }
            }
        }

        // Also check for already Overdue rentals and send daily notifications
        $existingOverdueReceipts = RentalReceipt::with(['rentalCar.carDetails', 'rentalOrder.user'])
            ->where('status', 'Overdue')
            ->get();

        foreach ($existingOverdueReceipts as $receipt) {
            // Calculate overdue days and fee
            $endDate = Carbon::parse($receipt->rental_end_date);
            $overdueDays = $endDate->diffInDays($today) + 1;
            $overdueFee = $receipt->rental_price_per_day * $overdueDays;

            // Check if notification already sent today
            $notificationExists = OverdueNotification::where('receipt_id', $receipt->receipt_id)
                ->where('notification_date', $today)
                ->exists();

            if (!$notificationExists && $receipt->rentalOrder && $receipt->rentalOrder->user) {
                // Create notification record
                OverdueNotification::create([
                    'receipt_id' => $receipt->receipt_id,
                    'notification_date' => $today,
                    'overdue_fee' => $overdueFee,
                    'overdue_days' => $overdueDays,
                ]);

                // Send email
                try {
                    Mail::to($receipt->rentalOrder->user->email)->send(new OverdueNotificationMail([
                        'name' => $receipt->rentalOrder->user->name,
                        'car_name' => $receipt->rentalCar->carDetails->name ?? 'N/A',
                        'rental_end_date' => $receipt->rental_end_date,
                        'overdue_days' => $overdueDays,
                        'rental_price_per_day' => $receipt->rental_price_per_day,
                        'overdue_fee' => $overdueFee,
                        'receipt_id' => $receipt->receipt_id,
                    ]));

                    $this->info("Email sent to {$receipt->rentalOrder->user->email} for receipt #{$receipt->receipt_id}");
                } catch (\Exception $e) {
                    $this->error("Failed to send email for receipt #{$receipt->receipt_id}: " . $e->getMessage());
                }
            }
        }

        $totalProcessed = $overdueReceipts->count() + $existingOverdueReceipts->count();
        $this->info("Processed {$totalProcessed} overdue rental(s)");

        return 0;
    }
}
