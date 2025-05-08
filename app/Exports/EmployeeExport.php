<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
class EmployeeExport implements FromCollection, WithHeadings, WithStrictNullComparison, WithMapping, Responsable, ShouldQueue
{
    use Exportable;
    private $index = 0;

    private $fileName = 'Danh_sach_nhan_vien.xlsx';
    private $writerType = ExcelExcel::XLSX;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Employee::all();
    }

    public function headings(): array{
        return [
            '#',
            'Họ tên',
            'Giới tính',
            'Ngày sinh',
            'Số điện thoại',
            'Địa chỉ',
            'Ngày tuyển dụng',
            'Đang làm việc',
            'Phòng ban',
            'Vị trí'
        ];
    }

    public function map($employee): array{
        return[
            ++$this->index,
            $employee->full_name,
            $employee->gender == 'male' ? 'Nam' : 'Nữ',
            $employee->date_of_birth->format('d/m/Y'),
            $employee->phone,
            $employee->address,
            $employee->hire_date->format('d/m/Y'),
            $employee->is_working == true ? 'x' : '',
            $employee->department->name,
            $employee->position->name
            
        ];
    }

    public function toResponse($request)
    {
        return Excel::download($this, $this->fileName, $this->writerType);
    }
}