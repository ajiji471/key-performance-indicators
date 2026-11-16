<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AgentTargetController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        Setting::put('agent_target', $data['target']);

        return Redirect::back()->with('success', 'Agent target updated.');
    }
}
