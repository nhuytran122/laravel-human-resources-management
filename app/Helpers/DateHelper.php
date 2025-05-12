<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function toDateFormat($date, $inputFormat = 'd/m/Y', $outputFormat = 'Y-m-d')
    {
        try {
            if($date == null){
                return;
            }
            return Carbon::createFromFormat($inputFormat, $date)->format($outputFormat);
        } catch (\Exception $e) {
            abort(response()->json([
                'message' => "Ngày không đúng định dạng. Định dạng hợp lệ: {$inputFormat}"
            ], 422));
        }
    }

    public static function toDateTimeFormat($dateTime, $inputFormat = ' H:i d/m/Y', $outputFormat = 'H:i Y-m-d')
    {
        try {
            if ($dateTime == null) {
                return;
            }
            return Carbon::createFromFormat($inputFormat, $dateTime)->format($outputFormat);
        } catch (\Exception $e) {
            abort(response()->json([
                'message' => "Ngày giờ không đúng định dạng. Định dạng hợp lệ: {$inputFormat}"
            ], 422));
        }
    }
}