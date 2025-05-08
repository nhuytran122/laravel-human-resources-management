<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelExcel;

class LeaveRequestExport implements WithMultipleSheets, Responsable
{
    use Exportable;
    private $index = 0;
    private $data;

    public function __construct($data){
        $this->data = $data;
    }

    private $fileName = 'Danh_sach_nghi_phep.xlsx';
    private $writerType = ExcelExcel::XLSX;

    public function toResponse($request)
    {
        return Excel::download($this, $this->fileName, $this->writerType);
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach($this->data as $employee) {
            $sheets[] = new LeaveRequestPerEmployeeSheet($employee);
        }

        return $sheets;
    }
}