<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$record = DB::table('sales_cars')->where('sale_id', 2)->first();
echo json_encode($record, JSON_PRETTY_PRINT);
?>
