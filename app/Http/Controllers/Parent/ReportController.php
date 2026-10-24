<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Access\CodeIssuer;
use App\Services\Reports\GuardianReport;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly CodeIssuer $codes,
        private readonly GuardianReport $reports,
    ) {}

    public function show(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $student = $this->codes->parentSubject($data['code']);

        return response()->json($this->reports->for($student));
    }
}
