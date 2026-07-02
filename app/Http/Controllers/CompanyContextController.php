<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanyContextController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        abort_unless($request->user()->canAccessCompany((int) $data['company_id']), 403);

        $request->user()->forceFill([
            'current_company_id' => $data['company_id'],
        ])->save();

        return back();
    }
}
