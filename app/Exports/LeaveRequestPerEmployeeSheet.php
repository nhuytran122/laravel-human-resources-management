<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeaveRequestPerEmployeeSheet implements FromCollection, WithMapping, WithHeadings, WithTitle
{
    private $employee;
    private $index = 0;

    public function __construct($employee){
        $this->employee = $employee;
    }
    public function collection()
    {
        return LeaveRequest::with(['leave_type'])
            ->where('employee_id', $this->employee->id)
            ->get();
    }
    
    public function headings(): array{
        return [
            '#',
            'Ngày bắt đầu',
            'Ngày kết thúc',
            'Trạng thái',
            'Lí do',
            'Loại nghỉ phép',
        ];
    }

    public function map($row): array{
        return [
            ++$this->index,
            $row->start_date->format('d/m/Y'),
            $row->end_date->format('d/m/Y'),
            $this->getStatusLabel($row->status),
            $row->reason,
            $row->leave_type->name,
        ];
    }

    public function title(): string
    {
        return $this->employee->full_name;
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending'  => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default    => 'Không xác định',
        };
    }

}